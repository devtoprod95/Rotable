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
        if (! Schema::hasTable('visitor_logs')) {
            Schema::create('visitor_logs', function (Blueprint $table) {
                $table->id();
                $table->string('ip', 45)->comment('방문자 IP 주소 (IPv6 포함)');
                $table->string('user_agent')->nullable()->comment('방문자 브라우저 및 기기 정보');
                $table->date('visited_at')->comment('방문 날짜');
                $table->timestamps();

                $table->index('visited_at');
                $table->index(['ip', 'visited_at']);
                $table->comment('방문자 데이터 테이블');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};
