<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    /** @use HasFactory<\Database\Factories\VoteFactory> */
    use HasFactory;

    protected $table = 'event_votes';

    /** 한 회원이 투표할 수 있는 최대 상대 수 */
    public const MAX_VOTES_PER_MEMBER = 2;

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'voter_id',
        'target_id',
    ];

    /**
     * 투표가 이루어진 행사.
     *
     * @return BelongsTo<EventRound, Vote>
     */
    public function eventRound(): BelongsTo
    {
        return $this->belongsTo(EventRound::class, 'event_id');
    }

    /**
     * 투표한 회원.
     *
     * @return BelongsTo<Member, Vote>
     */
    public function voter(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'voter_id');
    }

    /**
     * 지목당한 회원.
     *
     * @return BelongsTo<Member, Vote>
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'target_id');
    }
}
