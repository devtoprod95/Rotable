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
