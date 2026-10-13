<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 수신자가 메시지를 읽었을 때, 발신자의 개인 채널로 "읽음" 상태를 실시간 전파한다.
 */
class MessageRead implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param int             $senderId    읽음 알림을 받을 발신자 회원 id
     * @param array<int, int> $messageIds  읽음 처리된 메시지 id 목록
     */
    public function __construct(public int $senderId, public array $messageIds)
    {
    }

    /**
     * 발신자 개인 채널로 전송 (chat.{senderId}).
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->senderId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    /**
     * @return array{message_ids: array<int, int>}
     */
    public function broadcastWith(): array
    {
        return [
            'message_ids' => $this->messageIds,
        ];
    }
}
