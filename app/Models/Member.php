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

    /** 신청 플랫폼 */
    public const PLATFORM_MUNTO = 'munto'; // 문토
    public const PLATFORM_FRIP  = 'frip';  // 프립

    /**
     * 신청 플랫폼 셀렉트 옵션. (입장등록 화면에서 사용)
     *
     * @return array<int, array{id: string, label: string}>
     */
    public static function platformOptions(): array
    {
        return [
            ['id' => self::PLATFORM_MUNTO, 'label' => '문토'],
            ['id' => self::PLATFORM_FRIP, 'label' => '프립'],
        ];
    }

    /**
     * 이미 로그인된 상태에서도 입장/입장등록 화면 접근이 허용되는 예외 회원 이름.
     * (운영/디버깅 편의용)
     */
    public const BYPASS_REDIRECT_NAME = '김도한';

    /**
     * 주량 선택지 — 소주 기준 반 병 단위. (입장등록 셀렉트 / 서버 검증 공용, 저장 값은 문구 그대로)
     *
     * @return list<string>
     */
    public static function drinkCapacityOptions(): array
    {
        return ['못 마셔요', '소주 반 병', '소주 1병', '소주 1병 반', '소주 2병', '소주 2병 반', '소주 3병 이상'];
    }

    /**
     * 평균 음주 횟수 선택지.
     *
     * @return list<string>
     */
    public static function drinkFrequencyOptions(): array
    {
        return ['마시지 않아요', '한 달에 1~2번', '주 1번', '주 2~3번', '거의 매일'];
    }

    /**
     * 친구 약속 빈도 선택지.
     *
     * @return list<string>
     */
    public static function friendMeetFrequencyOptions(): array
    {
        return ['한 달에 1번 이하', '한 달에 2~3번', '주 1번', '주 2번 이상'];
    }

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
        // 생활 습관 — 주량/평균 음주 횟수/애완동물 여부/친구 약속 빈도
        'drink_capacity',
        'drink_frequency',
        'has_pet',
        'friend_meet_frequency',
        // 신청 플랫폼 (문토/프립)
        'platform',
        // 선택한 음료 (1:1 소개팅, event_drinks.id)
        'event_drink_id',
        // 매칭 결과 리뷰 인증 완료 시각
        'result_reviewed_at',
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
            'has_pet'    => 'boolean',
            'result_reviewed_at' => 'datetime',
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
     * 이 회원이 선택한 음료. (1:1 소개팅)
     *
     * @return BelongsTo<EventDrink, Member>
     */
    public function eventDrink(): BelongsTo
    {
        return $this->belongsTo(EventDrink::class, 'event_drink_id');
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
     * 로테이션 시작 전(0턴)에는 기준(홈) 테이블을 그대로 반환한다. 기준 좌석이 없으면 null.
     */
    public function currentTableNo(): ?int
    {
        $event = $this->eventRound;
        if ($event === null) {
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

        // 로테이션 시작 전(0턴)에는 아직 이동이 없으므로 기준(홈) 테이블을 그대로 쓴다.
        $round = (int) $event->current_round;
        if ($round < 1) {
            return (int) $homeTableNo;
        }

        $tableNos = RotationTable::where('event_id', $this->event_id)
            ->orderBy('table_no')
            ->pluck('table_no')
            ->all();

        return EventRound::seatedTableNoFor(
            round: $round,
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
