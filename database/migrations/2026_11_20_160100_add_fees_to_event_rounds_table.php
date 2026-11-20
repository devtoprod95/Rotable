<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * event_rounds 에 페이백 계산용 참가비를 더한다.
 *
 * - fee_prices: 플랫폼 × 성별 정가. 예) {"munto": {"M": 39000, "F": 29000}, "frip": {"M": 42000, "F": 32000}}
 *   (플랫폼이 늘어나도 Member::platformOptions() 만 늘리면 되도록 JSON 으로 둔다)
 * - early_bird_rates: 플랫폼별 얼리버드 할인율(%). 예) {"munto": 20, "frip": 10}
 *   얼리버드 적용자는 정가 × (100 - 그 플랫폼 할인율)% 를 결제한 것으로 본다.
 * - 페이백 대상자를 추가할 때 결제 금액을 채우는 기본값이며, 사람마다 결제 금액은 따로 고칠 수 있다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rounds', function (Blueprint $table) {
            $table->json('fee_prices')->nullable()->after('drink_enabled')->comment('플랫폼×성별 참가비 정가(원) JSON: {platform: {M: int, F: int}}');
            $table->json('early_bird_rates')->nullable()->after('fee_prices')->comment('플랫폼별 얼리버드 할인율(%) JSON: {platform: int}, 없으면 0');
        });
    }

    public function down(): void
    {
        Schema::table('event_rounds', function (Blueprint $table) {
            $table->dropColumn(['fee_prices', 'early_bird_rates']);
        });
    }
};
