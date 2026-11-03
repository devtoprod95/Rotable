<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 사전 참여자 명단(event_rosters)에 성별(gender) 컬럼을 추가한다.
 *
 * - 'M'(남) | 'F'(여). 체크인 화면에서 남/여를 구분해 리스트로 보여주기 위함.
 * - 성별은 필수. 기본값 'M'(남). (누락/과거 데이터도 남으로 귀속)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->string('gender', 1)->default('M')->after('name')->comment('성별: M(남) | F(여)');
        });
    }

    public function down(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
