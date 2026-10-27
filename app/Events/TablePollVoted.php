<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 테이블 익명 Yes/No 투표 게임 - "한 표"가 던져졌음을 같은 테이블에 알린다.
 *
 * 누가 투표했는지는 담지 않는(익명) 휘발성 신호다. 서버는 어떤 집계도 저장하지 않으며,
 * 각 클라이언트가 이 신호를 받아 자기 화면의 YES/NO 카운트만 올린다.
 * (질문이 바뀌면 카운트는 각 클라이언트에서 0으로 초기화된다)
 */
class TablePollVoted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param int    $eventId   행사 id
     * @param int    $tableNo   테이블 번호
     * @param int    $questionIndex 현재 질문 인덱스 (지난 질문의 늦은 표를 걸러내기 위함)
     * @param string $answer    'yes' | 'no'
     */
    public function __construct(
        public int $eventId,
        public int $tableNo,
        public int $questionIndex,
        public string $answer,
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
        return 'table-poll.voted';
    }

    /**
     * 익명 신호만 전달한다. (투표자 식별 정보 없음)
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'question_index' => $this->questionIndex,
            'answer'         => $this->answer,
        ];
    }
}
