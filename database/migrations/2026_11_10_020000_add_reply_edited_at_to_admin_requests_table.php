<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 관리자 답장의 "수정됨" 표시용 컬럼.
     * - replied_at: 최초 답장 시각 (수정해도 바뀌지 않는다)
     * - reply_edited_at: 마지막 수정 시각 (null: 수정한 적 없음)
     */
    public function up(): void
    {
        Schema::table('admin_requests', function (Blueprint $table) {
            $table->timestamp('replied_at')->nullable()->comment('관리자가 최초 답장한 시각')->change();
            $table->timestamp('reply_edited_at')->nullable()->after('replied_at')->comment('관리자 답장 마지막 수정 시각 (null: 수정 안 함)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_requests', function (Blueprint $table) {
            $table->dropColumn('reply_edited_at');
            $table->timestamp('replied_at')->nullable()->comment('관리자가 답장한(마지막 수정) 시각')->change();
        });
    }
};
