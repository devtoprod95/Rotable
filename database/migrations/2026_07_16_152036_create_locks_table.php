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
        if (! Schema::hasTable('locks')) {
            Schema::create('locks', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('status')->default('processing'); // processing, completed
                $table->integer('response_code')->nullable();
                $table->json('response_body')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locks');
    }
};
