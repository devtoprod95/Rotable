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
        if (! Schema::hasTable('rotation_tables')) {
            Schema::create('rotation_tables', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id')->comment('event_rounds.id (행사 회차)');
                $table->unsignedSmallInteger('table_no')->comment('테이블 번호');

                $table->timestamps();
                $table->comment('로테이션 소개팅 테이블(자리) 정의');

                $table->index('event_id');

                $table->foreign('event_id')->references('id')->on('event_rounds')->onUpdate('cascade')->onDelete('cascade');

                $table->unique(['event_id', 'table_no']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rotation_tables');
    }
};
