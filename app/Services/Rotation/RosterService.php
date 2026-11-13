<?php

namespace App\Services\Rotation;

use App\Models\EventRoster;
use App\Models\EventRound;
use App\Models\Member;
use App\Models\RotationTable;
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
     * - 체크인(참석)된 사람은 삭제할 수 없다. 먼저 체크인을 취소(미도착)해야 한다.
     *   (입장등록한 회원은 체크인 취소도 막혀 있으므로 자연히 삭제도 불가)
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
                // 체크인 이후(참석·입장등록)는 삭제 불가
                if ($roster->checked_in_at !== null) {
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
                $msg   = "체크인한 참가자({$names}{$more})는 삭제할 수 없습니다. 먼저 체크인을 취소해 주세요.";
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
    public function rosterToggleCheckIn(int $rosterId, ?int $manualTicket = null, ?int $tableId = null): array
    {
        try {
            $roster = EventRoster::findOrFail($rosterId);

            // 참석 → 미도착 (취소)
            if ($roster->checked_in_at !== null) {
                if ($roster->ticket_no !== null
                    && $this->memberByTicket((int) $roster->event_id, (string) $roster->gender, (int) $roster->ticket_no) !== null) {
                    return helpersFailMessage('이미 입장등록한 번호표라 체크인을 취소할 수 없습니다.');
                }

                $roster->checked_in_at     = null;
                $roster->ticket_no         = null;
                $roster->rotation_table_id = null;  // 테이블 지정도 함께 회수
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

            // 테이블 지정(필수): 같은 행사의 테이블이어야 한다.
            if ($tableId !== null) {
                $tableError = $this->tableAssignError($roster, $tableId);
                if ($tableError !== null) {
                    return helpersFailMessage($tableError);
                }

                $roster->rotation_table_id = $tableId;
            }

            // 체크인하려면 테이블이 반드시 지정되어 있어야 한다. (이번에 지정했거나, 이전에 지정된 값이 있어야 함)
            if ($roster->rotation_table_id === null) {
                return helpersFailMessage('체크인하려면 앉을 테이블을 지정해 주세요.');
            }

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

            $kindLabel                = ['manual' => '직접 입력', 'auto' => '자동 배정', 'existing' => '기존 번호 유지'][$assigned];
            $res                      = helpersSuccessMessage("참석 처리했습니다. 번호표 {$roster->ticket_no}번 ({$kindLabel})");
            $res['checked_in']        = true;
            $res['checked_in_at']     = $roster->checked_in_at?->format('Y-m-d H:i');
            $res['ticket_no']         = (int) $roster->ticket_no;
            $res['rotation_table_id'] = $roster->rotation_table_id !== null ? (int) $roster->rotation_table_id : null;
            $res['assigned']          = $assigned;

            return $res;
        } catch (UniqueConstraintViolationException) {
            return helpersFailMessage('번호표가 겹쳤습니다. 다시 시도해 주세요.');
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 이 명단 행을 해당 테이블에 앉힐 수 없는 이유를 돌려준다. (가능하면 null)
     *
     *  - 같은 행사의 테이블이어야 한다.
     *  - 1:1 소개팅은 테이블당 같은 성별 1명 원칙 → 같은 성별이 이미 앉은 테이블은 거부.
     */
    private function tableAssignError(EventRoster $roster, int $tableId): ?string
    {
        $eventId = (int) $roster->event_id;

        $validTable = RotationTable::where('event_id', $eventId)->where('id', $tableId)->exists();
        if (! $validTable) {
            return '선택한 테이블이 이 행사에 없습니다.';
        }

        $event = EventRound::find($eventId);
        if ($event !== null && $event->isOneToOne()) {
            $sameGenderTaken = EventRoster::where('event_id', $eventId)
                ->where('gender', (string) $roster->gender)
                ->where('rotation_table_id', $tableId)
                ->where('id', '!=', $roster->id)
                ->whereNotNull('checked_in_at')
                ->exists();
            if ($sameGenderTaken) {
                return '1:1 소개팅은 테이블당 같은 성별 한 명만 앉을 수 있어요. 다른 테이블을 선택해 주세요.';
            }
        }

        return null;
    }

    /**
     * 관리자가 번호표를 변경한다. (체크인되어 번호표가 있는 행만)
     *
     * - 이미 그 번호표로 입장등록한 회원이 있으면 회원의 번호표(members.name)도 함께 바꿔 연결을 유지한다.
     * - 바꾸려는 번호를 다른 사람이 쓰고 있으면 거부한다. (먼저 그 사람의 번호를 바꿔야 한다.)
     * - $tableId 를 주면 앉을 테이블도 함께 바꾼다. (체크인과 같은 검증)
     *
     * @return array{isSuccess:bool, msg?:string, ticket_no?:int, rotation_table_id?:?int}
     */
    public function rosterSetTicket(int $rosterId, int $newTicket, ?int $tableId = null): array
    {
        try {
            if ($newTicket < 1 || $newTicket > self::TICKET_MAX) {
                return helpersFailMessage('번호표는 1~' . self::TICKET_MAX . ' 사이 숫자로 입력해 주세요.');
            }

            return DB::transaction(function () use ($rosterId, $newTicket, $tableId): array {
                $roster = EventRoster::lockForUpdate()->findOrFail($rosterId);

                if ($roster->checked_in_at === null || $roster->ticket_no === null) {
                    return helpersFailMessage('체크인(참석) 처리된 사람만 번호표를 바꿀 수 있습니다.');
                }

                $eventId = (int) $roster->event_id;
                $gender  = (string) $roster->gender;
                $old     = (int) $roster->ticket_no;

                $oldTableId   = $roster->rotation_table_id !== null ? (int) $roster->rotation_table_id : null;
                $tableChanged = $tableId !== null && $tableId !== $oldTableId;

                if ($tableChanged) {
                    $tableError = $this->tableAssignError($roster, $tableId);
                    if ($tableError !== null) {
                        return helpersFailMessage($tableError);
                    }
                }

                if ($old === $newTicket) {
                    if (! $tableChanged) {
                        $res                      = helpersSuccessMessage('변경된 내용이 없습니다.');
                        $res['ticket_no']         = $old;
                        $res['rotation_table_id'] = $oldTableId;

                        return $res;
                    }

                    // 번호는 그대로, 테이블만 변경
                    $roster->rotation_table_id = $tableId;
                    $roster->save();

                    $newTableNo               = RotationTable::whereKey($tableId)->value('table_no');
                    $res                      = helpersSuccessMessage("앉을 테이블을 {$newTableNo}번으로 바꿨습니다.");
                    $res['ticket_no']         = $old;
                    $res['rotation_table_id'] = $tableId;

                    return $res;
                }

                if ($this->ticketTaken($eventId, $gender, $newTicket, $roster->id)) {
                    return helpersFailMessage("{$newTicket}번은 이미 사용 중입니다. 먼저 그 번호를 쓰는 사람의 번호표를 바꿔 주세요.");
                }

                $member = $this->memberByTicket($eventId, $gender, $old);

                $roster->ticket_no = $newTicket;
                if ($tableChanged) {
                    $roster->rotation_table_id = $tableId;
                }
                $roster->save();

                if ($member !== null) {
                    $member->name = (string) $newTicket;
                    $member->save();
                }

                $res                      = helpersSuccessMessage("번호표를 {$old}번 → {$newTicket}번으로 바꿨습니다."
                    . ($tableChanged ? ' (테이블도 변경)' : '')
                    . ($member ? ' (입장등록한 회원 번호도 함께 변경)' : ''));
                $res['ticket_no']         = $newTicket;
                $res['rotation_table_id'] = $roster->rotation_table_id !== null ? (int) $roster->rotation_table_id : null;

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

    /**
     * 체크인 명단(roster) 기준 테이블별 성비.
     *  - 체크인되어 테이블이 지정된 행만 집계한다. (입장등록 전에도 즉시 반영)
     *  - 모든 테이블을 0부터 포함해 반환한다.
     *
     * @return array<int, array{table_no:int, male:int, female:int, total:int}>
     */
    public function tableGenderRatios(int $eventId): array
    {
        $tables = RotationTable::where('event_id', $eventId)
            ->orderBy('table_no')
            ->get(['id', 'table_no']);

        // 체크인 + 테이블 지정된 roster 를 테이블별 성별로 집계.
        $rosters = EventRoster::where('event_id', $eventId)
            ->whereNotNull('checked_in_at')
            ->whereNotNull('rotation_table_id')
            ->get(['gender', 'rotation_table_id']);

        $byTableId = [];
        foreach ($rosters as $r) {
            $tid = (int) $r->rotation_table_id;
            if (! isset($byTableId[$tid])) { $byTableId[$tid] = ['M' => 0, 'F' => 0]; }
            if ($r->gender === Member::GENDER_FEMALE) { $byTableId[$tid]['F']++; }
            else { $byTableId[$tid]['M']++; }
        }

        return $tables->map(function (RotationTable $t) use ($byTableId) {
            $c = $byTableId[$t->id] ?? ['M' => 0, 'F' => 0];
            return [
                'table_no' => (int) $t->table_no,
                'male'     => $c['M'],
                'female'   => $c['F'],
                'total'    => $c['M'] + $c['F'],
            ];
        })->toArray();
    }

    /**
     * 번호표(같은 행사·같은 성별)로 체크인 때 지정된 테이블 id 를 찾는다.
     *  - 참가자 입장등록 시, 참가자가 고르지 않고 이 값으로 좌석을 자동 생성한다.
     *
     * @return int|null  지정된 테이블 id. 없으면 null.
     */
    public function tableForTicket(int $eventId, string $gender, int $ticket): ?int
    {
        $roster = EventRoster::where('event_id', $eventId)
            ->where('gender', $gender)
            ->where('ticket_no', $ticket)
            ->whereNotNull('checked_in_at')
            ->first(['rotation_table_id']);

        return $roster?->rotation_table_id !== null ? (int) $roster->rotation_table_id : null;
    }

    /**
     * [마이그레이션용] 기존 좌석(rotation_seats)의 테이블을 체크인 명단(event_rosters)에 역동기화한다.
     *
     * 옵션A 이전에 입장등록한 회원들은 event_rosters.rotation_table_id 가 비어 있다.
     * 이미 만들어진 rotation_seats(= 실제 좌석)의 테이블을 읽어, 같은 번호표·성별의 roster 행에 채운다.
     *  - 연결 키: member(gender, name=번호표) ↔ roster(gender, ticket_no)
     *  - 이미 roster 에 테이블이 있으면 덮어쓰지 않는다. (수동 지정 보존)
     *
     * @return array{isSuccess:bool, msg?:string, updated?:int, skipped?:int}
     */
    public function syncTablesFromSeats(int $eventId): array
    {
        try {
            // 이 행사의 좌석(회원 성별 + 번호표 + 좌석 테이블번호) 로드.
            $seats = \App\Models\RotationSeat::with(['member:id,gender,name', 'table:id,table_no'])
                ->where('event_id', $eventId)
                ->get();

            if ($seats->isEmpty()) {
                return helpersFailMessage('동기화할 좌석(입장등록)이 없습니다.');
            }

            $updated = 0;
            $skipped = 0;

            DB::transaction(function () use ($eventId, $seats, &$updated, &$skipped): void {
                foreach ($seats as $seat) {
                    $gender   = $seat->member?->gender;
                    $ticketNo = $seat->member?->name; // 번호표는 members.name 에 숫자 문자열로 저장
                    $tableId  = $seat->rotation_table_id;

                    if ($gender === null || $ticketNo === null || $tableId === null || ! ctype_digit((string) $ticketNo)) {
                        $skipped++;
                        continue;
                    }

                    // 같은 행사·성별·번호표의 roster 를 찾아, 테이블이 비어 있으면 채운다.
                    $roster = EventRoster::where('event_id', $eventId)
                        ->where('gender', $gender)
                        ->where('ticket_no', (int) $ticketNo)
                        ->first();

                    if ($roster === null || $roster->rotation_table_id !== null) {
                        $skipped++;
                        continue;
                    }

                    $roster->rotation_table_id = (int) $tableId;
                    $roster->save();
                    $updated++;
                }
            });

            $res            = helpersSuccessMessage("좌석 기준으로 명단 테이블을 동기화했습니다. 반영 {$updated}건, 건너뜀 {$skipped}건");
            $res['updated'] = $updated;
            $res['skipped'] = $skipped;

            return $res;
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * [개발용] 현재 체크인(참석)된 사람들 기준으로 입장등록(회원)을 더미 데이터로 생성한다.
     *
     * 실제 입장등록 흐름과 동일하게:
     *  - 체크인되어 번호표가 배정된 행 중, 아직 그 번호표로 가입한 회원이 없는 사람만 대상으로 한다.
     *  - 회원(members)을 더미 프로필(팩토리)로 생성하되 번호표(members.name)·성별·플랫폼은 체크인 값을 따른다.
     *  - 로테이션 기준(홈) 좌석(rotation_seats)을 테이블에 배치한다.
     *    (1:1 행사는 테이블당 같은 성별 1명 제약을 지키고, 그 외는 성별별 라운드로빈)
     *
     * 운영 데이터가 아닌 테스트 편의용이므로 비밀번호는 고정값('q')으로 둔다.
     *
     * @return array{isSuccess:bool, msg?:string, created?:int, skipped?:int}
     */
    public function devSeedRegistrations(int $eventId): array
    {
        try {
            $event = \App\Models\EventRound::find($eventId);
            if ($event === null) {
                return helpersFailMessage('행사를 찾을 수 없습니다.');
            }

            $tables = \App\Models\RotationTable::where('event_id', $eventId)
                ->orderBy('table_no')
                ->get();
            if ($tables->isEmpty()) {
                return helpersFailMessage('테이블이 없어 좌석을 배치할 수 없습니다. 먼저 테이블을 생성해 주세요.');
            }

            $isOne = $event->isOneToOne();

            // 체크인되어 번호표가 있는 사람만 (성별 → 번호표 순)
            $rosters = EventRoster::where('event_id', $eventId)
                ->whereNotNull('checked_in_at')
                ->whereNotNull('ticket_no')
                ->orderBy('gender')
                ->orderBy('ticket_no')
                ->get();

            return DB::transaction(function () use ($event, $eventId, $rosters, $tables, $isOne): array {
                // 이미 어떤 성별이 앉은 테이블 집합 (1:1 제약 판정용). [table_id => ['M'=>bool,'F'=>bool]]
                $occupied = [];
                \App\Models\RotationSeat::with('member:id,gender')
                    ->where('event_id', $eventId)
                    ->get()
                    ->each(function ($seat) use (&$occupied): void {
                        $g = $seat->member?->gender;
                        $occupied[$seat->rotation_table_id][$g] = true;
                    });

                // 성별별 라운드로빈 커서 (와인파티 균등 배치용)
                $cursor = ['M' => 0, 'F' => 0];

                // 더미 전화번호 충돌 방지: 이 행사에 이미 쓰인 번호 뒤에서부터 채운다.
                $usedPhones = \App\Models\Member::where('event_id', $eventId)->pluck('phone')->flip();

                $created = 0;
                $skipped = 0;

                foreach ($rosters as $roster) {
                    $gender = (string) $roster->gender;
                    $ticket = (int) $roster->ticket_no;

                    // 이미 그 번호표로 가입한 회원이 있으면 건너뛴다.
                    if ($this->memberByTicket($eventId, $gender, $ticket) !== null) {
                        $skipped++;

                        continue;
                    }

                    // 배치할 테이블 선택:
                    //  1순위) 체크인 때 관리자가 지정한 테이블(roster.rotation_table_id) — 실제 입장등록과 동일.
                    //  2순위) 과거 데이터 등으로 지정이 없으면 라운드로빈/1:1 규칙으로 폴백.
                    $table = null;
                    if ($roster->rotation_table_id !== null) {
                        $table = $tables->firstWhere('id', (int) $roster->rotation_table_id);
                    }
                    if ($table === null) {
                        $table = $this->pickTableForSeed($tables, $gender, $isOne, $occupied, $cursor);
                    }
                    if ($table === null) {
                        // 1:1 인데 그 성별을 더 앉힐 자리가 없으면 건너뛴다.
                        $skipped++;

                        continue;
                    }

                    // 유일한 더미 전화번호 생성 (이 행사 내 unique 제약 충족)
                    $phone = $this->nextDummyPhone($usedPhones);

                    $factory = \App\Models\Member::factory()->for($event, 'eventRound');
                    $factory = $gender === Member::GENDER_FEMALE ? $factory->female() : $factory->male();

                    $member = $factory->create([
                        'name'     => (string) $ticket,
                        'phone'    => $phone,
                        'password' => 'q',
                        'platform' => $this->normalizePlatform($roster->platform),
                    ]);

                    \App\Models\RotationSeat::create([
                        'event_id'          => $eventId,
                        'member_id'         => $member->id,
                        'rotation_table_id' => $table->id,
                    ]);

                    $occupied[$table->id][$gender] = true;
                    $created++;
                }

                $res            = helpersSuccessMessage("입장등록 더미 {$created}건 생성, {$skipped}건 건너뜀 (이미 등록/자리 없음)");
                $res['created'] = $created;
                $res['skipped'] = $skipped;

                return $res;
            });
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /** [개발용] 더미 명단 한 번에 추가할 수 있는 성별당 최대 인원 */
    public const DEV_ROSTER_MAX = 100;

    /**
     * [개발용] 체크인 명단에 더미 신청자를 추가한다. (닉네임: 더미남N / 더미여N, 플랫폼 무작위)
     *
     * 닉네임 번호는 이 행사에 이미 있는 더미 닉네임 뒤에서 이어 붙인다.
     *
     * @return array{isSuccess:bool, msg?:string, created?:int}
     */
    public function devSeedRosters(int $eventId, int $maleCount, int $femaleCount): array
    {
        try {
            $maleCount   = max(0, min(self::DEV_ROSTER_MAX, $maleCount));
            $femaleCount = max(0, min(self::DEV_ROSTER_MAX, $femaleCount));
            if ($maleCount + $femaleCount === 0) {
                return helpersFailMessage('추가할 인원을 1명 이상 입력해 주세요.');
            }

            $platforms = [Member::PLATFORM_MUNTO, Member::PLATFORM_FRIP];

            return DB::transaction(function () use ($eventId, $maleCount, $femaleCount, $platforms): array {
                $created = 0;
                foreach ([Member::GENDER_MALE => $maleCount, Member::GENDER_FEMALE => $femaleCount] as $gender => $count) {
                    $prefix = $gender === Member::GENDER_FEMALE ? '더미여' : '더미남';
                    $start  = EventRoster::where('event_id', $eventId)
                        ->where('name', 'like', $prefix . '%')
                        ->count();

                    for ($i = 1; $i <= $count; $i++) {
                        EventRoster::create([
                            'event_id' => $eventId,
                            'name'     => $prefix . ($start + $i),
                            'gender'   => $gender,
                            'platform' => $platforms[array_rand($platforms)],
                        ]);
                        $created++;
                    }
                }

                $res            = helpersSuccessMessage("더미 명단 {$created}명을 추가했습니다.");
                $res['created'] = $created;

                return $res;
            });
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * [개발용] 아직 미도착인 명단을 전부 체크인한다. (번호표 자동 배정 + 테이블 자동 지정)
     *
     * 테이블 지정 규칙은 체크인 모달의 기본 선택과 같다.
     *  - 1:1(one): 그 성별이 아직 없는 테이블 중 번호가 가장 작은 곳. 자리가 없으면 건너뛴다.
     *  - 그 외(와인파티): 그 성별 인원이 가장 적은 테이블 (동률이면 전체 인원 적은 곳 → 번호 작은 곳).
     *
     * @return array{isSuccess:bool, msg?:string, checked?:int, skipped?:int}
     */
    public function devCheckInAll(int $eventId): array
    {
        try {
            $event = EventRound::find($eventId);
            if ($event === null) {
                return helpersFailMessage('행사를 찾을 수 없습니다.');
            }

            $tables = RotationTable::where('event_id', $eventId)->orderBy('table_no')->get(['id', 'table_no']);
            if ($tables->isEmpty()) {
                return helpersFailMessage('테이블이 없어 체크인할 수 없습니다. 먼저 테이블을 생성해 주세요.');
            }

            $isOne = $event->isOneToOne();

            return DB::transaction(function () use ($eventId, $tables, $isOne): array {
                // 현재 테이블별 성별 인원 [table_id => ['M'=>n,'F'=>n]]
                $counts = [];
                foreach ($tables as $t) {
                    $counts[$t->id] = [Member::GENDER_MALE => 0, Member::GENDER_FEMALE => 0];
                }
                EventRoster::where('event_id', $eventId)
                    ->whereNotNull('checked_in_at')
                    ->whereNotNull('rotation_table_id')
                    ->get(['gender', 'rotation_table_id'])
                    ->each(function (EventRoster $r) use (&$counts): void {
                        $g = $r->gender === Member::GENDER_FEMALE ? Member::GENDER_FEMALE : Member::GENDER_MALE;
                        if (isset($counts[$r->rotation_table_id])) {
                            $counts[$r->rotation_table_id][$g]++;
                        }
                    });

                $pending = EventRoster::where('event_id', $eventId)
                    ->whereNull('checked_in_at')
                    ->orderBy('gender')
                    ->orderBy('id')
                    ->get();

                $checked = 0;
                $skipped = 0;

                foreach ($pending as $roster) {
                    $gender = $roster->gender === Member::GENDER_FEMALE ? Member::GENDER_FEMALE : Member::GENDER_MALE;
                    $other  = $gender === Member::GENDER_FEMALE ? Member::GENDER_MALE : Member::GENDER_FEMALE;

                    $tableId = null;
                    if ($isOne) {
                        foreach ($tables as $t) {
                            if ($counts[$t->id][$gender] === 0) {
                                $tableId = $t->id;
                                break;
                            }
                        }
                    } else {
                        $best = null;
                        foreach ($tables as $t) {
                            $key = [$counts[$t->id][$gender], $counts[$t->id][$gender] + $counts[$t->id][$other], (int) $t->table_no];
                            if ($best === null || $key < $best[0]) {
                                $best = [$key, $t->id];
                            }
                        }
                        $tableId = $best[1];
                    }

                    if ($tableId === null) {
                        $skipped++;
                        continue;
                    }

                    if ($roster->ticket_no === null) {
                        $roster->ticket_no = $this->nextTicketNo($eventId, $gender);
                    }
                    $roster->rotation_table_id = $tableId;
                    $roster->checked_in_at     = now();
                    $roster->save();

                    $counts[$tableId][$gender]++;
                    $checked++;
                }

                $msg            = "더미 체크인 {$checked}명 완료" . ($skipped > 0 ? " (남은 자리가 없어 {$skipped}명 건너뜀)" : '');
                $res            = helpersSuccessMessage($msg);
                $res['checked'] = $checked;
                $res['skipped'] = $skipped;

                return $res;
            });
        } catch (UniqueConstraintViolationException) {
            return helpersFailMessage('번호표가 겹쳤습니다. 다시 시도해 주세요.');
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * [개발용] 더미 좌석 배치용 테이블을 고른다.
     *
     * - 1:1(one): 그 성별이 아직 앉지 않은 테이블 중 테이블 번호가 가장 작은 곳. 없으면 null.
     * - 그 외(와인파티 등): 성별별 라운드로빈으로 순환 배정.
     *
     * @param \Illuminate\Support\Collection<int, \App\Models\RotationTable> $tables
     * @param array<int, array<string, bool>>                               $occupied
     * @param array<string, int>                                            $cursor
     */
    private function pickTableForSeed($tables, string $gender, bool $isOne, array $occupied, array &$cursor): ?\App\Models\RotationTable
    {
        if ($isOne) {
            foreach ($tables as $table) {
                if (empty($occupied[$table->id][$gender])) {
                    return $table;
                }
            }

            return null;
        }

        $count = $tables->count();
        $table = $tables[$cursor[$gender] % $count];
        $cursor[$gender]++;

        return $table;
    }

    /**
     * [개발용] 아직 쓰이지 않은 더미 전화번호(010 + 8자리)를 만든다.
     *
     * @param \Illuminate\Support\Collection<string, int> $usedPhones 사용 중인 번호 집합(flip). 생성한 번호를 넣어 갱신한다.
     */
    private function nextDummyPhone(\Illuminate\Support\Collection &$usedPhones): string
    {
        $seq = 10000000; // 01010000000 부터 순차
        do {
            $phone = '010' . $seq;
            $seq++;
        } while ($usedPhones->has($phone));

        $usedPhones->put($phone, true);

        return $phone;
    }
}
