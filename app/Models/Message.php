<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    /** @use HasFactory<\Database\Factories\MessageFactory> */
    use HasFactory;

    protected $table = 'messages';

    /** 한 회원이 보낼 수 있는 최대 메시지 수 */
    public const MAX_SENDS_PER_MEMBER = 5;

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'sender_id',
        'receiver_id',
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
     * 보낸 회원.
     *
     * @return BelongsTo<Member, Message>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'sender_id');
    }

    /**
     * 받는 회원.
     *
     * @return BelongsTo<Member, Message>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'receiver_id');
    }
}
