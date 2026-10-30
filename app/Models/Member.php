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
     * 이 회원의 로테이션 기준(홈) 좌석. (회원당 1행)
     *
     * @return HasMany<RotationSeat>
     */
    public function rotationSeats(): HasMany
    {
        return $this->hasMany(RotationSeat::class, 'member_id');
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
     * 현재 턴(event_rounds.current_round) 기준으로 이 회원이 앉아 있는 테이블 번호.
     * 기준 좌석이 없거나 로테이션 시작 전(current_round=0)이면 null.
     */
    public function currentTableNo(): ?int
    {
        $event = $this->eventRound;
        if ($event === null || (int) $event->current_round < 1) {
            return null;
        }

        $seat = RotationSeat::query()
            ->with('table')
            ->where('event_id', $this->event_id)
            ->where('member_id', $this->id)
            ->first();

        $homeTableNo = $seat?->table?->table_no;
        if ($homeTableNo === null) {
            return null;
        }

        $tableNos = RotationTable::where('event_id', $this->event_id)
            ->orderBy('table_no')
            ->pluck('table_no')
            ->all();

        return EventRound::seatedTableNoFor(
            round: (int) $event->current_round,
            isFemale: $this->gender === self::GENDER_FEMALE,
            homeTableNo: (int) $homeTableNo,
            tableNos: array_map('intval', $tableNos),
        );
    }

    /**
     * 현재 턴 기준으로 내가 앉은 테이블의 총 인원수.
     * 좌석이 없거나 로테이션 시작 전이면 0.
     *
     * "테이블 전원이 투표했는지" 판정 등 테이블 단위 집계에 쓰인다.
     */
    public function currentTableSeatCount(): int
    {
        $myTableNo = $this->currentTableNo();
        if ($myTableNo === null) {
            return 0;
        }

        $event = $this->eventRound;
        $round = (int) ($event?->current_round ?? 0);

        $tableNos = RotationTable::where('event_id', $this->event_id)
            ->orderBy('table_no')
            ->pluck('table_no')
            ->all();
        $tableNos = array_map('intval', $tableNos);

        // 같은 행사의 모든 기준 좌석을 현재 턴 공식으로 돌려, 내 테이블에 앉는 인원을 센다.
        $seats = RotationSeat::query()
            ->with(['member:id,gender', 'table:id,table_no'])
            ->where('event_id', $this->event_id)
            ->get();

        $count = 0;
        foreach ($seats as $seat) {
            $homeTableNo = $seat->table?->table_no;
            if ($homeTableNo === null) {
                continue;
            }

            $seatedNo = EventRound::seatedTableNoFor(
                round: $round,
                isFemale: $seat->member?->gender === self::GENDER_FEMALE,
                homeTableNo: (int) $homeTableNo,
                tableNos: $tableNos,
            );

            if ($seatedNo === $myTableNo) {
                $count++;
            }
        }

        return $count;
    }
}
