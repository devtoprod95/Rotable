<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\K6Controller;

Route::middleware('role')->group(function () {
    Route::middleware('admin:login')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::delete('/dashboard/log', [DashboardController::class, 'deleteLog'])->name('dashboard.log.delete');
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

        Route::prefix("erd")->name('erd.')->group(base_path("routes/erd.php"));

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
