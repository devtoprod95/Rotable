<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 행사별 음료 선택지. (1:1 소개팅에서 참가자가 고른다)
 *
 * @property int    $id
 * @property int    $event_id   event_rounds.id
 * @property string $name       음료 이름
 * @property int    $sort_order 노출 순서
 */
class EventDrink extends Model
{
    protected $table = 'event_drinks';

    /** 한 행사에 등록할 수 있는 최대 음료 수 */
    public const MAX_PER_EVENT = 30;

    /**
     * 1:1 행사 생성 시 자동으로 채우는 기본 음료. (음료 종류는 거의 바뀌지 않으므로 여기서 관리)
     *
     * @var list<string>
     */
    public const DEFAULT_NAMES = ['아메리카노(ICE)', '아메리카노(HOT)', '물(ICE)', '물(HOT)', '캐모마일(ICE)', '캐모마일(HOT)'];

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'name',
        'sort_order',
    ];

    /**
     * @return BelongsTo<EventRound, EventDrink>
     */
    public function eventRound(): BelongsTo
    {
        return $this->belongsTo(EventRound::class, 'event_id');
    }

    /**
     * 이 음료를 선택한 회원들.
     *
     * @return HasMany<Member>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'event_drink_id');
    }
}
