<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 행사별 비용 항목 1건. (대관비 · 음료/다과 · 스태프 등)
 *
 * @property int    $id
 * @property int    $event_id   event_rounds.id
 * @property string $name       항목명
 * @property int    $amount     금액(원)
 * @property int    $sort_order 노출 순서
 */
class EventCost extends Model
{
    protected $table = 'event_costs';

    /** 한 행사에 등록할 수 있는 최대 비용 항목 수 */
    public const MAX_PER_EVENT = 30;

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'name',
        'amount',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount'     => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<EventRound, EventCost>
     */
    public function eventRound(): BelongsTo
    {
        return $this->belongsTo(EventRound::class, 'event_id');
    }
}
