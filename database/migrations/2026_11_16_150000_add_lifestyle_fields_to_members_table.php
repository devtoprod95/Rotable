<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * members 테이블에 생활 습관 프로필을 더한다. (입장등록에서 입력)
 *
 * - 주량 / 평균 음주 횟수 / 친구 약속 빈도는 정해진 선택지의 문구를 그대로 저장한다. (Member::*Options())
 * - 애완동물 여부는 true/false.
 * - 과거 가입자 호환을 위해 모두 nullable. (미입력이면 화면에서 숨기거나 '-' 표시)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('drink_capacity', 30)->nullable()->after('tmi')->comment('주량');
            $table->string('drink_frequency', 30)->nullable()->after('drink_capacity')->comment('평균 음주 횟수');
            $table->boolean('has_pet')->nullable()->after('drink_frequency')->comment('애완동물 여부 (1 있음 / 0 없음)');
            $table->string('friend_meet_frequency', 30)->nullable()->after('has_pet')->comment('친구 약속 빈도');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['drink_capacity', 'drink_frequency', 'has_pet', 'friend_meet_frequency']);
        });
    }
};
