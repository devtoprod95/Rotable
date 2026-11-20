<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * event_rounds 참가비 컬럼 보정.
 *
 * 2026_11_20_160100 마이그레이션은 처음에 정가/얼리버드가(fee_regular, fee_early_bird) 두 칸으로 만들었다가,
 * 플랫폼 × 성별 정가(fee_prices) + 플랫폼별 얼리버드 할인율(early_bird_rates) 구조로 바뀌었다.
 * 예전 버전이 이미 적용된 DB 는 새 컬럼이 없어 행사 정보 저장이 실패하므로, 여기서 어떤 상태든 새 구조로 맞춘다.
 *
 * - 새 컬럼이 없으면 만든다. (이미 있으면 그대로)
 * - 예전 컬럼(fee_regular, fee_early_bird, early_bird_rate)이 있으면 지운다. (플랫폼·성별 구분이 없어 옮길 수 없는 값)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rounds', function (Blueprint $table) {
            if (! Schema::hasColumn('event_rounds', 'fee_prices')) {
                $table->json('fee_prices')->nullable()->after('drink_enabled')->comment('플랫폼×성별 참가비 정가(원) JSON: {platform: {M: int, F: int}}');
            }
            if (! Schema::hasColumn('event_rounds', 'early_bird_rates')) {
                $table->json('early_bird_rates')->nullable()->after('fee_prices')->comment('플랫폼별 얼리버드 할인율(%) JSON: {platform: int}, 없으면 0');
            }
        });

        $old = array_values(array_filter(
            ['fee_regular', 'fee_early_bird', 'early_bird_rate'],
            fn (string $column): bool => Schema::hasColumn('event_rounds', $column),
        ));
        if ($old !== []) {
            Schema::table('event_rounds', function (Blueprint $table) use ($old) {
                $table->dropColumn($old);
            });
        }
    }

    /**
     * 되돌리지 않는다. (예전 컬럼 구조로 돌아가면 현재 코드가 동작하지 않으므로)
     */
    public function down(): void
    {
    }
};
