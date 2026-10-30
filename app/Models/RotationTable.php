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
     * 이 테이블을 기준(홈) 자리로 가진 좌석들.
     *
     * @return HasMany<RotationSeat>
     */
    public function seats(): HasMany
    {
        return $this->hasMany(RotationSeat::class, 'rotation_table_id');
    }
}
