<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 상세페이지(홍보용) 제작 결과 저장 테이블.
     * - 유형(type)별로 1행만 둔다. (wine=와인파티 / solo=1:1 소개팅)
     * - 기존에는 storage 파일(detail_{type}.html)로 저장했으나, 배포(Railway 등) 시
     *   컨테이너 파일시스템이 초기화되어 유실되므로 DB 영속 저장으로 전환한다.
     * - "누가·언제 저장했는지" 메타도 같은 행에 함께 보관한다.
     */
    public function up(): void
    {
        if (! Schema::hasTable('event_page_details')) {
            Schema::create('event_page_details', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20)->unique()->comment('상세페이지 유형: wine(와인파티) | solo(1:1 소개팅)');
                $table->longText('html')->nullable()->comment('에디터로 작성한 상세페이지 HTML 전체');
                $table->string('saved_by')->nullable()->comment('마지막으로 저장한 관리자 이름(admins.name)');
                $table->string('saved_by_username')->nullable()->comment('마지막으로 저장한 관리자 아이디(admins.username)');
                $table->timestamp('saved_at')->nullable()->comment('마지막 저장 시각');

                $table->timestamps();
                $table->comment('상세페이지(홍보용) 제작 결과 (유형별 1행)');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_page_details');
    }
};
