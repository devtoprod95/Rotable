<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 테이블 할리갈리 - 게임 상태가 바뀔 때마다 같은 테이블에 공개 상태(덱 내용 제외)를 알린다.
 */
class TableHalliUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param int                  $eventId 행사 id
     * @param int                  $tableNo 테이블 번호
     * @param int                  $round   로테이션 턴 (턴이 다른 상태는 클라이언트가 무시)
     * @param array<string, mixed> $state   공개 상태
     */
    public function __construct(
        public int $eventId,
        public int $tableNo,
        public int $round,
        public array $state,
    ) {
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('table-halli.' . $this->eventId . '.' . $this->tableNo),
        ];
    }

    public function broadcastAs(): string
    {
        return 'table-halli.updated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'round' => $this->round,
            'state' => $this->state,
        ];
    }
}
