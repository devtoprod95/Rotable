<?php

namespace Database\Seeders;

use App\Models\EventRound;
use App\Models\Member;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * 샘플 회원 데이터 시딩.
     * 등록된 각 회차마다 남/여 회원을 고르게 생성한다.
     * (핸드폰 번호는 회차 + 번호 조합으로 유일하므로 회차별로 생성한다.)
     *
     * 이 시더만 단독 실행하려면:
     *   php artisan db:seed --class=MemberSeeder
     *
     * Docker 환경에서는:
     *   docker compose exec php83 php artisan db:seed --class=MemberSeeder
     */
    public function run(): void
    {
        $rounds = EventRound::query()->orderBy('event_time')->get();

        if ($rounds->isEmpty()) {
            return;
        }

        $maleNames   = Member::factory()->maleNames();
        $femaleNames = Member::factory()->femaleNames();

        foreach ($rounds as $round) {
            // 한 회차 안에서 이름이 겹치지 않도록, 이름 풀을 셔플해 순서대로 하나씩 배정한다.
            $maleShuffled   = collect($maleNames)->shuffle()->values();
            $femaleShuffled = collect($femaleNames)->shuffle()->values();

            // 각 회차에 남성 10명, 여성 10명 생성.
            for ($i = 0; $i < 10; $i++) {
                Member::factory()
                    ->male()
                    ->for($round, 'eventRound')
                    ->create(['name' => $maleShuffled[$i]]);
            }

            for ($i = 0; $i < 10; $i++) {
                Member::factory()
                    ->female()
                    ->for($round, 'eventRound')
                    ->create(['name' => $femaleShuffled[$i]]);
            }
        }
    }
}
