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

        // 로테이션 소개팅 관리 (행사 목록 / 당일 운영[체크인·로테이션 진행] / 홍보 / 안내 / 분석·도구)
        Route::prefix('rotation')->name('rotation.')->group(function () {
            // 사이드바 "체크인" 진입점 — 지금 운영할 행사의 체크인 화면으로 이동한다.
            Route::get('/checkin', [RotationController::class, 'checkinCurrent'])->name('checkin.current');

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
                // 체크인 명단 — 행사별 (닉네임·플랫폼·성별, 전화번호 없음)
                Route::get('/{id}/checkin', [RotationController::class, 'checkinIndex'])->whereNumber('id')->name('checkin');
                Route::get('/{id}/rosters', [RotationController::class, 'rosterList'])->whereNumber('id')->name('rosters.list');
                Route::post('/{id}/rosters', [RotationController::class, 'rosterAdd'])->whereNumber('id')->name('rosters.add');
                Route::put('/{id}/rosters/{rosterId}', [RotationController::class, 'rosterUpdate'])->whereNumber('id')->whereNumber('rosterId')->name('rosters.update');
                Route::delete('/{id}/rosters', [RotationController::class, 'rosterDelete'])->whereNumber('id')->name('rosters.delete');
                Route::patch('/{id}/rosters/{rosterId}/check-in', [RotationController::class, 'rosterToggleCheckIn'])->whereNumber('id')->whereNumber('rosterId')->name('rosters.checkIn');
                Route::patch('/{id}/rosters/{rosterId}/ticket', [RotationController::class, 'rosterSetTicket'])->whereNumber('id')->whereNumber('rosterId')->name('rosters.ticket');
                // [개발용] 체크인된 사람 기준 입장등록 더미 생성 (최고관리자 전용)
                Route::post('/{id}/rosters/dev-seed', [RotationController::class, 'rosterDevSeed'])->whereNumber('id')->middleware('superAdmin')->name('rosters.devSeed');
                // [운영/마이그레이션용] 기존 좌석(rotation_seats) 테이블을 체크인 명단(event_rosters)에 역동기화 (최고관리자 전용)
                Route::post('/{id}/rosters/sync-tables', [RotationController::class, 'rosterSyncTables'])->whereNumber('id')->middleware('superAdmin')->name('rosters.syncTables');
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
                Route::post('/rotate-back', [RotationController::class, 'seatingRotateBack'])->name('rotateBack');
                Route::post('/finish', [RotationController::class, 'seatingFinish'])->name('finish');
                Route::post('/close-voting', [RotationController::class, 'seatingCloseVoting'])->name('closeVoting');
                // 매칭 시작 취소 — 매칭중(V)→진행(S), 투표 데이터 삭제 (와인파티)
                Route::post('/cancel-voting', [RotationController::class, 'seatingCancelVoting'])->name('cancelVoting');
                // 종료(X) 되돌리기 — 실수 종료 복구 (와인파티→V, 1:1→S)
                Route::post('/reopen', [RotationController::class, 'seatingReopen'])->name('reopen');
                Route::delete('/delete', [RotationController::class, 'seatingDelete'])->name('delete');
            });

            // 상세페이지 디자이너 — HTML 에디터로 꾸미고 이미지(PNG)로 다운로드
            Route::prefix('detail')->name('detail.')->group(function () {
                Route::get('/', [RotationController::class, 'detailIndex'])->name('index');
                // 저장 목록(유형별 다중 스냅샷) — 목록/추가/불러오기/업데이트/삭제
                Route::get('/saves', [RotationController::class, 'detailSaveList'])->name('saves.list');
                Route::post('/saves', [RotationController::class, 'detailSaveStore'])->name('saves.store');
                Route::get('/saves/{id}', [RotationController::class, 'detailSaveShow'])
                    ->whereNumber('id')->name('saves.show');
                Route::post('/saves/{id}', [RotationController::class, 'detailSaveUpdate'])
                    ->whereNumber('id')->name('saves.update');
                Route::delete('/saves/{id}', [RotationController::class, 'detailSaveDelete'])
                    ->whereNumber('id')->name('saves.delete');
            });

            // 초대 멘트 — 유형(와인/1:1)·성별·무료 여부에 따라 초대 메시지 문구를 복사한다.
            Route::get('/invite', [RotationController::class, 'inviteIndex'])->name('invite');

            // QR 코드 설정 — 연결 대상 URL 을 등록하면 공개 주소(/qr/{code})가 발급된다.
            Route::prefix('qr')->name('qr.')->group(function () {
                Route::get('/', [RotationController::class, 'qrIndex'])->name('index');
                Route::post('/store', [RotationController::class, 'qrStore'])->name('store');
                Route::delete('/delete', [RotationController::class, 'qrDelete'])->name('delete');
            });

            // 참가자 안내 슬라이드 — 행사장에서 PPT 처럼 띄워 서비스 이용안내/룰/대화 주제를 안내한다.
            Route::prefix('guide')->name('guide.')->group(function () {
                Route::get('/{type?}', [RotationController::class, 'guideIndex'])
                    ->where('type', 'wine|solo')
                    ->name('index');
                // 채팅방 공지 문구 — 행사 시간/타입에 맞춰 자동 생성, 복사해서 채팅방에 붙여넣기
                Route::get('/notice', [RotationController::class, 'guideNotice'])->name('notice');
                // 호스트 발표 멘트 편집 — 유형별 대본을 섹션 단위로 편집/저장(DB)
                Route::get('/script', [RotationController::class, 'scriptIndex'])->name('script');
                Route::post('/script', [RotationController::class, 'scriptSave'])->name('script.save');
                // 예외 상황 대처 설명서 (별도 페이지)
                Route::get('/exception', [RotationController::class, 'guideException'])->name('exception');
            });

            // 공개 페이지 모음 — 공개 게임/안내 슬라이드/호스트 멘트 등 외부 공유용 링크를 한 곳에 모아 보여준다.
            Route::get('/public-hub', [RotationController::class, 'publicHub'])->name('public-hub');

            // 만남 이력 조회 — 전화번호(정확 일치) 기준. 만남 = 같은 행사 공동 참여.
            Route::prefix('history')->name('history.')->group(function () {
                // 만남 이력 기본 조회 (번호 1개 → 함께 참여한 상대)
                Route::get('/', [RotationController::class, 'historyIndex'])->name('index');
                Route::get('/search', [RotationController::class, 'historySearch'])->name('search');
                // 만남 이력 상세 조회 (번호 2개 → 두 사람의 만남 이력)
                Route::get('/detail', [RotationController::class, 'historyDetailIndex'])->name('detail');
                Route::get('/detail/data', [RotationController::class, 'historyDetailData'])->name('detail.data');
            });

            // 손익분기점 계산기 — 객단가/비용 항목(기타 추가금 포함)으로 1인당 흑자전환 참가비를 계산한다. 입력값은 저장/불러오기 가능.
            Route::prefix('bep')->name('bep.')->group(function () {
                Route::get('/', [RotationController::class, 'bepIndex'])->name('index');
                Route::get('/list', [RotationController::class, 'bepList'])->name('list');
                Route::post('/store', [RotationController::class, 'bepStore'])->name('store');
                Route::delete('/delete', [RotationController::class, 'bepDelete'])->name('delete');
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

            // 텔레그램 메시지 전달 on/off (최고관리자 전용) — 헤더 토글에서 호출
            Route::prefix('telegram')->name('telegram.')->middleware('superAdmin')->group(function () {
                Route::post('/toggle', [AdminController::class, 'toggleTelegram'])->name('toggle');
            });
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
