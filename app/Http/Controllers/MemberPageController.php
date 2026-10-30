<?php

namespace App\Http\Controllers;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\EventRound;
use App\Models\Member;
use App\Models\Message;
use App\Models\RotationSeat;
use App\Models\RotationTable;
use App\Models\Vote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MemberPageController extends Controller
{
    /**
     * 테이블 투표 게임 집계(Redis)의 TTL(초). 안전한 자동 청소용 — 한 행사 길이보다 넉넉히.
     */
    private const TABLE_POLL_TTL = 21600; // 6시간

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

        $myId        = $me->id;
        $oppositeGen = $me->gender === Member::GENDER_MALE ? Member::GENDER_FEMALE : Member::GENDER_MALE;

        // 테이블 번호 목록(오름차순) — 남자 이동 공식 기준. 테이블 수 = 마지막 턴 판정 기준.
        $tableNos   = RotationTable::where('event_id', $me->event_id)
            ->orderBy('table_no')
            ->pluck('table_no')
            ->map(fn ($n) => (int) $n)
            ->all();
        $tableCount = count($tableNos);

        // 현재 턴(0이면 아직 로테이션 시작 전 → 노출할 턴 없음).
        $currentRound = (int) ($event?->current_round ?? 0);

        // 이 행사의 기준(홈) 좌석 전부. (턴별 자리는 공식으로 계산)
        /** @var \Illuminate\Database\Eloquent\Collection<int, RotationSeat> $seats */
        $seats = RotationSeat::query()
            ->with(['member', 'table'])
            ->where('event_id', $me->event_id)
            ->get();

        // 각 좌석의 홈 테이블 번호 캐시.
        $homeTableNoByMember = [];
        foreach ($seats as $seat) {
            if ($seat->member_id !== null && $seat->table) {
                /** @var object $table */
                /** @var object $seat */
                $table = $seat->table;
                $homeTableNoByMember[$seat->member_id] = (int) ($table->table_no);
            }
        }

        // 현재 턴 기준 각 회원의 테이블 번호 (상세 모달 "현재 테이블" 표기용).
        $currentTableNoByMember = [];
        if ($currentRound >= 1) {
            foreach ($seats as $seat) {
                $homeNo = $homeTableNoByMember[$seat->member_id] ?? null;
                if ($homeNo === null) {
                    continue;
                }
                $currentTableNoByMember[$seat->member_id] = EventRound::seatedTableNoFor(
                    round: $currentRound,
                    isFemale: $seat->member?->gender === Member::GENDER_FEMALE,
                    homeTableNo: $homeNo,
                    tableNos: $tableNos,
                );
            }
        }

        // 1:1 소개팅 여부 (홈에서 매칭 선택 UI/투표 로직을 노출할지 결정).
        $isOneToOne = $event !== null && $event->isOneToOne();

        // 1턴부터 현재 턴까지 각 턴을 공식으로 계산해 최신 턴이 위로 오도록 내림차순 구성.
        $rotations = collect(range($currentRound, 1))
            ->filter(fn ($t) => $t >= 1) // currentRound=0 이면 빈 목록
            ->map(function (int $round) use ($seats, $homeTableNoByMember, $currentTableNoByMember, $myId, $oppositeGen, $tableNos, $tableCount, $eventStatus) {
                $myHome = $homeTableNoByMember[$myId] ?? null;
                $myTableNo = $myHome === null
                    ? null
                    : EventRound::seatedTableNoFor(
                        round: $round,
                        isFemale: $oppositeGen === Member::GENDER_MALE, // 내가 여자면 상대가 남자
                        homeTableNo: $myHome,
                        tableNos: $tableNos,
                    );

                // 이번 턴에 내 테이블에 앉는 이성 상대들.
                $partners = collect();
                if ($myTableNo !== null) {
                    $partners = $seats
                        ->filter(function ($s) use ($round, $oppositeGen, $tableNos, $homeTableNoByMember, $myTableNo) {
                            if ($s->member?->gender !== $oppositeGen) {
                                return false;
                            }
                            $home = $homeTableNoByMember[$s->member_id] ?? null;
                            if ($home === null) {
                                return false;
                            }
                            $seated = EventRound::seatedTableNoFor(
                                round: $round,
                                isFemale: $s->member?->gender === Member::GENDER_FEMALE,
                                homeTableNo: $home,
                                tableNos: $tableNos,
                            );

                            return $seated === $myTableNo;
                        })
                        ->map(fn ($s) => $this->transformMember($s->member, $currentTableNoByMember[$s->member_id] ?? null))
                        ->values();
                }

                return [
                    'id'       => $round,          // 턴 번호를 식별자로 사용 (저장 행이 없으므로)
                    'round_no' => $round,
                    'table_no' => $myTableNo,
                    'status'   => $eventStatus,
                    'partners' => $partners,
                    'is_last'  => $tableCount > 0 && $round >= $tableCount,
                ];
            })
            ->values();

        // 1:1 로테이션: 매칭 선택을 '홈'에서 수행한다.
        //  - isOneToOne: 프론트에서 선택 UI 노출 분기
        //  - matchSelectable: 지금 선택/수정이 가능한 기간인지(진행 S / 매칭중 V, 종료 전)
        //  - myVotedIds: 내가 이미 선택한 상대 id (카드 선택 상태 표시)
        //  - maxVotes: 최대 선택 가능 수
        $myVotedIds = [];
        if ($isOneToOne) {
            $myVotedIds = Vote::query()
                ->where('event_id', $me->event_id)
                ->where('voter_id', $me->id)
                ->pluck('target_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return Inertia::render('Home', [
            'rotations'       => $rotations,
            'eventTitle'      => $eventTitle,
            'eventTime'       => $eventTime,
            'eventStatus'     => $eventStatus,
            'isOneToOne'      => $isOneToOne,
            'matchSelectable' => $event !== null && $event->isMatchSelectable(),
            'myVotedIds'      => $myVotedIds,
            'maxVotes'        => Vote::MAX_VOTES_PER_MEMBER,
            ...$this->messagingData($me),
        ]);
    }

    /**
     * 기록 노출용 회원 정보 (members 전체 컬럼 + 공통 추가 프로필).
     *
     * @return array<string, mixed>
     */
    private function transformMember(Member $member, ?int $currentTableNo = null): array
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
            // 현재 턴 기준 상대가 앉아 있는 테이블 번호 (로테이션 시작 전/미배정이면 null)
            'current_table_no' => $currentTableNo,
            // 추가 프로필 (모든 유형 공통) — 상대 카드 상세 모달용
            'ideal_type' => $member->ideal_type,
            'merit'      => $member->merit,
            'hobby'      => $member->hobby,
            'tmi'        => $member->tmi,
        ];
    }

    /**
     * 현재 턴(event_rounds.current_round) 기준으로 행사 내 모든 회원의 테이블 번호 맵.
     * 턴별 자리는 저장하지 않고 기준자리 + current_round 공식으로 계산한다.
     * 로테이션 시작 전(current_round<1)이면 빈 맵.
     *
     * @return array<int, int> member_id => 현재 테이블 번호
     */
    private function currentTableNoMap(int $eventId): array
    {
        $event = EventRound::find($eventId);
        $round = (int) ($event?->current_round ?? 0);

        // 로테이션 시작 전(round<1)이거나 매칭이 종료(X)된 뒤에는 "현재 테이블"을 노출하지 않는다.
        if ($round < 1 || $event?->status === EventRound::STATUS_CLOSED) {
            return [];
        }

        $tableNos = RotationTable::where('event_id', $eventId)
            ->orderBy('table_no')
            ->pluck('table_no')
            ->map(fn ($n) => (int) $n)
            ->all();

        /** @var \Illuminate\Database\Eloquent\Collection<int, RotationSeat> $seats */
        $seats = RotationSeat::query()
            ->with(['member:id,gender', 'table:id,table_no'])
            ->where('event_id', $eventId)
            ->get();

        $map = [];
        foreach ($seats as $seat) {
            $homeNo = $seat->table?->table_no;
            if ($homeNo === null || $seat->member_id === null) {
                continue;
            }
            $map[$seat->member_id] = EventRound::seatedTableNoFor(
                round: $round,
                isFemale: $seat->member?->gender === Member::GENDER_FEMALE,
                homeTableNo: (int) $homeNo,
                tableNos: $tableNos,
            );
        }

        return $map;
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

        // 현재 턴 기준 각 회원의 테이블 번호. (상세 모달 "현재 테이블" 표기용)
        $currentTableNoByMember = $this->currentTableNoMap($me->event_id);

        $partners = Member::query()
            ->where('event_id', $me->event_id)
            ->where('gender', $oppositeGen)
            ->where('id', '!=', $me->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Member $member): array => $this->transformMember(
                $member,
                $currentTableNoByMember[$member->id] ?? null,
            ))
            ->values();

        $used = Message::where('event_id', $me->event_id)
            ->where('sender_id', $me->id)
            ->count();
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
        $used = Message::where('event_id', $me->event_id)
            ->where('sender_id', $me->id)
            ->count();
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
     *
     * - message_ids 가 전달되면 해당 메시지들만 읽음 처리한다. (받은함에서 메시지를 열어봤을 때)
     * - message_ids 가 없으면 받은 메시지 전체를 읽음 처리한다. (하위호환)
     */
    public function markMessagesRead(Request $request): RedirectResponse
    {
        /** @var \App\Models\Member $me */
        $me = Auth::guard('web')->user();

        $validated = $request->validate([
            'message_ids'   => ['sometimes', 'array'],
            'message_ids.*' => ['integer'],
        ]);

        // 읽음 처리 전에 대상 메시지(id/발신자)를 먼저 수집한다.
        $query = Message::query()
            ->where('receiver_id', $me->id)
            ->whereNull('read_at');

        // 특정 메시지만 열어본 경우: 전달받은 id 로 범위를 좁힌다.
        if (!empty($validated['message_ids'])) {
            $query->whereIn('id', $validated['message_ids']);
        }

        $unread = $query->get(['id', 'sender_id']);

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
     * - 투표중(V) 단계에서 이성 회원에게 최대 3명까지 투표(지목)한다.
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

        $isOneToOne = $event !== null && $event->isOneToOne();

        // 매칭 투표 가능 여부(와인파티): 상태가 '매칭중(V)'이면 열림.
        $isVotingOpen = $event !== null && $event->isVotingOpen();
        $isClosed     = $status === EventRound::STATUS_CLOSED;

        // 1:1은 매칭 선택을 '홈'에서만 하고, '매칭 결과' 탭은 종료(X) 후 결과만 보여준다.
        // 따라서 이 화면에서의 선택 가능(canVote)은 와인파티에서만 true.
        $canVote = ! $isOneToOne && $isVotingOpen;

        // 매칭 투표가 끝난 '마감 대기' 상태는 상태를 V→X 로 전환하는 스케줄러가 처리하므로
        // 화면에서는 별도의 시각 기반 마감 판정을 하지 않는다. (V 이면 항상 투표 가능)
        $isVotingEnded = false;

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

        // 현재 턴 기준 각 회원이 앉아 있는 테이블 번호 맵. (상세 모달에 "현재 테이블" 표기)
        $currentTableNoByMember = $this->currentTableNoMap($me->event_id);

        $candidateList = $candidates->map(function (Member $c) use ($myVotedIds, $votedForMeIds, $isClosed, $isOneToOne, $currentTableNoByMember) {
            $data        = $this->transformMember($c, $currentTableNoByMember[$c->id] ?? null);
            $iVotedThem  = in_array($c->id, $myVotedIds, true);
            $theyVotedMe = in_array($c->id, $votedForMeIds, true);
            // 매칭 여부는 종료(X) 후에만 공개한다. 매칭중(V)에는 쌍방이어도 노출하지 않는다.
            $isMatch     = $isClosed && $iVotedThem && $theyVotedMe;

            $data['voted']       = $iVotedThem;
            $data['voted_for_me'] = $isClosed && $theyVotedMe; // 종료 후에만 "나를 선택함" 공개
            $data['is_match']    = $isMatch;

            // 연락처 공개 규칙 (종료 후):
            //  - 와인파티: "상호 매칭"된 상대에게만 공개.
            //  - 1:1: 상호 매칭 + "나를 선택해준 상대"의 번호까지 공개.
            $revealPhone = $isClosed && ($isMatch || ($isOneToOne && $theyVotedMe));
            $data['phone'] = $revealPhone ? $c->phone : null;

            return $data;
        })->values();

        // 이번 행사에서 탄생한 커플(상호 매칭 쌍) 수. 종료(X) 상태에서만 계산해 안내에 쓴다.
        $coupleCount = 0;
        if ($isClosed && $event !== null) {
            // 서로 지목한 쌍(A→B, B→A)을 self-join 으로 센 뒤 2로 나눠 쌍 수를 구한다.
            $mutualRows = DB::table('event_votes as v1')
                ->join('event_votes as v2', function ($join) {
                    $join->on('v1.event_id', '=', 'v2.event_id')
                        ->on('v1.voter_id', '=', 'v2.target_id')
                        ->on('v1.target_id', '=', 'v2.voter_id');
                })
                ->where('v1.event_id', $event->id)
                ->count();
            $coupleCount = intdiv($mutualRows, 2);
        }

        return Inertia::render('Votes', [
            'eventTitle'    => $event?->title,
            'eventStatus'   => $status,
            'isOneToOne'    => $isOneToOne,
            'canVote'       => $canVote,
            'isClosed'      => $isClosed,
            'votingEnded'   => $isVotingEnded,
            'maxVotes'      => Vote::MAX_VOTES_PER_MEMBER,
            'candidates'    => $candidateList,
            'myVotedIds'    => $myVotedIds,
            'receivedCount' => count($votedForMeIds),
            'coupleCount'   => $coupleCount,
            'partnerGender' => $oppositeGen,
        ]);
    }

    /**
     * 투표 저장(수정). 투표중(V) 단계에서만 허용하며, 전달받은 대상 목록으로 전체 교체한다.
     * - 최대 3명까지만 허용.
     * - 같은 행사의 이성만 지목 가능.
     */
    public function saveVotes(Request $request): RedirectResponse
    {
        /** @var \App\Models\Member $me */
        $me = Auth::guard('web')->user();

        $event = EventRound::find($me->event_id);

        // 매칭 선택이 가능한 기간이 아니면 저장 불가.
        // - 1:1: 진행(S)·매칭중(V) 동안 종료 전까지 (선택은 홈에서 수행)
        // - 와인파티: 매칭중(V) + 자동마감 시각 전
        if ($event === null || ! $event->isMatchSelectable()) {
            return back()->with('error', '지금은 매칭 선택을 할 수 없어요.');
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
     * 게임 목록(선택) 화면.
     *
     * 와인파티(wine)이면서 진행중(S)·매칭중(V) 일 때만 접근 가능하다.
     * (대기/종료 상태이거나 1:1 타입이면 홈으로 돌려보낸다)
     */
    public function games(): InertiaResponse|RedirectResponse
    {
        if (! $this->isGamePlayable()) {
            return redirect()->route('home');
        }

        return Inertia::render('Games', []);
    }

    /**
     * 개별 게임 플레이 화면.
     *
     * slug ↔ Inertia 페이지 매핑. 아직 미구현 게임은 목록으로 되돌린다.
     * 와인파티 진행중(S/V)이 아니면 접근을 막는다.
     */
    public function gameShow(string $slug): InertiaResponse|RedirectResponse
    {
        if (! $this->isGamePlayable()) {
            return redirect()->route('home');
        }

        $pages = [
            'image-mild'  => 'Games/ImageMild',
            'image-spicy' => 'Games/ImageSpicy',
            'balance'     => 'Games/Balance',
            'table-poll'  => 'Games/TablePoll',
        ];

        if (! isset($pages[$slug])) {
            return redirect()->route('games');
        }

        // 테이블 투표 게임은 "현재 테이블"에 묶여야 하므로 행사/테이블 정보를 함께 내려준다.
        $props = [];
        if ($slug === 'table-poll') {
            /** @var \App\Models\Member $me */
            $me = Auth::guard('web')->user();

            $tableNo = $me->currentTableNo();

            // 아직 좌석(테이블)이 배정되지 않았으면 플레이할 수 없다 → 목록으로.
            if ($tableNo === null) {
                return redirect()->route('games')
                    ->with('error', '아직 테이블이 배정되지 않았어요. 자리에 앉은 뒤 다시 시도해 주세요.');
            }

            // 현재 테이블의 진행 상태(진행 중인 질문/집계)를 Redis 에서 읽어 함께 내려준다.
            // → 중간에 들어오거나 다른 페이지 갔다 온 사람도 현재 현황을 그대로 본다.
            $eventId = (int) $me->event_id;
            $state   = Redis::hgetall($this->tablePollKey($eventId, $tableNo));

            $props = [
                'eventId'      => $eventId,
                'tableNo'      => $tableNo,
                'seatCount'    => $me->currentTableSeatCount(),
                // 저장된 진행 상태가 없으면 null → 프론트는 0번 질문부터 시작.
                'initialState' => $state === []
                    ? null
                    : [
                        'questionIndex' => (int) ($state['q'] ?? 0),
                        'counts'        => [
                            'yes' => (int) ($state['yes'] ?? 0),
                            'no'  => (int) ($state['no'] ?? 0),
                        ],
                    ],
            ];
        }

        return Inertia::render($pages[$slug], $props);
    }

    /**
     * 테이블 투표 게임 - 익명 Yes/No 한 표를 처리한다.
     *
     * 현재 집계는 Redis 해시에 보관(휘발성)하여, 중간에 들어온 사람도
     * "지금까지 몇 명이 YES/NO 를 눌렀는지" 정확히 볼 수 있게 한다.
     * 누가 눌렀는지는 저장하지 않으므로 익명성은 유지된다.
     * 어떤 테이블에 던지는지는 서버가 "요청자의 현재 테이블" 로 직접 판정한다.
     */
    public function tablePollVote(Request $request): RedirectResponse
    {
        if (! $this->isGamePlayable()) {
            return back();
        }

        /** @var \App\Models\Member $me */
        $me = Auth::guard('web')->user();

        $validated = $request->validate([
            'question_index' => ['required', 'integer', 'min:0'],
            'answer'         => ['required', 'in:yes,no'],
        ]);

        $tableNo = $me->currentTableNo();
        if ($tableNo === null) {
            return back();
        }

        $eventId       = (int) $me->event_id;
        $questionIndex = (int) $validated['question_index'];
        $answer        = $validated['answer'];
        $key           = $this->tablePollKey($eventId, $tableNo);

        // 현재 진행 중인 질문과 일치할 때만 집계한다. (지난 질문의 늦은 표 무시)
        $currentQuestion = Redis::hget($key, 'q');
        if ($currentQuestion !== null && (int) $currentQuestion !== $questionIndex) {
            return back();
        }

        // 첫 표라면 질문 인덱스를 함께 기록한다.
        if ($currentQuestion === null) {
            Redis::hset($key, 'q', $questionIndex);
        }
        Redis::hincrby($key, $answer, 1);
        Redis::expire($key, self::TABLE_POLL_TTL);

        broadcast(new \App\Events\TablePollVoted(
            $eventId,
            $tableNo,
            $questionIndex,
            $answer,
        ));

        return back();
    }

    /**
     * 테이블 투표 게임 - "다음 질문" 으로 넘겼음을 처리한다.
     *
     * 새 질문으로 집계를 리셋(Redis 해시 초기화)하고 같은 테이블에 브로드캐스트한다.
     */
    public function tablePollNext(Request $request): RedirectResponse
    {
        if (! $this->isGamePlayable()) {
            return back();
        }

        /** @var \App\Models\Member $me */
        $me = Auth::guard('web')->user();

        $validated = $request->validate([
            'question_index' => ['required', 'integer', 'min:0'],
        ]);

        $tableNo = $me->currentTableNo();
        if ($tableNo === null) {
            return back();
        }

        $eventId       = (int) $me->event_id;
        $questionIndex = (int) $validated['question_index'];
        $key           = $this->tablePollKey($eventId, $tableNo);

        // 집계를 새 질문으로 초기화한다. (이전 질문 데이터는 사라짐)
        Redis::del($key);
        Redis::hset($key, 'q', $questionIndex);
        Redis::hset($key, 'yes', 0);
        Redis::hset($key, 'no', 0);
        Redis::expire($key, self::TABLE_POLL_TTL);

        broadcast(new \App\Events\TablePollQuestionChanged(
            $eventId,
            $tableNo,
            $questionIndex,
        ));

        return back();
    }

    /** 테이블 투표 게임의 현재 집계를 담는 Redis 해시 키. */
    private function tablePollKey(int $eventId, int $tableNo): string
    {
        return "table-poll:{$eventId}:{$tableNo}";
    }

    /**
     * 게임을 즐길 수 있는 상태인지 판정한다.
     * - 와인파티(wine) 타입이면서
     * - 진행중(S) 상태일 때만 true. (매칭중 V·종료 X 등은 불가)
     */
    private function isGamePlayable(): bool
    {
        /** @var \App\Models\Member|null $me */
        $me = Auth::guard('web')->user();
        if ($me === null) {
            return false;
        }

        $event = EventRound::find($me->event_id);
        if ($event === null) {
            return false;
        }

        return $event->type === EventRound::TYPE_WINE
            && $event->status === EventRound::STATUS_STARTED;
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
