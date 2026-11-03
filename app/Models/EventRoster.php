<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 사전 참여자 명단(화이트리스트) 1건.
 *
 * - 행사(회차)별로 "입장 가능한 참가자"를 미리 등록해 둔다.
 * - 입장등록/로그인 시 (event_id, phone) 이 존재해야 통과시킨다. (사전 명단은 항상 강제)
 * - checked_in_at: 당일 참석(체크인) 시각. null 이면 미참석(노쇼).
 *
 * @property int         $id
 * @property int         $event_id
 * @property string|null $name
 * @property string|null $gender
 * @property string      $phone
 * @property \Illuminate\Support\Carbon|null $checked_in_at
 * @property string|null $memo
 */
class EventRoster extends Model
{
    protected $table = 'event_rosters';

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'name',
        'gender',
        'phone',
        'checked_in_at',
        'memo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
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
     * 체크인(참석) 여부.
     */
    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }
}
