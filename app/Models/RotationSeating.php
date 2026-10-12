<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RotationSeating extends Model
{
    /** @use HasFactory<\Database\Factories\RotationSeatingFactory> */
    use HasFactory;

    protected $table = 'rotation_seatings';

    /** @var list<string> */
    protected $fillable = ['rotation_round_id', 'rotation_table_id', 'member_id'];

    /**
     * 몇 번째 턴.
     *
     * @return BelongsTo<RotationRound, RotationSeating>
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(RotationRound::class, 'rotation_round_id');
    }

    /**
     * 어느 테이블.
     *
     * @return BelongsTo<RotationTable, RotationSeating>
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(RotationTable::class, 'rotation_table_id');
    }

    /**
     * 앉은 회원.
     *
     * @return BelongsTo<Member, RotationSeating>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
