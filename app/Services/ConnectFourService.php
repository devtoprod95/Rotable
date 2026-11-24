<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * 4목(커넥트 포) - 2인 대전, 서버 권위(Redis) 게임 상태 관리.
 *
 * 규칙
 *  - 가로 7칸 × 세로 6칸 판. 번갈아 열을 골라 돌을 넣으면 그 열의 가장 아래 빈칸에 쌓인다.
 *  - 가로·세로·대각선으로 같은 색 돌 4개를 먼저 이으면 승리. 판이 가득 차면 무승부.
 *  - 방은 하나, 참가 자리는 2개. 2명이 모이면 자동 시작하고, 나머지는 구경한다.
 *  - 한 판이 끝난 뒤 "한 판 더"를 누르면 선공을 바꿔 다시 시작한다.
 *
 * 동시에 여러 기기가 누르므로 락 안에서 상태를 읽고-바꾸고-쓴다.
 */
class ConnectFourService
{
    public const STATE_TTL = 21600; // 6시간

    public const ROWS = 6;

    public const COLS = 7;

    public const CONNECT = 4;

    public const SEATS = 2;

    public const SCOPE = 'public';

    /**
     * @return array<string, mixed>
     */
    public function publicState(): array
    {
        return $this->present($this->load());
    }

    /**
     * 액션을 처리한다.
     *
     * @param  string $action join|drop|reset|leave|rename
     * @param  ?int   $col    drop 의 열 (0~6)
     * @return array{ok:bool, message:?string, state:array<string,mixed>, changed:bool}
     */
    public function act(string $playerId, string $label, string $action, ?int $col = null): array
    {
        $lock = Cache::lock('connect-four-lock:' . self::SCOPE, 5);

        return $lock->block(3, function () use ($playerId, $label, $action, $col): array {
            $state   = $this->load();
            $message = null;
            $changed = true;

            switch ($action) {
                case 'join':
                    [$changed, $message] = $this->join($state, $playerId, $label);
                    break;
                case 'drop':
                    [$changed, $message] = $this->drop($state, $playerId, $col);
                    break;
                case 'reset':
                    [$changed, $message] = $this->reset($state, $playerId);
                    break;
                case 'leave':
                    [$changed, $message] = $this->leave($state, $playerId);
                    break;
                case 'rename':
                    [$changed, $message] = $this->rename($state, $playerId, $label);
                    break;
                default:
                    $changed = false;
            }

            if ($changed) {
                $state['version'] = (int) $state['version'] + 1;
                $this->save($state);
            }

            return [
                'ok'      => $changed,
                'message' => $message,
                'state'   => $this->present($state),
                'changed' => $changed,
            ];
        });
    }

    /** @return array{0:bool,1:?string} */
    private function join(array &$state, string $playerId, string $label): array
    {
        if ($this->indexOf($state, $playerId) !== null) {
            return [false, null];
        }
        if (count($state['players']) >= self::SEATS) {
            return [false, '자리가 가득 찼어요. 구경하다가 자리가 나면 참가해 보세요.'];
        }

        $label = CharacterNickname::unique(array_column($state['players'], 'label'), $label);

        $state['players'][] = ['id' => $playerId, 'label' => $label];

        if (count($state['players']) === self::SEATS) {
            $this->startGame($state);
            $state['log'] = $label . ' 님이 참가해 게임을 시작해요! ' . $state['players'][$state['turn']]['label'] . ' 님이 먼저 둬요.';
        } else {
            $state['log'] = $label . ' 님이 참가했어요. 상대를 기다려요.';
        }

        return [true, null];
    }

    /** @return array{0:bool,1:?string} */
    private function drop(array &$state, string $playerId, ?int $col): array
    {
        if ($state['status'] !== 'playing') {
            return [false, null];
        }
        $idx = $this->indexOf($state, $playerId);
        if ($idx === null) {
            return [false, '참가한 사람만 둘 수 있어요.'];
        }
        if ($idx !== (int) $state['turn']) {
            return [false, '내 차례가 아니에요.'];
        }
        if ($col === null || $col < 0 || $col >= self::COLS) {
            return [false, null];
        }

        $row = $this->landingRow($state['board'], $col);
        if ($row === null) {
            return [false, '이 열은 가득 찼어요.'];
        }

        $mark = $idx + 1;
        $state['board'][$row][$col] = $mark;
        $state['lastMove']          = [$row, $col];
        $name                       = $state['players'][$idx]['label'];

        $line = $this->winningLine($state['board'], $row, $col, $mark);
        if ($line !== null) {
            $state['status']  = 'ended';
            $state['winner']  = $idx;
            $state['winLine'] = $line;
            $state['log']     = $name . ' 님이 4목을 완성했어요! 🎉';

            return [true, null];
        }

        if ($this->isFull($state['board'])) {
            $state['status'] = 'ended';
            $state['winner'] = 'draw';
            $state['log']    = '판이 가득 찼어요. 무승부!';

            return [true, null];
        }

        $state['turn'] = 1 - $idx;
        $state['log']  = $state['players'][$state['turn']]['label'] . ' 님 차례예요.';

        return [true, null];
    }

    /** "한 판 더". 참가자만 할 수 있고, 선공을 바꿔 새 판을 시작한다. 상대가 없으면 대기 상태가 된다. */
    private function reset(array &$state, string $playerId): array
    {
        if ($this->indexOf($state, $playerId) === null) {
            return [false, null];
        }
        if ($state['status'] === 'waiting') {
            return [false, null];
        }

        $state['starter'] = 1 - (int) $state['starter'];
        $this->startGame($state, true);
        $state['log'] = '새 판 시작! ' . $state['players'][$state['turn']]['label'] . ' 님이 먼저 둬요.';

        return [true, null];
    }

    /** 자리를 비운다. 게임 중이면 판은 중단되고 남은 사람은 새 상대를 기다린다. */
    private function leave(array &$state, string $playerId): array
    {
        $idx = $this->indexOf($state, $playerId);
        if ($idx === null) {
            return [false, null];
        }

        $name = $state['players'][$idx]['label'];
        array_splice($state['players'], $idx, 1);

        $state['status']   = 'waiting';
        $state['board']    = $this->emptyBoard();
        $state['winner']   = null;
        $state['winLine']  = [];
        $state['lastMove'] = null;
        $state['starter']  = 0;
        $state['turn']     = 0;
        $state['log']      = $name . ' 님이 자리를 비웠어요. 새 상대를 기다려요.';

        return [true, null];
    }

    /** 닉네임 변경. 다른 참가자와 겹치면 숫자를 붙인다. */
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

        $old                             = $state['players'][$idx]['label'];
        $state['players'][$idx]['label'] = $label;
        $state['log']                    = $old . ' 님이 ' . $label . ' (으)로 닉네임을 바꿨어요.';

        return [true, null];
    }

    /**
     * 새 판을 준비한다. (두 자리가 모두 찼을 때만 진행 상태가 된다)
     *
     * @param bool $keepStarter true 면 이미 바뀐 starter 를 그대로 쓴다.
     */
    private function startGame(array &$state, bool $keepStarter = false): void
    {
        $state['board']    = $this->emptyBoard();
        $state['winner']   = null;
        $state['winLine']  = [];
        $state['lastMove'] = null;

        if (! $keepStarter) {
            $state['starter'] = 0;
        }

        if (count($state['players']) === self::SEATS) {
            $state['status'] = 'playing';
            $state['turn']   = (int) $state['starter'];
        } else {
            $state['status'] = 'waiting';
            $state['turn']   = 0;
        }
    }

    /**
     * 돌이 떨어져 멈출 행. 열이 가득 차면 null. (행 0 이 맨 위)
     *
     * @param array<int, array<int, int>> $board
     */
    private function landingRow(array $board, int $col): ?int
    {
        for ($row = self::ROWS - 1; $row >= 0; $row--) {
            if ($board[$row][$col] === 0) {
                return $row;
            }
        }

        return null;
    }

    /**
     * 방금 둔 돌을 기준으로 4개 이상 이어졌는지 검사한다. 이어졌다면 그 돌 4개의 좌표.
     *
     * @param  array<int, array<int, int>> $board
     * @return array<int, array{0:int,1:int}>|null
     */
    private function winningLine(array $board, int $row, int $col, int $mark): ?array
    {
        $directions = [[0, 1], [1, 0], [1, 1], [1, -1]];

        foreach ($directions as [$dr, $dc]) {
            $cells = [[$row, $col]];

            foreach ([1, -1] as $sign) {
                $r = $row + $dr * $sign;
                $c = $col + $dc * $sign;
                while ($r >= 0 && $r < self::ROWS && $c >= 0 && $c < self::COLS && $board[$r][$c] === $mark) {
                    $cells[] = [$r, $c];
                    $r += $dr * $sign;
                    $c += $dc * $sign;
                }
            }

            if (count($cells) >= self::CONNECT) {
                return array_slice($cells, 0, self::CONNECT);
            }
        }

        return null;
    }

    /** @param array<int, array<int, int>> $board */
    private function isFull(array $board): bool
    {
        foreach ($board[0] as $cell) {
            if ($cell === 0) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int, array<int, int>> */
    private function emptyBoard(): array
    {
        return array_fill(0, self::ROWS, array_fill(0, self::COLS, 0));
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

    /**
     * 공개 상태. 비밀 정보가 없는 게임이라 저장된 상태를 거의 그대로 내려준다.
     *
     * @return array<string, mixed>
     */
    private function present(array $state): array
    {
        return [
            'version'  => (int) $state['version'],
            'status'   => $state['status'],
            'players'  => array_map(
                fn (array $p): array => ['id' => (string) $p['id'], 'label' => $p['label']],
                $state['players'],
            ),
            'board'    => $state['board'],
            'turn'     => (int) $state['turn'],
            'winner'   => $state['winner'],
            'winLine'  => $state['winLine'],
            'lastMove' => $state['lastMove'],
            'log'      => $state['log'],
        ];
    }

    /** @return array<string, mixed> */
    private function fresh(): array
    {
        return [
            'version'  => 0,
            'status'   => 'waiting', // waiting | playing | ended
            'players'  => [],
            'board'    => $this->emptyBoard(),
            'turn'     => 0,
            'starter'  => 0,
            'winner'   => null,      // null | 0 | 1 | 'draw'
            'winLine'  => [],
            'lastMove' => null,
            'log'      => '참가 버튼을 눌러 상대를 기다려요. (2명이 모이면 자동 시작)',
        ];
    }

    /** @return array<string, mixed> */
    private function load(): array
    {
        $raw = Redis::get('connect-four:' . self::SCOPE);
        if (! is_string($raw)) {
            return $this->fresh();
        }

        $state = json_decode($raw, true);

        return is_array($state) ? $state : $this->fresh();
    }

    /** @param array<string, mixed> $state */
    private function save(array $state): void
    {
        Redis::setex('connect-four:' . self::SCOPE, self::STATE_TTL, json_encode($state));
    }
}
