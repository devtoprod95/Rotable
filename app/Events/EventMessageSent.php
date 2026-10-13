<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 관리자 "메시지 조회" 화면용 실시간 브로드캐스트.
 *
 * 회원용(MessageSent)과 달리 발신/수신자 정보를 모두 포함하여
 * 행사 단위 공개 채널(event-messages.{eventId})로 전송한다.
 * (관리자 전용 화면에서만 구독하며, 관리자는 모든 정보를 열람할 수 있다.)
 */
class EventMessageSent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Message $message)
    {
    }

    /**
     * 행사 단위 채널로 전송.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('event-messages.' . $this->message->event_id),
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
     * 관리자 화면 테이블 행 생성에 필요한 전체 정보.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->message->loadMissing(['sender:id,name,gender', 'receiver:id,name,gender']);

        return [
            'id'         => $this->message->id,
            'body'       => $this->message->body,
            'read_at'    => $this->message->read_at?->toIso8601String(),
            'created_at' => $this->message->created_at?->toIso8601String(),
            'sender'     => $this->message->sender ? [
                'name'   => $this->message->sender->name,
                'gender' => $this->message->sender->gender,
            ] : null,
            'receiver' => $this->message->receiver ? [
                'name'   => $this->message->receiver->name,
                'gender' => $this->message->receiver->gender,
            ] : null,
        ];
    }
}
