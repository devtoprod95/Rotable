<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 체크인 명단(event_rosters)을 "전화번호 없이" 쓸 수 있게 바꾼다.
 *
 * - 문토/프립 신청자는 전화번호를 알 수 없고 닉네임·플랫폼·성별만 알 수 있다.
 *   → phone 을 nullable 로 풀고, 신청 플랫폼(platform) 컬럼을 추가한다.
 * - 기존 데이터 보존을 위해 phone 컬럼과 (event_id, phone) 유니크 인덱스는 그대로 둔다.
 *   (유니크 인덱스는 NULL 을 여러 개 허용하므로 번호 없는 행이 충돌하지 않는다.)
 * - name 컬럼은 그대로 쓰되 화면에서는 "닉네임"으로 부른다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->comment('(미사용·레거시) 전화번호. 번호를 알 수 없어 nullable')->change();
        });

        Schema::table('event_rosters', function (Blueprint $table) {
            $table->string('platform', 20)
                ->default('munto')
                ->after('gender')
                ->comment('신청 플랫폼: munto(문토) | frip(프립)');
        });
    }

    public function down(): void
    {
        // phone 의 NOT NULL 복원은 하지 않는다.
        // (번호 없는 행이 여러 개면 '' 로 치환 시 (event_id, phone) 유니크가 충돌해 롤백이 위험하다.)
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
    }
};
