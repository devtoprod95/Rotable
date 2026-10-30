<?php

namespace App\Services;

use App\Enums\CacheKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * 텔레그램 봇으로 운영진(관리자)에게 알림 메시지를 보낸다.
 *
 * - 봇 토큰/채팅 id 는 config/services.php (telegram) 에서 읽는다.
 * - 설정이 비어 있으면 전송을 조용히 건너뛴다. (본 기능 흐름을 막지 않기 위함)
 * - 전송 실패(네트워크/HTTP 오류)도 예외를 던지지 않고 로그만 남긴다.
 * - 최고관리자가 런타임에 전송을 on/off 할 수 있다. (캐시 플래그, 기본 on)
 */
class TelegramNotifier
{
    /** 로그 채널명 (storage/logs/telegram/*) */
    private const LOG_CHANNEL = 'telegram';

    public function __construct(
        private readonly ?string $botToken = null,
        private readonly ?string $defaultChatId = null,
    ) {
    }

    /**
     * 설정값으로 인스턴스를 만든다.
     */
    public static function fromConfig(): self
    {
        return new self(
            botToken: config('services.telegram.bot_token'),
            defaultChatId: config('services.telegram.admin_chat_id'),
        );
    }

    /**
     * 텔레그램 메시지 전달이 (최고관리자에 의해) 켜져 있는지 여부. 기본값은 켜짐.
     */
    public static function isEnabled(): bool
    {
        return (bool) Cache::rememberForever(CacheKey::TelegramEnabled->value, fn (): bool => true);
    }

    /**
     * 텔레그램 메시지 전달 on/off 를 설정한다. (최고관리자 전용 기능에서 호출)
     */
    public static function setEnabled(bool $enabled): void
    {
        Cache::forever(CacheKey::TelegramEnabled->value, $enabled);
    }

    /**
     * 봇 토큰과 수신 chat id 가 모두 설정되어 있고, 전달이 켜져 있어 전송 가능한 상태인지 여부.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->botToken) && ! empty($this->defaultChatId) && self::isEnabled();
    }

    /**
     * 운영진 채팅방으로 메시지를 보낸다.
     *
     * @param  string       $text    보낼 본문 (HTML parse_mode)
     * @param  string|null  $chatId  수신 chat id (미지정 시 기본 운영진 chat id)
     * @return bool 전송 성공 여부 (미설정/실패 시 false)
     */
    public function send(string $text, ?string $chatId = null): bool
    {
        $chatId ??= $this->defaultChatId;

        if (empty($this->botToken) || empty($chatId)) {
            // 미설정은 정상 상황으로 간주하고 조용히 건너뛴다.
            return false;
        }

        if (! self::isEnabled()) {
            // 최고관리자가 전달을 꺼둔 상태이면 조용히 건너뛴다.
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                    'chat_id'                  => $chatId,
                    'text'                     => $text,
                    'parse_mode'               => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            if ($response->failed()) {
                channelLog(
                    message: "[telegram] 전송 실패 status={$response->status()} body={$response->body()}",
                    channel: self::LOG_CHANNEL,
                    level: 'error',
                );

                return false;
            }

            return true;
        } catch (\Throwable $th) {
            channelLog(message: $th, channel: self::LOG_CHANNEL, level: 'error');

            return false;
        }
    }

    /**
     * 시스템 예외/에러를 운영진 채팅방으로 보낸다.
     *
     * @param  string                                           $exceptionClass  예외 클래스명
     * @param  string                                           $message         예외 메시지
     * @param  string                                           $file            발생 파일 경로
     * @param  int                                              $line            발생 라인
     * @param  array{method?:string, url?:string, ip?:string}   $context         요청 컨텍스트(선택)
     * @param  string|null                                      $environment     앱 환경
     */
    public function sendErrorAlert(
        string $exceptionClass,
        string $message,
        string $file,
        int $line,
        array $context = [],
        ?string $environment = null,
    ): bool {
        if (! $this->isConfigured()) {
            return false;
        }

        return $this->send($this->buildErrorAlert(
            $exceptionClass,
            $message,
            $file,
            $line,
            $context,
            $environment,
        ));
    }

    /**
     * 에러 알림 본문(텔레그램 HTML)을 조립한다.
     * 메시지가 과도하게 길면 텔레그램 한도를 넘지 않도록 잘라낸다.
     *
     * @param  array{method?:string, url?:string, ip?:string}  $context
     */
    private function buildErrorAlert(
        string $exceptionClass,
        string $message,
        string $file,
        int $line,
        array $context,
        ?string $environment,
    ): string {
        $shortMessage = mb_strimwidth($message, 0, 500, '…');

        $lines = [
            '🚨 <b>시스템 에러 발생</b>',
        ];

        if (! empty($environment)) {
            $lines[] = "🌐 <b>환경</b> : " . e($environment);
        }

        $lines[] = '';
        $lines[] = "❗ <b>예외</b> : <code>" . e($exceptionClass) . "</code>";
        $lines[] = "💬 <b>메시지</b> : " . e($shortMessage);
        $lines[] = "📄 <b>위치</b> : " . e(basename($file)) . ":{$line}";

        if (! empty($context['method']) || ! empty($context['url'])) {
            $method = e($context['method'] ?? '');
            $url    = e($context['url'] ?? '');
            $lines[] = '';
            $lines[] = "🔗 <b>요청</b> : {$method} {$url}";
        }

        if (! empty($context['ip'])) {
            $lines[] = "📍 <b>IP</b> : " . e($context['ip']);
        }

        return implode("\n", $lines);
    }
}
