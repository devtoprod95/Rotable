<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 공개 4목 - 판 상태가 바뀔 때마다 접속자 전원(두 참가자와 구경하는 사람)에게 알린다.
 * 숨길 정보가 없는 게임이라 공개 상태 전체를 그대로 보낸다.
 */
class PublicConnectFourUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param array<string, mixed> $state 공개 상태
     */
    public function __construct(public array $state)
    {
    }

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new Channel('public-connect-four')];
    }

    public function broadcastAs(): string
    {
        return 'public-connect-four.updated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return ['state' => $this->state];
    }
}
