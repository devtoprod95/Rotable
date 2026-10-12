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
        Schema::table('admins', function (Blueprint $table) {
            if (! Schema::hasColumn('admins', 'is_active')) {
                $table->boolean('is_active')->nullable(false)->default(true)->comment('관리자 계정 활성화 여부');
            }

            if (! Schema::hasColumn('admins', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable(false)->useCurrent()->comment('마지막 로그인 시간');
            }

            if (! Schema::hasColumn('admins', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable(false)->default('0.0.0.0')->comment('마지막 로그인 IP 주소');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'last_login_at', 'last_login_ip']);
        });
    }
};
