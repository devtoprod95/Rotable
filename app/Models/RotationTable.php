<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RotationTable extends Model
{
    /** @use HasFactory<\Database\Factories\RotationTableFactory> */
    use HasFactory;

    protected $table = 'rotation_tables';

    /** @var list<string> */
    protected $fillable = ['event_id', 'table_no'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'table_no' => 'integer',
        ];
    }

    /**
     * 이 테이블이 속한 행사 회차.
     *
     * @return BelongsTo<EventRound, RotationTable>
     */
    public function eventRound(): BelongsTo
    {
        return $this->belongsTo(EventRound::class, 'event_id');
    }

    /**
     * 이 테이블에서 발생한 모든 좌석 기록 (모든 턴).
     *
     * @return HasMany<RotationSeating>
     */
    public function seatings(): HasMany
    {
        return $this->hasMany(RotationSeating::class, 'rotation_table_id');
    }
}
