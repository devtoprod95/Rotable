<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 행사 페이백 대상 테이블.
 *
 * - 체크인 명단(event_rosters) 1명당 1행. 행사가 끝난 뒤 참가비의 100% 또는 80%를 돌려준다.
 * - 페이백 금액 = 결제 금액(플랫폼·성별 정가에 얼리버드 할인을 반영한 실제 결제가) × 페이백 비율.
 * - 노쇼 여부는 따로 저장하지 않고 체크인(event_rosters.checked_in_at) 기준으로 판단한다.
 * - 계좌번호는 애플리케이션 키로 암호화(encrypted cast)해 저장한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('event_paybacks')) {
            Schema::create('event_paybacks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id')->comment('event_rounds.id (어느 행사의 페이백인지)');
                $table->unsignedBigInteger('event_roster_id')->comment('event_rosters.id (페이백 대상자, 1명당 1행)');
                $table->unsignedTinyInteger('payback_rate')->comment('페이백 비율(%): 100=페이백 100%, 80=페이백 80%');
                $table->boolean('is_early_bird')->default(false)->comment('얼리버드 적용 여부: true=얼리버드가로 결제');
                $table->unsignedInteger('paid_amount')->comment('결제 금액(원). 얼리버드 적용 여부에 따른 실제 결제가');
                $table->unsignedInteger('payback_amount')->comment('페이백 금액(원) = 결제 금액 × 페이백 비율');
                $table->string('bank_name', 30)->nullable()->comment('입금 은행명');
                $table->text('account_number')->nullable()->comment('입금 계좌번호 (암호화 저장)');
                $table->string('account_holder', 30)->nullable()->comment('예금주');
                $table->char('status', 1)->default('W')->comment('입금 상태: W=입금 대기, D=입금 완료');
                $table->timestamp('transferred_at')->nullable()->comment('입금 완료 처리 시각');
                $table->unsignedBigInteger('transferred_by')->nullable()->comment('입금 완료 처리한 관리자 (admins.id)');
                $table->string('memo', 255)->nullable()->comment('메모');

                $table->timestamps();
                $table->comment('행사 페이백 대상');

                $table->unique('event_roster_id', 'event_paybacks_roster_unique');
                $table->index(['event_id', 'status'], 'event_paybacks_event_status_index');

                $table->foreign('event_id')->references('id')->on('event_rounds')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('event_roster_id')->references('id')->on('event_rosters')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('transferred_by')->references('id')->on('admins')->onUpdate('cascade')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_paybacks');
    }
};
