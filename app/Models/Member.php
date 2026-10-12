<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Member extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'members';

    /** @var list<string> */
    protected $fillable = ['event_id', 'name', 'phone', 'password'];

    /** @var list<string> */
    protected $hidden = ['password'];

    /**
     * 비밀번호는 요구사항에 따라 평문으로 저장한다.
     * (암호화/해싱하지 않으므로 'hashed' 캐스트를 사용하지 않는다.)
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * 입장/등록한 회차.
     *
     * @return BelongsTo<EventRound, Member>
     */
    public function eventRound(): BelongsTo
    {
        return $this->belongsTo(EventRound::class, 'event_id');
    }
}
