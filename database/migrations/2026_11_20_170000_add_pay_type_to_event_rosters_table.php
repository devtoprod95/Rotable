<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * event_rosters 에 신청자별 결제 유형(pay_type)을 더한다.
 *
 * - N: 정가로 결제 / E: 얼리버드가로 결제(선착순) / F: 무료 참가(결제 없음)
 * - 매출 화면에서 사람별 결제 금액을 계산하는 기준이다. 기존 명단은 정가(N)로 본다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->char('pay_type', 1)->default('N')->after('platform')->comment('결제 유형: N=정가, E=얼리버드, F=무료');
        });
    }

    public function down(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->dropColumn('pay_type');
        });
    }
};
