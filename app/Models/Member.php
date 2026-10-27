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
        // 추가 프로필 (모든 유형 공통) — 장점/이상형/취미/TMI
        'merit',
        'ideal_type',
        'hobby',
        'tmi',
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
     * 이 회원이 한 투표들 (내가 지목한 상대).
     *
     * @return HasMany<Vote>
     */
    public function castVotes(): HasMany
    {
        return $this->hasMany(Vote::class, 'voter_id');
    }

    /**
     * 이 회원이 받은 투표들 (나를 지목한 사람).
     *
     * @return HasMany<Vote>
     */
    public function receivedVotes(): HasMany
    {
        return $this->hasMany(Vote::class, 'target_id');
    }

    /**
     * 이 회원이 관리자에게 보낸 요청들.
     *
     * @return HasMany<AdminRequest>
     */
    public function adminRequests(): HasMany
    {
        return $this->hasMany(AdminRequest::class, 'member_id');
    }

    /**
     * 현재(가장 최신 턴 기준) 이 회원이 앉아 있는 테이블 번호.
     * 좌석 기록이 없으면 null 을 반환한다.
     */
    public function currentTableNo(): ?int
    {
        // 가장 최신 턴(round_no 역순) 1건만 DB에서 바로 가져온다.
        // (전체 좌석을 메모리에 올려 PHP로 정렬하지 않도록 join + orderBy 로 처리)
        $seating = RotationSeating::query()
            ->with('table')
            ->whereHas('round', fn ($q) => $q->where('event_id', $this->event_id))
            ->where('member_id', $this->id)
            ->join('rotation_rounds', 'rotation_rounds.id', '=', 'rotation_seatings.rotation_round_id')
            ->orderByDesc('rotation_rounds.round_no')
            ->select('rotation_seatings.*')
            ->first();

        return $seating?->table?->table_no;
    }

    /**
     * 현재(가장 최신 턴 기준) 내가 앉아 있는 테이블의 총 인원수.
     * 좌석 기록이 없으면 0 을 반환한다.
     *
     * "테이블 전원이 투표했는지" 판정 등 테이블 단위 집계에 쓰인다.
     */
    public function currentTableSeatCount(): int
    {
        // 내 최신 좌석(턴 + 테이블)을 먼저 찾는다.
        $mySeating = RotationSeating::query()
            ->whereHas('round', fn ($q) => $q->where('event_id', $this->event_id))
            ->where('member_id', $this->id)
            ->join('rotation_rounds', 'rotation_rounds.id', '=', 'rotation_seatings.rotation_round_id')
            ->orderByDesc('rotation_rounds.round_no')
            ->select('rotation_seatings.*')
            ->first();

        if ($mySeating === null) {
            return 0;
        }

        // 같은 턴 + 같은 테이블에 앉은 모든 좌석 수.
        return RotationSeating::query()
            ->where('rotation_round_id', $mySeating->rotation_round_id)
            ->where('rotation_table_id', $mySeating->rotation_table_id)
            ->count();
    }
}
