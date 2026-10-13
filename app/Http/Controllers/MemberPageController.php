<?php

namespace App\Http\Controllers;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\EventRound;
use App\Models\Member;
use App\Models\Message;
use App\Models\RotationRound;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return [
            'partners'      => $partners,
            'partnerGender' => $oppositeGen,
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
     */
    public function votes(): InertiaResponse
    {
        return Inertia::render('Votes');
    }

    /**
     * 요청하기 탭.
     */
    public function requests(): InertiaResponse
    {
        return Inertia::render('Requests');
    }
}
