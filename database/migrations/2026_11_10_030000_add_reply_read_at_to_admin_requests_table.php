<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 회원 하단 '요청' 탭 뱃지용 — 회원이 관리자 답장을 확인한 시각.
     * - 답장 등록/수정 시 null 로 되돌려 "새 답장"으로 다시 표시한다.
     */
    public function up(): void
    {
        Schema::table('admin_requests', function (Blueprint $table) {
            $table->timestamp('reply_read_at')->nullable()->after('reply_edited_at')->comment('회원이 관리자 답장을 확인한 시각 (null: 안읽음)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_requests', function (Blueprint $table) {
            $table->dropColumn('reply_read_at');
        });
    }
};
