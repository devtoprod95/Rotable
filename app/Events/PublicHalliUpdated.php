<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 공개 할리갈리 - 게임 상태가 바뀔 때마다 접속자 전원에게 공개 상태(덱 내용 제외)를 알린다.
 */
class PublicHalliUpdated implements ShouldBroadcastNow
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
        return [new Channel('public-halli')];
    }

    public function broadcastAs(): string
    {
        return 'public-halli.updated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return ['state' => $this->state];
    }
}
