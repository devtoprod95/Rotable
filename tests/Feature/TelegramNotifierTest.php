<?php

namespace Tests\Feature;

use App\Services\TelegramNotifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 텔레그램 알림 전송기(TelegramNotifier) 동작 검증.
 *
 * 실제 네트워크 호출은 Http::fake() 로 가로채며, DB 에 의존하지 않는다.
 */
class TelegramNotifierTest extends TestCase
{
    /**
     * 봇 토큰과 chat id 가 모두 설정되어 있으면 sendMessage 엔드포인트로 전송한다.
     */
    public function test_sends_message_when_configured(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $notifier = new TelegramNotifier(botToken: 'TEST_TOKEN', defaultChatId: '12345');

        $this->assertTrue($notifier->isConfigured());
        $this->assertTrue($notifier->send('안녕하세요 리포트'));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/botTEST_TOKEN/sendMessage')
                && $request['chat_id'] === '12345'
                && $request['text'] === '안녕하세요 리포트'
                && $request['parse_mode'] === 'HTML';
        });
    }

    /**
     * 봇 토큰이나 chat id 가 비어 있으면 전송하지 않고 false 를 반환한다. (조용히 건너뜀)
     */
    public function test_skips_when_not_configured(): void
    {
        Http::fake();

        $notifier = new TelegramNotifier(botToken: null, defaultChatId: null);

        $this->assertFalse($notifier->isConfigured());
        $this->assertFalse($notifier->send('보내지 않음'));

        Http::assertNothingSent();
    }

    /**
     * 텔레그램 API 가 오류를 반환해도 예외를 던지지 않고 false 를 반환한다.
     */
    public function test_returns_false_on_api_error(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'chat not found'], 400),
        ]);

        $notifier = new TelegramNotifier(botToken: 'TEST_TOKEN', defaultChatId: '99999');

        $this->assertFalse($notifier->send('실패 케이스'));
    }

    /**
     * send() 의 두 번째 인자로 기본 chat id 를 덮어쓸 수 있다.
     */
    public function test_send_overrides_chat_id(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $notifier = new TelegramNotifier(botToken: 'TEST_TOKEN', defaultChatId: '12345');
        $notifier->send('특정 방으로', chatId: '67890');

        Http::assertSent(fn ($request) => $request['chat_id'] === '67890');
    }
}
