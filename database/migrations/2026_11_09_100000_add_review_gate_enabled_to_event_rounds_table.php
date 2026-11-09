<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * event_rounds 테이블에 "리뷰 인증 게이트" on/off 토글(review_gate_enabled)을 추가한다.
 *
 * - true 이면, 종료(X) 후 참가자가 매칭 결과를 보기 전에 "리뷰 인증 이미지 업로드"를 거쳐야 한다.
 * - 행사(회차)별로 관리자가 켜고 끌 수 있다. 기본값은 false(끔).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rounds', function (Blueprint $table) {
            $table->boolean('review_gate_enabled')
                ->default(false)
                ->after('voting_opened_at')
                ->comment('리뷰 인증 게이트 사용 여부: true=결과 확인 전 리뷰 이미지 업로드 요구');
        });
    }

    public function down(): void
    {
        Schema::table('event_rounds', function (Blueprint $table) {
            $table->dropColumn('review_gate_enabled');
        });
    }
};
