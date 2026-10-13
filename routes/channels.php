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
