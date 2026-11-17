<?php

namespace App\Http\Controllers\Rotation;

use App\Dtos\RotationTableListRequestDto;
use App\Http\Requests\Admin\Rotation\EventDrinkSaveRequest;
use App\Http\Requests\Admin\Rotation\EventMemberUpdateRequest;
use App\Http\Requests\Admin\Rotation\EventStoreRequest;
use App\Http\Requests\Admin\Rotation\EventUpdateRequest;
use App\Http\Requests\Admin\Rotation\IdsRequest;
use App\Models\EventRound;
use App\Models\EventVisitSnapshot;
use App\Models\Member;
use App\Services\Rotation\MemberLabelService;
use App\Services\Rotation\MemberProfileService;
use App\Services\Rotation\RotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 행사(회차) 관리 — event_rounds 생성/목록/수정/삭제 + 행사의 테이블(rotation_tables) CRUD.
 */
class EventController extends RotationBaseController
{
    public function __construct(
        Request $request,
        protected RotationService $rotationService,
        protected MemberLabelService $memberLabelService,
        protected MemberProfileService $memberProfileService,
    ) {
        parent::__construct($request);
    }

    public function eventIndex(): View
    {
        return view('admin.rotation.eventList', [
            'page'          => $this->request->input('page') ?: 1,
            'size'          => $this->request->input('size') ?: 30,
            'sort'          => $this->request->input('sort')[0] ?? ['field' => 'id', 'dir' => 'desc'],
            'searchText'    => $this->request->input('searchText') ?: '',
            'type'          => $this->request->input('type') ?: '',
            'status'        => $this->request->input('status') ?: '',
            'types'         => EventRound::typeOptions(),
            'statuses'      => EventRound::statusOptions(),
            // 방문 통계: 가장 최근 자정 스냅샷 1건. (없으면 null)
            'visitSnapshot' => EventVisitSnapshot::orderByDesc('snapshot_date')->first(),
        ]);
    }

    public function eventList(): JsonResponse
    {
        return response()->json($this->rotationService->eventList(
            (int) $this->request->input('page', 1),
            (int) $this->request->input('size', 30),
            $this->request->input('sort.0', ['field' => 'id', 'dir' => 'desc']),
            [
                'searchText' => $this->request->input('searchText', ''),
                'type'       => $this->request->input('type', ''),
                'status'     => $this->request->input('status', ''),
            ]
        ));
    }

    /**
     * 행사 생성 전용 페이지. 상세(eventDetail)와 공용 폼 뷰를 사용한다.
     */
    public function eventCreate(): View
    {
        return view('admin.rotation.eventForm', [
            'event' => null,
            'types' => EventRound::typeOptions(),
        ]);
    }

    public function eventStore(EventStoreRequest $request): JsonResponse
    {
        $data               = $request->validated();
        $data['created_by'] = auth('admin')->id();

        return $this->respond($this->rotationService->eventStore((object) $data), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function eventDelete(IdsRequest $request): JsonResponse
    {
        return $this->respond($this->rotationService->eventDelete($request->validated('ids')), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 행사 상세 — 생성 페이지와 공용 폼 뷰를 사용하며, 테이블을 CRUD 한다.
     */
    public function eventDetail(int $id): View
    {
        $event = $this->rotationService->eventDetail($id);
        abort_if($event === null, 404);

        return view('admin.rotation.eventForm', [
            'event'          => $event,
            'types'          => EventRound::typeOptions(),
            'statuses'       => EventRound::statusOptions(),
            // 참여자 표기 `닉네임(플랫폼)_N호` — 회원 id => 라벨
            'memberLabels'   => $this->memberLabelsFor($id, $event->members),
            // 회원별 기준(입장등록 때 배정된) 테이블 번호 (1:1 음료 선택 현황 표기용)
            'memberTableNos' => $this->memberTableNosFor($event),
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
     * 행사 정보 수정 (상세 화면). 상태(status)는 로테이션 흐름에서 관리하므로 수정하지 않는다.
     */
    public function eventUpdate(EventUpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->rotationService->eventUpdate($id, (object) $request->validated()), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 행사 음료 선택지 저장 (1:1 소개팅). 입력한 목록으로 통째로 맞춘다.
     */
    public function eventDrinkSave(EventDrinkSaveRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->rotationService->eventDrinkSync($id, $request->validated('drinks') ?? []), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 행사 상세의 참여자 정보 수정. 번호표·성별·테이블은 체크인 화면에서만 바꾼다.
     * 비밀번호는 최고관리자만 바꿀 수 있다. (일반 관리자가 보내도 무시)
     */
    public function eventMemberUpdate(EventMemberUpdateRequest $request, int $id, int $memberId): JsonResponse
    {
        return $this->respond(
            $this->memberProfileService->update($id, $memberId, $request->validated(), $this->isSuperAdmin()),
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }

    /**
     * 행사 상세의 테이블 목록 (Tabulator ajax).
     */
    public function eventTableList(int $id): JsonResponse
    {
        $dto = new RotationTableListRequestDto();
        $dto->bind([
            'eventId' => (string) $id,
            'page'    => $this->request->input('page', 1),
            'size'    => $this->request->input('size', 100),
            'sort'    => $this->request->input('sort.0', ['field' => 'table_no', 'dir' => 'asc']),
        ]);

        return response()->json($this->rotationService->tableList($dto));
    }

    /**
     * 행사 상세에서 테이블 1개 추가.
     */
    public function eventTableAdd(int $id): JsonResponse
    {
        return $this->respond($this->rotationService->tableAdd($id), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 행사 상세에서 테이블 삭제.
     */
    public function eventTableDelete(IdsRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->rotationService->tableDelete($request->validated('ids')), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 행사 회원들의 관리자 표기 라벨 맵(회원 id => `닉네임(플랫폼)_N호`).
     *
     * @param  iterable<int, Member>  $members
     * @return array<int, string>
     */
    private function memberLabelsFor(int $eventId, iterable $members): array
    {
        $map = $this->memberLabelService->labelMap($eventId);

        $out = [];
        foreach ($members as $m) {
            $out[$m->id] = $this->memberLabelService->label($map, $m->gender, $m->name, $m->platform);
        }

        return $out;
    }
}
