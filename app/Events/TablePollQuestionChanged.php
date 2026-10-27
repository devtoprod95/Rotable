<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 테이블 익명 Yes/No 투표 게임 - 질문이 다음으로 넘어갔음을 같은 테이블에 알린다.
 *
 * 테이블의 누군가 "다음 질문"을 누르면 발행되어, 같은 테이블 전원의 화면이
 * 동일한 질문으로 동기화되고 이전 질문의 YES/NO 카운트는 리셋된다. 저장하지 않는다.
 */
class TablePollQuestionChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param int $eventId       행사 id
     * @param int $tableNo       테이블 번호
     * @param int $questionIndex 모두가 맞출 질문 인덱스
     */
    public function __construct(
        public int $eventId,
        public int $tableNo,
        public int $questionIndex,
    ) {
    }

    /**
     * 같은 테이블 전용 private 채널로 전송.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('table-poll.' . $this->eventId . '.' . $this->tableNo),
        ];
    }

    public function broadcastAs(): string
    {
        return 'table-poll.question';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'question_index' => $this->questionIndex,
        ];
    }
}
