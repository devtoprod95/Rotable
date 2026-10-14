<?php

namespace Database\Seeders;

use App\Models\EventRound;
use App\Models\Member;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * 회원을 생성할 대상 행사(event_rounds.id).
     * 이 id의 행사에만 샘플 회원을 생성한다.
     */
    private const TARGET_EVENT_ID = 3;

    /**
     * 샘플 회원 데이터 시딩.
     * 지정한 특정 회차(TARGET_EVENT_ID)에만 남/여 회원을 고르게 생성한다.
     * (핸드폰 번호는 회차 + 번호 조합으로 유일하다.)
     *
     * 이 시더만 단독 실행하려면:
     *   php artisan db:seed --class=MemberSeeder
     *
     * Docker 환경에서는:
     *   docker compose exec php83 php artisan db:seed --class=MemberSeeder
     */
    public function run(): void
    {
        $round = EventRound::query()->find(self::TARGET_EVENT_ID);

        if ($round === null) {
            $this->command?->warn('MemberSeeder: event_rounds.id=' . self::TARGET_EVENT_ID . ' 행사를 찾을 수 없어 건너뜁니다.');

            return;
        }

        $maleNames   = Member::factory()->maleNames();
        $femaleNames = Member::factory()->femaleNames();

        // 한 회차 안에서 이름이 겹치지 않도록, 이름 풀을 셔플해 순서대로 하나씩 배정한다.
        $maleShuffled   = collect($maleNames)->shuffle()->values();
        $femaleShuffled = collect($femaleNames)->shuffle()->values();

        // 남성 10명, 여성 10명 생성.
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
