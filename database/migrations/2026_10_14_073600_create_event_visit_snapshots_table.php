<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 방문 통계 일일 스냅샷 테이블.
     * - 매일 자정 스케줄러가 "그 시점(= 전날 자정)까지"의 방문 통계를 1행으로 적재한다.
     * - "방문"은 회원(members) 1건을 1회 방문으로 보고, 같은 사람(= 전화번호 기준)이 여러 행사에
     *   참여하면 그 전화번호의 누적 방문 횟수가 올라간다.
     * - visit_1 ~ visit_5 는 "정확히 N번 방문한 사람 수", visit_6_plus 는 "6번 이상 방문한 사람 수".
     */
    public function up(): void
    {
        if (! Schema::hasTable('event_visit_snapshots')) {
            Schema::create('event_visit_snapshots', function (Blueprint $table) {
                $table->id();
                $table->date('snapshot_date')->comment('스냅샷 기준일 (이 날짜 자정까지의 누적 데이터)');

                $table->unsignedInteger('total_visits')->default(0)->comment('총 방문 수 (회원 레코드 총 개수)');
                $table->unsignedInteger('total_male')->default(0)->comment('총 남 방문 수');
                $table->unsignedInteger('total_female')->default(0)->comment('총 여 방문 수');

                // 방문 횟수 구간별 "사람(전화번호) 수". 각 구간마다 전체/남/여로 나눠 저장한다.
                // (전화번호의 성별은 그 전화번호의 가장 최근 방문 레코드 기준)
                $table->unsignedInteger('visit_1')->default(0)->comment('정확히 1번 방문한 사람 수 (전체)');
                $table->unsignedInteger('visit_1_male')->default(0)->comment('정확히 1번 방문한 남 수');
                $table->unsignedInteger('visit_1_female')->default(0)->comment('정확히 1번 방문한 여 수');
                $table->unsignedInteger('visit_2')->default(0)->comment('정확히 2번 방문한 사람 수 (전체)');
                $table->unsignedInteger('visit_2_male')->default(0)->comment('정확히 2번 방문한 남 수');
                $table->unsignedInteger('visit_2_female')->default(0)->comment('정확히 2번 방문한 여 수');
                $table->unsignedInteger('visit_3')->default(0)->comment('정확히 3번 방문한 사람 수 (전체)');
                $table->unsignedInteger('visit_3_male')->default(0)->comment('정확히 3번 방문한 남 수');
                $table->unsignedInteger('visit_3_female')->default(0)->comment('정확히 3번 방문한 여 수');
                $table->unsignedInteger('visit_4')->default(0)->comment('정확히 4번 방문한 사람 수 (전체)');
                $table->unsignedInteger('visit_4_male')->default(0)->comment('정확히 4번 방문한 남 수');
                $table->unsignedInteger('visit_4_female')->default(0)->comment('정확히 4번 방문한 여 수');
                $table->unsignedInteger('visit_5')->default(0)->comment('정확히 5번 방문한 사람 수 (전체)');
                $table->unsignedInteger('visit_5_male')->default(0)->comment('정확히 5번 방문한 남 수');
                $table->unsignedInteger('visit_5_female')->default(0)->comment('정확히 5번 방문한 여 수');
                $table->unsignedInteger('visit_6_plus')->default(0)->comment('6번 이상 방문한 사람 수 (전체)');
                $table->unsignedInteger('visit_6_plus_male')->default(0)->comment('6번 이상 방문한 남 수');
                $table->unsignedInteger('visit_6_plus_female')->default(0)->comment('6번 이상 방문한 여 수');

                $table->timestamps();
                $table->comment('방문 통계 일일 스냅샷 (매일 자정 적재)');

                $table->unique('snapshot_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_visit_snapshots');
    }
};
