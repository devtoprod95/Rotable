<?php

namespace App\Console\Commands;

use App\Models\EventRound;
use App\Services\RotationService;
use Illuminate\Console\Command;

class CloseVoting extends Command
{
    protected $signature   = 'rotation:close-voting';
    protected $description = '투표중(V) 행사 중 자동 마감 시각(행사 다음 날 오전 9시)이 지난 행사를 종료(X)로 전환한다.';

    public function __construct(protected RotationService $rotationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        // 투표중(V) 상태인 행사만 후보로 조회한다.
        $events = EventRound::query()
            ->where('status', EventRound::STATUS_VOTING)
            ->get();

        $closed = 0;

        foreach ($events as $event) {
            $closesAt = $event->votingClosesAt();

            // 자동 마감 시각이 없거나 아직 지나지 않았으면 건너뛴다.
            if ($closesAt === null || now()->lt($closesAt)) {
                continue;
            }

            $result = $this->rotationService->closeVoting($event->id, 'auto');

            if (($result['isSuccess'] ?? false) === true) {
                $closed++;
                channelLog(
                    message: "[rotation:close-voting] 행사(id={$event->id}, {$event->title}) 매칭 자동 마감",
                    channel: 'cron',
                );
            }
        }

        if ($closed > 0) {
            $this->info("{$closed}개 행사의 투표를 자동 마감했습니다.");
        }

        return self::SUCCESS;
    }
}
