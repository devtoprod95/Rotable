<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 로테이션 기준(홈) 좌석 — 회원당 1행.
 *
 * 여자는 이 테이블에 고정, 남자는 1턴 출발 테이블이다.
 * t턴에서 실제 앉는 테이블은 저장하지 않고 EventRound::seatedTableNoFor() 로 계산한다.
 */
class RotationSeat extends Model
{
    /** @use HasFactory<\Database\Factories\RotationSeatFactory> */
    use HasFactory;

    protected $table = 'rotation_seats';

    /** @var list<string> */
    protected $fillable = ['event_id', 'member_id', 'rotation_table_id'];

    /**
     * 이 좌석이 속한 행사 회차.
     *
     * @return BelongsTo<EventRound, RotationSeat>
     */
    public function eventRound(): BelongsTo
    {
        return $this->belongsTo(EventRound::class, 'event_id');
    }

    /**
     * 기준(홈) 테이블.
     *
     * @return BelongsTo<RotationTable, RotationSeat>
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(RotationTable::class, 'rotation_table_id');
    }

    /**
     * 앉은 회원.
     *
     * @return BelongsTo<Member, RotationSeat>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
