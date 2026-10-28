<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 공개 Yes/No 게임 - 한 표가 던져졌음을 "지금 이 페이지에 접속한 모두"에게 알린다.
 *
 * 로그인/테이블 개념이 없는 공개(비인증) 버전이라 public Channel 을 쓴다.
 * 익명 휘발성 신호이며 누가 투표했는지는 담지 않는다.
 */
class PublicPollVoted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $questionIndex,
        public string $answer,
    ) {
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
        return 'public-poll.voted';
    }

    /**
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
