<?php

namespace Tests\Unit;

use App\Models\EventRound;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 로테이션 횟수(rotation_limit) 기반 마지막 턴 계산.
 * 테이블 수를 직접 넘겨 DB 없이 검증한다.
 */
class EventRoundMaxRoundTest extends TestCase
{
    /**
     * @return array<string, array{0:?int, 1:int, 2:int}>
     */
    public static function maxRoundCases(): array
    {
        return [
            '지정 안 함 → 테이블 수'              => [null, 15, 15],
            '테이블보다 적게 지정'                => [10, 15, 10],
            '5턴만'                               => [5, 15, 5],
            '테이블 수와 같게 지정'               => [15, 15, 15],
            '테이블 수보다 크면 테이블 수로 제한' => [20, 15, 15],
            '테이블이 없으면 0'                   => [10, 0, 0],
        ];
    }

    #[DataProvider('maxRoundCases')]
    public function test_max_round(?int $limit, int $tableCount, int $expected): void
    {
        $event = new EventRound(['rotation_limit' => $limit]);

        $this->assertSame($expected, $event->maxRound($tableCount));
    }

    public function test_is_last_round_uses_rotation_limit(): void
    {
        $event = new EventRound(['rotation_limit' => 10, 'current_round' => 9]);
        $this->assertFalse($event->isLastRound(15));

        $event->current_round = 10;
        $this->assertTrue($event->isLastRound(15));

        // 특정 턴을 넘겨 판정
        $this->assertFalse($event->isLastRound(15, 3));
        $this->assertTrue($event->isLastRound(15, 10));
    }

    public function test_is_last_round_without_limit_falls_back_to_table_count(): void
    {
        $event = new EventRound(['current_round' => 10]);

        $this->assertFalse($event->isLastRound(15));
        $this->assertTrue($event->isLastRound(15, 15));
    }

    public function test_no_tables_is_never_last(): void
    {
        $event = new EventRound(['current_round' => 3]);

        $this->assertFalse($event->isLastRound(0));
    }
}
