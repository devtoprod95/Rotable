<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * members 테이블에 "회원이 직접 입장(로그인)한 마지막 시각"을 더한다.
 *
 * - null 이면 기록 전. 입장등록 직후와 입장(로그인) 때 갱신한다.
 * - 관리자가 "회원으로 로그인"한 경우에는 기록하지 않는다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('drink_served_at')->comment('회원이 직접 입장한 마지막 시각 (null=기록 없음, 관리자 대리 로그인은 제외)');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('last_login_at');
        });
    }
};
