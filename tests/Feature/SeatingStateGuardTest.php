<?php

namespace Tests\Feature;

use App\Models\EventRound;
use App\Models\Member;
use App\Models\RotationSeat;
use App\Models\RotationTable;
use App\Models\Vote;
use App\Services\Rotation\SeatingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * 좌석/로테이션 진행 서비스의 상태 가드와 투표 참여 현황 집계.
 *
 * ⚠️ phpunit.xml 이 .env 의 실제 DB 에 연결되므로 DatabaseTransactions 로 각 테스트를 롤백한다.
 *    (데이터를 지우지 않고, 테스트가 만든 행도 남기지 않는다.)
 *
 * 실행:
 *   php artisan test --compact tests/Feature/SeatingStateGuardTest.php
 */
class SeatingStateGuardTest extends TestCase
{
    use DatabaseTransactions;

    private SeatingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SeatingService::class);
    }

    /**
     * 행사 + 테이블(1..$tables) 을 만든다.
     */
    private function makeEvent(string $status, int $currentRound = 0, int $tables = 3): EventRound
    {
        $event = EventRound::factory()->create([
            'type'          => EventRound::TYPE_WINE,
            'status'        => $status,
            'current_round' => $currentRound,
        ]);

        for ($no = 1; $no <= $tables; $no++) {
            RotationTable::create(['event_id' => $event->id, 'table_no' => $no]);
        }

        return $event;
    }

    private function makeMember(EventRound $event, string $gender, string $name): Member
    {
        return Member::factory()->create([
            'event_id' => $event->id,
            'gender'   => $gender,
            'name'     => $name,
        ]);
    }

    private function seatOf(EventRound $event, Member $member, int $tableNo): RotationSeat
    {
        $table = RotationTable::where('event_id', $event->id)->where('table_no', $tableNo)->firstOrFail();

        return RotationSeat::create([
            'event_id'          => $event->id,
            'member_id'         => $member->id,
            'rotation_table_id' => $table->id,
        ]);
    }

    private function tableOf(EventRound $event, int $tableNo): RotationTable
    {
        return RotationTable::where('event_id', $event->id)->where('table_no', $tableNo)->firstOrFail();
    }

    /* ======================================================================
     | 매칭 시작 (openVoting)
     * ==================================================================== */

    public function test_open_voting_succeeds_only_from_started(): void
    {
        $event = $this->makeEvent(EventRound::STATUS_STARTED, 3);

        $res = $this->service->openVoting($event->id);

        $this->assertTrue($res['isSuccess']);
        $this->assertSame(EventRound::STATUS_VOTING, $event->fresh()->status);
        $this->assertNotNull($event->fresh()->voting_opened_at);
    }

    public function test_open_voting_is_rejected_when_pending(): void
    {
        $event = $this->makeEvent(EventRound::STATUS_PENDING);

        $res = $this->service->openVoting($event->id);

        $this->assertFalse($res['isSuccess']);
        $this->assertStringContainsString('로테이션이 시작되지 않았습니다', $res['msg']);
        $this->assertSame(EventRound::STATUS_PENDING, $event->fresh()->status);
    }

    public function test_open_voting_is_rejected_when_closed(): void
    {
        $event = $this->makeEvent(EventRound::STATUS_CLOSED, 3);

        $res = $this->service->openVoting($event->id);

        $this->assertFalse($res['isSuccess']);
        $this->assertStringContainsString('종료 되돌리기', $res['msg']);
        $this->assertSame(EventRound::STATUS_CLOSED, $event->fresh()->status);
    }

    public function test_open_voting_is_rejected_when_already_voting(): void
    {
        $event = $this->makeEvent(EventRound::STATUS_VOTING, 3);

        $res = $this->service->openVoting($event->id);

        $this->assertFalse($res['isSuccess']);
        $this->assertStringContainsString('이미 매칭이 진행 중', $res['msg']);
    }

    /* ======================================================================
     | 로테이션 진행 (rotateProgress)
     * ==================================================================== */

    public function test_rotate_progress_moves_pending_to_started(): void
    {
        $event  = $this->makeEvent(EventRound::STATUS_PENDING);
        $member = $this->makeMember($event, Member::GENDER_MALE, 'M1');
        $this->seatOf($event, $member, 1);

        $res = $this->service->rotateProgress($event->id);

        $this->assertTrue($res['isSuccess']);
        $event->refresh();
        $this->assertSame(1, $event->current_round);
        $this->assertSame(EventRound::STATUS_STARTED, $event->status);
    }

    public function test_rotate_progress_is_rejected_while_voting_and_keeps_state(): void
    {
        $event  = $this->makeEvent(EventRound::STATUS_VOTING, 1);
        $member = $this->makeMember($event, Member::GENDER_MALE, 'M1');
        $this->seatOf($event, $member, 1);

        $res = $this->service->rotateProgress($event->id);

        $this->assertFalse($res['isSuccess']);
        $this->assertStringContainsString('매칭 시작 취소', $res['msg']);
        $event->refresh();
        $this->assertSame(EventRound::STATUS_VOTING, $event->status);
        $this->assertSame(1, $event->current_round);
    }

    public function test_rotate_progress_is_rejected_when_closed_and_keeps_state(): void
    {
        $event  = $this->makeEvent(EventRound::STATUS_CLOSED, 1);
        $member = $this->makeMember($event, Member::GENDER_MALE, 'M1');
        $this->seatOf($event, $member, 1);

        $res = $this->service->rotateProgress($event->id);

        $this->assertFalse($res['isSuccess']);
        $this->assertStringContainsString('종료된 행사', $res['msg']);
        $event->refresh();
        $this->assertSame(EventRound::STATUS_CLOSED, $event->status);
        $this->assertSame(1, $event->current_round);
    }

    public function test_rotate_progress_rejects_past_last_round(): void
    {
        $event  = $this->makeEvent(EventRound::STATUS_STARTED, 3, tables: 3);
        $member = $this->makeMember($event, Member::GENDER_MALE, 'M1');
        $this->seatOf($event, $member, 1);

        $res = $this->service->rotateProgress($event->id);

        $this->assertFalse($res['isSuccess']);
        $this->assertStringContainsString('마지막 턴', $res['msg']);
        $this->assertSame(3, $event->fresh()->current_round);
    }

    public function test_rotate_progress_rejects_stale_expected_round(): void
    {
        $event  = $this->makeEvent(EventRound::STATUS_STARTED, 2);
        $member = $this->makeMember($event, Member::GENDER_MALE, 'M1');
        $this->seatOf($event, $member, 1);

        $res = $this->service->rotateProgress($event->id, expectedRound: 1);

        $this->assertFalse($res['isSuccess']);
        $this->assertSame(2, $event->fresh()->current_round);
    }

    /* ======================================================================
     | 좌석 배정 (seatingUpsert) / 삭제 (seatingDelete)
     * ==================================================================== */

    public function test_upsert_rejects_table_from_another_event(): void
    {
        $event        = $this->makeEvent(EventRound::STATUS_PENDING);
        $other        = $this->makeEvent(EventRound::STATUS_PENDING);
        $member       = $this->makeMember($event, Member::GENDER_MALE, 'M1');
        $foreignTable = $this->tableOf($other, 1);

        $res = $this->service->seatingUpsert((object) [
            'event_id'          => $event->id,
            'rotation_table_id' => $foreignTable->id,
            'member_id'         => $member->id,
        ]);

        $this->assertFalse($res['isSuccess']);
        $this->assertStringContainsString('이 행사의 테이블이 아닙니다', $res['msg']);
        $this->assertSame(0, RotationSeat::where('member_id', $member->id)->count());
    }

    public function test_upsert_rejects_member_from_another_event(): void
    {
        $event   = $this->makeEvent(EventRound::STATUS_PENDING);
        $other   = $this->makeEvent(EventRound::STATUS_PENDING);
        $foreign = $this->makeMember($other, Member::GENDER_MALE, 'M1');

        $res = $this->service->seatingUpsert((object) [
            'event_id'          => $event->id,
            'rotation_table_id' => $this->tableOf($event, 1)->id,
            'member_id'         => $foreign->id,
        ]);

        $this->assertFalse($res['isSuccess']);
        $this->assertStringContainsString('이 행사의 참가자가 아닙니다', $res['msg']);
        $this->assertSame(0, RotationSeat::where('member_id', $foreign->id)->count());
    }

    public function test_upsert_rejects_closed_event(): void
    {
        $event  = $this->makeEvent(EventRound::STATUS_CLOSED, 3);
        $member = $this->makeMember($event, Member::GENDER_MALE, 'M1');

        $res = $this->service->seatingUpsert((object) [
            'event_id'          => $event->id,
            'rotation_table_id' => $this->tableOf($event, 1)->id,
            'member_id'         => $member->id,
        ]);

        $this->assertFalse($res['isSuccess']);
        $this->assertStringContainsString('종료된 행사', $res['msg']);
    }

    public function test_upsert_blocks_moving_assigned_member_during_rotation(): void
    {
        $event  = $this->makeEvent(EventRound::STATUS_STARTED, 2);
        $member = $this->makeMember($event, Member::GENDER_MALE, 'M1');
        $seat   = $this->seatOf($event, $member, 1);
        $target = $this->tableOf($event, 2);

        $res = $this->service->seatingUpsert((object) [
            'event_id'          => $event->id,
            'rotation_table_id' => $target->id,
            'member_id'         => $member->id,
        ]);

        $this->assertFalse($res['isSuccess']);
        $this->assertStringContainsString('0턴으로 되돌려', $res['msg']);
        $this->assertNotSame($target->id, $seat->fresh()->rotation_table_id);
    }

    public function test_upsert_allows_new_member_during_rotation(): void
    {
        $event  = $this->makeEvent(EventRound::STATUS_STARTED, 2);
        $member = $this->makeMember($event, Member::GENDER_FEMALE, 'F1');

        $res = $this->service->seatingUpsert((object) [
            'event_id'          => $event->id,
            'rotation_table_id' => $this->tableOf($event, 2)->id,
            'member_id'         => $member->id,
        ]);

        $this->assertTrue($res['isSuccess']);
        $this->assertSame(1, RotationSeat::where('member_id', $member->id)->count());
    }

    public function test_upsert_allows_moving_member_before_rotation_starts(): void
    {
        $event  = $this->makeEvent(EventRound::STATUS_PENDING, 0);
        $member = $this->makeMember($event, Member::GENDER_MALE, 'M1');
        $seat   = $this->seatOf($event, $member, 1);
        $target = $this->tableOf($event, 2);

        $res = $this->service->seatingUpsert((object) [
            'event_id'          => $event->id,
            'rotation_table_id' => $target->id,
            'member_id'         => $member->id,
        ]);

        $this->assertTrue($res['isSuccess']);
        $this->assertSame($target->id, $seat->fresh()->rotation_table_id);
    }

    public function test_delete_is_rejected_for_closed_event(): void
    {
        $event  = $this->makeEvent(EventRound::STATUS_CLOSED, 3);
        $member = $this->makeMember($event, Member::GENDER_MALE, 'M1');
        $seat   = $this->seatOf($event, $member, 1);

        $res = $this->service->seatingDelete($seat->id);

        $this->assertFalse($res['isSuccess']);
        $this->assertStringContainsString('종료된 행사의 좌석', $res['msg']);
        $this->assertNotNull(RotationSeat::find($seat->id));
    }

    public function test_delete_succeeds_for_started_event(): void
    {
        $event  = $this->makeEvent(EventRound::STATUS_STARTED, 1);
        $member = $this->makeMember($event, Member::GENDER_MALE, 'M1');
        $seat   = $this->seatOf($event, $member, 1);

        $res = $this->service->seatingDelete($seat->id);

        $this->assertTrue($res['isSuccess']);
        $this->assertNull(RotationSeat::find($seat->id));
    }

    /* ======================================================================
     | 투표 참여 현황 (voteProgress)
     * ==================================================================== */

    public function test_vote_progress_counts_distinct_voters_by_gender(): void
    {
        $event = $this->makeEvent(EventRound::STATUS_VOTING, 3);
        $m1    = $this->makeMember($event, Member::GENDER_MALE, 'M1');
        $m2    = $this->makeMember($event, Member::GENDER_MALE, 'M2');
        $f1    = $this->makeMember($event, Member::GENDER_FEMALE, 'F1');
        $f2    = $this->makeMember($event, Member::GENDER_FEMALE, 'F2');
        $f3    = $this->makeMember($event, Member::GENDER_FEMALE, 'F3');

        // m1 은 2명을 골라도 1명으로 센다. m2 는 투표하지 않았다. 여자는 f1 만 투표했다.
        Vote::create(['event_id' => $event->id, 'voter_id' => $m1->id, 'target_id' => $f1->id]);
        Vote::create(['event_id' => $event->id, 'voter_id' => $m1->id, 'target_id' => $f2->id]);
        Vote::create(['event_id' => $event->id, 'voter_id' => $f1->id, 'target_id' => $m1->id]);

        $p = $this->service->voteProgress($event->id);

        $this->assertSame(['voted' => 1, 'total' => 2], $p['male']);
        $this->assertSame(['voted' => 1, 'total' => 3], $p['female']);
        $this->assertSame(2, $p['voted']);
        $this->assertSame(5, $p['total']);
    }

    public function test_vote_progress_ignores_other_events_and_deleted_members(): void
    {
        $event = $this->makeEvent(EventRound::STATUS_VOTING, 3);
        $other = $this->makeEvent(EventRound::STATUS_VOTING, 3);

        $m1 = $this->makeMember($event, Member::GENDER_MALE, 'M1');
        $f1 = $this->makeMember($event, Member::GENDER_FEMALE, 'F1');
        $om = $this->makeMember($other, Member::GENDER_MALE, 'M1');
        $of = $this->makeMember($other, Member::GENDER_FEMALE, 'F1');

        Vote::create(['event_id' => $event->id, 'voter_id' => $m1->id, 'target_id' => $f1->id]);
        Vote::create(['event_id' => $other->id, 'voter_id' => $om->id, 'target_id' => $of->id]);

        // 탈퇴(소프트 삭제)한 회원의 표는 집계에서 빠진다.
        $m1->delete();

        $p = $this->service->voteProgress($event->id);

        $this->assertSame(['voted' => 0, 'total' => 0], $p['male']);
        $this->assertSame(['voted' => 0, 'total' => 1], $p['female']);
    }

    public function test_vote_progress_is_zero_for_event_without_members(): void
    {
        $event = $this->makeEvent(EventRound::STATUS_VOTING, 3);

        $p = $this->service->voteProgress($event->id);

        $this->assertSame(0, $p['voted']);
        $this->assertSame(0, $p['total']);
    }
}
