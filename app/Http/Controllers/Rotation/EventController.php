<?php

namespace App\Http\Controllers\Rotation;

use App\Dtos\RotationTableListRequestDto;
use App\Http\Requests\Admin\Rotation\EventStoreRequest;
use App\Http\Requests\Admin\Rotation\EventUpdateRequest;
use App\Http\Requests\Admin\Rotation\IdsRequest;
use App\Models\EventRound;
use App\Models\EventVisitSnapshot;
use App\Models\Member;
use App\Services\Rotation\MemberLabelService;
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
            'event'        => $event,
            'types'        => EventRound::typeOptions(),
            'statuses'     => EventRound::statusOptions(),
            // 참여자 표기 `닉네임(플랫폼)_N호` — 회원 id => 라벨
            'memberLabels' => $this->memberLabelsFor($id, $event->members),
        ]);
    }

    /**
     * 행사 정보 수정 (상세 화면). 상태(status)는 로테이션 흐름에서 관리하므로 수정하지 않는다.
     */
    public function eventUpdate(EventUpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->rotationService->eventUpdate($id, (object) $request->validated()), Response::HTTP_INTERNAL_SERVER_ERROR);
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
