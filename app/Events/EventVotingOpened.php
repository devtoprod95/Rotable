<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 행사의 매칭(투표)이 시작(V)되었음을 참가자에게 실시간으로 알린다.
 *
 * 관리자가 "매칭 시작"을 눌러 상태가 V로 전환되는 순간 발행된다.
 * 참가자 화면은 수신 즉시 리로드하여, '매칭하기' 탭이 바로 열리도록 한다.
 *
 * 와인파티·1:1 공통으로 동일 채널(event-rotation.{eventId})을 사용한다.
 */
class EventVotingOpened implements ShouldBroadcastNow
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
        return 'event.voting.opened';
    }

    /**
     * 신호만 전달한다. (실제 화면 상태는 수신 측에서 재요청)
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
