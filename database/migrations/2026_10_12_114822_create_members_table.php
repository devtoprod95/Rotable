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
                $table->string('phone', 20)->comment('핸드폰 번호 (로그인 아이디)');
                $table->string('password')->comment('비밀번호');
                $table->unsignedBigInteger('event_id')->comment('event_rounds.id');

                $table->char('gender', 1)->comment('성별 (M: 남, F: 여)');
                $table->year('birth_year')->comment('출생연도');
                $table->string('job', 50)->comment('직업');
                $table->unsignedSmallInteger('height')->comment('키 (cm)');
                $table->string('mbti', 4)->comment('MBTI');
                $table->string('region', 50)->comment('지역/거주지');
                $table->boolean('is_smoker')->comment('흡연 여부 (true: 흡연, false: 비흡연)');

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
