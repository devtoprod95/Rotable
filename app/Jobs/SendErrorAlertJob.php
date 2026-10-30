<?php

namespace App\Jobs;

use App\Services\TelegramNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * 시스템에서 발생한 예외/에러를 운영진 텔레그램으로 비동기 전송한다.
 *
 * 예외 객체는 직렬화가 불안정하므로, 보고에 필요한 문자열 정보만 추출해 들고 있는다.
 * 전송 자체 실패가 에러 처리 흐름을 막지 않도록 실패해도 조용히 로그만 남긴다.
 *
 * @phpstan-type ErrorContext array{method?:string, url?:string, ip?:string}
 */
class SendErrorAlertJob implements ShouldQueue
{
    use Queueable;

    /** 재시도 횟수 (일시적 네트워크 오류 대비) */
    public int $tries = 2;

    /** 재시도 간격(초) */
    public int $backoff = 15;

    /**
     * @param  string                                  $exceptionClass  예외 클래스명
     * @param  string                                  $message         예외 메시지
     * @param  string                                  $file            발생 파일
     * @param  int                                     $line            발생 라인
     * @param  array{method?:string, url?:string, ip?:string}  $context  요청 컨텍스트(선택)
     * @param  string|null                             $environment     앱 환경(production 등)
     */
    public function __construct(
        public string $exceptionClass,
        public string $message,
        public string $file,
        public int $line,
        public array $context = [],
        public ?string $environment = null,
    ) {
    }

    public function handle(): void
    {
        TelegramNotifier::fromConfig()->sendErrorAlert(
            exceptionClass: $this->exceptionClass,
            message: $this->message,
            file: $this->file,
            line: $this->line,
            context: $this->context,
            environment: $this->environment,
        );
    }
}
