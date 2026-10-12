<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventRound extends Model
{
    /** @use HasFactory<\Database\Factories\EventRoundFactory> */
    use HasFactory;

    /** 타입 */
    public const TYPE_WINE = 'wine'; // 와인파티
    public const TYPE_ONE  = 'one';  // 1:1

    /** 상태 */
    public const STATUS_PENDING  = 'P'; // 대기
    public const STATUS_STARTED  = 'S'; // 진행
    public const STATUS_CLOSED   = 'X'; // 종료

    /**
     * 타입 셀렉트 옵션.
     *
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_WINE => '와인파티',
            self::TYPE_ONE  => '1:1',
        ];
    }

    /**
     * 상태 셀렉트 옵션. (상세 수정에서 사용)
     *
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => '대기',
            self::STATUS_STARTED => '진행',
            self::STATUS_CLOSED  => '종료',
        ];
    }

    protected $table = 'event_rounds';

    /** @var list<string> */
    protected $fillable = ['title', 'type', 'status', 'event_time'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_time' => 'datetime',
        ];
    }

    /**
     * 이 회차로 입장/등록한 회원들.
     *
     * @return HasMany<Member>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'event_id');
    }

    /**
     * 이 회차의 로테이션 테이블들.
     *
     * @return HasMany<RotationTable>
     */
    public function rotationTables(): HasMany
    {
        return $this->hasMany(RotationTable::class, 'event_id');
    }

    /**
     * 셀렉트박스 표시용 라벨.
     * 예) "로테이블 와인파티 (2026년 1월 10일 토요일 19:00)"
     */
    public function getLabelAttribute(): string
    {
        $weekdays = ['일요일', '월요일', '화요일', '수요일', '목요일', '금요일', '토요일'];
        $time     = $this->event_time;

        if (! $time) {
            return $this->title;
        }

        $formatted = $time->format('Y') . '/'
            . $time->format('n') . '/'
            . $time->format('j') . ' '
            . $time->format('H:i') . ' '
            . $weekdays[(int) $time->format('w')];

        return "{$this->title} ({$formatted})";
    }
}
