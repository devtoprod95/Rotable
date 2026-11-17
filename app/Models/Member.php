<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /** 이상형 조건의 "상관없음" — 다른 항목과 함께 고를 수 없다. */
    public const PREFERENCE_ANY = '상관없음';

    /**
     * 다중 선택 항목 검증용: '상관없음'은 다른 항목과 함께 고를 수 없다.
     */
    public static function exclusiveAnyRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_array($value) && count($value) > 1 && in_array(self::PREFERENCE_ANY, $value, true)) {
                $fail('상관없음은 다른 항목과 함께 고를 수 없습니다.');
            }
        };
    }

    /**
     * 다중 선택 값을 선택지 목록의 순서대로 정렬한다. (고른 순서와 무관하게 누구에게나 같은 순서로 보이게)
     * 목록에 없는 값은 버린다.
     *
     * @param  list<string>|null  $values
     * @param  list<string>       $options  기준 순서(선택지 목록)
     * @return list<string>
     */
    public static function sortByOptions(?array $values, array $options): array
    {
        return array_values(array_filter($options, fn (string $option): bool => in_array($option, $values ?? [], true)));
    }

    /**
     * 종교 선택지.
     *
     * @return list<string>
     */
    public static function religionOptions(): array
    {
        return ['무교', '기독교', '천주교', '불교', '기타'];
    }

    /**
     * 이상형 외적 조건 선택지. (다중 선택, '상관없음'은 단독 선택)
     *
     * @return list<string>
     */
    public static function outerPreferenceOptions(): array
    {
        return ['얼굴', '몸매', '스타일', self::PREFERENCE_ANY];
    }

    /**
     * 이상형 내적 조건 선택지. (다중 선택, '상관없음'은 단독 선택)
     *
     * @return list<string>
     */
    public static function innerPreferenceOptions(): array
    {
        return ['성격', '가치관', '유머 감각', '배려심', '대화 코드', '생활 습관', '성실함', '경제관념', self::PREFERENCE_ANY];
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
        // 종교 / 이상형 조건(외적·내적)
        'religion',
        'outer_preference',
        'inner_preference',
        // 신청 플랫폼 (문토/프립)
        'platform',
        // 선택한 음료 (1:1 소개팅, event_drinks.id)
        'event_drink_id',
        // 음료 전달 완료 시각 (관리자 체크, null=전달 전)
        'drink_served_at',
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
    /**
     * 이상형 외적 조건 — 다중 선택 문구 배열(JSON).
     * 저장할 때도, 읽을 때도 선택지 목록 순서로 정렬해 고른 순서와 무관하게 늘 같은 순서로 보인다.
     *
     * @return Attribute<list<string>|null, list<string>|null>
     */
    protected function outerPreference(): Attribute
    {
        return $this->sortedListAttribute(self::outerPreferenceOptions());
    }

    /**
     * 이상형 내적 조건 — 다중 선택 문구 배열(JSON). (외적 조건과 같은 방식)
     *
     * @return Attribute<list<string>|null, list<string>|null>
     */
    protected function innerPreference(): Attribute
    {
        return $this->sortedListAttribute(self::innerPreferenceOptions());
    }

    /**
     * 정렬 접근자 생성 헬퍼.
     *
     * ⚠️ 반환 타입(: Attribute)을 선언하지 않는다. Eloquent 는 Attribute 를 반환 타입으로 가진 메서드를
     *    모두 "속성 접근자"로 보고 인자 없이 호출하므로, 인자가 필요한 이 헬퍼가 거기에 잡히면 안 된다.
     *
     * @param  list<string>  $options  기준 순서(선택지 목록)
     * @return Attribute<list<string>|null, list<string>|null>
     */
    private function sortedListAttribute(array $options)
    {
        return Attribute::make(
            get: fn (?string $value): ?array => $value === null
                ? null
                : self::sortByOptions(json_decode($value, true) ?: [], $options),
            set: fn (?array $value): ?string => $value === null
                ? null
                : json_encode(self::sortByOptions($value, $options), JSON_UNESCAPED_UNICODE),
        );
    }

    protected function casts(): array
    {
        return [
            'birth_year' => 'integer',
            'height'     => 'integer',
            'is_smoker'  => 'boolean',
            'has_pet'    => 'boolean',
            'result_reviewed_at' => 'datetime',
            'drink_served_at'    => 'datetime',
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
