<?php

namespace App\Http\Controllers\Rotation;

use App\Http\Requests\Admin\Rotation\EventCostSaveRequest;
use App\Http\Requests\Admin\Rotation\RosterPayTypeRequest;
use App\Models\EventRoster;
use App\Models\EventRound;
use App\Models\Member;
use App\Services\Rotation\EventContextService;
use App\Services\Rotation\RevenueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 행사 매출 — 결제 합계 · 플랫폼 수수료 · 페이백 · 비용 · 순이익.
 */
class RevenueController extends RotationBaseController
{
    public function __construct(
        Request $request,
        protected RevenueService $revenueService,
        protected EventContextService $eventContextService,
    ) {
        parent::__construct($request);
    }

    /**
     * 사이드바 "매출" 진입점. 지금 운영할 행사의 매출 화면으로 보낸다.
     */
    public function revenueCurrent(): RedirectResponse
    {
        $eventId = $this->eventContextService->currentEventId();

        if ($eventId === null) {
            return redirect()->route('admin.rotation.event.create');
        }

        return redirect()->route('admin.rotation.event.revenue', ['id' => $eventId]);
    }

    /**
     * 매출 화면.
     */
    public function revenueIndex(int $id): View
    {
        $event = EventRound::find($id);
        abort_if($event === null, 404);

        return view('admin.rotation.revenue', [
            'event'           => $event,
            'platformOptions' => Member::platformOptions(),
            'payTypeOptions'  => EventRoster::payTypeOptions(),
        ]);
    }

    /**
     * 매출 데이터 (ajax).
     */
    public function revenueData(int $id): JsonResponse
    {
        $event = EventRound::find($id);
        abort_if($event === null, 404);

        return response()->json($this->revenueService->eventRevenue($event));
    }

    /**
     * 비용 항목 저장 (목록 통째로).
     */
    public function costSave(EventCostSaveRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->revenueService->syncCosts($id, $request->validated('costs') ?? []), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 신청자 결제 유형 변경.
     */
    public function rosterPayType(RosterPayTypeRequest $request, int $id, int $rosterId): JsonResponse
    {
        return $this->respond($this->revenueService->setPayType($id, $rosterId, (string) $request->validated('pay_type')));
    }
}
