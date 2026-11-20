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
use App\Services\Rotation\MemberLabelService;
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
        protected MemberLabelService $memberLabelService,
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

        // 1:1 소개팅(음료 선택 켜짐)은 체크인 화면에서 음료 선택지/선택 현황도 다룬다. (음료별 선택 인원수 포함)
        $memberTableNos = [];
        if ($event->usesDrinks()) {
            $event->load([
                'drinks'  => fn ($q) => $q->withCount('members'),
                'members' => fn ($q) => $q->select(['id', 'event_id', 'name', 'gender', 'event_drink_id', 'drink_served_at']),
            ]);
            $memberTableNos = $this->memberTableNosFor($event);
        }

        return view('admin.rotation.checkin', [
            'event'           => $event,
            // 1:1 음료 현황 표기용 — 회원 id => 기준(입장등록 때 배정된) 테이블 번호
            'memberTableNos'  => $memberTableNos,
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
     * 회원별 기준(홈) 테이블 번호 맵(회원 id => 테이블 번호). 입장등록 때 배정된 자리이며 로테이션으로 바뀌지 않는다.
     * 기준 좌석이 없으면 키가 없다.
     *
     * @return array<int, int>
     */
    private function memberTableNosFor(EventRound $event): array
    {
        $out = [];
        foreach ($event->rotationSeats()->with('table:id,table_no')->get() as $seat) {
            $home = $seat->table?->table_no;
            if ($home !== null && $seat->member_id !== null) {
                $out[$seat->member_id] = (int) $home;
            }
        }

        return $out;
    }

    /**
     * 체크인 명단 목록 (ajax) + 테이블별 성비.
     */
    public function rosterList(int $id): JsonResponse
    {
        // 번호표로 입장등록한 회원 현황 ("성별|번호" => 등록 시각)
        $registered = $this->rosterService->registeredTickets($id);
        // 입장등록 완료자의 회원 상세 모달용 데이터 ("성별|번호" => 회원 배열)
        $memberDetails = $this->memberDetailsByTicket($id);

        $rosters = $this->rosterService->rosterList($id)->map(function (EventRoster $r) use ($registered, $memberDetails): array {
            $regKey = $r->ticket_no !== null ? ($r->gender . '|' . (int) $r->ticket_no) : null;

            return [
                'member'            => $regKey !== null ? ($memberDetails[$regKey] ?? null) : null,
                'id'                => $r->id,
                'name'              => $r->name,
                'gender'            => $r->gender,
                'platform'          => $r->platform,
                'height'            => $r->height,
                'birth_year'        => $r->birth_year,
                'job'               => $r->job,
                'merit'             => $r->merit,
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
     * 행사 회원의 상세 모달용 데이터. 키는 "성별|번호표". (숫자 번호표가 아닌 회원은 명단과 연결되지 않으므로 제외)
     * 행사 상세의 상세보기 버튼과 같은 형태(label·age, 최고관리자는 password 포함)로 만든다.
     *
     * @return array<string, array<string, mixed>>
     */
    private function memberDetailsByTicket(int $eventId): array
    {
        $labelMap   = $this->memberLabelService->labelMap($eventId);
        $superAdmin = $this->isSuperAdmin();
        $out        = [];

        Member::where('event_id', $eventId)->get()->each(function (Member $m) use (&$out, $labelMap, $superAdmin): void {
            if (! ctype_digit((string) $m->name)) {
                return;
            }

            $data = $m->toArray();
            if ($superAdmin) {
                $data['password'] = $m->password;
            }
            $data['age']           = $m->birth_year ? (now()->year - $m->birth_year + 1) : null;
            $data['last_login_at'] = $m->last_login_at?->format('Y-m-d H:i');
            $data['label']         = $this->memberLabelService->label($labelMap, $m->gender, $m->name, $m->platform);

            $out[$m->gender . '|' . (int) $m->name] = $data;
        });

        return $out;
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
            $request->safe()->only(['height', 'birth_year', 'job', 'merit']),
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
