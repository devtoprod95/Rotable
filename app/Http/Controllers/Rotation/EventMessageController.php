<?php

namespace App\Http\Controllers\Rotation;

use App\Http\Requests\Admin\Rotation\EventMessageSendRequest;
use App\Services\Rotation\RotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 행사 메시지 — 누가 누구에게 보냈는지 조회 + 관리자 메시지 발송.
 */
class EventMessageController extends RotationBaseController
{
    public function __construct(
        Request $request,
        protected RotationService $rotationService,
    ) {
        parent::__construct($request);
    }

    /**
     * 행사 메시지 목록 (Tabulator ajax).
     */
    public function eventMessageList(int $id): JsonResponse
    {
        return response()->json($this->rotationService->eventMessageList(
            $id,
            (int) $this->request->input('page', 1),
            (int) $this->request->input('size', 30),
            $this->request->input('sort.0', ['field' => 'id', 'dir' => 'desc']),
            [
                'searchText' => $this->request->input('searchText', ''),
                'read'       => $this->request->input('read', ''),
                'senderId'   => (int) $this->request->input('senderId', 0),
                'convA'      => (int) $this->request->input('convA', 0),
                'convB'      => (int) $this->request->input('convB', 0),
            ]
        ));
    }

    /**
     * 관리자 메시지 발송 대상(참가자) 목록.
     */
    public function eventMessageRecipients(int $id): JsonResponse
    {
        return response()->json(['data' => $this->rotationService->messageRecipientList($id)]);
    }

    /**
     * 관리자가 참가자에게 메시지 발송. (참가자에게는 익명으로 표시)
     */
    public function eventMessageSend(EventMessageSendRequest $request, int $id): JsonResponse
    {
        $result = $this->rotationService->adminSendMessage(
            $id,
            (int) auth('admin')->id(),
            (string) $request->validated('body'),
            (string) $request->validated('target'),
            array_map('intval', $request->validated('member_ids') ?? []),
        );

        return $this->respond($result, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
