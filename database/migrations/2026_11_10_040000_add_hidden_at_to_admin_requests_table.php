<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 관리자 플로팅 위젯(회원 요청)에서 "숨기기"한 요청을 목록에서 숨기기 위한 컬럼.
     * - 물리 삭제하지 않는다. (요청 조회 모달·회원 화면에는 그대로 남는다)
     */
    public function up(): void
    {
        Schema::table('admin_requests', function (Blueprint $table) {
            $table->timestamp('hidden_at')->nullable()->after('read_at')->comment('관리자 위젯 목록에서 삭제(숨김)한 시각 (null: 노출)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_requests', function (Blueprint $table) {
            $table->dropColumn('hidden_at');
        });
    }
};
