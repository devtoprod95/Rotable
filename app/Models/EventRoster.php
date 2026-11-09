<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 체크인 명단 1건.
 *
 * - 행사(회차)별로 "신청자"를 닉네임·플랫폼·성별로 미리 적어 두고, 당일 참석(체크인)을 표시한다.
 * - 문토/프립 신청자는 전화번호를 알 수 없으므로 번호 기반 연동(입장 제한·자동 체크인)은 하지 않는다.
 *   회원(members)과 자동으로 연결되지 않는 독립 목록이다.
 * - checked_in_at: 당일 참석(체크인) 시각. null 이면 미참석(노쇼).
 *
 * @property int         $id
 * @property int         $event_id
 * @property string|null $name       닉네임(플랫폼 신청 닉네임)
 * @property string|null $gender     M|F
 * @property string      $platform   munto|frip
 * @property int|null    $ticket_no  번호표 숫자(같은 행사·같은 성별 안에서 유일). null=미배정
 * @property int|null    $rotation_table_id 체크인 때 지정한 기준(홈) 테이블 (rotation_tables.id)
 * @property \Illuminate\Support\Carbon|null $checked_in_at
 */
class EventRoster extends Model
{
    protected $table = 'event_rosters';

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'name',
        'gender',
        'platform',
        'ticket_no',
        'rotation_table_id',
        'checked_in_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'ticket_no'     => 'integer',
            'rotation_table_id' => 'integer',
        ];
    }

    /**
     * 소속 행사(회차).
     *
     * @return BelongsTo<EventRound, EventRoster>
     */
    public function eventRound(): BelongsTo
    {
        return $this->belongsTo(EventRound::class, 'event_id');
    }

    /**
     * 체크인 때 지정한 기준(홈) 테이블.
     *
     * @return BelongsTo<RotationTable, EventRoster>
     */
    public function rotationTable(): BelongsTo
    {
        return $this->belongsTo(RotationTable::class, 'rotation_table_id');
    }

    /**
     * 체크인(참석) 여부.
     */
    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }
}
