<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * members 테이블에 추가 프로필 필드를 더한다. (모든 행사 유형 공통)
 *
 * - 대화를 유도하는 재미 요소(장점/이상형/취미/TMI)를 담는다.
 * - 과거 가입자 호환을 위해 기본값 ''(빈 문자열)로 둔다. (신규 가입은 폼에서 필수 입력)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('merit', 100)->default('')->after('is_smoker')->comment('나의 장점');
            $table->string('ideal_type', 100)->default('')->after('merit')->comment('이상형');
            $table->string('hobby', 100)->default('')->after('ideal_type')->comment('취미/요즘 빠진 것');
            $table->string('tmi', 100)->default('')->after('hobby')->comment('TMI/대화 거리');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['merit', 'ideal_type', 'hobby', 'tmi']);
        });
    }
};
