<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * members 테이블에 종교 · 이상형 조건(외적/내적)을 더한다. (입장등록 선택 항목)
 *
 * - 종교는 정해진 선택지의 문구를 그대로, 외적/내적 조건은 다중 선택이라 문구 배열(JSON)로 저장한다. (Member::religionOptions() / outerPreferenceOptions() / innerPreferenceOptions())
 * - 과거 가입자 호환을 위해 모두 nullable. (미입력이면 화면에서 숨기거나 '-' 표시)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('religion', 30)->nullable()->after('friend_meet_frequency')->comment('종교 (무교/기독교/천주교/불교/기타)');
            $table->json('outer_preference')->nullable()->after('religion')->comment('이상형 외적 조건 — 다중 선택 배열 (얼굴/몸매/스타일/상관없음)');
            $table->json('inner_preference')->nullable()->after('outer_preference')->comment('이상형 내적 조건 — 다중 선택 배열 (성격/가치관/유머 감각/배려심/대화 코드/생활 습관/성실함/경제관념/상관없음)');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['religion', 'outer_preference', 'inner_preference']);
        });
    }
};
