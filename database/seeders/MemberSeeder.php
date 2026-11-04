<?php

namespace Database\Seeders;

use App\Models\EventRoster;
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
    private const TARGET_EVENT_ID = 1;

    /** 성별당 생성할 인원 수 */
    private const COUNT_PER_GENDER = 10;

    /**
     * 샘플 회원 데이터 시딩.
     *
     * 지금 구조에서는 "체크인 명단(event_rosters)에서 번호표를 배정받은" 사람만 입장등록할 수 있다.
     * 그래서 순서는 실제 운영 흐름과 같다:
     *   1) 체크인 명단에 닉네임·플랫폼·성별을 적고 번호표(ticket_no)를 부여(=체크인)한다.
     *   2) 그 번호표로 회원(members)을 생성한다. (members.name = 번호표 숫자, members.platform = 체크인 명단의 플랫폼)
     *   3) 좌석(기준 자리)을 배치한다.
     *
     * 지정한 특정 회차(TARGET_EVENT_ID)에만 남/여 회원을 고르게 생성하며,
     * 핸드폰 번호는 회차 + 번호 조합으로 유일하다.
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

        $males   = $this->createGender($round, Member::GENDER_MALE, $phoneSeq);
        $phoneSeq += self::COUNT_PER_GENDER;
        $females = $this->createGender($round, Member::GENDER_FEMALE, $phoneSeq);

        // 테이블(1~TABLE_COUNT) 생성 및 좌석(기준 자리) 배치.
        $this->seatMembers($round, $males, $females);
    }

    /**
     * 한 성별에 대해 "체크인(번호표 부여) → 입장등록(회원 생성)"을 번호표 1번부터 순서대로 만든다.
     *
     * @return list<Member>
     */
    private function createGender(EventRound $round, string $gender, int $phoneSeq): array
    {
        $members = [];

        for ($ticket = 1; $ticket <= self::COUNT_PER_GENDER; $ticket++) {
            // 1) 체크인 명단 — 닉네임·플랫폼·성별을 적고, 이 번호표로 체크인(참석) 처리.
            $platform = $ticket % 2 === 0 ? Member::PLATFORM_FRIP : Member::PLATFORM_MUNTO;
            $nickname = ($gender === Member::GENDER_MALE ? '남' : '여') . '_닉네임' . $ticket;

            EventRoster::updateOrCreate(
                [
                    'event_id'  => $round->id,
                    'gender'    => $gender,
                    'ticket_no' => $ticket,
                ],
                [
                    'name'          => $nickname,
                    'platform'      => $platform,
                    'checked_in_at' => now(),
                ],
            );

            // 2) 그 번호표로 입장등록(회원 생성). members.name 은 번호표 숫자 문자열.
            $factory = Member::factory()->for($round, 'eventRound');
            $factory = $gender === Member::GENDER_MALE ? $factory->male() : $factory->female();

            $members[] = $factory->create([
                'name'     => (string) $ticket,
                'phone'    => '010' . ($phoneSeq + $ticket - 1),
                'platform' => $platform,
            ]);
        }

        return $members;
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
