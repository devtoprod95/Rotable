<?php

namespace App\Events;

use App\Models\AdminRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 회원이 관리자에게 요청을 보냈을 때, 관리자 플로팅 위젯으로 실시간 브로드캐스트한다.
 *
 * 모든 관리자 페이지에서 공통으로 구독하는 공개 채널(admin-requests)로 전송한다.
 * (관리자 전용 화면에서만 구독하며, 관리자는 요청자 정보를 열람할 수 있다.)
 */
class AdminRequestSent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public AdminRequest $adminRequest)
    {
    }

    /**
     * 전역 관리자 요청 채널.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('admin-requests'),
        ];
    }

    /**
     * 프론트에서 수신할 이벤트명.
     */
    public function broadcastAs(): string
    {
        return 'request.sent';
    }

    /**
     * 관리자 위젯 렌더에 필요한 정보.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->adminRequest->loadMissing(['member:id,name,gender,event_id', 'eventRound:id,title']);

        return [
            'id'         => $this->adminRequest->id,
            'body'       => $this->adminRequest->body,
            'read_at'    => $this->adminRequest->read_at?->toIso8601String(),
            'created_at' => $this->adminRequest->created_at?->toIso8601String(),
            'event_id'   => $this->adminRequest->event_id,
            'event_title' => $this->adminRequest->eventRound?->title,
            'table_no'   => $this->adminRequest->table_no,
            'member'     => $this->adminRequest->member ? [
                'id'     => $this->adminRequest->member->id,
                'name'   => $this->adminRequest->member->name,
                'gender' => $this->adminRequest->member->gender,
            ] : null,
        ];
    }
}
