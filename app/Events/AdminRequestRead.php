<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 관리자가 회원의 요청을 확인(읽음) 처리했을 때,
 * 해당 회원의 개인 채널로 "확인됨" 상태를 실시간 전파한다.
 */
class AdminRequestRead implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param int             $memberId   요청을 보낸 회원 id (알림 대상)
     * @param array<int, int> $requestIds 확인 처리된 요청 id 목록
     */
    public function __construct(public int $memberId, public array $requestIds)
    {
    }

    /**
     * 회원 개인 채널로 전송 (chat.{memberId}).
     * 회원 메시지와 동일 채널을 재사용하되 이벤트명으로 구분한다.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->memberId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'request.read';
    }

    /**
     * @return array{request_ids: array<int, int>}
     */
    public function broadcastWith(): array
    {
        return [
            'request_ids' => $this->requestIds,
        ];
    }
}
