<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 관리자가 참가자에게 보내는 메시지를 지원한다.
     * - sender_type 으로 발신 주체(회원/관리자)를 명시한다.
     * - 관리자 발송이면 sender_id 는 null, sender_admin_id 에 보낸 관리자를 기록한다.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('sender_id')->nullable()->comment('members.id (보낸 회원, 관리자 발송이면 null)')->change();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->string('sender_type', 10)->default('member')->after('event_id')->comment('발신 주체 (member: 회원, admin: 관리자)');
            $table->unsignedBigInteger('sender_admin_id')->nullable()->after('sender_id')->comment('admins.id (sender_type=admin 일 때 보낸 관리자)');

            $table->index('sender_admin_id');
            $table->foreign('sender_admin_id')->references('id')->on('admins')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['sender_admin_id']);
            $table->dropIndex(['sender_admin_id']);
            $table->dropColumn(['sender_type', 'sender_admin_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('sender_id')->nullable(false)->comment('members.id (보낸 회원)')->change();
        });
    }
};
