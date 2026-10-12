<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50)->comment('이름');
                $table->string('phone', 20)->comment('핸드폰번호 (로그인 아이디)');
                $table->string('password')->comment('비밀번호');
                $table->unsignedBigInteger('event_id')->comment('event_rounds.id');

                $table->timestamps();
                $table->softDeletes();
                $table->comment('회원 데이터 테이블');

                $table->index('event_id');

                $table->foreign('event_id')->references('id')->on('event_rounds')->onUpdate('cascade')->onDelete('restrict');

                $table->unique(['event_id', 'phone']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
