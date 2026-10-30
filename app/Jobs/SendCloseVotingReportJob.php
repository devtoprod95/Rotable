<?php

namespace App\Jobs;

use App\Services\RotationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * 투표(매칭) 종료 시 운영진 텔레그램 요약 리포트를 비동기로 전송한다.
 *
 * 모델 대신 식별자(eventId)만 들고 있다가 큐 처리 시점에 최신 데이터를 조회해 집계한다.
 * 전송 실패가 본래의 마감 처리 흐름을 막지 않도록, 리포트 전송은 이 Job 으로 분리했다.
 */
class SendCloseVotingReportJob implements ShouldQueue
{
    use Queueable;

    /** 재시도 횟수 (텔레그램 일시 오류 대비) */
    public int $tries = 3;

    /** 재시도 간격(초) */
    public int $backoff = 10;

    /**
     * @param  int     $eventId  종료된 행사 id
     * @param  string  $trigger  마감 트리거: 'manual'(수동) | 'auto'(자동 9시 마감)
     */
    public function __construct(
        public int $eventId,
        public string $trigger = 'manual',
    ) {
    }

    public function handle(RotationService $rotationService): void
    {
        $rotationService->sendCloseVotingReport($this->eventId, $this->trigger);
    }
}
