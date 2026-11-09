<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * members 테이블에 "매칭 결과 리뷰 인증" 완료 시각을 추가한다.
 *
 * - result_reviewed_at : 리뷰 인증을 완료한 시각. 이 값이 있으면 결과 게이트를 통과한 것으로 본다.
 *
 * 업로드한 이미지는 저장하지 않는다(진위도 검사하지 않음). 형식(이미지 파일)만 확인하고
 * 인증 여부만 이 컬럼으로 기록한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('result_reviewed_at')
                ->nullable()
                ->after('platform')
                ->comment('매칭 결과 리뷰 인증 완료 시각 (값이 있으면 결과 게이트 통과)');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('result_reviewed_at');
        });
    }
};
