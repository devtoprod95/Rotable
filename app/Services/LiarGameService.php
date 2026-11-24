<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * 라이어 게임 - 서버 권위(Redis) 게임 상태 관리.
 *
 * 규칙
 *  - 한 명(라이어)을 뺀 모든 참가자는 같은 "주제 + 제시어"를 받고, 라이어는 주제만 받는다.
 *  - 정해진 순서대로 한 명씩 제시어를 직접(말로) 설명한다. 라이어는 들키지 않게 아는 척한다.
 *  - 모두 설명하면 투표로 라이어를 지목한다. 최다 득표자가 라이어면 시민 승리,
 *    아니면(동점 포함) 라이어 승리.
 *
 * 비밀 정보(제시어/라이어)는 공개 상태에 담지 않는다. 각 참가자는 자기 몫(secret)만 응답으로 받는다.
 * 게임판은 `scope` 문자열로 구분한다. (HalliGalliService 와 같은 방식)
 */
class LiarGameService
{
    public const STATE_TTL = 21600; // 6시간

    public const MIN_PLAYERS = 3;

    /** 공개 게임방(전역 1개) scope. */
    public const PUBLIC_SCOPE = 'public';

    /** 주제 => 제시어 목록 */
    public const WORDS = [
        '음식'   => ['김치찌개', '떡볶이', '삼겹살', '초밥', '짜장면', '치킨', '피자', '냉면', '라면', '비빔밥', '햄버거', '마라탕'],
        '동물'   => ['강아지', '고양이', '펭귄', '기린', '코끼리', '토끼', '고슴도치', '판다', '햄스터', '수달', '독수리', '상어'],
        '장소'   => ['놀이공원', '도서관', '찜질방', '편의점', '공항', '영화관', '캠핑장', '노래방', '수영장', '미술관', '해변', '병원'],
        '직업'   => ['소방관', '요리사', '의사', '선생님', '경찰관', '가수', '변호사', '파일럿', '바리스타', '개발자', '화가', '농부'],
        '스포츠' => ['축구', '야구', '농구', '볼링', '테니스', '수영', '골프', '스키', '배드민턴', '탁구', '요가', '클라이밍'],
        '영화'   => ['어벤져스', '겨울왕국', '기생충', '타이타닉', '해리포터', '인터스텔라', '라라랜드', '반지의 제왕', '토이스토리', '범죄도시', '명량', '스파이더맨'],
        '물건'   => ['우산', '이어폰', '거울', '손목시계', '텀블러', '여권', '선글라스', '지갑', '보조배터리', '향수', '립밤', '캐리어'],
        '데이트' => ['벚꽃 구경', '영화 보기', '한강 피크닉', '방탈출', '보드게임 카페', '드라이브', '맛집 탐방', '전시회', '놀이공원', '야구장', '온천 여행', '쿠킹 클래스'],
        '계절'   => ['벚꽃', '장마', '단풍', '눈사람', '수박', '붕어빵', '크리스마스', '해수욕', '황사', '설날', '추석', '핫팩'],
        '음료'   => ['아메리카노', '소주', '맥주', '와인', '막걸리', '콜라', '핫초코', '버블티', '위스키', '칵테일', '레몬에이드', '식혜'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function publicState(string $scope): array
    {
        return $this->present($this->load($scope));
    }

    /**
     * 내 몫의 비밀 정보. (게임 중이 아니거나 참가자가 아니면 null)
     *
     * @return array{role:string, topic:string, word:?string, vote:?string}|null
     */
    public function secretFor(string $scope, string $playerId): ?array
    {
        return $this->secret($this->load($scope), $playerId);
    }

    /**
     * 액션을 처리한다.
     *
     * @param  string $action  join|start|to_vote|vote|reveal|reset|rename
     * @param  ?string $target vote 의 대상 참가자 id / start 의 주제(없거나 모르는 주제면 랜덤)
     * @return array{ok:bool, message:?string, state:array<string,mixed>, secret:?array<string,mixed>, changed:bool}
     */
    public function act(string $scope, string $playerId, string $label, string $action, ?string $target = null): array
    {
        $lock = Cache::lock('liar-lock:' . $scope, 5);

        return $lock->block(3, function () use ($scope, $playerId, $label, $action, $target): array {
            $state   = $this->load($scope);
            $message = null;
            $changed = true;

            switch ($action) {
                case 'join':
                    $changed = $this->join($state, $playerId, $label);
                    break;
                case 'start':
                    [$changed, $message] = $this->start($state, $playerId, $target);
                    break;
                case 'to_vote':
                    [$changed, $message] = $this->toVote($state, $playerId);
                    break;
                case 'vote':
                    [$changed, $message] = $this->vote($state, $playerId, $target);
                    break;
                case 'reveal':
                    [$changed, $message] = $this->forceReveal($state, $playerId);
                    break;
                case 'reset':
                    [$changed, $message] = $this->reset($state, $playerId, $label);
                    break;
                case 'rename':
                    [$changed, $message] = $this->rename($state, $playerId, $label);
                    break;
                default:
                    $changed = false;
            }

            if ($changed) {
                $state['version'] = (int) $state['version'] + 1;
                $this->save($scope, $state);
            }

            return [
                'ok'      => $changed,
                'message' => $message,
                'state'   => $this->present($state),
                'secret'  => $this->secret($state, $playerId),
                'changed' => $changed,
            ];
        });
    }

    /** 닉네임 변경. 게임 중에도 가능하며, 다른 참가자와 겹치면 숫자를 붙인다. */
    private function rename(array &$state, string $playerId, string $label): array
    {
        $idx = $this->indexOf($state, $playerId);
        if ($idx === null) {
            return [false, '참가한 사람만 바꿀 수 있어요.'];
        }

        $others = [];
        foreach ($state['players'] as $i => $p) {
            if ($i !== $idx) {
                $others[] = (string) $p['label'];
            }
        }
        $label = CharacterNickname::unique($others, $label);

        if ($label === $state['players'][$idx]['label']) {
            return [false, null];
        }

        $old                           = $state['players'][$idx]['label'];
        $state['players'][$idx]['label'] = $label;
        $state['log']                  = $old . ' 님이 ' . $label . ' (으)로 닉네임을 바꿨어요.';

        return [true, null];
    }

    /** 대기 중에만 참가할 수 있다. */
    private function join(array &$state, string $playerId, string $label): bool
    {
        if ($this->indexOf($state, $playerId) !== null || $state['status'] !== 'waiting') {
            return false;
        }

        $label = CharacterNickname::unique(array_column($state['players'], 'label'), $label);

        $state['players'][] = ['id' => $playerId, 'label' => $label];
        $state['log']       = $label . ' 님이 참가했어요.';

        return true;
    }

    /** @return array{0:bool,1:?string} */
    private function start(array &$state, string $playerId, ?string $chosenTopic = null): array
    {
        if ($this->indexOf($state, $playerId) === null) {
            return [false, '먼저 참가해 주세요.'];
        }
        if ($state['status'] !== 'waiting') {
            return [false, null];
        }
        if (count($state['players']) < self::MIN_PLAYERS) {
            return [false, self::MIN_PLAYERS . '명 이상 참가해야 시작할 수 있어요.'];
        }

        $topic = ($chosenTopic !== null && isset(self::WORDS[$chosenTopic]))
            ? $chosenTopic
            : (string) array_rand(self::WORDS);
        $words = self::WORDS[$topic];
        $ids   = array_map(fn (array $p): string => (string) $p['id'], $state['players']);

        $state['status']  = 'describe';
        $state['gameNo']  = (int) $state['gameNo'] + 1;
        $state['topic']   = $topic;
        $state['word']    = $words[array_rand($words)];
        $state['liarId']  = $ids[array_rand($ids)];
        $state['votes']   = [];
        $state['log']     = '게임 시작! 주제는 "' . $topic . '" 이에요. 내 카드를 확인하고 돌아가며 설명해요.';

        return [true, null];
    }

    /** 설명이 끝났다고 보고 투표 단계로 넘어간다. 참가자 누구나 누를 수 있다. */
    private function toVote(array &$state, string $playerId): array
    {
        if ($state['status'] !== 'describe' || $this->indexOf($state, $playerId) === null) {
            return [false, null];
        }

        $state['status'] = 'vote';
        $state['log']    = '설명이 끝났어요! 라이어라고 생각하는 사람을 투표해요.';

        return [true, null];
    }

    /** @return array{0:bool,1:?string} */
    private function vote(array &$state, string $playerId, ?string $target): array
    {
        if ($state['status'] !== 'vote' || $this->indexOf($state, $playerId) === null) {
            return [false, null];
        }
        if ($target === null || $this->indexOf($state, $target) === null) {
            return [false, '투표할 사람을 골라주세요.'];
        }
        if ($target === $playerId) {
            return [false, '나 자신에게는 투표할 수 없어요.'];
        }

        // 결과를 공개하기 전까지는 다시 눌러 투표를 바꿀 수 있다. (자동 공개하지 않는다)
        $state['votes'][$playerId] = $target;
        $state['log'] = '투표 중이에요. (' . count($state['votes']) . '/' . count($state['players']) . ') 모두 끝나면 결과를 공개하세요.';

        return [true, null];
    }

    /** 지금까지의 표로 결과를 공개한다. (투표를 안 한 사람이 있어도 가능) */
    private function forceReveal(array &$state, string $playerId): array
    {
        if ($state['status'] !== 'vote' || $this->indexOf($state, $playerId) === null) {
            return [false, null];
        }

        $this->finish($state);

        return [true, null];
    }

    /** 대기 상태로 돌아간다. 참가자는 그대로 유지해 바로 다시 시작할 수 있다. */
    private function reset(array &$state, string $playerId, string $label): array
    {
        if ($state['status'] === 'waiting') {
            return [false, null];
        }

        $players = $state['players'];
        $version = (int) $state['version'];
        $gameNo  = (int) $state['gameNo'];

        $state            = $this->fresh();
        $state['version'] = $version;
        $state['gameNo']  = $gameNo;
        $state['players'] = $players;

        // 공개방처럼 구경하던 사람이 새로 시작하는 경우 자동으로 참가시킨다.
        $this->join($state, $playerId, $label);
        $state['log'] = '새 게임을 준비해요. 준비되면 시작하세요.';

        return [true, null];
    }

    private function finish(array &$state): void
    {
        $tally = [];
        foreach ($state['votes'] as $target) {
            $tally[$target] = ($tally[$target] ?? 0) + 1;
        }

        arsort($tally);
        $counts = array_values($tally);
        $top    = $counts[0] ?? 0;
        // 최다 득표자가 단독이고 그 사람이 라이어일 때만 시민 승리. (동점이면 라이어 승리)
        $topIds = array_keys(array_filter($tally, fn (int $c): bool => $c === $top));
        $caught = $top > 0 && count($topIds) === 1 && (string) $topIds[0] === (string) $state['liarId'];

        $state['status'] = 'result';
        $state['caught'] = $caught;
        $state['log']    = $caught
            ? '라이어를 찾았어요! 시민 승리 🎉'
            : '라이어가 끝까지 숨었어요! 라이어 승리 🕵️';
    }

    /** @return array{role:string, topic:string, word:?string, vote:?string}|null */
    private function secret(array $state, string $playerId): ?array
    {
        if (! in_array($state['status'], ['describe', 'vote', 'result'], true)) {
            return null;
        }
        if ($this->indexOf($state, $playerId) === null) {
            return null;
        }

        $isLiar = (string) $state['liarId'] === $playerId;

        return [
            'role'  => $isLiar ? 'liar' : 'citizen',
            'topic' => (string) $state['topic'],
            'word'  => $isLiar ? null : (string) $state['word'],
            // 내가 지금 지목한 사람 (투표 수정/표시용. 다른 사람에게는 보이지 않는다)
            'vote'  => isset($state['votes'][$playerId]) ? (string) $state['votes'][$playerId] : null,
        ];
    }

    /**
     * 공개 상태. 결과 단계 전에는 라이어/제시어/누가 누구에게 투표했는지를 담지 않는다.
     *
     * @return array<string, mixed>
     */
    private function present(array $state): array
    {
        $isResult = $state['status'] === 'result';
        $started  = $state['status'] !== 'waiting';

        $tally = [];
        if ($isResult) {
            foreach ($state['votes'] as $target) {
                $tally[(string) $target] = ($tally[(string) $target] ?? 0) + 1;
            }
        }

        $players = [];
        foreach ($state['players'] as $p) {
            $id        = (string) $p['id'];
            $players[] = [
                'id'    => $id,
                'label' => $p['label'],
                'voted' => isset($state['votes'][$id]),
                'votes' => $isResult ? ($tally[$id] ?? 0) : null,
            ];
        }

        return [
            'version' => (int) $state['version'],
            'gameNo'  => (int) $state['gameNo'],
            'status'  => $state['status'],
            'players' => $players,
            'topic'   => $started ? $state['topic'] : null,
            'topics'  => array_keys(self::WORDS),
            'log'     => $state['log'],
            'result'  => $isResult
                ? [
                    'liarId' => (string) $state['liarId'],
                    'word'   => $state['word'],
                    'caught' => (bool) $state['caught'],
                ]
                : null,
        ];
    }

    /** @return array<string, mixed> */
    private function fresh(): array
    {
        return [
            'version' => 0,
            'gameNo'  => 0,
            'status'  => 'waiting', // waiting | describe | vote | result
            'players' => [],
            'topic'   => null,
            'word'    => null,
            'liarId'  => null,
            'votes'   => [],
            'caught'  => false,
            'log'     => '참가 버튼을 눌러 함께할 사람을 모아요. (' . self::MIN_PLAYERS . '명 이상)',
        ];
    }

    private function indexOf(array $state, string $playerId): ?int
    {
        foreach ($state['players'] as $i => $p) {
            if ((string) $p['id'] === $playerId) {
                return $i;
            }
        }

        return null;
    }

    private function labelFor(array $state, string $playerId): string
    {
        $idx = $this->indexOf($state, $playerId);

        return $idx === null ? '누군가' : (string) $state['players'][$idx]['label'];
    }

    /** @return array<string, mixed> */
    private function load(string $scope): array
    {
        $raw = Redis::get('liar:' . $scope);
        if (! is_string($raw)) {
            return $this->fresh();
        }

        $state = json_decode($raw, true);

        return is_array($state) ? $state : $this->fresh();
    }

    /** @param array<string, mixed> $state */
    private function save(string $scope, array $state): void
    {
        Redis::setex('liar:' . $scope, self::STATE_TTL, json_encode($state));
    }

    /** 테이블 게임판 scope. 로테이션 턴이 바뀌면 앉은 사람이 달라지므로 포함해 새로 시작한다. */
    public static function tableScope(int $eventId, int $tableNo, int $round): string
    {
        return "table:{$eventId}:{$tableNo}:{$round}";
    }
}
