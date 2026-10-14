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
        if (Schema::hasTable('event_rounds') && ! Schema::hasColumn('event_rounds', 'created_by')) {
            Schema::table('event_rounds', function (Blueprint $table) {
                $table->foreignId('created_by')
                    ->after('voting_opened_at')
                    ->comment('행사를 생성한 관리자 id (admins.id)')
                    ->constrained('admins')
                    ->restrictOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('event_rounds') && Schema::hasColumn('event_rounds', 'created_by')) {
            Schema::table('event_rounds', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }
    }
};
