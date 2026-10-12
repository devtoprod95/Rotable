<?php

namespace App\Http\Controllers;

use App\Models\EventRound;
use App\Models\Member;
use App\Models\RotationRound;
use Illuminate\Http\RedirectResponse;
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

        // 로테이션 상태는 행사(event_rounds) 단위로 관리한다.
        $eventStatus = EventRound::whereKey($me->event_id)->value('status') ?? EventRound::STATUS_PENDING;

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

        $rotations = $rounds->map(function (RotationRound $round) use ($eventStatus, $myId, $oppositeGen) {
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
            ];
        });

        return Inertia::render('Home', [
            'rotations'     => $rotations,
            'partnerGender' => $oppositeGen,
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
            'phone'      => $member->phone,
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
     * 채팅함 탭.
     */
    public function chats(): InertiaResponse
    {
        return Inertia::render('Chats');
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
