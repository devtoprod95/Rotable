<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * members 테이블에 참가자가 선택한 음료(event_drinks.id)를 더한다.
 *
 * - 선택 전/선택 취소는 null.
 * - 관리자가 음료를 목록에서 지우면 선택값은 null 로 풀린다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->unsignedBigInteger('event_drink_id')->nullable()->after('platform')->comment('선택한 음료 (event_drinks.id)');

            $table->foreign('event_drink_id')->references('id')->on('event_drinks')->onUpdate('cascade')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['event_drink_id']);
            $table->dropColumn('event_drink_id');
        });
    }
};
