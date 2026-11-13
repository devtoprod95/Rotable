<?php

namespace App\Http\Controllers\Rotation;

use App\Http\Requests\Admin\RotationSeatingStoreRequest;
use App\Http\Requests\Admin\Rotation\SeatingDeleteRequest;
use App\Http\Requests\Admin\Rotation\SeatingEventRequest;
use App\Http\Requests\Admin\Rotation\SeatingRotationLimitRequest;
use App\Models\EventRound;
use App\Services\Rotation\EventContextService;
use App\Services\Rotation\SeatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 로테이션 진행 — 기준자리 배정 + 턴 진행/되돌리기 + 매칭 시작·종료 등 행사 상태 전이.
 * (규칙·상태 전이는 SeatingService, 마지막 턴 계산은 EventRound::maxRound())
 */
class SeatingController extends RotationBaseController
{
    public function __construct(
        Request $request,
        protected SeatingService $seatingService,
        protected EventContextService $eventContextService,
    ) {
        parent::__construct($request);
    }

    public function seatingIndex(): View
    {
        $events  = EventRound::orderByDesc('id')->get();
        // 행사 미지정 시: 진행 중 > 가까운 예정 > 최근 행사 (사이드바 "체크인"과 같은 기준)
        $eventId = (int) ($this->request->input('eventId')
            ?: ($this->eventContextService->currentEventId() ?? ($events->first()->id ?? 0)));

        return view('admin.rotation.seating', [
            'events'   => $events,
            'eventId'  => $eventId,
            'roundNo'  => (int) ($this->request->input('roundNo') ?? 0),
            'statuses' => EventRound::statusOptions(),
        ]);
    }

    /**
     * 특정 행사·턴의 회원 목록과 좌석 현황을 한 번에 반환.
     */
    public function seatingData(): JsonResponse
    {
        [$eventId, $roundNo] = $this->boardQuery();

        return response()->json([
            'members' => $this->seatingService->seatingMembers($eventId),
            'board'   => $this->seatingService->seatingBoard($eventId, $roundNo),
        ]);
    }

    /**
     * 특정 행사·턴의 좌석 현황(테이블 그리드).
     */
    public function seatingBoard(): JsonResponse
    {
        [$eventId, $roundNo] = $this->boardQuery();

        return response()->json($this->seatingService->seatingBoard($eventId, $roundNo));
    }

    /**
     * 행사의 좌석 배정 대상 회원 목록 (성별 포함).
     */
    public function seatingMembers(): JsonResponse
    {
        return response()->json($this->seatingService->seatingMembers((int) $this->request->input('eventId')));
    }

    /**
     * 기준자리 배정. 넣을 때마다 호출.
     */
    public function seatingStore(RotationSeatingStoreRequest $request): JsonResponse
    {
        return $this->respond($this->seatingService->seatingUpsert((object) $request->validated()), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 기준자리 제거.
     */
    public function seatingDelete(SeatingDeleteRequest $request): JsonResponse
    {
        return $this->respond($this->seatingService->seatingDelete((int) $request->validated('id')), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 로테이션 횟수(총 턴 수) 지정. 비우면 테이블 수만큼. (0턴에서만 가능 — 서비스에서 검증)
     */
    public function seatingRotationLimit(SeatingRotationLimitRequest $request): JsonResponse
    {
        return $this->respond($this->seatingService->setRotationLimit(
            (int) $request->validated('event_id'),
            $request->nullableInt('rotation_limit'),
        ));
    }

    /**
     * 로테이션 진행: 현재 턴 +1. (여자 고정 / 남자 한 칸 이동)
     */
    public function seatingRotate(SeatingEventRequest $request): JsonResponse
    {
        return $this->respond($this->seatingService->rotateProgress($this->eventId($request)), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 로테이션 되돌리기: 현재 턴 -1. (실수로 한 번 더 진행했을 때)
     */
    public function seatingRotateBack(SeatingEventRequest $request): JsonResponse
    {
        return $this->respond($this->seatingService->rotateBack($this->eventId($request)), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 매칭(투표) 시작: 진행(S) → 매칭중(V). (와인파티)
     */
    public function seatingFinish(SeatingEventRequest $request): JsonResponse
    {
        return $this->respond($this->seatingService->openVoting($this->eventId($request)), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 매칭 종료(수동): → 종료(X). (자동 9시 마감과 별개로 즉시 마감)
     */
    public function seatingCloseVoting(SeatingEventRequest $request): JsonResponse
    {
        return $this->respond($this->seatingService->closeVoting($this->eventId($request)), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 종료 되돌리기: 종료(X) → 직전 상태. (와인파티 → 매칭중 V, 1:1 → 진행 S)
     */
    public function seatingReopen(SeatingEventRequest $request): JsonResponse
    {
        return $this->respond($this->seatingService->reopenFromClosed($this->eventId($request)));
    }

    /**
     * 매칭 시작 취소: 매칭중(V) → 진행(S). (와인파티 전용)
     */
    public function seatingCancelVoting(SeatingEventRequest $request): JsonResponse
    {
        return $this->respond($this->seatingService->cancelVoting($this->eventId($request)));
    }

    /**
     * 보드 조회 쿼리 (eventId, roundNo). roundNo < 0 이면 서비스가 현재 턴을 고른다.
     *
     * @return array{0:int, 1:int}
     */
    private function boardQuery(): array
    {
        return [(int) $this->request->input('eventId'), (int) $this->request->input('roundNo')];
    }

    private function eventId(SeatingEventRequest $request): int
    {
        return (int) $request->validated('event_id');
    }
}
