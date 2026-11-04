<?php

namespace App\Services\Rotation;

use App\Models\EventRoster;
use App\Models\Member;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * 체크인 명단 도메인 서비스 — event_rosters.
 *
 * 관리자(호스트)가 행사별로 신청자를 닉네임·플랫폼·성별로 적어 두고, 현장에서 체크인하며 번호표를 부여한다.
 *
 * 번호표 규칙
 *  - 같은 행사·같은 성별 안에서만 유일하다. (남 3번 · 여 3번은 공존)
 *  - 1순위: 호스트가 직접 입력한 번호.  2순위: 비우면 그 성별의 가장 작은 빈 번호를 자동 배정.
 *  - 입장등록은 "체크인되어 번호표가 배정된" 번호로만 가능하고, 같은 번호로 두 번 가입할 수 없다.
 *  - 번호표를 바꾸면 이미 입장등록한 회원의 번호표(members.name)도 함께 바뀐다.
 */
class RosterService
{
    /** 번호표 최댓값 */
    public const TICKET_MAX = 999;

    /** 자동 배정 시 동시 체크인으로 번호가 겹칠 때 재시도 횟수 */
    private const AUTO_RETRY = 3;

    /**
     * 플랫폼 값 정규화. 허용값(문토/프립)이 아니면 기본값(문토)으로 귀속한다.
     */
    private function normalizePlatform(?string $platform): string
    {
        return in_array($platform, [Member::PLATFORM_MUNTO, Member::PLATFORM_FRIP], true)
            ? $platform
            : Member::PLATFORM_MUNTO;
    }

    /**
     * 성별 값 정규화. M/F 가 아니면 기본값 남(M)으로 귀속한다.
     */
    private function normalizeGender(?string $gender): string
    {
        return $gender === Member::GENDER_FEMALE ? Member::GENDER_FEMALE : Member::GENDER_MALE;
    }

    /**
     * 번호표 입력값을 정수로 정규화한다. 숫자가 아니거나 범위(1~TICKET_MAX) 밖이면 null.
     * ("03" → 3, 공백/비숫자 포함 시 null)
     */
    public function normalizeTicket(mixed $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '' || ! ctype_digit($value)) {
            return null;
        }
        $n = (int) $value;

        return ($n >= 1 && $n <= self::TICKET_MAX) ? $n : null;
    }

    /**
     * 특정 행사의 체크인 명단. 성별(남→여) → 번호표 → 닉네임 순 정렬.
     *
     * @return Collection<int, EventRoster>
     */
    public function rosterList(int $eventId): Collection
    {
        return EventRoster::where('event_id', $eventId)
            ->orderByRaw("case when gender = 'M' then 0 when gender = 'F' then 1 else 2 end")
            ->orderByRaw('ticket_no is null')
            ->orderBy('ticket_no')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * 이 번호표가 이미 쓰이고 있는지. (체크인 명단 + 이미 입장등록한 회원 모두 확인)
     *
     * @param int|null $exceptRosterId 자기 자신 행은 제외
     */
    public function ticketTaken(int $eventId, string $gender, int $ticket, ?int $exceptRosterId = null): bool
    {
        $inRoster = EventRoster::where('event_id', $eventId)
            ->where('gender', $gender)
            ->where('ticket_no', $ticket)
            ->when($exceptRosterId !== null, fn ($q) => $q->where('id', '!=', $exceptRosterId))
            ->exists();
        if ($inRoster) {
            return true;
        }

        // 자기 행에 연결된 회원은 "남"이 아니므로, 제외 대상 행의 현재 번호와 같은 회원은 걸러낸다.
        $ownTicket = $exceptRosterId !== null
            ? EventRoster::where('id', $exceptRosterId)->value('ticket_no')
            : null;
        if ($ownTicket !== null && (int) $ownTicket === $ticket) {
            return false;
        }

        return $this->memberByTicket($eventId, $gender, $ticket) !== null;
    }

    /**
     * 번호표로 입장등록한 회원 조회. (회원의 번호표는 members.name 에 숫자 문자열로 저장된다.)
     */
    public function memberByTicket(int $eventId, string $gender, int $ticket): ?Member
    {
        return Member::where('event_id', $eventId)
            ->where('gender', $gender)
            ->where('name', (string) $ticket)
            ->first();
    }

    /**
     * 입장등록 시 회원에게 복사할 신청 플랫폼 — 연결 키(행사·성별·번호표)로 찾은 체크인 행의 값.
     * 행이 없으면 기본값(문토).
     */
    public function platformForTicket(int $eventId, string $gender, int $ticket): string
    {
        $platform = EventRoster::where('event_id', $eventId)
            ->where('gender', $gender)
            ->where('ticket_no', $ticket)
            ->value('platform');

        return $this->normalizePlatform($platform);
    }

    /**
     * 행사에서 입장등록한 번호표 현황. 키는 "성별|번호", 값은 입장등록 시각.
     * 체크인 목록에서 "이 번호표로 가입했는지"를 한 번의 조회로 표시하는 데 쓴다.
     *
     * @return array<string, string>
     */
    public function registeredTickets(int $eventId): array
    {
        $out = [];

        Member::where('event_id', $eventId)
            ->get(['gender', 'name', 'created_at'])
            ->each(function (Member $m) use (&$out): void {
                if (ctype_digit((string) $m->name)) {
                    $out[$m->gender . '|' . (int) $m->name] = $m->created_at?->format('Y-m-d H:i') ?? '';
                }
            });

        return $out;
    }

    /**
     * [2순위·자동] 그 성별에서 쓰이지 않은 가장 작은 번호.
     * 체크인 명단에 배정된 번호와 이미 입장등록한 회원의 번호를 모두 피한다. (빈 번호가 있으면 채운다)
     */
    public function nextTicketNo(int $eventId, string $gender): int
    {
        $used = EventRoster::where('event_id', $eventId)
            ->where('gender', $gender)
            ->whereNotNull('ticket_no')
            ->pluck('ticket_no')
            ->map(fn ($n) => (int) $n)
            ->all();

        $memberUsed = Member::where('event_id', $eventId)
            ->where('gender', $gender)
            ->pluck('name')
            ->filter(fn ($n) => ctype_digit((string) $n))
            ->map(fn ($n) => (int) $n)
            ->all();

        $taken = array_flip(array_merge($used, $memberUsed));

        $n = 1;
        while (isset($taken[$n])) {
            $n++;
        }

        return $n;
    }

    /**
     * 명단 1명 추가 (닉네임/플랫폼/성별). 번호표는 체크인할 때 부여한다.
     *
     * - 전화번호가 없으므로 중복 판정은 하지 않는다. (동명이인·동일 닉네임 허용)
     *
     * @return array{isSuccess:bool, msg?:string}
     */
    public function rosterAdd(int $eventId, string $name, ?string $platform, ?string $gender): array
    {
        try {
            EventRoster::create([
                'event_id' => $eventId,
                'name'     => trim($name),
                'gender'   => $this->normalizeGender($gender),
                'platform' => $this->normalizePlatform($platform),
            ]);

            return helpersSuccessMessage('명단에 추가했습니다.');
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 명단 1건 수정 (닉네임/플랫폼/성별).
     *
     * - 번호표가 배정된 뒤에는 성별을 바꿀 수 없다. (번호표는 성별 안에서만 유일하므로)
     *
     * @param array{name?:?string, platform?:?string, gender?:?string} $data
     */
    public function rosterUpdate(int $rosterId, array $data): array
    {
        try {
            $roster = EventRoster::findOrFail($rosterId);

            if (array_key_exists('gender', $data) && $data['gender'] !== null) {
                $gender = $this->normalizeGender($data['gender']);
                if ($gender !== $roster->gender && $roster->ticket_no !== null) {
                    return helpersFailMessage('번호표가 배정된 뒤에는 성별을 바꿀 수 없습니다. 체크인을 취소한 뒤 변경해 주세요.');
                }
                $roster->gender = $gender;
            }
            if (array_key_exists('name', $data)) {
                $roster->name = ($data['name'] !== null && $data['name'] !== '') ? trim($data['name']) : null;
            }
            if (array_key_exists('platform', $data) && $data['platform'] !== null) {
                $roster->platform = $this->normalizePlatform($data['platform']);
            }
            $roster->save();

            // 이미 그 번호표로 입장등록한 회원이 있으면 플랫폼도 함께 맞춘다. (회원의 플랫폼은 체크인 값을 따른다.)
            if ($roster->ticket_no !== null) {
                $member = $this->memberByTicket((int) $roster->event_id, (string) $roster->gender, (int) $roster->ticket_no);
                if ($member !== null && $member->platform !== $roster->platform) {
                    $member->platform = $roster->platform;
                    $member->save();
                }
            }

            return helpersSuccessMessage('명단을 수정했습니다.');
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 명단 삭제.
     *
     * - 이미 그 번호표로 입장등록한 회원이 있으면 삭제할 수 없다. (번호표-회원 연결 보호)
     *
     * @param array<int, int|string> $ids
     */
    public function rosterDelete(array $ids): array
    {
        try {
            $rosters = EventRoster::whereIn('id', $ids)->get();
            if ($rosters->isEmpty()) {
                return helpersFailMessage('삭제할 명단을 찾을 수 없습니다.');
            }

            $blocked   = [];
            $deletable = [];
            foreach ($rosters as $roster) {
                $registered = $roster->ticket_no !== null
                    && $this->memberByTicket((int) $roster->event_id, (string) $roster->gender, (int) $roster->ticket_no) !== null;
                if ($registered) {
                    $blocked[] = $roster->name ?: ($roster->ticket_no . '번');
                } else {
                    $deletable[] = $roster->id;
                }
            }

            if (! empty($deletable)) {
                EventRoster::whereIn('id', $deletable)->delete();
            }

            if (! empty($blocked)) {
                $names = implode(', ', array_slice($blocked, 0, 5));
                $more  = count($blocked) > 5 ? ' 외 ' . (count($blocked) - 5) . '명' : '';
                $msg   = "이미 입장등록한 참가자({$names}{$more})는 삭제할 수 없습니다.";
                if (! empty($deletable)) {
                    $msg .= ' 나머지 ' . count($deletable) . '건만 삭제했습니다.';
                }

                return helpersFailMessage($msg);
            }

            return helpersSuccessMessage('명단을 삭제했습니다.');
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 체크인(참석) 토글.
     *
     * 미도착 → 참석:
     *   1순위) $manualTicket 이 있으면 그 번호를 부여한다. (이미 쓰이는 번호면 거부)
     *   2순위) 비어 있으면 그 성별의 가장 작은 빈 번호를 자동 배정한다.
     *   (이미 번호표가 있는 행은 그 번호를 유지한다.)
     * 참석 → 미도착: 번호표를 회수한다. 이미 그 번호로 입장등록한 회원이 있으면 취소할 수 없다.
     *
     * @return array{isSuccess:bool, msg?:string, checked_in?:bool, checked_in_at?:?string, ticket_no?:?int, assigned?:string}
     */
    public function rosterToggleCheckIn(int $rosterId, ?int $manualTicket = null): array
    {
        try {
            $roster = EventRoster::findOrFail($rosterId);

            // 참석 → 미도착 (취소)
            if ($roster->checked_in_at !== null) {
                if ($roster->ticket_no !== null
                    && $this->memberByTicket((int) $roster->event_id, (string) $roster->gender, (int) $roster->ticket_no) !== null) {
                    return helpersFailMessage('이미 입장등록한 번호표라 체크인을 취소할 수 없습니다.');
                }

                $roster->checked_in_at = null;
                $roster->ticket_no     = null;
                $roster->save();

                $res                  = helpersSuccessMessage('미도착으로 되돌렸습니다. 번호표를 회수했어요.');
                $res['checked_in']    = false;
                $res['checked_in_at'] = null;
                $res['ticket_no']     = null;

                return $res;
            }

            // 미도착 → 참석
            $eventId = (int) $roster->event_id;
            $gender  = (string) $roster->gender;

            if ($manualTicket !== null) {
                // 1순위: 호스트가 직접 입력한 번호
                if ($manualTicket < 1 || $manualTicket > self::TICKET_MAX) {
                    return helpersFailMessage('번호표는 1~' . self::TICKET_MAX . ' 사이 숫자로 입력해 주세요.');
                }
                if ($this->ticketTaken($eventId, $gender, $manualTicket, $roster->id)) {
                    return helpersFailMessage("이미 사용 중인 번호표입니다. ({$manualTicket}번)");
                }
                $roster->ticket_no = $manualTicket;
                $assigned          = 'manual';
            } elseif ($roster->ticket_no !== null) {
                $assigned = 'existing';
            } else {
                // 2순위: 자동 배정 (동시 체크인으로 겹치면 다음 빈 번호로 재시도)
                $assigned = 'auto';
                for ($try = 1; ; $try++) {
                    try {
                        $roster->ticket_no     = $this->nextTicketNo($eventId, $gender);
                        $roster->checked_in_at = now();
                        $roster->save();
                        break;
                    } catch (UniqueConstraintViolationException $e) {
                        if ($try >= self::AUTO_RETRY) {
                            throw $e;
                        }
                    }
                }
            }

            if ($roster->checked_in_at === null) {
                $roster->checked_in_at = now();
                $roster->save();
            }

            $kindLabel            = ['manual' => '직접 입력', 'auto' => '자동 배정', 'existing' => '기존 번호 유지'][$assigned];
            $res                  = helpersSuccessMessage("참석 처리했습니다. 번호표 {$roster->ticket_no}번 ({$kindLabel})");
            $res['checked_in']    = true;
            $res['checked_in_at'] = $roster->checked_in_at?->format('Y-m-d H:i');
            $res['ticket_no']     = (int) $roster->ticket_no;
            $res['assigned']      = $assigned;

            return $res;
        } catch (UniqueConstraintViolationException) {
            return helpersFailMessage('번호표가 겹쳤습니다. 다시 시도해 주세요.');
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 관리자가 번호표를 변경한다. (체크인되어 번호표가 있는 행만)
     *
     * - 이미 그 번호표로 입장등록한 회원이 있으면 회원의 번호표(members.name)도 함께 바꿔 연결을 유지한다.
     * - 바꾸려는 번호를 다른 사람이 쓰고 있으면 거부한다. (먼저 그 사람의 번호를 바꿔야 한다.)
     *
     * @return array{isSuccess:bool, msg?:string, ticket_no?:int}
     */
    public function rosterSetTicket(int $rosterId, int $newTicket): array
    {
        try {
            if ($newTicket < 1 || $newTicket > self::TICKET_MAX) {
                return helpersFailMessage('번호표는 1~' . self::TICKET_MAX . ' 사이 숫자로 입력해 주세요.');
            }

            return DB::transaction(function () use ($rosterId, $newTicket): array {
                $roster = EventRoster::lockForUpdate()->findOrFail($rosterId);

                if ($roster->checked_in_at === null || $roster->ticket_no === null) {
                    return helpersFailMessage('체크인(참석) 처리된 사람만 번호표를 바꿀 수 있습니다.');
                }

                $eventId = (int) $roster->event_id;
                $gender  = (string) $roster->gender;
                $old     = (int) $roster->ticket_no;

                if ($old === $newTicket) {
                    $res              = helpersSuccessMessage('번호표가 그대로입니다.');
                    $res['ticket_no'] = $old;

                    return $res;
                }

                if ($this->ticketTaken($eventId, $gender, $newTicket, $roster->id)) {
                    return helpersFailMessage("{$newTicket}번은 이미 사용 중입니다. 먼저 그 번호를 쓰는 사람의 번호표를 바꿔 주세요.");
                }

                $member = $this->memberByTicket($eventId, $gender, $old);

                $roster->ticket_no = $newTicket;
                $roster->save();

                if ($member !== null) {
                    $member->name = (string) $newTicket;
                    $member->save();
                }

                $res              = helpersSuccessMessage("번호표를 {$old}번 → {$newTicket}번으로 바꿨습니다." . ($member ? ' (입장등록한 회원 번호도 함께 변경)' : ''));
                $res['ticket_no'] = $newTicket;

                return $res;
            });
        } catch (UniqueConstraintViolationException) {
            return helpersFailMessage('번호표가 겹쳤습니다. 다시 시도해 주세요.');
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 입장등록 가능한 번호표인지 판정한다.
     *
     * 통과 조건: 체크인되어 이 번호표가 배정된 행이 있고(같은 행사·같은 성별), 아직 그 번호로 가입한 회원이 없을 것.
     *
     * @return array{ok:bool, message?:string, ticket?:int}
     */
    public function checkRegistrableTicket(int $eventId, string $gender, mixed $rawTicket): array
    {
        $ticket = $this->normalizeTicket($rawTicket);
        if ($ticket === null) {
            return ['ok' => false, 'message' => '번호표는 숫자로 입력해주세요.'];
        }

        $assigned = EventRoster::where('event_id', $eventId)
            ->where('gender', $gender)
            ->where('ticket_no', $ticket)
            ->whereNotNull('checked_in_at')
            ->exists();
        if (! $assigned) {
            return ['ok' => false, 'message' => '체크인 명단에 없는 번호표입니다. 호스트에게 번호표를 받은 뒤 입력해주세요.'];
        }

        if ($this->memberByTicket($eventId, $gender, $ticket) !== null) {
            return ['ok' => false, 'message' => '이미 입장등록된 번호표입니다. 번호표를 다시 확인해주세요.'];
        }

        return ['ok' => true, 'ticket' => $ticket];
    }
}
