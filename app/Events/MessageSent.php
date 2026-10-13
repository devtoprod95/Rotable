<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 회원이 메시지를 보냈을 때, 수신자의 개인 채널로 실시간 브로드캐스트한다.
 */
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Message $message)
    {
    }

    /**
     * 수신자 개인 채널로만 전송 (chat.{receiverId}).
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->receiver_id),
        ];
    }

    /**
     * 프론트에서 수신할 이벤트명.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * 브로드캐스트 페이로드. 발신자 정보는 익명 처리하여 노출하지 않는다.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id'      => $this->message->id,
            'body'    => $this->message->body,
            'is_read' => $this->message->read_at !== null,
            'sent_at' => $this->message->created_at?->format('Y/n/j H:i'),
        ];
    }
}
