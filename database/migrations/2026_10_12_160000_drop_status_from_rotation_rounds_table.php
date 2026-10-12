<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 로테이션 상태는 event_rounds.status로 일원화한다.
     * rotation_rounds.status 는 더 이상 사용하지 않으므로 제거한다.
     * (이미 status 없이 생성된 환경에서는 아무 동작도 하지 않는다.)
     */
    public function up(): void
    {
        if (Schema::hasColumn('rotation_rounds', 'status')) {
            Schema::table('rotation_rounds', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('rotation_rounds', 'status')) {
            Schema::table('rotation_rounds', function (Blueprint $table) {
                $table->string('status', 1)->default('P')->comment('상태 (P: 대기, S: 진행, X: 종료)');
            });
        }
    }
};
