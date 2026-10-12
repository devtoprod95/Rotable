<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RotationRound extends Model
{
    /** @use HasFactory<\Database\Factories\RotationRoundFactory> */
    use HasFactory;

    protected $table = 'rotation_rounds';

    /** @var list<string> */
    protected $fillable = ['event_id', 'round_no'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'round_no' => 'integer',
        ];
    }

    /**
     * 이 턴이 속한 행사 회차.
     *
     * @return BelongsTo<EventRound, RotationRound>
     */
    public function eventRound(): BelongsTo
    {
        return $this->belongsTo(EventRound::class, 'event_id');
    }

    /**
     * 이 턴의 모든 좌석 기록.
     *
     * @return HasMany<RotationSeating>
     */
    public function seatings(): HasMany
    {
        return $this->hasMany(RotationSeating::class, 'rotation_round_id');
    }
}
