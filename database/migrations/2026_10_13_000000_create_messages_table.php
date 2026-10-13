<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 회원 간 1:1 메시지 전달 테이블.
     * - 같은 행사(event_id)에 참여한 회원끼리 이성에게 메시지를 보낸다.
     * - read_at 으로 수신자의 읽음 여부 및 안읽은 개수(뱃지)를 판정한다.
     */
    public function up(): void
    {
        if (! Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id')->comment('event_rounds.id (어느 행사에서의 대화인지)');
                $table->unsignedBigInteger('sender_id')->comment('members.id (보낸 회원)');
                $table->unsignedBigInteger('receiver_id')->comment('members.id (받는 회원)');
                $table->text('body')->comment('메시지 내용');
                $table->timestamp('read_at')->nullable()->comment('수신자가 읽은 시각 (null: 안읽음)');

                $table->timestamps();
                $table->comment('회원 간 1:1 메시지');

                $table->index('event_id');
                $table->index('sender_id');
                $table->index(['receiver_id', 'read_at']);

                $table->foreign('event_id')->references('id')->on('event_rounds')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('sender_id')->references('id')->on('members')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('receiver_id')->references('id')->on('members')->onUpdate('cascade')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
