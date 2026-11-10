<?php

namespace App\Events;

use App\Models\AdminRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 관리자가 회원 요청에 답장했을 때, 요청한 회원의 개인 채널로 실시간 전파한다.
 * 답장한 관리자 정보는 회원에게 노출하지 않는다.
 */
class AdminRequestReplied implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public AdminRequest $adminRequest)
    {
    }

    /**
     * 회원 개인 채널로 전송 (chat.{memberId}).
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->adminRequest->member_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'request.replied';
    }

    /**
     * @return array{id:int, reply_body:?string, replied_at:?string, reply_edited_at:?string}
     */
    public function broadcastWith(): array
    {
        return [
            'id'         => $this->adminRequest->id,
            'reply_body' => $this->adminRequest->reply_body,
            'replied_at' => $this->adminRequest->replied_at?->format('Y/n/j H:i'),
            'reply_edited_at' => $this->adminRequest->reply_edited_at?->format('Y/n/j H:i'),
        ];
    }
}
