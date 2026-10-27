<?php

use App\Models\Member;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// 회원 개인 채팅 채널. 본인(web 가드로 인증된 회원)만 구독할 수 있다.
Broadcast::channel('chat.{memberId}', function (Member $member, int $memberId) {
    return (int) $member->id === $memberId;
}, ['guards' => ['web']]);

// 행사 단위 로테이션/종료 실시간 채널. 그 행사에 속한 회원만 구독할 수 있다.
// (로테이션 좌석에는 상대 프로필이 포함되므로 공개가 아닌 private 으로 보호한다.)
Broadcast::channel('event-rotation.{eventId}', function (Member $member, int $eventId) {
    return (int) $member->event_id === $eventId;
}, ['guards' => ['web']]);

// 테이블 단위 익명 Yes/No 투표 게임 채널.
// 같은 행사 && "지금 그 테이블에 앉아 있는" 회원만 구독할 수 있다.
// (투표 신호만 오가며 서버에 저장하지 않는 휘발성 데이터)
Broadcast::channel('table-poll.{eventId}.{tableNo}', function (Member $member, int $eventId, int $tableNo) {
    return (int) $member->event_id === $eventId
        && $member->currentTableNo() === $tableNo;
}, ['guards' => ['web']]);
