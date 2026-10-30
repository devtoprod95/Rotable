<?php

use App\Http\Controllers\Auth\MemberAuthController;
use App\Http\Controllers\MemberPageController;
use App\Http\Middleware\RedirectIfRegistered;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 유저(회원) 영역 - member 가드(web) 기반, 관리자 영역과 분리
|--------------------------------------------------------------------------
*/

// 소개팅 랜딩 (게스트 포함 누구나)
Route::get('/', [MemberPageController::class, 'landing'])->name('landing');

// QR 코드 공개 리다이렉트 — 관리자가 설정한 대상 URL 로 이동한다. (게스트 포함 누구나)
Route::get('/qr/{code}', [\App\Http\Controllers\QrRedirectController::class, 'redirect'])
    ->where('code', '[A-Za-z0-9]+')
    ->name('qr.redirect');

// 참가자 안내 슬라이드 공개 페이지 (비인증) — /public/guide 또는 /public/guide/{type}(wine|solo)
// 공개 미니게임 — 로그인/테이블 없이 "지금 이 페이지에 접속한 사람들"끼리 즐기는 버전.
Route::prefix('public')->name('public.')->group(function () {
    Route::get('/guide/{type?}', [\App\Http\Controllers\RotationController::class, 'guidePublic'])
        ->where('type', 'wine|solo')
        ->name('guide');

    // 호스트 발표 멘트(대본) — 안내 슬라이드를 보며 읽는 진행 멘트. 핸드폰으로 보기 좋은 단일 페이지.
    Route::get('/script/{type?}', [\App\Http\Controllers\RotationController::class, 'scriptPublic'])
        ->where('type', 'wine|solo')
        ->name('script');

    Route::get('/games', [\App\Http\Controllers\PublicGameController::class, 'index'])->name('games');
    Route::get('/games/{slug}', [\App\Http\Controllers\PublicGameController::class, 'show'])
        ->where('slug', '[a-z0-9-]+')
        ->name('games.show');

    // 공개 Yes/No — 저장 없는 휘발성 집계(Redis) + public 채널 브로드캐스트.
    Route::post('/games/poll/vote', [\App\Http\Controllers\PublicGameController::class, 'pollVote'])
        ->middleware('throttle:120,1')->name('games.poll.vote');
    Route::post('/games/poll/next', [\App\Http\Controllers\PublicGameController::class, 'pollNext'])
        ->middleware('throttle:120,1')->name('games.poll.next');
    // 접속자 수 하트비트(presence 대체) — 클라가 주기적으로 핑.
    Route::post('/games/poll/heartbeat', [\App\Http\Controllers\PublicGameController::class, 'pollHeartbeat'])
        ->middleware('throttle:120,1')->name('games.poll.heartbeat');
});

// (구) /guide → /public/guide 로 영구 이동. 기존 QR/링크 보존용 리다이렉트.
Route::permanentRedirect('/guide', '/public/guide');
Route::get('/guide/{type}', function (string $type) {
    return redirect()->route('public.guide', ['type' => $type], 301);
})->where('type', 'wine|solo');

// 입장/입장등록 화면.
// 이미 등록(로그인)된 회원은 메인(홈)으로 보낸다. (단, 예외 이름 회원은 접근 허용)
Route::middleware(RedirectIfRegistered::class)->group(function () {
    Route::get('/register', [MemberAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [MemberAuthController::class, 'register']);
    Route::get('/login', [MemberAuthController::class, 'showLogin'])->name('member.login');
    Route::post('/login', [MemberAuthController::class, 'login']);
});

// 로그인한 유저만
Route::middleware('auth:web')->group(function () {
    Route::get('/home', [MemberPageController::class, 'home'])->name('home');
    Route::get('/chats', [MemberPageController::class, 'chats'])->name('chats');
    Route::post('/chats/messages', [MemberPageController::class, 'sendMessage'])->name('chats.messages.send');
    Route::post('/chats/messages/read', [MemberPageController::class, 'markMessagesRead'])->name('chats.messages.read');
    Route::get('/votes', [MemberPageController::class, 'votes'])->name('votes');
    Route::post('/votes', [MemberPageController::class, 'saveVotes'])
        ->middleware('throttle:20,1')
        ->name('votes.save');
    Route::get('/requests', [MemberPageController::class, 'requests'])->name('requests');
    Route::get('/games', [MemberPageController::class, 'games'])->name('games');
    Route::get('/games/{slug}', [MemberPageController::class, 'gameShow'])
        ->where('slug', '[a-z0-9-]+')
        ->name('games.show');
    // 테이블 익명 Yes/No 투표 게임 - 저장 없는 실시간 신호(브로드캐스트)만 발생.
    Route::post('/games/table-poll/vote', [MemberPageController::class, 'tablePollVote'])
        ->middleware('throttle:60,1')
        ->name('games.table-poll.vote');
    Route::post('/games/table-poll/next', [MemberPageController::class, 'tablePollNext'])
        ->middleware('throttle:60,1')
        ->name('games.table-poll.next');
    Route::post('/requests', [MemberPageController::class, 'sendRequest'])
        ->middleware('throttle:5,1')
        ->name('requests.send');
    Route::post('/logout', [MemberAuthController::class, 'logout'])->name('member.logout');
});

Route::prefix("admin")->name('admin.')->group(base_path("routes/admin.php"));
