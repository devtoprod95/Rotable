<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_statistics')) {
            Schema::create('site_statistics', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('total_visitors')->default(0)->comment('누적 방문자 수');
                $table->unsignedInteger('weekly_visitors')->default(0)->comment('최근 일주일 방문자 수');
                $table->unsignedInteger('yesterday_visitors')->default(0)->comment('어제 방문자 수');
                $table->timestamps();
                $table->comment('사이트 통계 데이터 테이블');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_statistics');
    }
};
