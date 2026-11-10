<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 회원 요청(admin_requests)에 관리자 답장을 1건 달 수 있게 한다. (수정 시 덮어쓴다)
     */
    public function up(): void
    {
        Schema::table('admin_requests', function (Blueprint $table) {
            $table->text('reply_body')->nullable()->after('body')->comment('관리자 답장 내용 (null: 답장 없음)');
            $table->timestamp('replied_at')->nullable()->after('reply_body')->comment('관리자가 답장한(마지막 수정) 시각');
            $table->unsignedBigInteger('replied_admin_id')->nullable()->after('replied_at')->comment('admins.id (답장한 관리자)');

            $table->foreign('replied_admin_id')->references('id')->on('admins')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_requests', function (Blueprint $table) {
            $table->dropForeign(['replied_admin_id']);
            $table->dropColumn(['reply_body', 'replied_at', 'replied_admin_id']);
        });
    }
};
