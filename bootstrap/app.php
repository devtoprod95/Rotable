<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\TrackAdminActivityMiddleware;
use App\Http\Middleware\TrackHttpStatusCountsMiddleware;
use App\Http\Middleware\TrackVisitorMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role'       => RoleMiddleware::class,
            'admin'      => AdminMiddleware::class,
            'superAdmin' => SuperAdminMiddleware::class,
        ]);
        $middleware->append(TrackVisitorMiddleware::class);
        $middleware->append(TrackHttpStatusCountsMiddleware::class);
        $middleware->appendToGroup('web', TrackAdminActivityMiddleware::class);
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // 미인증 사용자 리다이렉트 대상.
        // admin 영역은 관리자 로그인, 그 외(member 영역)는 입장 화면으로 보낸다.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.show');
            }

            return route('member.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
