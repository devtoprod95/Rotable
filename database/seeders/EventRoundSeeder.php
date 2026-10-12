<?php

namespace Database\Seeders;

use App\Models\EventRound;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventRoundSeeder extends Seeder
{
    /**
     * 샘플 회차 데이터 시딩.
     * 다가오는 토요일부터 매주 토요일 저녁 7시로 3개 회차를 생성한다.
     */
    public function run(): void
    {
        $saturday = Carbon::now()->next(Carbon::SATURDAY)->setTime(19, 0);

        $rounds = [
            ['title' => '로테이블 와인파티', 'type' => EventRound::TYPE_WINE],
            ['title' => '로테이블 1:1 매칭', 'type' => EventRound::TYPE_ONE],
            ['title' => '로테이블 와인파티', 'type' => EventRound::TYPE_WINE],
        ];

        foreach ($rounds as $index => $round) {
            $eventTime = $saturday->copy()->addWeeks($index);

            EventRound::updateOrCreate(
                [
                    'title'      => $round['title'],
                    'event_time' => $eventTime,
                ],
                [
                    'type'   => $round['type'],
                    'status' => EventRound::STATUS_PENDING,
                ],
            );
        }
    }
}
