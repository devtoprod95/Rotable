<?php

namespace App\Http\Controllers\Rotation;

use App\Http\Requests\Admin\Rotation\PaybackSaveRequest;
use App\Http\Requests\Admin\Rotation\PaybackTransferRequest;
use App\Models\EventPayback;
use App\Models\EventRound;
use App\Models\Member;
use App\Services\Rotation\EventContextService;
use App\Services\Rotation\PaybackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 행사 페이백 관리 — 페이백(100% / 80%) 대상, 계좌, 입금 처리.
 */
class PaybackController extends RotationBaseController
{
    public function __construct(
        Request $request,
        protected PaybackService $paybackService,
        protected EventContextService $eventContextService,
    ) {
        parent::__construct($request);
    }

    /**
     * 사이드바 "페이백 관리" 진입점. 지금 운영할 행사의 페이백 화면으로 보낸다.
     */
    public function paybackCurrent(): RedirectResponse
    {
        $eventId = $this->eventContextService->currentEventId();

        if ($eventId === null) {
            return redirect()->route('admin.rotation.event.create');
        }

        return redirect()->route('admin.rotation.event.payback', ['id' => $eventId]);
    }

    /**
     * 페이백 관리 화면.
     */
    public function paybackIndex(int $id): View
    {
        $event = EventRound::find($id);
        abort_if($event === null, 404);

        return view('admin.rotation.payback', [
            'event'           => $event,
            'rateOptions'     => EventPayback::rateOptions(),
            'bankOptions'     => EventPayback::bankOptions(),
            'platformOptions' => Member::platformOptions(),
        ]);
    }

    /**
     * 페이백 데이터 (ajax): 대상 목록 · 합계 · 추가 가능한 명단 · 참가비.
     */
    public function paybackList(int $id): JsonResponse
    {
        $event = EventRound::find($id);
        abort_if($event === null, 404);

        return response()->json($this->paybackService->eventPaybacks($event));
    }

    /**
     * 페이백 대상 추가.
     */
    public function paybackAdd(PaybackSaveRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->paybackService->add($id, $request->validated()));
    }

    /**
     * 페이백 정보 수정.
     */
    public function paybackUpdate(PaybackSaveRequest $request, int $id, int $paybackId): JsonResponse
    {
        return $this->respond($this->paybackService->update($id, $paybackId, $request->validated()));
    }

    /**
     * 입금 완료 / 완료 취소.
     */
    public function paybackTransfer(PaybackTransferRequest $request, int $id, int $paybackId): JsonResponse
    {
        return $this->respond($this->paybackService->setTransferred(
            $id,
            $paybackId,
            $request->boolean('done'),
            auth('admin')->id(),
        ));
    }

    /**
     * 페이백 대상에서 빼기.
     */
    public function paybackDelete(int $id, int $paybackId): JsonResponse
    {
        return $this->respond($this->paybackService->delete($id, $paybackId));
    }
}
