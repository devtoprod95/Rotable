<?php

namespace App\Http\Controllers;

use App\Events\PublicPollPresence;
use App\Events\PublicPollQuestionChanged;
use App\Events\PublicPollVoted;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * 공개(비로그인) 미니게임.
 *
 * 로그인·테이블 개념 없이 "지금 이 페이지에 접속해 있는 사람들"을 한 방으로 묶어 즐긴다.
 * - 이미지게임/밸런스: 전적으로 클라이언트 로컬(서버 불필요).
 * - Yes/No: 전역 단일 방(Redis 키 `public-poll`)에서 익명 집계 + public 채널 브로드캐스트.
 *   접속자 수는 하트비트(sorted set, TTL 성격)로 집계한다.
 */
class PublicGameController extends Controller
{
    /** Yes/No 집계(현재 질문/yes/no)를 담는 Redis 해시 키. */
    private const POLL_KEY = 'public-poll';

    /** 접속자(하트비트) sorted set 키. member=clientId, score=unixtime. */
    private const PRESENCE_KEY = 'public-poll:presence';

    /** 접속자로 인정하는 하트비트 유효시간(초). 이보다 오래되면 나간 것으로 본다.
     *  클라이언트 하트비트 간격(2초)의 2배로 잡아, 한 번 놓쳐도 유지되고
     *  완전히 떠나면 약 4초 안에 접속자 집계에서 소멸한다. */
    private const PRESENCE_TTL = 4;

    /** 집계 해시 자동 소멸 TTL(초) — 안전 청소용. */
    private const POLL_TTL = 21600; // 6시간

    /**
     * 공개 게임 목록.
     */
    public function index(): InertiaResponse
    {
        return Inertia::render('Public/Games', []);
    }

    /**
     * 개별 공개 게임 플레이 화면.
     *
     * slug ↔ Inertia 페이지 매핑. 미지원 slug 는 목록으로 되돌린다.
     */
    public function show(string $slug): InertiaResponse|RedirectResponse
    {
        $pages = [
            // 소개팅
            'image-mild'  => 'Public/ImageMild',
            'image-spicy' => 'Public/ImageSpicy',
            'balance'     => 'Public/Balance',
            'poll'        => 'Public/Poll',
            // 술게임 (🔞 성인)
            'drink-balance'    => 'Public/DrinkBalance',
            'kings-game'       => 'Public/KingsGame',
            'drink-image'      => 'Public/DrinkImage',
            'truth-game'       => 'Public/TruthGame',
            'spin-bottle'      => 'Public/SpinBottle',
        ];

        if (! isset($pages[$slug])) {
            return redirect()->route('public.games');
        }

        // Yes/No 는 현재 진행 중인 질문/집계를 Redis 에서 읽어 함께 내려준다.
        $props = [];
        if ($slug === 'poll') {
            $state = Redis::hgetall(self::POLL_KEY);
            $props = [
                'initialState' => $state === []
                    ? null
                    : [
                        'questionIndex' => (int) ($state['q'] ?? 0),
                        'counts'        => [
                            'yes' => (int) ($state['yes'] ?? 0),
                            'no'  => (int) ($state['no'] ?? 0),
                        ],
                    ],
                'initialCount' => $this->activePresenceCount(),
            ];
        }

        return Inertia::render($pages[$slug], $props);
    }

    /**
     * 공개 Yes/No - 익명 한 표 처리. 현재 질문과 일치할 때만 집계한다.
     */
    public function pollVote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question_index' => ['required', 'integer', 'min:0'],
            'answer'         => ['required', 'in:yes,no'],
        ]);

        $questionIndex = (int) $validated['question_index'];
        $answer        = $validated['answer'];

        $currentQuestion = Redis::hget(self::POLL_KEY, 'q');
        if ($currentQuestion !== null && (int) $currentQuestion !== $questionIndex) {
            return response()->json(['ok' => false]);
        }

        if ($currentQuestion === null) {
            Redis::hset(self::POLL_KEY, 'q', $questionIndex);
        }
        Redis::hincrby(self::POLL_KEY, $answer, 1);
        Redis::expire(self::POLL_KEY, self::POLL_TTL);

        broadcast(new PublicPollVoted($questionIndex, $answer));

        return response()->json(['ok' => true]);
    }

    /**
     * 공개 Yes/No - 다음 질문으로 넘긴다(집계 리셋 + 브로드캐스트).
     */
    public function pollNext(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question_index' => ['required', 'integer', 'min:0'],
        ]);

        $questionIndex = (int) $validated['question_index'];

        Redis::del(self::POLL_KEY);
        Redis::hset(self::POLL_KEY, 'q', $questionIndex);
        Redis::hset(self::POLL_KEY, 'yes', 0);
        Redis::hset(self::POLL_KEY, 'no', 0);
        Redis::expire(self::POLL_KEY, self::POLL_TTL);

        broadcast(new PublicPollQuestionChanged($questionIndex));

        return response()->json(['ok' => true]);
    }

    /**
     * 접속자 수 하트비트. 클라이언트가 주기적으로 호출한다.
     * 오래된 하트비트를 정리하고, 현재 접속자 수를 집계해 브로드캐스트한다.
     */
    public function pollHeartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string', 'max:64'],
            'leaving'   => ['sometimes', 'boolean'],
        ]);

        $now = now()->timestamp;

        if ($request->boolean('leaving')) {
            // 페이지를 떠날 때(beacon) — 즉시 제거.
            Redis::zrem(self::PRESENCE_KEY, $validated['client_id']);
        } else {
            Redis::zadd(self::PRESENCE_KEY, $now, $validated['client_id']);
            Redis::expire(self::PRESENCE_KEY, self::PRESENCE_TTL * 4);
        }

        // 유효시간이 지난(오래된) 하트비트 제거.
        Redis::zremrangebyscore(self::PRESENCE_KEY, 0, $now - self::PRESENCE_TTL);

        $count = $this->activePresenceCount();
        broadcast(new PublicPollPresence($count));

        return response()->json(['ok' => true, 'count' => $count]);
    }

    /** 현재 유효한(만료되지 않은) 접속자 수. */
    private function activePresenceCount(): int
    {
        $threshold = now()->timestamp - self::PRESENCE_TTL;
        Redis::zremrangebyscore(self::PRESENCE_KEY, 0, $threshold);

        return (int) Redis::zcard(self::PRESENCE_KEY);
    }
}
