<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\Auth\LoginController;
use App\Models\Admin;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Login using existing LoginController logic and generate a dynamic browser auto-login URL.')]
class LoginTool extends Tool
{
    /**
     * Handle the tool request by delegating to LoginController and generating an auto-login token.
     */
    public function handle(McpRequest $request): Response
    {
        $username = $request->get('username');
        $password = $request->get('password');

        // 1. MCP 인자를 라라벨 HttpRequest 객체로 변환
        $httpRequest = HttpRequest::create('/admin/login', 'POST', [
            'username' => $username,
            'password' => $password,
        ]);
        $httpRequest->setLaravelSession(app('session.store'));

        // 2. 기존 LoginController 인스턴스를 생성하고 login() 메서드를 직접 실행
        $controller = app()->makeWith(LoginController::class, ['request' => $httpRequest]);
        $response   = $controller->login($httpRequest);

        // 3. 기존 컨트롤러의 처리 결과 검증
        if (session()->has('errors')) {
            $errors = session('errors')->all();
            return Response::text('로그인 실패: ' . implode(', ', $errors));
        }

        // 4. 웹 브라우저 자동 로그인용 일회성 토큰 및 동적 APP_URL 생성
        $admin = Admin::where('username', $username)->first();
        if ($admin) {
            $token = Str::random(40);
            Cache::put('auto_login_token_' . $token, $admin->id, now()->addMinutes(15));

            $baseUrl      = rtrim(config('app.url'), '/');
            $autoLoginUrl = "{$baseUrl}/auto-login?token={$token}";

            return Response::text("로그인 성공! 아래 웹 브라우저 자동 로그인 링크를 접속하세요 (15분간 유효):\n" . $autoLoginUrl);
        }

        return Response::text('로그인 성공!');
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'username' => $schema->string()->description('Admin username')->required(),
            'password' => $schema->string()->description('Admin password')->required(),
        ];
    }
}
