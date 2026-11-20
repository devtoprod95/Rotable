<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * event_rounds 에 플랫폼별 수수료율(%)을 더한다. 예) {"munto": 10, "frip": 15}
 *
 * - 매출 화면에서 결제 금액 중 플랫폼이 떼어 가는 금액을 계산할 때 쓴다. 없으면 0%.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rounds', function (Blueprint $table) {
            $table->json('platform_fee_rates')->nullable()->after('early_bird_rates')->comment('플랫폼별 수수료율(%) JSON: {platform: int}, 없으면 0');
        });
    }

    public function down(): void
    {
        Schema::table('event_rounds', function (Blueprint $table) {
            $table->dropColumn('platform_fee_rates');
        });
    }
};
