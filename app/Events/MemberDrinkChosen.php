<?php

namespace App\Events;

use App\Models\Member;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 참가자가 음료를 선택/변경/취소했을 때 관리자 "로테이션 진행" 화면에 알리는 실시간 브로드캐스트. (1:1 소개팅)
 *
 * 관리자 전용 화면에서만 구독하는 행사 단위 채널(event-drinks.{eventId})로 전송한다.
 * (EventMessageSent 와 같은 방식. 화면은 신호를 받으면 좌석 보드를 다시 불러온다.)
 */
class MemberDrinkChosen implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Member $member)
    {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('event-drinks.' . $this->member->event_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'drink.chosen';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->member->loadMissing('eventDrink:id,name');

        return [
            'member_id'  => $this->member->id,
            'drink_id'   => $this->member->event_drink_id,
            'drink_name' => $this->member->eventDrink?->name,
        ];
    }
}
