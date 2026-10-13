<?php

namespace App\Http\Controllers;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\EventRound;
use App\Models\Member;
use App\Models\Message;
use App\Models\RotationRound;
use App\Models\Vote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MemberPageController extends Controller
{
    /**
     * 루트(/) 진입점.
     * - 비로그인: 로그인 페이지로 이동
     * - 로그인: 기록(home)로 이동
     */
    public function landing(): RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('home');
        }

        return redirect()->route('member.login');
    }

    /**
     * 로그인한 유저 메인 홈 (기록 탭).
     *
     * 로테이션 소개팅 좌석 이력을 보여준다.
     *  - 여성은 테이블에 고정, 남성은 턴(round)마다 테이블을 이동한다.
     *  - "몇 번째 턴에 어느 테이블에서 남/여 누가 만났는지"를 턴별로 모아 보여준다.
     */
    public function home(): InertiaResponse
    {
        /** @var \App\Models\Member $me */
        $me = Auth::guard('web')->user();

        // 로테이션 상태/제목은 행사(event_rounds) 단위로 관리한다.
        $event       = EventRound::find($me->event_id);
        $eventStatus = $event?->status ?? EventRound::STATUS_PENDING;
        $eventTitle  = $event?->title;

        // 행사 일시 표시용 (예: "2026/1/10 19:00 토요일")
        $eventTime = null;
        if ($event?->event_time) {
            $weekdays  = ['일요일', '월요일', '화요일', '수요일', '목요일', '금요일', '토요일'];
            $t         = $event->event_time;
            $eventTime = $t->format('Y/n/j H:i') . ' ' . $weekdays[(int) $t->format('w')];
        }

        // 같은 행사 회차의 모든 턴 + 각 턴의 좌석(회원 포함)을 로드.
        // 0턴(세팅용)은 사용자 페이지에 노출하지 않는다.
        $rounds = RotationRound::query()
            ->where('event_id', $me->event_id)
            ->where('round_no', '>', 0)
            ->with(['seatings' => function ($query) {
                $query->with(['table', 'member'])
                    ->orderBy('rotation_table_id')
                    ->orderBy('member_id');
            }])
            ->orderByDesc('round_no')
            ->get();

        $myId        = $me->id;
        $oppositeGen = $me->gender === Member::GENDER_MALE ? Member::GENDER_FEMALE : Member::GENDER_MALE;

        // 테이블 수 = 마지막 로테이션 판정 기준 (round_no >= 테이블 수면 마지막).
        $tableCount = \App\Models\RotationTable::where('event_id', $me->event_id)->count();

        $rotations = $rounds->map(function (RotationRound $round) use ($eventStatus, $myId, $oppositeGen, $tableCount) {
            // 이번 턴에 내가 앉은 테이블의 좌석들.
            $myTableId = $round->seatings
                ->firstWhere('member_id', $myId)?->rotation_table_id;

            // 그 테이블에서 나와 만난 상대 성별 회원만 추린다.
            $partners = collect();
            if ($myTableId !== null) {
                $partners = $round->seatings
                    ->where('rotation_table_id', $myTableId)
                    ->filter(fn ($s) => $s->member?->gender === $oppositeGen)
                    ->map(fn ($s) => $this->transformMember($s->member))
                    ->values();
            }

            return [
                'id'       => $round->id,
                'round_no' => $round->round_no,
                'status'   => $eventStatus,
                'partners' => $partners,
                'is_last'  => $tableCount > 0 && $round->round_no >= $tableCount,
            ];
        });

        return Inertia::render('Home', [
            'rotations'     => $rotations,
            'eventTitle'    => $eventTitle,
            'eventTime'     => $eventTime,
            'eventStatus'   => $eventStatus,
            ...$this->messagingData($me),
        ]);
    }

    /**
     * 기록 노출용 회원 정보 (members 전체 컬럼).
     *
     * @return array<string, mixed>
     */
    private function transformMember(Member $member): array
    {
        return [
            'id'         => $member->id,
            'name'       => $member->name,
            'gender'     => $member->gender,
            'birth_year' => $member->birth_year,
            'job'        => $member->job,
            'height'     => $member->height,
            'mbti'       => $member->mbti,
            'region'     => $member->region,
            'is_smoker'  => $member->is_smoker,
        ];
    }

    /**
     * 메시지 전송 UI(모달)에 공통으로 필요한 데이터.
     * - partners: 같은 행사의 이성 회원 리스트 (전송 대상)
     * - partnerGender: 이성 성별
     * - sendLimit: 최대/사용/남은 전송 횟수
     *
     * @return array{partners: \Illuminate\Support\Collection<int, array<string, mixed>>, partnerGender: string, sendLimit: array{max: int, used: int, remaining: int}}
     */
    private function messagingData(Member $me): array
    {
        $oppositeGen = $me->gender === Member::GENDER_MALE
            ? Member::GENDER_FEMALE
            : Member::GENDER_MALE;

        $partners = Member::query()
            ->where('event_id', $me->event_id)
            ->where('gender', $oppositeGen)
            ->where('id', '!=', $me->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Member $member) => $this->transformMember($member))
            ->values();

        $used = Message::where('sender_id', $me->id)->count();
        $max  = Message::MAX_SENDS_PER_MEMBER;

        // 메시지 전달은 로테이션 진행중(S)·매칭중(V) 상태에서만 가능하다.
        $eventStatus    = EventRound::whereKey($me->event_id)->value('status') ?? EventRound::STATUS_PENDING;
        $canSendMessage = in_array($eventStatus, [EventRound::STATUS_STARTED, EventRound::STATUS_VOTING], true);

        return [
            'partners'       => $partners,
            'partnerGender'  => $oppositeGen,
            'canSendMessage' => $canSendMessage,
            'sendLimit'     => [
                'max'       => $max,
                'used'      => $used,
                'remaining' => max(0, $max - $used),
            ],
        ];
    }

    /**
     * 메시지함 탭.
     *
     * - 상단: 나에게 온 메시지(받은 메시지)
     * - 하단: 같은 행사의 이성 회원 리스트 (여기서 메시지를 전달)
     */
    public function chats(): InertiaResponse
    {
        /** @var \App\Models\Member $me */
        $me = Auth::guard('web')->user();

        // 나에게 온 메시지 (최신순). 발신자 정보는 익명 처리하여 노출하지 않는다.
        $received = Message::query()
            ->where('receiver_id', $me->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Message $message) => [
                'id'      => $message->id,
                'body'    => $message->body,
                'is_read' => $message->read_at !== null,
                'sent_at' => $message->created_at?->format('Y/n/j H:i'),
            ])
            ->values();

        // 내가 보낸 메시지 (최신순). 보낸 대상은 내가 선택했으므로 이름을 노출한다.
        $sent = Message::query()
            ->with('receiver')
            ->where('sender_id', $me->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Message $message) => [
                'id'            => $message->id,
                'body'          => $message->body,
                'is_read'       => $message->read_at !== null,
                'sent_at'       => $message->created_at?->format('Y/n/j H:i'),
                'receiver_name' => $message->receiver?->name,
            ])
            ->values();

        return Inertia::render('Chats', [
            'received' => $received,
            'sent'     => $sent,
            ...$this->messagingData($me),
        ]);
    }

    /**
     * 이성 회원에게 메시지를 전달한다. (회원당 최대 전송 횟수 제한 적용)
     */
    public function sendMessage(Request $request): RedirectResponse
    {
        /** @var \App\Models\Member $me */
        $me = Auth::guard('web')->user();

        // 메시지 전달은 로테이션 진행중(S)·매칭중(V) 상태에서만 허용한다.
        $event  = EventRound::find($me->event_id);
        $status = $event?->status ?? EventRound::STATUS_PENDING;
        if (! in_array($status, [EventRound::STATUS_STARTED, EventRound::STATUS_VOTING], true)) {
            return back()->with('error', '지금은 메시지를 전달할 수 없어요.');
        }

        $validated = $request->validate([
            'receiver_id' => ['required', 'integer'],
            'body'        => ['required', 'string', 'max:1000'],
        ]);

        // 최대 전송 횟수 제한 체크.
        $used = Message::where('sender_id', $me->id)->count();
        if ($used >= Message::MAX_SENDS_PER_MEMBER) {
            return back()->with('error', '메시지는 최대 ' . Message::MAX_SENDS_PER_MEMBER . '번까지만 보낼 수 있어요.');
        }

        // 수신자는 같은 행사에 참여한 이성이어야 한다. (본인에게 전송 불가)
        $oppositeGen = $me->gender === Member::GENDER_MALE
            ? Member::GENDER_FEMALE
            : Member::GENDER_MALE;

        $receiver = Member::query()
            ->where('id', $validated['receiver_id'])
            ->where('event_id', $me->event_id)
            ->where('gender', $oppositeGen)
            ->where('id', '!=', $me->id)
            ->first();

        if ($receiver === null) {
            return back()->with('error', '메시지를 보낼 수 없는 상대입니다.');
        }

        $message = Message::create([
            'event_id'    => $me->event_id,
            'sender_id'   => $me->id,
            'receiver_id' => $receiver->id,
            'body'        => $validated['body'],
        ]);

        // 수신자 개인 채널로 실시간 브로드캐스트.
        broadcast(new MessageSent($message));

        // 관리자 "메시지 조회" 화면용 행사 단위 브로드캐스트.
        broadcast(new \App\Events\EventMessageSent($message));

        $remaining = max(0, Message::MAX_SENDS_PER_MEMBER - ($used + 1));

        return back()->with('success', "전달되었습니다! 익명으로 메시지를 보냈어요. (남은 전송 횟수 {$remaining}회)");
    }

    /**
     * 나에게 온 메시지를 읽음 처리하고, 각 발신자에게 "읽음"을 실시간 전파한다.
     */
    public function markMessagesRead(Request $request): RedirectResponse
    {
        /** @var \App\Models\Member $me */
        $me = Auth::guard('web')->user();

        // 읽음 처리 전에 대상 메시지(id/발신자)를 먼저 수집한다.
        $unread = Message::query()
            ->where('receiver_id', $me->id)
            ->whereNull('read_at')
            ->get(['id', 'sender_id']);

        if ($unread->isEmpty()) {
            return back();
        }

        Message::query()
            ->whereIn('id', $unread->pluck('id'))
            ->update(['read_at' => now()]);

        // 발신자별로 묶어, 각 발신자의 개인 채널로 읽음 상태를 브로드캐스트.
        $unread
            ->groupBy('sender_id')
            ->each(function ($messages, $senderId) {
                broadcast(new MessageRead((int) $senderId, $messages->pluck('id')->map(fn ($id) => (int) $id)->all()));
            });

        return back();
    }

    /**
     * 투표하기 탭.
     *
     * - 투표중(V) 단계에서 이성 회원에게 최대 2명까지 투표(지목)한다.
     * - 마감 전까지 자유롭게 수정 가능.
     * - 종료(X) 후에는 상호 매칭(서로 지목한 상대)의 연락처를 공개한다.
     * - 내가 받은 표 수는 항상 보여준다.
     */
    public function votes(): InertiaResponse
    {
        /** @var \App\Models\Member $me */
        $me = Auth::guard('web')->user();

        $event  = EventRound::find($me->event_id);
        $status = $event?->status ?? EventRound::STATUS_PENDING;

        // 매칭 투표 가능 여부: 상태가 '매칭중(V)'이고 자동 마감 시각(행사 다음 날 오전 9시) 전.
        $isVotingOpen = $event !== null && $event->isVotingOpen();
        $isClosed     = $status === EventRound::STATUS_CLOSED;

        // 상태는 V 이지만 마감 시각이 지나 투표만 닫힌 '마감 대기' 상태.
        // (스케줄이 아직 X로 전환하기 전의 공백 구간)
        $isVotingEnded = $status === EventRound::STATUS_VOTING && ! $isVotingOpen;

        $oppositeGen = $me->gender === Member::GENDER_MALE
            ? Member::GENDER_FEMALE
            : Member::GENDER_MALE;

        // 투표 대상(같은 행사의 이성) 목록.
        $candidates = Member::query()
            ->where('event_id', $me->event_id)
            ->where('gender', $oppositeGen)
            ->where('id', '!=', $me->id)
            ->orderBy('name')
            ->get();

        // 내가 지목한 상대 id 집합.
        $myVotedIds = Vote::query()
            ->where('event_id', $me->event_id)
            ->where('voter_id', $me->id)
            ->pluck('target_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // 나를 지목한(= 나에게 투표한) 상대 id 집합. (상호 매칭 판정 + 받은 표 수)
        $votedForMeIds = Vote::query()
            ->where('event_id', $me->event_id)
            ->where('target_id', $me->id)
            ->pluck('voter_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $candidateList = $candidates->map(function (Member $c) use ($myVotedIds, $votedForMeIds, $isClosed) {
            $data        = $this->transformMember($c);
            $iVotedThem  = in_array($c->id, $myVotedIds, true);
            $theyVotedMe = in_array($c->id, $votedForMeIds, true);
            $isMatch     = $iVotedThem && $theyVotedMe;

            $data['voted']    = $iVotedThem;
            $data['is_match'] = $isMatch;
            // 연락처는 종료 후 "상호 매칭"된 상대에게만 공개한다.
            $data['phone']    = ($isClosed && $isMatch) ? $c->phone : null;

            return $data;
        })->values();

        return Inertia::render('Votes', [
            'eventTitle'    => $event?->title,
            'eventStatus'   => $status,
            'canVote'       => $isVotingOpen,
            'isClosed'      => $isClosed,
            'votingEnded'   => $isVotingEnded,
            'maxVotes'      => Vote::MAX_VOTES_PER_MEMBER,
            'candidates'    => $candidateList,
            'myVotedIds'    => $myVotedIds,
            'receivedCount' => count($votedForMeIds),
            'partnerGender' => $oppositeGen,
        ]);
    }

    /**
     * 투표 저장(수정). 투표중(V) 단계에서만 허용하며, 전달받은 대상 목록으로 전체 교체한다.
     * - 최대 2명까지만 허용.
     * - 같은 행사의 이성만 지목 가능.
     */
    public function saveVotes(Request $request): RedirectResponse
    {
        /** @var \App\Models\Member $me */
        $me = Auth::guard('web')->user();

        $event = EventRound::find($me->event_id);

        // 투표가 열려 있지 않으면(대기/진행/종료, 또는 자동마감 시각 경과) 저장 불가.
        if ($event === null || ! $event->isVotingOpen()) {
            return back()->with('error', '지금은 매칭 투표를 할 수 없어요.');
        }

        $validated = $request->validate([
            'target_ids'   => ['present', 'array', 'max:' . Vote::MAX_VOTES_PER_MEMBER],
            'target_ids.*' => ['integer', 'distinct'],
        ]);

        $targetIds = array_values(array_unique(array_map('intval', $validated['target_ids'] ?? [])));

        // 지목 대상은 같은 행사의 이성이어야 하며, 본인은 제외한다.
        $oppositeGen = $me->gender === Member::GENDER_MALE
            ? Member::GENDER_FEMALE
            : Member::GENDER_MALE;

        $validTargetIds = Member::query()
            ->whereIn('id', $targetIds)
            ->where('event_id', $me->event_id)
            ->where('gender', $oppositeGen)
            ->where('id', '!=', $me->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($validTargetIds) !== count($targetIds)) {
            return back()->with('error', '선택할 수 없는 상대가 포함되어 있어요.');
        }

        // 내 기존 투표를 전부 지우고 새로 저장한다. (전체 교체 방식)
        DB::transaction(function () use ($me, $validTargetIds) {
            Vote::where('event_id', $me->event_id)
                ->where('voter_id', $me->id)
                ->delete();

            foreach ($validTargetIds as $targetId) {
                Vote::create([
                    'event_id'  => $me->event_id,
                    'voter_id'  => $me->id,
                    'target_id' => $targetId,
                ]);
            }
        });

        return back()->with('success', '매칭 선택을 저장했어요!');
    }

    /**
     * 요청하기 탭.
     *
     * 회원이 관리자(운영진)에게 보낸 요청 목록을 함께 내려준다.
     */
    public function requests(): InertiaResponse
    {
        /** @var \App\Models\Member $me */
        $me = Auth::guard('web')->user();

        $event    = EventRound::find($me->event_id);
        $isClosed = ($event?->status ?? EventRound::STATUS_PENDING) === EventRound::STATUS_CLOSED;

        $requests = \App\Models\AdminRequest::query()
            ->where('member_id', $me->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (\App\Models\AdminRequest $req) => [
                'id'         => $req->id,
                'body'       => $req->body,
                'is_read'    => $req->read_at !== null,
                'created_at' => $req->created_at?->format('Y/n/j H:i'),
            ])
            ->values();

        return Inertia::render('Requests', [
            'requests'   => $requests,
            'eventClosed' => $isClosed,
        ]);
    }

    /**
     * 관리자(운영진)에게 요청을 전송한다.
     */
    public function sendRequest(Request $request): RedirectResponse
    {
        /** @var \App\Models\Member $me */
        $me = Auth::guard('web')->user();

        // 종료된 행사에서는 요청 불가.
        $event = EventRound::find($me->event_id);
        if (($event?->status ?? EventRound::STATUS_PENDING) === EventRound::STATUS_CLOSED) {
            return back()->with('error', '종료된 행사에서는 요청을 보낼 수 없어요.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        // 연속/중복 전송 방지: 최근 10초 내 동일 내용 요청이 있으면 무시한다.
        $isDuplicate = \App\Models\AdminRequest::query()
            ->where('member_id', $me->id)
            ->where('body', $validated['body'])
            ->where('created_at', '>=', now()->subSeconds(10))
            ->exists();

        if ($isDuplicate) {
            return back()->with('error', '방금 보낸 요청이에요. 잠시 후 다시 시도해 주세요.');
        }

        $adminRequest = \App\Models\AdminRequest::create([
            'event_id'  => $me->event_id,
            'member_id' => $me->id,
            'table_no'  => $me->currentTableNo(),
            'body'      => $validated['body'],
        ]);

        // 관리자 플로팅 위젯으로 실시간 브로드캐스트.
        broadcast(new \App\Events\AdminRequestSent($adminRequest));

        return back()->with('success', '요청을 전달했어요. 관리자가 확인할게요!');
    }
}
