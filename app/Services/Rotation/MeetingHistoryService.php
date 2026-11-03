<?php

namespace App\Services\Rotation;

use App\Models\EventRound;
use App\Models\Member;
use App\Models\Vote;
use App\Services\VisitStatService;

/**
 * 만남 이력 조회 도메인 서비스.
 *
 * "만남"은 같은 행사에 함께 참여한 것을 의미하며, 사람은 전화번호(phone)로 식별한다.
 * (RotationService 에서 분리)
 */
class MeetingHistoryService
{

    /* ======================================================================
     | 만남 이력 조회 — "만남"은 같은 행사에 함께 참여한 것을 의미한다.
     | 사람은 전화번호(phone)로 식별한다. (정확히 일치)
     * ==================================================================== */

    /**
     * 특정 전화번호의 사람이 "함께 참여한(= 같은 행사에 있었던)" 모든 상대 목록.
     *
     * - 입력 전화번호로 참여한 모든 회원 레코드(여러 행사 가능)를 찾고,
     *   그 각 행사에 함께 참여한 "이성" 회원들을 상대로 모은다. (동성은 제외)
     * - 상대는 (전화번호 + 행사) 단위로 1건씩 나열한다. (같은 상대와 여러 행사에서 만났으면 여러 건)
     * - 상대 프로필은 그 전화번호의 "가장 최근 입력 정보"로 통일한다.
     * - 각 건에는 행사 정보, 상대 프로필, 그 행사에서의 상호 매칭 여부를 담는다.
     *
     * @return array{
     *     found: bool,
     *     me: array<string, mixed>|null,
     *     events_count: int,
     *     partners: array<int, array<string, mixed>>,
     *     events: array<int, array<string, mixed>>
     * }
     */
    public function meetingPartnersByPhone(string $phone): array
    {
        $phone = trim($phone);

        // 이 전화번호로 참여한 내 회원 레코드들(행사별). id 역순 → first() 가 가장 최근 입력 정보.
        $myMembers = Member::query()
            ->where('phone', $phone)
            ->with('eventRound')
            ->orderByDesc('id')
            ->get();

        if ($myMembers->isEmpty()) {
            return ['found' => false, 'me' => null, 'events_count' => 0, 'events_count_by_type' => [], 'partners' => [], 'events' => []];
        }

        // 검색한 사람의 성별(최근 레코드 기준). 상대는 "이성"만 보여준다.
        $myGender = $myMembers->first()->gender;
        $oppositeGender = $myGender === Member::GENDER_MALE
            ? Member::GENDER_FEMALE
            : ($myGender === Member::GENDER_FEMALE ? Member::GENDER_MALE : null);

        $eventIds = $myMembers->pluck('event_id')->unique()->values()->all();

        // 내가 참여한 행사들에 함께 있던 회원 전부. (나 자신 제외)
        // 성별 필터는 프런트에서 토글(기본 이성)하므로 여기서는 전부 가져오고 is_opposite 로 표시한다.
        $myMemberIds = $myMembers->pluck('id')->all();
        $others = Member::query()
            ->whereIn('event_id', $eventIds)
            ->whereNotIn('id', $myMemberIds)
            ->with('eventRound')
            ->orderByDesc('id') // id 역순 → 전화번호별 첫 등장이 "가장 최근 입력 정보".
            ->get();

        // 전화번호별 최신 프로필(가장 최근 입력 정보) 맵.
        $latestByPhone = [];
        foreach ($others as $o) {
            if (! isset($latestByPhone[$o->phone])) {
                $latestByPhone[$o->phone] = $o; // 이미 id 역순이라 첫 등장이 최신.
            }
        }

        // 상호 매칭 판정용: 내가 참여한 행사들의 모든 투표를 (event, voter, target) 맵으로.
        $votedMap = $this->buildVotedMap($eventIds);

        // 상대별 "나(검색자) ↔ 그 상대" 사이에 오간 메시지 수 집계. (양방향, 쿼리 1개)
        // 2-2 상세 페이지의 "A↔B 메시지 합계"와 동일한 기준으로 맞춘다.
        // key = 상대(other) 회원 id, value = 나와 그 상대가 주고받은 총 메시지 수.
        $otherIds = $others->pluck('id')->all();
        $betweenCountByOther = [];
        if (! empty($otherIds) && ! empty($myMemberIds)) {
            $msgRows = \App\Models\Message::query()
                ->where(function ($q) use ($myMemberIds, $otherIds) {
                    $q->where(function ($qq) use ($myMemberIds, $otherIds) {
                        $qq->whereIn('sender_id', $myMemberIds)->whereIn('receiver_id', $otherIds);
                    })->orWhere(function ($qq) use ($myMemberIds, $otherIds) {
                        $qq->whereIn('sender_id', $otherIds)->whereIn('receiver_id', $myMemberIds);
                    });
                })
                ->get(['sender_id', 'receiver_id']);

            $myIdSet = array_flip($myMemberIds);
            foreach ($msgRows as $row) {
                // 나/상대 중 "상대 쪽" id 를 키로 집계한다.
                $otherSide = isset($myIdSet[$row->sender_id]) ? $row->receiver_id : $row->sender_id;
                $betweenCountByOther[$otherSide] = ($betweenCountByOther[$otherSide] ?? 0) + 1;
            }
        }

        // 상대의 "행사 유형별 누적 방문 횟수" 맵. (phone => ['wine'=>N, 'one'=>M])
        // 행사 상세 참여자 리스트와 동일한 표기(VisitStatService 재사용).
        $otherPhones = $others->pluck('phone')->unique()->values()->all();
        $visitCountsByPhone = app(VisitStatService::class)->visitCountsByType($otherPhones);

        // 행사별 "내 회원 레코드" 매핑 (행사 안에서 상대와 매칭 비교할 때 사용).
        $myByEvent = $myMembers->keyBy('event_id');

        // 행사별 "본인이 관리자에게 보낸 요청 수" 집계. (쿼리 1개) — 선택 행사별로 버튼 노출.
        $myRequestByEvent = [];
        if (! empty($myMemberIds)) {
            $myRequestByEvent = \App\Models\AdminRequest::query()
                ->whereIn('member_id', $myMemberIds)
                ->selectRaw('event_id, member_id, COUNT(*) as cnt')
                ->groupBy('event_id', 'member_id')
                ->get()
                ->mapWithKeys(fn ($row) => [(int) $row->event_id => ['member_id' => (int) $row->member_id, 'cnt' => (int) $row->cnt]])
                ->all();
        }

        // 행사별 selectbox 용 행사 목록(내가 참여한 행사만, 최근순).
        $eventsForFilter = [];

        $partners = [];
        foreach ($others as $other) {
            $eventId  = $other->event_id;
            $meInEvt  = $myByEvent->get($eventId);
            if ($meInEvt === null) {
                continue;
            }

            $iVotedThem = isset($votedMap[$eventId][$meInEvt->id][$other->id]);
            $theyVotedMe = isset($votedMap[$eventId][$other->id][$meInEvt->id]);

            // 프로필은 그 전화번호의 "가장 최근 입력 정보"로 통일.
            $profileSource = $latestByPhone[$other->phone] ?? $other;

            $partners[] = [
                'event'             => $this->eventPayload($other->eventRound),
                'event_id'          => $eventId,
                // 그 행사에서의 상대 회원 레코드 id (메시지 모달 필터용).
                'partner_member_id' => $other->id,
                // 그 행사에서의 내(검색자) 회원 레코드 id (대화 메시지 모달 필터용).
                'my_member_id'      => $meInEvt->id,
                'partner'           => $this->meetingPersonPayload($profileSource),
                // 그 행사 "당시" 상대가 신청한 플랫폼. (프로필과 달리 행사마다 다를 수 있어 최신값이 아닌 그 레코드 값)
                'event_platform'    => $other->platform,
                // 상대가 이성인지(검색자 기준). 프런트에서 이성/동성 토글에 사용.
                'is_opposite'       => $oppositeGender !== null && $other->gender === $oppositeGender,
                // 나와 그 상대가 이 행사에서 주고받은 메시지 수 (양방향 합계, 0이면 버튼 숨김).
                'message_count'     => (int) ($betweenCountByOther[$other->id] ?? 0),
                // 상대의 행사 유형별 누적 방문 횟수. (예: ['wine'=>3,'one'=>2])
                'visit_counts'      => $visitCountsByPhone[$other->phone] ?? [],
                'i_voted'           => $iVotedThem,
                'they_voted'        => $theyVotedMe,
                'is_mutual'         => $iVotedThem && $theyVotedMe,
            ];

            if (! isset($eventsForFilter[$eventId])) {
                $eventsForFilter[$eventId] = $this->eventPayload($other->eventRound);
            }
        }

        // 정렬: ① 상호 매칭 먼저 → ② 한쪽이라도 투표(나 선택/상대 선택) → ③ 이름 가나다순.
        usort($partners, function ($a, $b) {
            $rank = function ($p) {
                if ($p['is_mutual']) {
                    return 0;
                }
                if ($p['i_voted'] || $p['they_voted']) {
                    return 1;
                }
                return 2;
            };
            $ra = $rank($a);
            $rb = $rank($b);
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }
            return strcmp((string) ($a['partner']['name'] ?? ''), (string) ($b['partner']['name'] ?? ''));
        });

        // 행사 필터 목록도 일시 역순. 각 행사에 "내가 이 행사에서 보낸 요청" 정보를 붙인다.
        $events = array_values($eventsForFilter);
        foreach ($events as &$evt) {
            $eid = $evt['id'];
            $myReq = $myRequestByEvent[$eid] ?? null;
            // 그 행사에서의 내 회원 레코드 id (요청 모달 필터용).
            $evt['my_member_id']    = optional($myByEvent->get($eid))->id;
            $evt['my_request_count'] = $myReq['cnt'] ?? 0;
        }
        unset($evt);
        usort($events, function ($a, $b) {
            return ($b['event_time'] ?? '') <=> ($a['event_time'] ?? '');
        });

        return [
            'found'        => true,
            'me'           => $this->meetingPersonPayload($myMembers->first()),
            'my_gender'    => $myGender,
            'events_count' => count($eventIds),
            // 본인(검색한 전화번호)의 행사 유형별 참여 횟수. (예: ['wine'=>3,'one'=>2])
            'events_count_by_type' => app(VisitStatService::class)
                ->visitCountsByType([$myMembers->first()->phone])[$myMembers->first()->phone] ?? [],
            'partners'     => $partners,
            'events'       => $events,
        ];
    }

    /**
     * 두 전화번호(A, B)가 "함께 참여한" 모든 행사의 만남 이력 상세.
     *
     * - 두 번호가 같은 행사에 모두 참여한 행사만 모은다.
     * - 각 행사마다: 행사 정보, 양쪽 프로필, 서로의 투표/매칭 관계, 주고받은 메시지 건수.
     *
     * @return array{
     *     found: bool,
     *     a: array<string, mixed>|null,
     *     b: array<string, mixed>|null,
     *     meetings: array<int, array<string, mixed>>
     * }
     */
    public function meetingHistoryBetween(string $phoneA, string $phoneB): array
    {
        $phoneA = trim($phoneA);
        $phoneB = trim($phoneB);

        // 각 전화번호의 참여 레코드(행사별). id 역순으로 정렬해 first() 가 "가장 최근 입력 정보"가 되게 한다.
        $aAll = Member::where('phone', $phoneA)->with('eventRound')->orderByDesc('id')->get();
        $bAll = Member::where('phone', $phoneB)->with('eventRound')->orderByDesc('id')->get();

        // 최신 프로필(별도 쿼리 없이 이미 로드한 컬렉션에서 추출).
        $aLatest = $aAll->first();
        $bLatest = $bAll->first();

        $aMembers = $aAll->keyBy('event_id');
        $bMembers = $bAll->keyBy('event_id');

        if ($aMembers->isEmpty() || $bMembers->isEmpty()) {
            return [
                'found' => false,
                'a'     => $aLatest ? $this->meetingPersonPayload($aLatest) : null,
                'b'     => $bLatest ? $this->meetingPersonPayload($bLatest) : null,
                'meetings' => [],
            ];
        }

        // 두 사람이 모두 참여한 행사 id.
        $sharedEventIds = $aMembers->keys()->intersect($bMembers->keys())->values()->all();

        if (empty($sharedEventIds)) {
            return [
                'found'    => true,
                'a'        => $this->meetingPersonPayload($aLatest),
                'b'        => $this->meetingPersonPayload($bLatest),
                'meetings' => [],
            ];
        }

        // 공유 행사들의 투표/메시지를 한 번에 로드.
        $votedMap = $this->buildVotedMap($sharedEventIds);
        $messages = \App\Models\Message::whereIn('event_id', $sharedEventIds)->get();

        // 프로필 payload(최신 정보) — 모든 행사 카드에서 공통으로 쓴다.
        $aProfile = $this->meetingPersonPayload($aLatest);
        $bProfile = $this->meetingPersonPayload($bLatest);

        $meetings = [];
        foreach ($sharedEventIds as $eventId) {
            $a = $aMembers->get($eventId);
            $b = $bMembers->get($eventId);

            $aVotedB = isset($votedMap[$eventId][$a->id][$b->id]);
            $bVotedA = isset($votedMap[$eventId][$b->id][$a->id]);

            // 두 사람 사이에 오간 메시지만 추린다.
            $between = $messages->filter(function ($m) use ($eventId, $a, $b) {
                return $m->event_id === $eventId
                    && (($m->sender_id === $a->id && $m->receiver_id === $b->id)
                        || ($m->sender_id === $b->id && $m->receiver_id === $a->id));
            });

            $aToB = $between->where('sender_id', $a->id)->count();
            $bToA = $between->where('sender_id', $b->id)->count();

            $messageList = $between->sortBy('id')->map(function ($m) use ($a) {
                return [
                    'from'       => $m->sender_id === $a->id ? 'a' : 'b',
                    'body'       => $m->body,
                    'read'       => $m->read_at !== null,
                    'created_at' => $m->created_at?->toIso8601String(),
                ];
            })->values()->all();

            $meetings[] = [
                'event'        => $this->eventPayload($a->eventRound),
                'event_id'     => $eventId,
                'a'            => $aProfile,
                'b'            => $bProfile,
                // 그 행사에서의 각자 member 레코드 id (매칭 조회 모달 필터용).
                'a_member_id'  => $a->id,
                'b_member_id'  => $b->id,
                // 그 행사 "당시" 각자 신청한 플랫폼. (프로필과 달리 행사마다 다를 수 있음)
                'a_platform'   => $a->platform,
                'b_platform'   => $b->platform,
                'a_voted_b'    => $aVotedB,
                'b_voted_a'    => $bVotedA,
                'is_mutual'    => $aVotedB && $bVotedA,
                'messages_a_to_b' => $aToB,
                'messages_b_to_a' => $bToA,
                'messages'     => $messageList,
            ];
        }

        // 행사 일시 역순.
        usort($meetings, function ($x, $y) {
            return ($y['event']['event_time'] ?? '') <=> ($x['event']['event_time'] ?? '');
        });

        return [
            'found'    => true,
            'a'        => $aProfile,
            'b'        => $bProfile,
            'meetings' => $meetings,
        ];
    }

    /**
     * 주어진 행사들의 투표를 (event_id => [voter_id => [target_id => true]]) 맵으로 만든다.
     * 상호 매칭/투표 방향 판정에 공통으로 쓴다.
     *
     * @param array<int, int> $eventIds
     * @return array<int, array<int, array<int, true>>>
     */
    private function buildVotedMap(array $eventIds): array
    {
        $map = [];
        if (empty($eventIds)) {
            return $map;
        }

        foreach (Vote::query()->whereIn('event_id', $eventIds)->get(['event_id', 'voter_id', 'target_id']) as $v) {
            $map[$v->event_id][$v->voter_id][$v->target_id] = true;
        }

        return $map;
    }

    /**
     * 만남 이력 응답용 회원 프로필 payload.
     *
     * @return array<string, mixed>
     */
    private function meetingPersonPayload(?Member $member): array
    {
        return [
            'id'         => $member?->id,
            'name'       => $member?->name,
            'phone'      => $member?->phone,
            'gender'     => $member?->gender,
            'birth_year' => $member?->birth_year,
            'age'        => $member?->birth_year ? ((int) now()->year - (int) $member->birth_year + 1) : null,
            'job'        => $member?->job,
            'height'     => $member?->height,
            'mbti'       => $member?->mbti,
            'region'     => $member?->region,
            'is_smoker'  => $member?->is_smoker,
        ];
    }

    /**
     * 만남 이력 응답용 행사 payload.
     *
     * @return array<string, mixed>
     */
    private function eventPayload(?EventRound $event): array
    {
        return [
            'id'         => $event?->id,
            'title'      => $event?->title,
            'type'       => $event?->type,
            'type_label' => $event ? (EventRound::typeOptions()[$event->type] ?? $event->type) : null,
            'status'     => $event?->status,
            'status_label' => $event ? (EventRound::statusOptions()[$event->status] ?? $event->status) : null,
            'event_time' => $event?->event_time?->toIso8601String(),
        ];
    }
}
