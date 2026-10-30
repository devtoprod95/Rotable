<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\TrackAdminActivityMiddleware;
use App\Http\Middleware\TrackHttpStatusCountsMiddleware;
use App\Http\Middleware\TrackVisitorMiddleware;
use App\Jobs\SendErrorAlertJob;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
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

        // 시스템 예외/에러를 운영진 텔레그램으로 비동기 알림한다.
        // 운영(production) 환경에서만 전송한다. (local/개발/테스트 환경은 노이즈이므로 제외)
        // 404/419/인증/검증 등 '정상적인' HTTP 예외는 제외하고, 500 급 서버 오류만 보고한다.
        $exceptions->report(function (\Throwable $e): void {
            // 운영 환경이 아니면 알림을 보내지 않는다.
            if (! app()->isProduction()) {
                return;
            }

            // 인증/CSRF/검증 실패는 운영상 정상 범주 → 제외.
            if (
                $e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof TokenMismatchException
            ) {
                return;
            }

            // 4xx HTTP 예외(404/403 등)는 제외하고 5xx 서버 오류만 보고한다.
            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                return;
            }

            $request = request();
            $context = [];
            if ($request !== null) {
                $context = [
                    'method' => $request->method(),
                    'url'    => $request->fullUrl(),
                    'ip'     => $request->ip(),
                ];
            }

            SendErrorAlertJob::dispatch(
                exceptionClass: $e::class,
                message: $e->getMessage(),
                file: $e->getFile(),
                line: $e->getLine(),
                context: $context,
                environment: app()->environment(),
            );
        });
    })->create();
