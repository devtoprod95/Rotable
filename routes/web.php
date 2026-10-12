<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MemberAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberPageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\K6Controller;

/*
|--------------------------------------------------------------------------
| 유저(회원) 영역 - member 가드(web) 기반, 관리자 영역과 분리
|--------------------------------------------------------------------------
*/

// 소개팅 랜딩 (게스트 포함 누구나)
Route::get('/', [MemberPageController::class, 'landing'])->name('landing');

// 비로그인 유저만 (이미 로그인 시 홈으로)
Route::middleware('guest:web')->group(function () {
    Route::get('/register', [MemberAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [MemberAuthController::class, 'register']);
    Route::get('/login', [MemberAuthController::class, 'showLogin'])->name('member.login');
    Route::post('/login', [MemberAuthController::class, 'login']);
});

// 로그인한 유저만
Route::middleware('auth:web')->group(function () {
    Route::get('/home', [MemberPageController::class, 'home'])->name('home');
    Route::get('/chats', [MemberPageController::class, 'chats'])->name('chats');
    Route::get('/votes', [MemberPageController::class, 'votes'])->name('votes');
    Route::get('/requests', [MemberPageController::class, 'requests'])->name('requests');
    Route::post('/logout', [MemberAuthController::class, 'logout'])->name('member.logout');
});

Route::prefix("admin")->name('admin.')->group(base_path("routes/admin.php"));
