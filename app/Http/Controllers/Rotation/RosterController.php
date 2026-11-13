<?php

namespace App\Http\Controllers\Rotation;

use App\Http\Requests\Admin\Rotation\IdsRequest;
use App\Http\Requests\Admin\Rotation\RosterAddRequest;
use App\Http\Requests\Admin\Rotation\RosterCheckInRequest;
use App\Http\Requests\Admin\Rotation\RosterDevSeedRostersRequest;
use App\Http\Requests\Admin\Rotation\RosterSetTicketRequest;
use App\Http\Requests\Admin\Rotation\RosterUpdateRequest;
use App\Models\EventRoster;
use App\Models\EventRound;
use App\Models\Member;
use App\Models\RotationTable;
use App\Services\Rotation\EventContextService;
use App\Services\Rotation\RosterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 체크인 — event_rosters (닉네임·플랫폼·성별 명단, 현장 체크인·번호표·테이블 지정).
 */
class RosterController extends RotationBaseController
{
    private const DUMMY_FORBIDDEN = '운영 환경이 아닌 곳에서 최고관리자만 사용할 수 있습니다.';

    public function __construct(
        Request $request,
        protected RosterService $rosterService,
        protected EventContextService $eventContextService,
    ) {
        parent::__construct($request);
    }

    /**
     * 사이드바 "체크인" 진입점. 지금 운영할 행사(진행 중 > 가까운 예정 > 최근)의 체크인 화면으로 보낸다.
     * 행사가 하나도 없으면 행사 생성 화면으로 보낸다. (행사는 체크인 화면 상단 탭바에서 바꿀 수 있다.)
     */
    public function checkinCurrent(): RedirectResponse
    {
        $eventId = $this->eventContextService->currentEventId();

        if ($eventId === null) {
            return redirect()->route('admin.rotation.event.create');
        }

        return redirect()->route('admin.rotation.event.checkin', ['id' => $eventId]);
    }

    /**
     * 체크인 전용 화면 (현장용).
     */
    public function checkinIndex(int $id): View
    {
        $event = EventRound::find($id);
        abort_if($event === null, 404);

        return view('admin.rotation.checkin', [
            'event'           => $event,
            'platformOptions' => Member::platformOptions(),
            // 체크인 때 테이블 지정용 — 이 행사의 테이블 목록 (번호 오름차순)
            'tables'          => RotationTable::where('event_id', $id)->orderBy('table_no')->get(['id', 'table_no']),
            // 최고관리자 전용 도구(기존 테이블 동기화 등) 노출 여부
            'devMode'         => $this->isSuperAdmin(),
            // 더미 데이터 버튼(명단 추가/체크인/입장등록)은 최고관리자 + 운영(production)이 아닐 때만 노출
            'dummyMode'       => $this->canUseDummyTools(),
        ]);
    }

    /**
     * 체크인 명단 목록 (ajax) + 테이블별 성비.
     */
    public function rosterList(int $id): JsonResponse
    {
        // 번호표로 입장등록한 회원 현황 ("성별|번호" => 등록 시각)
        $registered = $this->rosterService->registeredTickets($id);

        $rosters = $this->rosterService->rosterList($id)->map(function (EventRoster $r) use ($registered): array {
            $regKey = $r->ticket_no !== null ? ($r->gender . '|' . (int) $r->ticket_no) : null;

            return [
                'id'                => $r->id,
                'name'              => $r->name,
                'gender'            => $r->gender,
                'platform'          => $r->platform,
                'ticket_no'         => $r->ticket_no,
                'rotation_table_id' => $r->rotation_table_id !== null ? (int) $r->rotation_table_id : null,
                'checked_in'        => $r->checked_in_at !== null,
                'checked_in_at'     => $r->checked_in_at?->format('Y-m-d H:i'),
                // 입장등록 여부: 이 번호표(같은 성별)로 가입한 회원이 있는지
                'registered'        => $regKey !== null && isset($registered[$regKey]),
                'registered_at'     => $regKey !== null ? ($registered[$regKey] ?? null) : null,
            ];
        })->all();

        return response()->json([
            'data'         => $rosters,
            'total'        => count($rosters),
            // 테이블별 성비 요약 — 체크인 명단(roster) 기준이라 입장등록 전에도 즉시 반영된다.
            'table_ratios' => $this->rosterService->tableGenderRatios($id),
        ]);
    }

    /**
     * 체크인 명단 1명 추가 (닉네임/플랫폼/성별).
     */
    public function rosterAdd(RosterAddRequest $request, int $id): JsonResponse
    {
        $result = $this->rosterService->rosterAdd(
            $id,
            (string) $request->validated('name'),
            $request->validated('platform'),
            $request->validated('gender'),
        );

        return $this->respond($result, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 체크인 명단 1건 수정.
     */
    public function rosterUpdate(RosterUpdateRequest $request, int $id, int $rosterId): JsonResponse
    {
        return $this->respond($this->rosterService->rosterUpdate($rosterId, $request->validated()), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 체크인 명단 삭제 (ids 배열). 체크인한 사람은 서비스에서 거부한다.
     */
    public function rosterDelete(IdsRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->rosterService->rosterDelete($request->validated('ids')), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 체크인(참석/노쇼) 토글 + 번호표 부여.
     * ticket_no 를 보내면 그 번호(1순위·수동), 비우면 그 성별의 가장 작은 빈 번호(2순위·자동).
     */
    public function rosterToggleCheckIn(RosterCheckInRequest $request, int $id, int $rosterId): JsonResponse
    {
        return $this->respond($this->rosterService->rosterToggleCheckIn(
            $rosterId,
            $request->nullableInt('ticket_no'),
            $request->nullableInt('rotation_table_id'),
        ));
    }

    /**
     * 번호표 변경 (관리자). 이미 입장등록한 회원의 번호표도 함께 바뀐다.
     * rotation_table_id 를 보내면 앉을 테이블도 함께 바꾼다.
     */
    public function rosterSetTicket(RosterSetTicketRequest $request, int $id, int $rosterId): JsonResponse
    {
        return $this->respond($this->rosterService->rosterSetTicket(
            $rosterId,
            (int) $request->validated('ticket_no'),
            $request->nullableInt('rotation_table_id'),
        ));
    }

    /**
     * [개발용] 체크인(참석)된 사람들 기준으로 입장등록(회원) 더미 데이터 생성. (최고관리자 · 운영 환경 제외)
     */
    public function rosterDevSeed(int $id): JsonResponse
    {
        if (! $this->canUseDummyTools()) {
            return $this->forbidden(self::DUMMY_FORBIDDEN);
        }

        return $this->respond($this->rosterService->devSeedRegistrations($id));
    }

    /**
     * [개발용] 체크인 명단에 더미 신청자를 추가한다. (최고관리자 · 운영 환경 제외)
     */
    public function rosterDevSeedRosters(RosterDevSeedRostersRequest $request, int $id): JsonResponse
    {
        if (! $this->canUseDummyTools()) {
            return $this->forbidden(self::DUMMY_FORBIDDEN);
        }

        return $this->respond($this->rosterService->devSeedRosters(
            $id,
            (int) $request->validated('male'),
            (int) $request->validated('female'),
        ));
    }

    /**
     * [개발용] 미도착 명단 전체를 체크인한다. (번호표·테이블 자동, 최고관리자 · 운영 환경 제외)
     */
    public function rosterDevCheckIn(int $id): JsonResponse
    {
        if (! $this->canUseDummyTools()) {
            return $this->forbidden(self::DUMMY_FORBIDDEN);
        }

        return $this->respond($this->rosterService->devCheckInAll($id));
    }

    /**
     * [데이터 보정] 기존 좌석(rotation_seats)의 테이블을 체크인 명단(event_rosters)에 역동기화한다. (최고관리자 전용)
     */
    public function rosterSyncTables(int $id): JsonResponse
    {
        if (! $this->isSuperAdmin()) {
            return $this->forbidden('최고관리자만 사용할 수 있습니다.');
        }

        return $this->respond($this->rosterService->syncTablesFromSeats($id));
    }
}
