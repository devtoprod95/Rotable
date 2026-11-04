<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 체크인 명단(event_rosters)에서 더 이상 쓰지 않는 컬럼을 제거한다.
 *
 * - phone: 문토/프립 신청자는 전화번호를 알 수 없어 번호 기반 연동을 접었다. (번호표로 대체)
 *   (event_id, phone) 유니크 인덱스에 묶여 있으므로 인덱스를 먼저 지운다.
 *   컬럼만 지우면 MySQL 이 인덱스를 event_id 단독 유니크로 남겨 중복 오류가 날 수 있다.
 * - memo: 화면/로직 어디에서도 쓰지 않는다.
 *
 * 주의: 이미 저장된 phone/memo 값은 복구할 수 없다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'phone']);
        });

        Schema::table('event_rosters', function (Blueprint $table) {
            $table->dropColumn(['phone', 'memo']);
        });
    }

    public function down(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('platform')->comment('(레거시) 전화번호');
            $table->string('memo', 255)->nullable()->comment('메모 (선택)');
        });

        Schema::table('event_rosters', function (Blueprint $table) {
            $table->unique(['event_id', 'phone']);
        });
    }
};
