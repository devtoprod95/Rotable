<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 회원 → 관리자 "요청하기" 메시지.
 */
class AdminRequest extends Model
{
    /** @use HasFactory<\Database\Factories\AdminRequestFactory> */
    use HasFactory;

    protected $table = 'admin_requests';

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'member_id',
        'table_no',
        'body',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * 요청한 회원.
     *
     * @return BelongsTo<Member, AdminRequest>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * 요청이 속한 행사.
     *
     * @return BelongsTo<EventRound, AdminRequest>
     */
    public function eventRound(): BelongsTo
    {
        return $this->belongsTo(EventRound::class, 'event_id');
    }
}
