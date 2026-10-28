<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 공개 Yes/No 게임 - 질문이 다음으로 넘어갔음을 접속자 모두에게 알린다.
 * 이전 질문의 YES/NO 집계는 리셋된다. 저장하지 않는다.
 */
class PublicPollQuestionChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public int $questionIndex)
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
        return 'public-poll.question';
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
