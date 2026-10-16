<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RotationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\K6Controller;

Route::middleware('role')->group(function () {
    Route::middleware('admin:login')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::delete('/dashboard/log', [DashboardController::class, 'deleteLog'])->name('dashboard.log.delete');
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

        // 회원 → 관리자 요청하기 (플로팅 위젯)
        Route::prefix('requests')->name('requests.')->group(function () {
            Route::get('/', [\App\Http\Controllers\AdminRequestController::class, 'index'])->name('index');
            Route::get('/list', [\App\Http\Controllers\AdminRequestController::class, 'listData'])->name('list');
            Route::patch('/{id}/read', [\App\Http\Controllers\AdminRequestController::class, 'markRead'])->whereNumber('id')->name('read');
            Route::patch('/read-all', [\App\Http\Controllers\AdminRequestController::class, 'markAllRead'])->name('readAll');
        });

        Route::prefix("erd")->name('erd.')->group(base_path("routes/erd.php"));

        // 로테이션 소개팅 관리 (행사 관리 / 테이블 관리 / 좌석 배정)
        Route::prefix('rotation')->name('rotation.')->group(function () {
            Route::prefix('event')->name('event.')->group(function () {
                Route::get('/', [RotationController::class, 'eventIndex'])->name('index');
                Route::get('/list', [RotationController::class, 'eventList'])->name('list');
                Route::get('/create', [RotationController::class, 'eventCreate'])->name('create');
                Route::post('/store', [RotationController::class, 'eventStore'])->name('store');
                Route::delete('/delete', [RotationController::class, 'eventDelete'])->name('delete');
                Route::get('/{id}', [RotationController::class, 'eventDetail'])->whereNumber('id')->name('detail');
                Route::put('/{id}', [RotationController::class, 'eventUpdate'])->whereNumber('id')->name('update');
                Route::get('/{id}/tables', [RotationController::class, 'eventTableList'])->whereNumber('id')->name('tables');
                Route::post('/{id}/tables', [RotationController::class, 'eventTableAdd'])->whereNumber('id')->name('tables.add');
                Route::delete('/{id}/tables', [RotationController::class, 'eventTableDelete'])->whereNumber('id')->name('tables.delete');
                Route::get('/{id}/messages/list', [RotationController::class, 'eventMessageList'])->whereNumber('id')->name('messages.list');
                Route::get('/{id}/matches/list', [RotationController::class, 'eventMatchList'])->whereNumber('id')->name('matches.list');
                Route::get('/{id}/members/{memberId}/voters/list', [RotationController::class, 'eventMemberVoterList'])->whereNumber('id')->whereNumber('memberId')->name('members.voters.list');
                Route::get('/{id}/members/{memberId}/voted/list', [RotationController::class, 'eventMemberVotedList'])->whereNumber('id')->whereNumber('memberId')->name('members.voted.list');
            });

            Route::prefix('seating')->name('seating.')->group(function () {
                Route::get('/', [RotationController::class, 'seatingIndex'])->name('index');
                Route::get('/data', [RotationController::class, 'seatingData'])->name('data');
                Route::get('/board', [RotationController::class, 'seatingBoard'])->name('board');
                Route::get('/members', [RotationController::class, 'seatingMembers'])->name('members');
                Route::post('/store', [RotationController::class, 'seatingStore'])->name('store');
                Route::post('/rotate', [RotationController::class, 'seatingRotate'])->name('rotate');
                Route::post('/finish', [RotationController::class, 'seatingFinish'])->name('finish');
                Route::post('/close-voting', [RotationController::class, 'seatingCloseVoting'])->name('closeVoting');
                Route::delete('/delete', [RotationController::class, 'seatingDelete'])->name('delete');
            });

            // 상세페이지 디자이너 — HTML 에디터로 꾸미고 이미지(PNG)로 다운로드
            Route::prefix('detail')->name('detail.')->group(function () {
                Route::get('/', [RotationController::class, 'detailIndex'])->name('index');
                Route::post('/save', [RotationController::class, 'detailSave'])->name('save');
            });

            // QR 코드 설정 — 연결 대상 URL 을 등록하면 공개 주소(/qr/{code})가 발급된다.
            Route::prefix('qr')->name('qr.')->group(function () {
                Route::get('/', [RotationController::class, 'qrIndex'])->name('index');
                Route::post('/store', [RotationController::class, 'qrStore'])->name('store');
                Route::delete('/delete', [RotationController::class, 'qrDelete'])->name('delete');
            });

            // 만남 이력 조회 — 전화번호(정확 일치) 기준. 만남 = 같은 행사 공동 참여.
            Route::prefix('history')->name('history.')->group(function () {
                // 만남 이력 기본 조회 (번호 1개 → 함께 참여한 상대)
                Route::get('/', [RotationController::class, 'historyIndex'])->name('index');
                Route::get('/search', [RotationController::class, 'historySearch'])->name('search');
                // 만남 이력 상세 조회 (번호 2개 → 두 사람의 만남 이력)
                Route::get('/detail', [RotationController::class, 'historyDetailIndex'])->name('detail');
                Route::get('/detail/data', [RotationController::class, 'historyDetailData'])->name('detail.data');
            });
        });

        Route::prefix('role')->name('role.')->group(function () {
            Route::prefix('route')->name('route.')->group(function () {
                Route::get('/', [AdminController::class, 'roleRouteIndex'])->name('index');
                Route::get('/list', [AdminController::class, 'roleRouteList'])->name('list');
                Route::get('/{id?}', [AdminController::class, 'roleRouteInfo'])->name('info');
                Route::post('/store', [AdminController::class, 'roleRouteStore'])->name('store');
                Route::delete('/delete', [AdminController::class, 'roleRouteDelete'])->name('delete');
                Route::patch('/update', [AdminController::class, 'roleRouteUpdate'])->name('update');
            });

            Route::get('/', [AdminController::class, 'roleIndex'])->name('index');
            Route::get('/list', [AdminController::class, 'roleList'])->name('list');
            Route::get('/{id?}', [AdminController::class, 'roleInfo'])->name('info');
            Route::post('/store', [AdminController::class, 'roleStore'])->name('store');
            Route::delete('/delete', [AdminController::class, 'roleDelete'])->name('delete');
        });

        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/list', [AdminController::class, 'list'])->name('list');
        Route::get('/view/{id?}', [AdminController::class, 'view'])->name('view');
        Route::post('/store', [AdminController::class, 'store'])->name('store');
        Route::delete('/delete', [AdminController::class, 'delete'])->name('delete');
        Route::patch('/active', [AdminController::class, 'updateActive'])->name('updateActive');
        Route::post('/impersonate/{admin}', [AdminController::class, 'impersonate'])->name('impersonate');
        Route::post('/impersonate-leave', [AdminController::class, 'leaveImpersonation'])->name('impersonate.leave');

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/icons', function () {
                return view('icons');
            })->name('icons');
            Route::get('/general', function () {
                return view('setting-general');
            })->name('general');
            Route::prefix('k6')->name('k6.')->group(function () {
                Route::get('/', [K6Controller::class, 'index'])->name('index');
                Route::post('/run', [K6Controller::class, 'run'])->name('run');
                Route::post('/install', [K6Controller::class, 'installK6'])->name('install');
            });
            Route::get('/security', function () {
                return abort(404);
            })->name('security');

            Route::prefix('notifications')->name('notifications.')->group(function () {
                Route::get('/email', function () {
                    return abort(404);
                })->name('email');
                Route::get('/sms', function () {
                    return abort(404);
                })->name('sms');
            });
        });

    });

    Route::middleware('admin:guest')->group(function () {
        Route::get('/login', [LoginController::class, 'show'])->name('show');
        Route::post('/login', [LoginController::class, 'login'])->name('login');

        Route::get('/auto-login', [LoginController::class, 'autoLogin'])->name('auto-login');
    });
});
