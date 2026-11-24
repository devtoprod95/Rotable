<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * 테이블 할리갈리 - 서버 권위(Redis) 게임 상태 관리.
 *
 * 규칙(폰 버전 단순화)
 *  - 카드: 과일 4종(0~3) × 개수(1~5), 원작 구성 56장. 모든 참가자가 한 덱을 공유한다.
 *  - 자기 차례 참가자가 '카드 뒤집기'를 누르면 덱 맨 위 카드가 그 사람 앞 더미 맨 위에 놓인다.
 *  - 모든 사람 더미의 '맨 위 카드'에서 같은 과일 합이 정확히 5개면 종을 칠 수 있다.
 *  - 종 성공: 깔린 카드를 전부 가져가 점수로 얻는다(카드 1장 = 1점).
 *  - 종 실패: -1점. 단, 다른 사람이 먼저 쳐서 이미 상황이 바뀐 뒤 도착한 종은 무시(벌점 없음).
 *  - 덱이 비면 종료, 점수가 가장 높은 사람이 승자.
 *
 * 게임판은 `scope` 문자열 하나로 구분한다. (테이블: "table:{행사}:{테이블}:{턴}", 공개방: "public")
 * 동시에 여러 기기가 누르므로 scope 단위 락 안에서 상태를 읽고-바꾸고-쓴다.
 * 상태의 `version` 은 변경될 때마다 올라가며, 종은 "내가 본 version" 과 같을 때만 인정한다.
 */
class HalliGalliService
{
    public const STATE_TTL = 21600; // 6시간

    public const FRUITS = 4;

    public const BELL_TARGET = 5;

    /** 과일별 카드 구성: 개수 => 장수 (과일당 14장) */
    private const COMPOSITION = [1 => 5, 2 => 3, 3 => 3, 4 => 2, 5 => 1];

    public const MIN_PLAYERS = 2;

    /**
     * 현재 상태를 클라이언트용으로 변환해 돌려준다. (덱 내용은 숨기고 장수만 노출)
     *
     * @return array<string, mixed>
     */
    public function publicState(string $scope): array
    {
        return $this->present($this->load($scope));
    }

    /**
     * 액션을 처리한다.
     *
     * @param  string      $scope    게임판 구분자
     * @param  string      $playerId 참가자 식별자 (테이블: 회원 id, 공개방: 클라이언트 id)
     * @param  string      $label    화면에 보일 이름 (join 때만 사용)
     * @param  string      $action   join|start|flip|bell|reset|rename
     * @return array{ok:bool, message:?string, state:array<string,mixed>, changed:bool}
     */
    public function act(string $scope, string $playerId, string $label, string $action, ?int $version = null): array
    {
        $lock = Cache::lock('halli-lock:' . $scope, 5);

        return $lock->block(3, function () use ($scope, $playerId, $label, $action, $version): array {
            $state   = $this->load($scope);
            $message = null;
            $changed = true;

            switch ($action) {
                case 'join':
                    $changed = $this->join($state, $playerId, $label);
                    break;
                case 'start':
                    [$changed, $message] = $this->start($state, $playerId);
                    break;
                case 'flip':
                    [$changed, $message] = $this->flip($state, $playerId);
                    break;
                case 'bell':
                    [$changed, $message] = $this->bell($state, $playerId, $version);
                    break;
                case 'reset':
                    // version 은 계속 증가해야 클라이언트가 초기화 상태를 '오래된 것'으로 오인하지 않는다.
                    $previousVersion  = (int) $state['version'];
                    $state            = $this->fresh();
                    $state['version'] = $previousVersion;
                    $this->join($state, $playerId, $label);
                    $state['log'] = $label . ' 님이 게임을 새로 시작했어요.';
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

    /** 참가 등록. 이미 참가 중이면 변화 없음. 진행 중에는 새 참가를 받지 않는다. */
    private function join(array &$state, string $playerId, string $label): bool
    {
        if ($this->indexOf($state, $playerId) !== null) {
            return false;
        }
        if ($state['status'] !== 'waiting') {
            return false;
        }

        $label = CharacterNickname::unique(array_column($state['players'], 'label'), $label);

        $state['players'][] = ['id' => $playerId, 'label' => $label];
        $state['scores'][$playerId] = 0;
        $state['piles'][$playerId]  = [];
        $state['log'] = $label . ' 님이 참가했어요.';

        return true;
    }

    /** @return array{0:bool,1:?string} */
    private function start(array &$state, string $playerId): array
    {
        if ($this->indexOf($state, $playerId) === null) {
            return [false, '먼저 참가해 주세요.'];
        }
        if ($state['status'] !== 'waiting') {
            return [false, null];
        }
        if (count($state['players']) < self::MIN_PLAYERS) {
            return [false, '2명 이상 참가해야 시작할 수 있어요.'];
        }

        $state['status'] = 'playing';
        $state['deck']   = $this->shuffledDeck();
        $state['turn']   = 0;
        $state['log']    = '게임 시작! ' . $state['players'][0]['label'] . ' 님부터 카드를 뒤집어요.';

        return [true, null];
    }

    /** @return array{0:bool,1:?string} */
    private function flip(array &$state, string $playerId): array
    {
        if ($state['status'] !== 'playing') {
            return [false, null];
        }
        $idx = $this->indexOf($state, $playerId);
        if ($idx === null || $idx !== (int) $state['turn']) {
            return [false, '내 차례가 아니에요.'];
        }

        // 덱이 비었는데 또 뒤집으려 하면 종료. (마지막 카드는 종을 칠 기회를 준 뒤에 끝낸다)
        if (count($state['deck']) === 0) {
            $state['status'] = 'ended';
            $state['log']    = '카드가 모두 소진되어 게임이 끝났어요!';

            return [true, null];
        }

        $card = array_shift($state['deck']);
        $state['piles'][$playerId][] = $card;
        $state['turn'] = ($idx + 1) % count($state['players']);
        $state['log']  = $this->labelFor($state, $playerId) . ' 님이 카드를 뒤집었어요.';

        return [true, null];
    }

    /** @return array{0:bool,1:?string} */
    private function bell(array &$state, string $playerId, ?int $version): array
    {
        if ($state['status'] !== 'playing' || $this->indexOf($state, $playerId) === null) {
            return [false, null];
        }

        // 내가 보던 상황이 이미 바뀌었다면(다른 사람이 먼저 침 등) 벌점 없이 무시한다.
        if ($version === null || $version !== (int) $state['version']) {
            return [false, '한발 늦었어요!'];
        }

        $counts = $this->fruitCounts($state);
        if (in_array(self::BELL_TARGET, $counts, true)) {
            $taken = 0;
            foreach ($state['piles'] as $pid => $pile) {
                $taken += count($pile);
                $state['piles'][$pid] = [];
            }
            $state['scores'][$playerId] = (int) $state['scores'][$playerId] + $taken;
            $state['log'] = $this->labelFor($state, $playerId) . ' 님이 종을 쳐서 ' . $taken . '장을 가져갔어요! 🔔';

            return [true, null];
        }

        $state['scores'][$playerId] = (int) $state['scores'][$playerId] - 1;
        $state['log'] = $this->labelFor($state, $playerId) . ' 님이 잘못 쳤어요! -1점 😱';

        return [true, null];
    }

    /**
     * 각 사람 더미의 맨 위 카드만 모아 과일별 개수를 센다.
     *
     * @return array<int, int> 과일 index => 합
     */
    private function fruitCounts(array $state): array
    {
        $counts = array_fill(0, self::FRUITS, 0);
        foreach ($state['piles'] as $pile) {
            if ($pile === []) {
                continue;
            }
            $top = $pile[count($pile) - 1];
            $counts[(int) $top['f']] += (int) $top['n'];
        }

        return $counts;
    }

    /** @return array<int, array{f:int,n:int}> */
    private function shuffledDeck(): array
    {
        $deck = [];
        for ($f = 0; $f < self::FRUITS; $f++) {
            foreach (self::COMPOSITION as $n => $copies) {
                for ($i = 0; $i < $copies; $i++) {
                    $deck[] = ['f' => $f, 'n' => $n];
                }
            }
        }
        shuffle($deck);

        return $deck;
    }

    /** @return array<string, mixed> */
    private function fresh(): array
    {
        return [
            'version' => 0,
            'status'  => 'waiting', // waiting | playing | ended
            'players' => [],
            'deck'    => [],
            'piles'   => [],
            'scores'  => [],
            'turn'    => 0,
            'log'     => '참가 버튼을 눌러 같은 테이블 사람들과 함께해요.',
        ];
    }

    private function indexOf(array $state, string $memberId): ?int
    {
        foreach ($state['players'] as $i => $p) {
            if ((string) $p['id'] === $memberId) {
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

    /**
     * @param  array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function present(array $state): array
    {
        $players = [];
        foreach ($state['players'] as $p) {
            $pile = $state['piles'][(string) $p['id']] ?? [];
            $players[] = [
                'id'    => (string) $p['id'],
                'label' => $p['label'],
                'score' => (int) ($state['scores'][(string) $p['id']] ?? 0),
                'top'   => $pile === [] ? null : $pile[count($pile) - 1],
                'count' => count($pile),
            ];
        }

        return [
            'version'   => (int) $state['version'],
            'status'    => $state['status'],
            'players'   => $players,
            'deckCount' => count($state['deck']),
            'turn'      => (int) $state['turn'],
            'log'       => $state['log'],
        ];
    }

    /** @return array<string, mixed> */
    private function load(string $scope): array
    {
        $raw = Redis::get($this->stateKey($scope));
        if (! is_string($raw)) {
            return $this->fresh();
        }

        $state = json_decode($raw, true);

        return is_array($state) ? $state : $this->fresh();
    }

    /** @param array<string, mixed> $state */
    private function save(string $scope, array $state): void
    {
        Redis::setex($this->stateKey($scope), self::STATE_TTL, json_encode($state));
    }

    private function stateKey(string $scope): string
    {
        return 'halli:' . $scope;
    }

    /** 테이블 게임판 scope. 로테이션 턴(round)이 바뀌면 앉은 사람이 달라지므로 포함해 게임을 새로 시작한다. */
    public static function tableScope(int $eventId, int $tableNo, int $round): string
    {
        return "table:{$eventId}:{$tableNo}:{$round}";
    }

    /** 공개 게임방(전역 1개) scope. */
    public const PUBLIC_SCOPE = 'public';
}
