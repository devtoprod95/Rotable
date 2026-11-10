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
        'reply_body',
        'replied_at',
        'reply_edited_at',
        'reply_read_at',
        'replied_admin_id',
        'read_at',
        'hidden_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at'    => 'datetime',
            'replied_at' => 'datetime',
            'reply_edited_at' => 'datetime',
            'reply_read_at'   => 'datetime',
            'hidden_at'       => 'datetime',
        ];
    }

    /**
     * 관리자 답장이 있는지 여부.
     */
    public function hasReply(): bool
    {
        return $this->reply_body !== null && $this->reply_body !== '';
    }

    /**
     * 답장한 관리자.
     *
     * @return BelongsTo<Admin, AdminRequest>
     */
    public function repliedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'replied_admin_id');
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
