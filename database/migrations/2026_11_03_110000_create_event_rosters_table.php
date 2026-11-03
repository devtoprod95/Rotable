<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 사전 참여자 명단(화이트리스트) 테이블.
 *
 * - 관리자가 행사(회차)별로 "입장 가능한 참가자"를 미리 등록한다.
 * - 입장등록/로그인 시 (event_id, phone) 이 이 명단에 있어야 통과시킨다. (행사별 강제 토글 ON 일 때)
 * - checked_in_at: 당일 실제 참석(체크인) 시각. null 이면 미참석(노쇼). 관리자가 토글로 체크한다.
 * - 네이밍 규칙상 행사 도메인 → event_ prefix.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('event_rounds')
                ->cascadeOnDelete()
                ->comment('소속 행사(회차)');
            $table->string('name', 50)->nullable()->comment('예정자 이름/닉네임 (선택)');
            $table->string('phone', 20)->comment('입장 허용 기준 전화번호 (숫자만)');
            $table->timestamp('checked_in_at')->nullable()->comment('당일 참석(체크인) 시각. null=미참석(노쇼)');
            $table->string('memo', 255)->nullable()->comment('메모 (선택)');
            $table->timestamps();

            // 같은 행사 안에서 전화번호 중복 금지.
            $table->unique(['event_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_rosters');
    }
};
