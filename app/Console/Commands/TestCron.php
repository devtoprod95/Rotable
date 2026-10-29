<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestCron extends Command
{
    protected $signature   = 'cron:test_cron';
    protected $description = '테스트 크론';

    public function handle()
    {
        channelLog(message: '[cron:test_cron] 테스트 크론 실행', channel: 'cron');
    }
}
