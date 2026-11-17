<?php

namespace App\Services\Rotation;

use App\Events\MemberDrinkChosen;
use App\Events\MemberDrinkServed;
use App\Models\EventRoster;
use App\Models\EventRound;
use App\Models\Member;
use App\Models\Message;
use App\Models\RotationSeat;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;

/**
 * 관리자가 입장 회원(members)의 정보를 수정하는 도메인 서비스.
 *
 * 번호표(members.name)·성별은 체크인 명단(event_rosters)과 (행사, 성별, 번호표)로 연결되는 값이다.
 * 좌석(rotation_seats)·로테이션 이동·투표 대상(이성)·관리자 라벨이 이 값에 의존하므로 아래 규칙으로만 바꾼다.
 *  - 바꾸려는 (성별, 번호표)를 체크인 명단의 다른 행이나 다른 회원이 쓰고 있으면 거부한다.
 *  - 연결된 체크인 명단 행의 번호표·성별도 같은 트랜잭션에서 함께 바꾼다.
 *  - 성별은 투표·메시지 기록이 있으면 바꿀 수 없다. (투표/메시지는 이성끼리만 성립하므로 기록이 깨진다)
 *  - 1:1 소개팅은 테이블당 같은 성별 1명이므로, 바꾼 성별이 이미 그 테이블에 앉아 있으면 거부한다.
 *  - 테이블은 여기서 바꾸지 않는다. (체크인 화면의 "번호·테이블 변경")
 *  - 신청 플랫폼은 관리자 라벨이 체크인 명단 값을 우선하므로 여기서 바꾸지 않는다.
 *
 * 그 밖에 수정할 수 있는 값
 *  - 연락처(핸드폰, 행사 안에서 유일) · 비밀번호(최고관리자만) · 프로필 · 생활 습관 · 자기소개 · 음료(1:1)
 *  - 키·출생연도·직업은 체크인 명단에도 같은 값을 반영한다. (RosterService::syncProfileFromMember)
 */
class MemberProfileService
{
    /** 요청 값이 있으면 그대로 저장하는 단순 컬럼 */
    private const PLAIN_FIELDS = [
        'phone', 'region', 'job', 'height', 'birth_year',
        'merit', 'ideal_type', 'hobby', 'tmi',
        'drink_capacity', 'drink_frequency', 'friend_meet_frequency',
        'religion',
    ];

    public function __construct(protected RosterService $rosterService)
    {
    }

    /**
     * 회원 정보 수정.
     *
     * @param array<string, mixed> $data              검증된 입력
     * @param bool                 $canChangePassword 비밀번호 변경 권한(최고관리자)
     * @return array{isSuccess:bool, msg?:string}
     */
    public function update(int $eventId, int $memberId, array $data, bool $canChangePassword): array
    {
        try {
            $drinkChanged = false;

            $member = DB::transaction(function () use ($eventId, $memberId, $data, $canChangePassword, &$drinkChanged): Member {
                // 행 잠금: 같은 회원을 동시에 고치는 경우 마지막 저장이 조용히 덮어쓰이지 않도록 순서를 맞춘다.
                $member = Member::where('event_id', $eventId)->lockForUpdate()->find($memberId);
                if ($member === null) {
                    throw new \RuntimeException('회원을 찾을 수 없습니다.');
                }

                // 번호표·성별 (체크인 명단과 연결된 값) — 다른 값보다 먼저 검증해 실패하면 아무것도 저장하지 않는다.
                $this->changeIdentity($member, $data);

                foreach (self::PLAIN_FIELDS as $field) {
                    if (array_key_exists($field, $data)) {
                        $member->{$field} = $data[$field];
                    }
                }

                // 다중 선택(이상형 외적/내적 조건): 선택지 목록 순서로 정렬해 저장. 비우면 null.
                foreach (['outer_preference' => Member::outerPreferenceOptions(), 'inner_preference' => Member::innerPreferenceOptions()] as $field => $options) {
                    if (array_key_exists($field, $data)) {
                        $sorted = Member::sortByOptions(is_array($data[$field]) ? $data[$field] : null, $options);
                        $member->{$field} = $sorted !== [] ? $sorted : null;
                    }
                }

                if (array_key_exists('mbti', $data)) {
                    $member->mbti = $data['mbti'] !== null ? strtoupper((string) $data['mbti']) : null;
                }
                foreach (['is_smoker', 'has_pet'] as $field) {
                    if (array_key_exists($field, $data)) {
                        $member->{$field} = $data[$field] !== null ? (bool) $data[$field] : null;
                    }
                }

                // 비밀번호는 최고관리자만, 값을 입력한 경우에만 바꾼다. (비우면 유지, 평문 저장 정책)
                if ($canChangePassword && ! empty($data['password'])) {
                    $member->password = (string) $data['password'];
                }

                // 음료는 1:1 행사의 등록된 음료만 허용한다. (검증은 요청에서 하고, 와인파티면 무시)
                if (array_key_exists('event_drink_id', $data)
                    && EventRound::whereKey($eventId)->value('type') === EventRound::TYPE_ONE) {
                    $newDrink     = $data['event_drink_id'] !== null ? (int) $data['event_drink_id'] : null;
                    $drinkChanged = $newDrink !== ($member->event_drink_id !== null ? (int) $member->event_drink_id : null);
                    $member->event_drink_id = $newDrink;
                    // 음료가 바뀌면 새 음료는 아직 전달 전이다.
                    if ($drinkChanged) {
                        $member->drink_served_at = null;
                    }
                }

                $member->save();

                // 체크인 명단과 공유하는 값(키·출생연도·직업)은 명단에도 맞춘다.
                if (ctype_digit((string) $member->name)) {
                    $this->rosterService->syncProfileFromMember($eventId, (string) $member->gender, (int) $member->name, [
                        'height'     => $member->height,
                        'birth_year' => $member->birth_year,
                        'job'        => $member->job,
                    ]);
                }

                return $member;
            });

            // 음료가 바뀌면 행사 상세의 음료 현황이 실시간으로 갱신되도록 커밋 후 알린다.
            if ($drinkChanged) {
                broadcast(new MemberDrinkChosen($member->refresh()));
            }

            return helpersSuccessMessage('회원 정보를 수정했습니다.');
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 음료 전달 완료 체크/해제. 음료를 고른 회원만 체크할 수 있다.
     *
     * @return array{isSuccess:bool, msg?:string, served?:bool, served_at?:?string}
     */
    public function setDrinkServed(int $eventId, int $memberId, bool $served): array
    {
        try {
            $member = DB::transaction(function () use ($eventId, $memberId, $served): Member {
                $member = Member::where('event_id', $eventId)->lockForUpdate()->find($memberId);
                if ($member === null) {
                    throw new \RuntimeException('회원을 찾을 수 없습니다.');
                }
                if ($served && $member->event_drink_id === null) {
                    throw new \RuntimeException('음료를 고르지 않은 회원은 전달 완료로 체크할 수 없습니다.');
                }

                // 이미 같은 상태면 그대로 둔다. (처음 체크한 시각 보존)
                if ($served && $member->drink_served_at === null) {
                    $member->drink_served_at = now();
                } elseif (! $served) {
                    $member->drink_served_at = null;
                }
                $member->save();

                return $member;
            });

            // 같은 행사 상세를 보고 있는 다른 관리자 화면도 갱신한다.
            broadcast(new MemberDrinkServed($member));

            $res              = helpersSuccessMessage($served ? '전달 완료로 체크했습니다.' : '전달 완료를 해제했습니다.');
            $res['served']    = $member->drink_served_at !== null;
            $res['served_at'] = $member->drink_served_at?->format('H:i');

            return $res;
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 번호표/성별 변경. 바뀐 값이 없으면 아무 것도 하지 않는다. (실패 시 예외 → 트랜잭션 롤백)
     *
     * @param array<string, mixed> $data
     */
    private function changeIdentity(Member $member, array $data): void
    {
        $eventId   = (int) $member->event_id;
        $oldGender = (string) $member->gender;
        $oldName   = (string) $member->name;

        $newGender = isset($data['gender']) ? (string) $data['gender'] : $oldGender;
        // 번호표는 숫자만 바꾼다. (숫자가 아닌 예외 회원 이름은 그대로 둔다)
        $newName = (ctype_digit($oldName) && isset($data['name'])) ? (string) (int) $data['name'] : $oldName;

        $genderChanged = $newGender !== $oldGender;
        $nameChanged   = $newName !== $oldName;
        if (! $genderChanged && ! $nameChanged) {
            return;
        }

        // 이 회원과 연결된 체크인 명단 행 (없을 수 있다: 과거 데이터)
        $roster = ctype_digit($oldName)
            ? EventRoster::where('event_id', $eventId)->where('gender', $oldGender)->where('ticket_no', (int) $oldName)->lockForUpdate()->first()
            : null;

        if ($genderChanged) {
            $hasVotes = Vote::where('event_id', $eventId)
                ->where(fn ($q) => $q->where('voter_id', $member->id)->orWhere('target_id', $member->id))
                ->exists();
            $hasMessages = Message::where('event_id', $eventId)
                ->where(fn ($q) => $q->where('sender_id', $member->id)->orWhere('receiver_id', $member->id))
                ->exists();
            if ($hasVotes || $hasMessages) {
                throw new \RuntimeException('투표나 메시지 기록이 있는 회원은 성별을 바꿀 수 없습니다. (기록이 이성 기준으로 쌓여 있어요)');
            }
        }

        // 바꾸려는 (성별, 번호표)가 이미 쓰이는지 — 체크인 명단의 다른 행 / 다른 회원
        if (ctype_digit($newName)) {
            $rosterTaken = EventRoster::where('event_id', $eventId)
                ->where('gender', $newGender)
                ->where('ticket_no', (int) $newName)
                ->when($roster !== null, fn ($q) => $q->where('id', '!=', $roster->id))
                ->exists();
            $memberTaken = Member::where('event_id', $eventId)
                ->where('gender', $newGender)
                ->where('name', $newName)
                ->where('id', '!=', $member->id)
                ->exists();
            if ($rosterTaken || $memberTaken) {
                $label = ($newGender === Member::GENDER_FEMALE ? '여' : '남') . ' ' . $newName . '번';
                throw new \RuntimeException("{$label}은 이미 사용 중입니다. 먼저 그 번호를 쓰는 사람의 번호표를 바꿔 주세요.");
            }
        }

        // 1:1 소개팅: 테이블당 같은 성별 1명
        if ($genderChanged && EventRound::whereKey($eventId)->value('type') === EventRound::TYPE_ONE) {
            $tableId = RotationSeat::where('event_id', $eventId)->where('member_id', $member->id)->value('rotation_table_id')
                ?? $roster?->rotation_table_id;
            if ($tableId !== null) {
                $seatTaken = RotationSeat::where('event_id', $eventId)
                    ->where('rotation_table_id', $tableId)
                    ->where('member_id', '!=', $member->id)
                    ->whereHas('member', fn ($q) => $q->where('gender', $newGender))
                    ->exists();
                $rosterSeatTaken = EventRoster::where('event_id', $eventId)
                    ->where('rotation_table_id', $tableId)
                    ->where('gender', $newGender)
                    ->whereNotNull('checked_in_at')
                    ->when($roster !== null, fn ($q) => $q->where('id', '!=', $roster->id))
                    ->exists();
                if ($seatTaken || $rosterSeatTaken) {
                    throw new \RuntimeException('1:1 소개팅은 테이블당 같은 성별 한 명만 앉을 수 있어요. 그 테이블에 이미 같은 성별이 있어 성별을 바꿀 수 없습니다. (테이블을 먼저 바꿔 주세요)');
                }
            }
        }

        $member->gender = $newGender;
        $member->name   = $newName;

        if ($roster !== null) {
            $roster->gender    = $newGender;
            $roster->ticket_no = (int) $newName;
            $roster->save();
        }
    }
}
