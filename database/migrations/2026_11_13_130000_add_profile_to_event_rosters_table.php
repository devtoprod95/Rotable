<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * event_rosters 에 상세페이지(홍보용) 명단 자동 채움용 프로필(키·출생연도·직업)을 추가한다.
 *
 * - 신청자는 입장등록(members) 전이라 members 와 연결할 수 없으므로, 체크인 명단에 직접 입력해 둔다.
 * - 모두 nullable: 기존 명단·미입력 호환.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->unsignedSmallInteger('height')
                ->nullable()
                ->after('gender')
                ->comment('키(cm)');
            $table->unsignedSmallInteger('birth_year')
                ->nullable()
                ->after('height')
                ->comment('출생연도(4자리)');
            $table->string('job', 50)
                ->nullable()
                ->after('birth_year')
                ->comment('직업');
        });
    }

    public function down(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->dropColumn(['height', 'birth_year', 'job']);
        });
    }
};
