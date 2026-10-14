<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 투표 자동 마감: 매일 오전 9시에 "행사 다음 날 오전 9시"가 지난 매칭중(V) 행사를 종료(X)로 전환.
Schedule::command('rotation:close-voting')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->description('매칭 자동 마감 (매일 오전 9시)');

// 방문 통계 스냅샷: 매일 자정에 "그 자정까지의" 누적 방문 통계를 1행으로 적재.
Schedule::command('rotation:snapshot-visit-stats')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->description('방문 통계 일일 스냅샷 적재 (매일 자정)');

// Schedule::command('cron:test_cron')->description('테스트 크론')->cron('* * * * *');
