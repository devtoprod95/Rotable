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

// Schedule::command('cron:test_cron')->description('테스트 크론')->cron('* * * * *');
