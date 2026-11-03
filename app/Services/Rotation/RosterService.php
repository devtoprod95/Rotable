<?php

namespace App\Services\Rotation;

use App\Models\EventRoster;
use App\Models\Member;
use Illuminate\Database\Eloquent\Collection;

/**
 * 사전 참여자 명단(화이트리스트) 도메인 서비스 — event_rosters.
 *
 * 관리자가 행사별로 "입장 가능한 번호"를 미리 등록하고, 당일 체크인(노쇼)을 관리한다.
 * (RotationService 에서 분리 — 명단/체크인 관련 로직만 담당)
 */
class RosterService
{
    /** @var array<string, mixed> 기본 응답 메시지 템플릿 */
    protected array $returnMsg = [];

    public function __construct()
    {
        $this->returnMsg = helpersDefaultMessage();
    }

    /**
     * 전화번호에서 숫자만 남긴다. (하이픈/공백 제거)
     */
    public function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    /**
     * 특정 행사의 사전 명단 목록. 성별 → 이름 → 번호 순 정렬.
     *
     * @return Collection<int, EventRoster>
     */
    public function rosterList(int $eventId): Collection
    {
        return EventRoster::where('event_id', $eventId)
            ->orderByRaw("case when gender = 'M' then 0 when gender = 'F' then 1 else 2 end")
            ->orderBy('name')
            ->orderBy('phone')
            ->get();
    }

    /**
     * 사전 명단 1명 추가 (이름/번호/성별 개별 입력).
     *
     * - 같은 행사에 이미 있는 번호면 거부한다.
     *
     * @return array{isSuccess:bool, message?:string}
     */
    public function rosterAdd(int $eventId, ?string $name, string $phone, ?string $gender): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $phone = $this->normalizePhone($phone);

            if (! preg_match('/^010\d{8}$/', $phone)) {
                return helpersFailMessage('전화번호는 010으로 시작하는 11자리 숫자여야 합니다.');
            }

            $dup = EventRoster::where('event_id', $eventId)->where('phone', $phone)->exists();
            if ($dup) {
                return helpersFailMessage('같은 행사에 이미 등록된 번호입니다.');
            }

            EventRoster::create([
                'event_id' => $eventId,
                'name'     => ($name !== null && $name !== '') ? $name : null,
                'phone'    => $phone,
                // 성별은 필수. M/F 가 아니면 기본값 남(M)으로 저장한다.
                'gender'   => $gender === Member::GENDER_FEMALE ? Member::GENDER_FEMALE : Member::GENDER_MALE,
            ]);

            $returnMsg = helpersSuccessMessage('명단에 추가했습니다.');
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 사전 명단 1건 수정 (이름/번호/성별/메모).
     *
     * @param array{name?:?string, phone?:string, gender?:?string, memo?:?string} $data
     */
    public function rosterUpdate(int $rosterId, array $data): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $roster = EventRoster::findOrFail($rosterId);

            if (array_key_exists('phone', $data)) {
                $phone = $this->normalizePhone($data['phone']);
                // 같은 행사 내 다른 레코드와 번호 중복 체크.
                $dup = EventRoster::where('event_id', $roster->event_id)
                    ->where('phone', $phone)
                    ->where('id', '!=', $roster->id)
                    ->exists();
                if ($dup) {
                    return helpersFailMessage('같은 행사에 이미 등록된 번호입니다.');
                }
                $roster->phone = $phone;
            }
            if (array_key_exists('name', $data)) {
                $roster->name = $data['name'] !== '' ? $data['name'] : null;
            }
            if (array_key_exists('gender', $data)) {
                $roster->gender = in_array($data['gender'], [Member::GENDER_MALE, Member::GENDER_FEMALE], true) ? $data['gender'] : null;
            }
            if (array_key_exists('memo', $data)) {
                $roster->memo = $data['memo'] !== '' ? $data['memo'] : null;
            }
            $roster->save();

            $returnMsg = helpersSuccessMessage('명단을 수정했습니다.');
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 사전 명단 삭제.
     *
     * - 이미 입장등록(members)한 번호는 삭제 금지. (명단-실제 참가자 정합성 보호)
     *
     * @param array<int, int|string> $ids
     */
    public function rosterDelete(array $ids): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $rosters = EventRoster::whereIn('id', $ids)->get();
            if ($rosters->isEmpty()) {
                return helpersFailMessage('삭제할 명단을 찾을 수 없습니다.');
            }

            // 각 roster 의 (event_id, phone) 이 members 에 존재하는지 확인한다.
            $blockedNames = [];
            $deletableIds = [];
            foreach ($rosters as $roster) {
                $registered = Member::where('event_id', $roster->event_id)
                    ->where('phone', $roster->phone)
                    ->exists();
                if ($registered) {
                    $blockedNames[] = $roster->name ?: $roster->phone;
                } else {
                    $deletableIds[] = $roster->id;
                }
            }

            if (! empty($deletableIds)) {
                EventRoster::whereIn('id', $deletableIds)->delete();
            }

            if (! empty($blockedNames)) {
                $names = implode(', ', array_slice($blockedNames, 0, 5));
                $more  = count($blockedNames) > 5 ? ' 외 ' . (count($blockedNames) - 5) . '명' : '';
                $deletedCnt = count($deletableIds);
                $msg = "이미 입장등록한 참가자({$names}{$more})는 삭제할 수 없습니다.";
                if ($deletedCnt > 0) {
                    $msg .= " 나머지 {$deletedCnt}건만 삭제했습니다.";
                }

                return helpersFailMessage($msg);
            }

            $returnMsg = helpersSuccessMessage('명단을 삭제했습니다.');
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 체크인(참석) 토글. checked_in_at 이 null 이면 지금 시각으로, 있으면 null 로.
     *
     * @return array{isSuccess:bool, message?:string, checked_in?:bool, checked_in_at?:?string}
     */
    public function rosterToggleCheckIn(int $rosterId): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $roster = EventRoster::findOrFail($rosterId);
            $roster->checked_in_at = $roster->checked_in_at === null ? now() : null;
            $roster->save();

            $returnMsg = helpersSuccessMessage($roster->checked_in_at ? '참석(체크인) 처리했습니다.' : '미참석(노쇼)으로 되돌렸습니다.');
            $returnMsg['checked_in']    = $roster->checked_in_at !== null;
            $returnMsg['checked_in_at'] = $roster->checked_in_at?->format('Y-m-d H:i');
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 입장등록/로그인 허용 여부 판정.
     * - (event_id, phone) 이 event_rosters 에 있어야 허용.
     */
    public function isPhoneAllowedForEvent(int $eventId, string $phone): bool
    {
        $phone = $this->normalizePhone($phone);

        return EventRoster::where('event_id', $eventId)->where('phone', $phone)->exists();
    }
}
