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

// 참가자 안내 슬라이드 공개 페이지 — 비인증(누구나). /guide 또는 /guide/{type}(wine|solo)
Route::get('/guide/{type?}', [\App\Http\Controllers\RotationController::class, 'guidePublic'])
    ->where('type', 'wine|solo')
    ->name('public.guide');

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
    Route::post('/requests', [MemberPageController::class, 'sendRequest'])
        ->middleware('throttle:5,1')
        ->name('requests.send');
    Route::post('/logout', [MemberAuthController::class, 'logout'])->name('member.logout');
});

Route::prefix("admin")->name('admin.')->group(base_path("routes/admin.php"));
