<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * event_rounds.rotation_limit 추가 — 로테이션 횟수(총 턴 수)를 테이블 수와 별개로 지정.
 *
 *  - null : 테이블 수만큼 진행 (기존 동작. N개 테이블이면 N턴이 마지막)
 *  - k    : k턴에서 종료 (1 ≤ k ≤ 테이블 수). 테이블 수를 넘기면 같은 상대를 다시 만나므로 상한은 테이블 수.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('event_rounds', 'rotation_limit')) {
            Schema::table('event_rounds', function (Blueprint $table) {
                $table->unsignedSmallInteger('rotation_limit')
                    ->nullable()
                    ->after('current_round')
                    ->comment('로테이션 횟수(총 턴 수). null=테이블 수만큼, k=k턴에서 종료(최대 테이블 수)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('event_rounds', 'rotation_limit')) {
            Schema::table('event_rounds', function (Blueprint $table) {
                $table->dropColumn('rotation_limit');
            });
        }
    }
};
