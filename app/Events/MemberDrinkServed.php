<?php

namespace App\Events;

use App\Models\Member;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 관리자가 음료 "전달 완료"를 체크/해제했을 때, 같은 행사 상세를 보고 있는 다른 관리자 화면에 알린다. (1:1 소개팅)
 *
 * 음료 선택 신호(MemberDrinkChosen)와 같은 채널(event-drinks.{eventId})을 쓰고 이벤트명만 다르다.
 */
class MemberDrinkServed implements ShouldBroadcastNow
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
        return 'drink.served';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'member_id' => $this->member->id,
            'served'    => $this->member->drink_served_at !== null,
        ];
    }
}
