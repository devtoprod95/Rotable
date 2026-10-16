<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * QR 코드 리다이렉트 링크 테이블.
     * - 관리자가 "연결 대상 URL(target_url)"을 등록하면 고유 코드(code)가 발급된다.
     * - 우리 사이트의 공개 주소 `/qr/{code}` 에 접근하면 target_url 로 302 리다이렉트한다.
     * - 이 공개 주소를 QR 코드로 만들어 배포하면, 대상 URL 이 바뀌어도 QR 은 그대로 재사용할 수 있다.
     */
    public function up(): void
    {
        if (! Schema::hasTable('qr_links')) {
            Schema::create('qr_links', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique()->comment('공개 리다이렉트 식별 코드 (/qr/{code})');
                $table->string('title')->nullable()->comment('관리용 라벨');
                $table->text('target_url')->comment('접근 시 이동할 대상 URL');
                $table->boolean('is_active')->default(true)->comment('활성 여부 (비활성 시 404)');
                $table->unsignedBigInteger('hit_count')->default(0)->comment('누적 접근(이동) 횟수');
                $table->unsignedBigInteger('created_by')->nullable()->comment('admins.id (생성 관리자)');

                $table->timestamps();
                $table->comment('QR 코드 리다이렉트 링크');

                $table->index('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_links');
    }
};
