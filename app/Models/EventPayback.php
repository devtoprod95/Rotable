<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 행사 페이백 대상 1명.
 *
 * - 체크인 명단(event_rosters) 1명당 1행이며, 행사가 끝난 뒤 결제 금액의 100% 또는 80%를 돌려준다.
 * - 페이백 금액 = 결제 금액(플랫폼·성별 정가에 얼리버드 할인을 반영한 실제 결제가) × 페이백 비율.
 * - 노쇼 여부는 저장하지 않고 체크인 명단(roster.checked_in_at)으로 판단한다.
 *
 * @property int                             $id
 * @property int                             $event_id
 * @property int                             $event_roster_id
 * @property int                             $payback_rate    100|80
 * @property bool                            $is_early_bird
 * @property int                             $paid_amount
 * @property int                             $payback_amount
 * @property string|null                     $bank_name
 * @property string|null                     $account_number  암호화 저장
 * @property string|null                     $account_holder
 * @property string                          $status          W|D
 * @property \Illuminate\Support\Carbon|null $transferred_at
 * @property int|null                        $transferred_by
 * @property string|null                     $memo
 */
class EventPayback extends Model
{
    protected $table = 'event_paybacks';

    public const RATE_FULL    = 100; // 페이백 100%
    public const RATE_PARTIAL = 80;  // 페이백 80%

    public const STATUS_WAITING = 'W'; // 입금 대기
    public const STATUS_DONE    = 'D'; // 입금 완료

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'event_roster_id',
        'payback_rate',
        'is_early_bird',
        'paid_amount',
        'payback_amount',
        'bank_name',
        'account_number',
        'account_holder',
        'status',
        'transferred_at',
        'transferred_by',
        'memo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payback_rate'   => 'integer',
            'is_early_bird'  => 'boolean',
            'paid_amount'    => 'integer',
            'payback_amount' => 'integer',
            'account_number' => 'encrypted',
            'transferred_at' => 'datetime',
        ];
    }

    /**
     * 페이백 비율 선택지.
     *
     * @return array<int, string>
     */
    public static function rateOptions(): array
    {
        return [
            self::RATE_PARTIAL => '페이백 80%',
            self::RATE_FULL    => '페이백 100%',
        ];
    }

    /**
     * 입금 은행 선택지. (목록에 없으면 '기타'를 고르고 메모에 적는다)
     *
     * @return list<string>
     */
    public static function bankOptions(): array
    {
        return [
            '국민', '신한', '우리', '하나', '농협', '기업', '카카오뱅크', '토스뱅크', '케이뱅크',
            '새마을금고', '우체국', '신협', '수협', 'SC제일', '씨티', '산업',
            'iM뱅크(대구)', '부산', '경남', '광주', '전북', '제주', '저축은행', '기타',
        ];
    }

    /**
     * 결제 금액과 비율로 페이백 금액을 계산한다. (원 단위 내림)
     */
    public static function calcAmount(int $paidAmount, int $rate): int
    {
        return intdiv(max(0, $paidAmount) * $rate, 100);
    }

    /**
     * @return BelongsTo<EventRound, EventPayback>
     */
    public function eventRound(): BelongsTo
    {
        return $this->belongsTo(EventRound::class, 'event_id');
    }

    /**
     * 페이백 대상자 (체크인 명단).
     *
     * @return BelongsTo<EventRoster, EventPayback>
     */
    public function roster(): BelongsTo
    {
        return $this->belongsTo(EventRoster::class, 'event_roster_id');
    }

    /**
     * 입금 완료 처리한 관리자.
     *
     * @return BelongsTo<Admin, EventPayback>
     */
    public function transferrer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'transferred_by');
    }

    /**
     * 입금 완료 여부.
     */
    public function isTransferred(): bool
    {
        return $this->status === self::STATUS_DONE;
    }
}
