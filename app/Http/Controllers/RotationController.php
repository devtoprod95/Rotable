<?php

namespace App\Http\Controllers;

use App\Dtos\RotationTableBulkStoreRequestDto;
use App\Dtos\RotationTableListRequestDto;
use App\Http\Requests\Admin\RotationSeatingStoreRequest;
use App\Http\Requests\Admin\RotationTableBulkStoreRequest;
use App\Models\EventRound;
use App\Services\RotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class RotationController extends Controller
{
    public function __construct(
        protected Request $request,
        protected RotationService $rotationService
    ) {
    }

    /* ======================================================================
     | 테이블 관리 — 개수만 입력하면 증분 자동 생성
     * ==================================================================== */

    public function tableIndex(): View
    {
        $params = [
            'eventId'       => $this->request->input('eventId') ?: '',
            'searchText'    => $this->request->input('searchText') ?: '',
            'page'          => $this->request->input('page') ?: 1,
            'size'          => $this->request->input('size') ?: 30,
            'sort'          => $this->request->input('sort')[0] ?? ['field' => 'id', 'dir' => 'desc'],
            'pendingEvents' => EventRound::where('status', EventRound::STATUS_PENDING)->orderByDesc('id')->get(),
            'events'        => EventRound::orderByDesc('id')->get(),
        ];

        return view('admin.rotation.tableList', $params);
    }

    public function tableList(): JsonResponse
    {
        $dto = new RotationTableListRequestDto();
        $dto->bind([
            'eventId'    => $this->request->input('eventId', ''),
            'searchText' => $this->request->input('searchText', ''),
            'page'       => $this->request->input('page', 1),
            'size'       => $this->request->input('size', 30),
            'sort'       => $this->request->input('sort.0', ['field' => 'id', 'dir' => 'desc']),
        ]);

        return response()->json($this->rotationService->tableList($dto));
    }

    /**
     * 테이블 벌크 생성: 행사 + 개수 → 기존 다음 번호부터 N개 증분 추가.
     */
    public function tableBulkStore(RotationTableBulkStoreRequest $request): JsonResponse
    {
        $dto    = new RotationTableBulkStoreRequestDto((object) $request->validated());
        $result = $this->rotationService->tableBulkStore($dto);

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    public function tableDelete(): JsonResponse
    {
        if ($this->invalidIds()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $result = $this->rotationService->tableDelete($this->request->input('ids'));

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    /* ======================================================================
     | 좌석 배정 — 단일 화면 (턴 선택 + 테이블 그리드, 넣을 때마다 즉시 저장)
     * ==================================================================== */

    public function seatingIndex(): View
    {
        $events  = EventRound::orderByDesc('id')->get();
        $eventId = (int) ($this->request->input('eventId') ?: ($events->first()->id ?? 0));
        $roundNo = (int) ($this->request->input('roundNo') ?? 0);

        return view('admin.rotation.seating', [
            'events'  => $events,
            'eventId' => $eventId,
            'roundNo' => $roundNo,
        ]);
    }

    /**
     * 특정 행사·턴의 회원 목록과 좌석 현황을 한 번에 반환.
     */
    public function seatingData(): JsonResponse
    {
        $eventId = (int) $this->request->input('eventId');
        $roundNo = (int) $this->request->input('roundNo');

        return response()->json([
            'members' => $this->rotationService->seatingMembers($eventId),
            'board'   => $this->rotationService->seatingBoard($eventId, $roundNo),
        ]);
    }

    /**
     * 특정 행사·턴의 좌석 현황을 테이블 그리드 데이터로 반환.
     */
    public function seatingBoard(): JsonResponse
    {
        $eventId = (int) $this->request->input('eventId');
        $roundNo = (int) $this->request->input('roundNo');

        return response()->json($this->rotationService->seatingBoard($eventId, $roundNo));
    }

    /**
     * 행사의 좌석 배정 대상 회원 목록 (성별 포함).
     */
    public function seatingMembers(): JsonResponse
    {
        $eventId = (int) $this->request->input('eventId');

        return response()->json($this->rotationService->seatingMembers($eventId));
    }

    /**
     * 좌석 추가 (턴 없으면 자동 생성). 넣을 때마다 호출.
     */
    public function seatingStore(RotationSeatingStoreRequest $request): JsonResponse
    {
        $result = $this->rotationService->seatingUpsert((object) $request->validated());

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    /**
     * 좌석 제거.
     */
    public function seatingDelete(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $result = $this->rotationService->seatingDelete((int) $this->request->input('id'));

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    /**
     * 로테이션 진행: 현재 턴 기준으로 다음 턴을 자동 생성한다.
     * (여자 고정 / 남자 한 칸 이동)
     */
    public function seatingRotate(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'event_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $result = $this->rotationService->rotateProgress((int) $this->request->input('event_id'));

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    /**
     * 로테이션 종료: 행사 상태를 '종료(X)'로 전환한다.
     */
    public function seatingFinish(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'event_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $result = $this->rotationService->rotateFinish((int) $this->request->input('event_id'));

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    /**
     * ids 공통 검증 (벌크 삭제용).
     */
    private function invalidIds(): bool
    {
        $validator = Validator::make($this->request->all(), [
            'ids'   => 'required|array',
            'ids.*' => 'integer',
        ]);

        return $validator->fails();
    }
}
