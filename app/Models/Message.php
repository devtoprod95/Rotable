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

    /** 발신 주체: 회원 */
    public const SENDER_MEMBER = 'member';

    /** 발신 주체: 관리자 */
    public const SENDER_ADMIN = 'admin';

    /** 한 회원이 보낼 수 있는 최대 메시지 수 */
    public const MAX_SENDS_PER_MEMBER = 5;

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'sender_type',
        'sender_id',
        'sender_admin_id',
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
     * 관리자가 보낸 메시지인지 여부.
     */
    public function isFromAdmin(): bool
    {
        return $this->sender_type === self::SENDER_ADMIN;
    }

    /**
     * 보낸 관리자. (sender_type=admin 일 때만 값이 있다.)
     *
     * @return BelongsTo<Admin, Message>
     */
    public function senderAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sender_admin_id');
    }

    /**
     * 보낸 회원. (관리자 발송이면 null)
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
