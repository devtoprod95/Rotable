<?php

namespace App\Http\Controllers\Rotation;

use App\Http\Requests\Admin\Rotation\HistoryDetailRequest;
use App\Http\Requests\Admin\Rotation\HistorySearchRequest;
use App\Services\Rotation\MeetingHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 만남 이력 조회 — 전화번호(정확 일치) 기준. 만남 = 같은 행사 공동 참여.
 */
class MeetingHistoryController extends RotationBaseController
{
    public function __construct(
        Request $request,
        protected MeetingHistoryService $meetingHistoryService,
    ) {
        parent::__construct($request);
    }

    /**
     * 기본 조회 화면. (번호 1개로 함께 참여한 상대 조회)
     */
    public function historyIndex(): View
    {
        return view('admin.rotation.history.search');
    }

    /**
     * 기본 조회 — 그 번호가 함께 참여한 상대 목록(ajax).
     */
    public function historySearch(HistorySearchRequest $request): JsonResponse
    {
        return response()->json(
            $this->meetingHistoryService->meetingPartnersByPhone((string) $request->validated('phone'))
        );
    }

    /**
     * 상세 조회 화면. (번호 2개, 쿼리스트링 phoneA/phoneB 가 있으면 초기값으로 채운다)
     */
    public function historyDetailIndex(): View
    {
        return view('admin.rotation.history.detail', [
            'initialPhoneA' => (string) $this->request->query('phoneA', ''),
            'initialPhoneB' => (string) $this->request->query('phoneB', ''),
        ]);
    }

    /**
     * 상세 조회 — 두 번호의 만남 이력 상세(ajax).
     */
    public function historyDetailData(HistoryDetailRequest $request): JsonResponse
    {
        return response()->json(
            $this->meetingHistoryService->meetingHistoryBetween(
                (string) $request->validated('phoneA'),
                (string) $request->validated('phoneB'),
            )
        );
    }
}
