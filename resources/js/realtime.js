import { useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';
import echo from '@/echo';

/**
 * 회원 화면 실시간(소켓) 단일 관리 모듈.
 *
 * - 채널 구독/해제는 지속 레이아웃(AppShell)의 useRealtimeConnection 한 곳에서만 한다.
 *   화면(페이지·컴포넌트)은 채널을 직접 건드리지 않고 useRealtimeEvent 로 필요한 이벤트만 받는다.
 *   → 한 화면이 채널을 끊어 다른 곳이 신호를 못 받는 문제가 구조적으로 생기지 않는다.
 * - 이벤트는 "바뀌었다"는 신호다. 화면 데이터는 requestReload 로 서버(Inertia props)에서 다시 받는다.
 *   같은 신호에 여러 곳이 재조회를 요청하면 한 번의 router.reload 로 합친다. (요청 수 절감)
 */

/** 구독하는 채널별 이벤트 (서버 broadcastAs 와 동일한 이름) */
const CHAT_EVENTS = ['message.sent', 'message.read', 'request.read', 'request.replied'];
const EVENT_ROTATION_EVENTS = ['rotation.updated', 'event.voting.opened', 'event.closed'];

/** 이벤트명 → 핸들러 집합 */
const handlers = new Map();

/** 받은 이벤트를 등록된 핸들러에 전달한다. */
function emit(name, payload) {
    const set = handlers.get(name);
    if (!set) {
        return;
    }
    // 핸들러 안에서 해제되어도 안전하도록 복사본으로 순회한다.
    [...set].forEach((fn) => {
        try {
            fn(payload);
        } catch (e) {
            console.error(`[realtime] ${name} 처리 중 오류`, e);
        }
    });
}

/**
 * 실시간 이벤트 핸들러 등록. 컴포넌트가 사라지면 자동 해제된다.
 * 핸들러는 ref 로 최신 값을 유지하므로 매 렌더마다 다시 등록하지 않는다.
 *
 * @param {string} name 이벤트명 (예: 'rotation.updated', 'message.sent')
 * @param {(payload: any) => void} [handler]
 */
export function useRealtimeEvent(name, handler) {
    const ref = useRef(handler);
    ref.current = handler;

    useEffect(() => {
        const fn = (payload) => ref.current?.(payload);
        if (!handlers.has(name)) {
            handlers.set(name, new Set());
        }
        handlers.get(name).add(fn);

        return () => {
            handlers.get(name)?.delete(fn);
        };
    }, [name]);
}

/*
 | 재조회 합치기
 |  - requestReload(['a', 'b']) : 해당 props 만 다시 받는다.
 |  - requestReload()           : 화면 전체 props 를 다시 받는다. (하나라도 전체면 전체로 합친다)
 |  같은 이벤트 처리 중(같은 tick)에 들어온 요청은 모아서 한 번만 보낸다.
 */
let pendingKeys = null; // Set<string> | 'all' | null
let flushTimer = null;

export function requestReload(only) {
    if (only === undefined || only === null) {
        pendingKeys = 'all';
    } else if (pendingKeys !== 'all') {
        pendingKeys ??= new Set();
        only.forEach((k) => pendingKeys.add(k));
    }

    if (flushTimer !== null) {
        return;
    }
    flushTimer = setTimeout(() => {
        const keys = pendingKeys;
        pendingKeys = null;
        flushTimer = null;

        if (keys === 'all') {
            router.reload();
        } else if (keys && keys.size > 0) {
            router.reload({ only: [...keys] });
        }
    }, 0);
}

/** 대기 중인 재조회를 취소한다. (곧 페이지를 옮겨 새 props 를 받을 때 — 이동이 재조회에 끊기지 않게) */
export function cancelPendingReload() {
    if (flushTimer !== null) {
        clearTimeout(flushTimer);
    }
    flushTimer = null;
    pendingKeys = null;
}

/**
 * 채널 구독. AppShell(지속 레이아웃)에서 한 번만 호출한다.
 *  - chat.{memberId}         : 메시지 수신/읽음, 관리자 요청 확인/답장
 *  - event-rotation.{eventId}: 로테이션 진행, 매칭 시작/종료
 * 로그인 회원·행사가 바뀔 때만 다시 구독한다.
 *
 * @param {number|null|undefined} memberId
 * @param {number|null|undefined} eventId
 */
export function useRealtimeConnection(memberId, eventId) {
    useEffect(() => {
        if (!memberId) {
            return undefined;
        }
        const name = `chat.${memberId}`;
        const channel = echo.private(name);
        CHAT_EVENTS.forEach((ev) => channel.listen(`.${ev}`, (payload) => emit(ev, payload)));

        return () => {
            echo.leave(`private-${name}`);
        };
    }, [memberId]);

    useEffect(() => {
        if (!eventId) {
            return undefined;
        }
        const name = `event-rotation.${eventId}`;
        const channel = echo.private(name);
        EVENT_ROTATION_EVENTS.forEach((ev) => channel.listen(`.${ev}`, (payload) => emit(ev, payload)));

        return () => {
            echo.leave(`private-${name}`);
        };
    }, [eventId]);
}
