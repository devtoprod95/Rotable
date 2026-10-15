<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 로테이션 좌석/턴이 갱신되었음을 참가자에게 실시간으로 알린다.
 *
 * 관리자가 "로테이션 진행"을 눌러 다음 턴 좌석이 생성되면 발행된다.
 * 페이로드는 신호(eventId)만 담고, 참가자 화면은 수신 즉시 서버에서
 * 최신 좌석을 다시 받아온다(router.reload). → 좌석 가공 로직 중복 없음.
 *
 * 와인파티·1:1 공통으로 동일 채널(event-rotation.{eventId})을 사용한다.
 */
class RotationUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public int $eventId)
    {
    }

    /**
     * 행사 단위 private 채널로 전송. (같은 행사 참가자만 구독)
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('event-rotation.' . $this->eventId),
        ];
    }

    /**
     * 프론트에서 수신할 이벤트명.
     */
    public function broadcastAs(): string
    {
        return 'rotation.updated';
    }

    /**
     * 신호만 전달한다. (실제 데이터는 수신 측에서 재요청)
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
        ];
    }
}
