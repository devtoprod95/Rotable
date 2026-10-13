<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Member extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'members';

    /** 성별 */
    public const GENDER_MALE   = 'M'; // 남
    public const GENDER_FEMALE = 'F'; // 여

    /**
     * 이미 로그인된 상태에서도 입장/입장등록 화면 접근이 허용되는 예외 회원 이름.
     * (운영/디버깅 편의용)
     */
    public const BYPASS_REDIRECT_NAME = '김도한';

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'name',
        'phone',
        'password',
        'gender',
        'birth_year',
        'job',
        'height',
        'mbti',
        'region',
        'is_smoker',
    ];

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
        return [
            'birth_year' => 'integer',
            'height'     => 'integer',
            'is_smoker'  => 'boolean',
        ];
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

    /**
     * 이 회원의 모든 좌석 기록 (턴별 어느 테이블에 앉았는지).
     * 성별과 무관하게 모든 좌석이 여기에 기록된다.
     *
     * @return HasMany<RotationSeating>
     */
    public function seatings(): HasMany
    {
        return $this->hasMany(RotationSeating::class, 'member_id');
    }

    /**
     * 이 회원이 보낸 메시지들.
     *
     * @return HasMany<Message>
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * 이 회원이 받은 메시지들.
     *
     * @return HasMany<Message>
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /**
     * 현재(가장 최신 턴 기준) 이 회원이 앉아 있는 테이블 번호.
     * 좌석 기록이 없으면 null 을 반환한다.
     */
    public function currentTableNo(): ?int
    {
        $seating = RotationSeating::query()
            ->with(['round', 'table'])
            ->where('member_id', $this->id)
            ->whereHas('round', fn ($q) => $q->where('event_id', $this->event_id))
            ->get()
            ->sortByDesc(fn (RotationSeating $s) => $s->round?->round_no ?? -1)
            ->first();

        return $seating?->table?->table_no;
    }
}
