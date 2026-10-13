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
    public const STATUS_VOTING   = 'V'; // 투표중
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
            self::STATUS_VOTING  => '매칭중',
            self::STATUS_CLOSED  => '종료',
        ];
    }

    protected $table = 'event_rounds';

    /** @var list<string> */
    protected $fillable = ['title', 'type', 'status', 'event_time', 'voting_opened_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_time'       => 'datetime',
            'voting_opened_at' => 'datetime',
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
     * 이 회차에서 오간 메시지들.
     *
     * @return HasMany<Message>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'event_id');
    }

    /**
     * 이 회차에서 이루어진 투표들.
     *
     * @return HasMany<Vote>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'event_id');
    }

    /**
     * 이 회차에서 회원이 관리자에게 보낸 요청들.
     *
     * @return HasMany<AdminRequest>
     */
    public function adminRequests(): HasMany
    {
        return $this->hasMany(AdminRequest::class, 'event_id');
    }

    /**
     * 투표가 자동으로 마감되어야 하는 시각(= 행사 다음 날 오전 9시).
     * 행사 시간이 없으면 null 을 반환한다.
     */
    public function votingClosesAt(): ?\Illuminate\Support\Carbon
    {
        if (! $this->event_time) {
            return null;
        }

        return $this->event_time->copy()->addDay()->setTime(9, 0, 0);
    }

    /**
     * 현재 투표가 열려 있고(V), 아직 자동 마감 시각이 지나지 않았는지 여부.
     */
    public function isVotingOpen(): bool
    {
        if ($this->status !== self::STATUS_VOTING) {
            return false;
        }

        $closesAt = $this->votingClosesAt();

        return $closesAt === null || now()->lt($closesAt);
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
