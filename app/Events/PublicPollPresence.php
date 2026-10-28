<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 공개 Yes/No 게임 - 현재 접속자 수(지금 이 페이지에 머무는 사람 수)를 알린다.
 * presence 채널 대신 서버의 하트비트 집계 결과를 브로드캐스트한다.
 */
class PublicPollPresence implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public int $count)
    {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('public-poll')];
    }

    public function broadcastAs(): string
    {
        return 'public-poll.presence';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'count' => $this->count,
        ];
    }
}
