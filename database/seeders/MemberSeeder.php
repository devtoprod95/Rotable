<?php

namespace Database\Seeders;

use App\Models\EventRound;
use App\Models\Member;
use App\Models\RotationSeat;
use App\Models\RotationTable;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * 회원을 생성할 대상 행사(event_rounds.id).
     * 이 id의 행사에만 샘플 회원을 생성한다.
     */
    private const TARGET_EVENT_ID = 2;

    /**
     * 샘플 회원 데이터 시딩.
     * 지정한 특정 회차(TARGET_EVENT_ID)에만 남/여 회원을 고르게 생성한다.
     * (핸드폰 번호는 회차 + 번호 조합으로 유일하다.)
     *
     * 이 시더만 단독 실행하려면:
     *   php artisan db:seed --class=MemberSeeder
     *
     * Docker 환경에서는:
     *   docker compose exec php php artisan db:seed --class=MemberSeeder
     */
    public function run(): void
    {
        $round = EventRound::query()->find(self::TARGET_EVENT_ID);

        if ($round === null) {
            $this->command?->warn('MemberSeeder: event_rounds.id=' . self::TARGET_EVENT_ID . ' 행사를 찾을 수 없어 건너뜁니다.');

            return;
        }

        // 핸드폰 번호는 010 + 8자리(11111111 부터 순차)로 유일하게 매긴다.
        // 남1=01011111111, 남2=01011111112 … 여10=01011111130
        $phoneSeq = 11111111;

        // 남성 10명: 이름 김남자1~김남자10
        $males = [];
        for ($i = 1; $i <= 10; $i++) {
            $males[] = Member::factory()
                ->male()
                ->for($round, 'eventRound')
                ->create([
                    'name'  => '김남자' . $i,
                    'phone' => '010' . $phoneSeq++,
                ]);
        }

        // 여성 10명: 이름 김여자1~김여자10
        $females = [];
        for ($i = 1; $i <= 10; $i++) {
            $females[] = Member::factory()
                ->female()
                ->for($round, 'eventRound')
                ->create([
                    'name'  => '김여자' . $i,
                    'phone' => '010' . $phoneSeq++,
                ]);
        }

        // 테이블(1~TABLE_COUNT) 생성 및 좌석(기준 자리) 배치.
        $this->seatMembers($round, $males, $females);
    }

    /**
     * 테이블을 읽어 남/여 회원을 번갈아 균등 배치한다.
     * - 테이블은 **이 행사의 rotation_tables** 에서 table_no 오름차순으로 가져온다.
     *   (테이블 개수/번호는 관리자가 행사에 미리 정의한 것을 그대로 따른다.)
     * - 남/여를 각각 생성 순서대로 테이블에 라운드로빈 배정 → 테이블마다 성비가 맞는다.
     * - rotation_seats 는 회원당 "기준(홈) 자리" 1행. 여자=고정석, 남자=1턴 출발석.
     *   턴별 실제 자리는 event_rounds.current_round 로 계산하므로 여기서는 기준자리만 저장.
     *
     * @param list<Member> $males
     * @param list<Member> $females
     */
    private function seatMembers(EventRound $round, array $males, array $females): void
    {
        // 이 행사에 정의된 테이블을 번호 순으로 사용한다.
        $tables = RotationTable::query()
            ->where('event_id', $round->id)
            ->orderBy('table_no')
            ->get();

        if ($tables->isEmpty()) {
            $this->command?->warn('MemberSeeder: event_id=' . $round->id . ' 에 rotation_tables 가 없어 좌석 배치를 건너뜁니다. (행사 테이블을 먼저 생성하세요)');

            return;
        }

        // 남/여를 라운드로빈으로 각 테이블에 배정해 기준 좌석 생성
        $assign = function (array $people) use ($round, $tables): void {
            $count = $tables->count();
            foreach ($people as $index => $member) {
                $table = $tables[$index % $count];
                RotationSeat::updateOrCreate(
                    [
                        'event_id'  => $round->id,
                        'member_id' => $member->id,
                    ],
                    [
                        'rotation_table_id' => $table->id,
                    ],
                );
            }
        };

        $assign($males);
        $assign($females);
    }
}
