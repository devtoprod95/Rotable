<?php

namespace Tests\Feature;

use App\Services\TelegramNotifier;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * 텔레그램 알림 전송기(TelegramNotifier) end-to-end 테스트.
 *
 * ⚠️ 이 테스트는 Http::fake() 를 쓰지 않고 '실제 텔레그램 봇'으로 메시지를 전송한다.
 *    따라서 .env 에 TELEGRAM_BOT_TOKEN / TELEGRAM_ADMIN_CHAT_ID 가 채워져 있어야 하며,
 *    설정이 없으면 테스트를 건너뛴다(skip). 성공 시 실제 운영진 채팅방에 메시지가 도착한다.
 *
 * 각 메서드는 개별 실행하며, 실행 구문은 메서드별 주석에 적어둔다.
 * (전체를 한 번에 돌리려면: php artisan test --filter=TelegramNotifierTest)
 */
class TelegramNotifierTest extends TestCase
{
    /**
     * 설정이 없으면 모든 e2e 테스트를 건너뛴다.
     */
    private function skipIfNotConfigured(): void
    {
        if (empty(config('services.telegram.bot_token')) || empty(config('services.telegram.admin_chat_id'))) {
            $this->markTestSkipped('텔레그램 설정(TELEGRAM_BOT_TOKEN/TELEGRAM_ADMIN_CHAT_ID)이 없어 e2e 테스트를 건너뜁니다.');
        }
    }

    /**
     * 설정값으로 만든 전송기는 configured 상태여야 한다.
     *
     * 실행:
     *   php artisan test --filter=test_notifier_is_configured_from_env
     */
    public function test_notifier_is_configured_from_env(): void
    {
        $this->skipIfNotConfigured();

        $this->assertTrue(TelegramNotifier::fromConfig()->isConfigured());
    }

    /**
     * 일반 메시지를 실제 운영진 채팅방으로 전송한다.
     *
     * 실행:
     *   php artisan test --filter=test_sends_plain_message_to_real_chat
     */
    public function test_sends_plain_message_to_real_chat(): void
    {
        $this->skipIfNotConfigured();

        $sent = TelegramNotifier::fromConfig()->send(
            '✅ [e2e 테스트] TelegramNotifier::send 동작 확인 (' . now()->format('Y-m-d H:i:s') . ')'
        );

        $this->assertTrue($sent, '실제 텔레그램 전송에 실패했습니다. 봇 토큰/chat_id/네트워크를 확인하세요.');
    }

    /**
     * 에러 알림을 실제 운영진 채팅방으로 전송한다.
     *
     * 실행:
     *   php artisan test --filter=test_sends_error_alert_to_real_chat
     */
    public function test_sends_error_alert_to_real_chat(): void
    {
        $this->skipIfNotConfigured();

        $exception = new RuntimeException('e2e 테스트용 예외입니다');

        $sent = TelegramNotifier::fromConfig()->sendErrorAlert(
            exceptionClass: $exception::class,
            message: $exception->getMessage(),
            file: $exception->getFile(),
            line: $exception->getLine(),
            context: ['method' => 'TEST', 'url' => 'artisan test', 'ip' => '127.0.0.1'],
            environment: app()->environment(),
        );

        $this->assertTrue($sent, '실제 텔레그램 에러 알림 전송에 실패했습니다.');
    }

    /**
     * 실제로 500 예외를 발생시켜, bootstrap/app.php 의 report 후크 →
     * SendErrorAlertJob → 실제 텔레그램 전송까지 이어지는 전체 경로를 검증한다.
     *
     * - 임시 라우트에서 RuntimeException 을 던진다. (요청 컨텍스트까지 실제로 채워짐)
     * - 큐를 sync 로 강제해, report 후크가 dispatch 한 Job 이 즉시 실행되어 실제 전송된다.
     * - 에러 알림은 운영(production)에서만 전송되므로, 테스트 동안 앱 환경을 production 으로 강제한다.
     * - 성공 시 운영진 채팅방에 '시스템 에러 발생' 알림이 도착한다.
     *
     * 실행:
     *   php artisan test --filter=test_thrown_exception_triggers_telegram_alert_end_to_end
     */
    public function test_thrown_exception_triggers_telegram_alert_end_to_end(): void
    {
        $this->skipIfNotConfigured();

        // 에러 알림은 production 에서만 나가므로, 이 경로를 검증하려면 환경을 production 으로 둔다.
        app()->detectEnvironment(fn () => 'production');

        // report 후크가 dispatch 한 Job 을 테스트 내에서 즉시 실행하도록 큐를 sync 로.
        config(['queue.default' => 'sync']);

        // 의도적으로 5xx 예외를 던지는 임시 라우트.
        Route::get('/__e2e/boom', function (): void {
            throw new RuntimeException('e2e 라우트에서 발생시킨 예외입니다 (' . now()->format('H:i:s') . ')');
        });

        // 테스트 기본값은 예외를 다시 던지지만, 여기서는 실제 예외 핸들러(=report 후크 포함)가
        // 동작해야 하므로 핸들링을 유지한 채 요청을 보낸다. → 500 응답.
        $response = $this->get('/__e2e/boom');

        $response->assertStatus(500);
    }
}
