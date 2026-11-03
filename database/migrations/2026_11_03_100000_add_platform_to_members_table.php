<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * members 테이블에 "신청 플랫폼"(platform) 컬럼을 추가한다.
 *
 * - 참가자가 어느 외부 플랫폼(문토/프립)에서 신청해 들어왔는지 구분한다.
 * - 기본값은 'munto'(문토), NOT NULL. (과거 가입자/미지정도 문토로 간주)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('platform', 20)
                ->default('munto')
                ->after('tmi')
                ->comment('신청 플랫폼: munto(문토) | frip(프립)');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
    }
};
