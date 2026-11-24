import { Head, Link } from "@inertiajs/react";
import { useCallback, useEffect, useRef, useState } from "react";
import echo from "@/echo";

/** CSRF 토큰 (meta 태그에서) */
function csrfToken() {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") ?? ""
    );
}

/** 할리갈리 액션 전송. 실패 시 null. */
async function postAction(url, body) {
    try {
        const res = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken(),
                Accept: "application/json",
            },
            body: JSON.stringify(body),
        });
        return res.ok ? res.json() : null;
    } catch {
        return null;
    }
}

/** 과일 index → 이모지 */
const FRUITS = ["🍓", "🍌", "🍇", "🍏"];

/** 과일별 카드 테두리/배경 색 (한눈에 구분되도록) */
const FRUIT_TONES = [
    "border-rose-400 bg-rose-50",
    "border-yellow-400 bg-yellow-50",
    "border-purple-400 bg-purple-50",
    "border-lime-500 bg-lime-50",
];

/** 개수에 따라 과일 크기/열 수를 정한다. (많을수록 작게, 카드 안에 꽉 차게) */
function fruitLayout(n) {
    if (n <= 1) return { cols: 1, size: "text-6xl" };
    if (n === 2) return { cols: 1, size: "text-5xl" };
    if (n === 3) return { cols: 2, size: "text-4xl" };
    if (n === 4) return { cols: 2, size: "text-4xl" };
    return { cols: 3, size: "text-3xl" };
}

/** 카드 한 장 (맨 위 카드). 없으면 빈 자리 표시. 과일을 개수만큼 크게 그린다. */
function Card({ card }) {
    if (!card) {
        return (
            <div className="flex h-28 w-full items-center justify-center rounded-2xl border-2 border-dashed border-secondary-200 text-xs font-bold text-secondary-300">
                카드 없음
            </div>
        );
    }
    const { cols, size } = fruitLayout(card.n);
    return (
        <div
            className={
                "relative flex h-28 w-full items-center justify-center rounded-2xl border-4 shadow-md " +
                FRUIT_TONES[card.f]
            }
        >
            <span className="absolute left-2 top-1 text-xl font-black text-secondary-600">
                {card.n}
            </span>
            <div
                className="grid place-items-center gap-1"
                style={{ gridTemplateColumns: `repeat(${cols}, auto)` }}
            >
                {Array.from({ length: card.n }).map((_, i) => (
                    <span key={i} className={size + " leading-none"}>
                        {FRUITS[card.f]}
                    </span>
                ))}
            </div>
        </div>
    );
}

/**
 * 할리갈리 보드 - 테이블 게임/공개 게임이 함께 쓰는 실시간 게임 화면.
 *
 * 상태는 서버(Redis)가 관리하고, 변경될 때마다 채널로 전체 공개 상태가 온다.
 * 종은 "내가 본 version" 과 함께 보내며, 서버 도착 순서로 첫 번째만 인정된다.
 *
 * @param {Object}   props
 * @param {Function} props.Layout        페이지 레이아웃 컴포넌트
 * @param {string}   props.actionUrl     액션 POST 주소
 * @param {Function} props.subscribe     (onMessage) => unsubscribe. 채널 구독
 * @param {string}   props.badge         우측 상단 배지 문구
 * @param {string}   props.backHref      뒤로가기 주소
 * @param {?number}  props.round         테이블 게임의 로테이션 턴 (공개방은 null)
 * @param {string}   props.myId          내 참가자 id
 * @param {Object}   props.initialState  초기 공개 상태
 * @param {boolean}  [props.askNickname] 참가 시 닉네임 입력 여부 (공개방)
 * @param {Object}   [props.extraBody]   모든 요청에 덧붙일 값 (공개방: client_id)
 */
export default function HalliGalliBoard({
    Layout,
    actionUrl,
    subscribe,
    badge,
    backHref,
    round = null,
    myId,
    initialState,
    askNickname = false,
    extraBody = {},
}) {
    const [nickname, setNickname] = useState("");
    const [state, setState] = useState(initialState);
    const [toast, setToast] = useState(null);
    const [busy, setBusy] = useState(false);

    // 최신 version 보다 낮은 상태(늦게 도착한 응답/이벤트)는 무시하기 위한 ref.
    const versionRef = useRef(initialState.version);
    versionRef.current = state.version;

    const applyState = useCallback((next) => {
        if (!next || next.version < versionRef.current) {
            return;
        }
        versionRef.current = next.version;
        setState(next);
    }, []);

    const showToast = (msg) => {
        setToast(msg);
        setTimeout(() => setToast(null), 1800);
    };

    // 채널 구독. 로테이션 턴이 다른 상태(테이블 게임)는 다른 게임판이므로 무시한다.
    useEffect(() => {
        return subscribe((payload) => {
            if (round !== null && Number(payload.round) !== Number(round)) {
                return;
            }
            applyState(payload.state);
        });
        // subscribe 는 부모에서 고정된 함수로 넘긴다.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [round, applyState]);

    const act = async (action) => {
        if (busy && action !== "bell") {
            return;
        }
        setBusy(true);
        const res = await postAction(actionUrl, {
            ...extraBody,
            action,
            label: action === "join" || action === "reset" ? nickname.trim() : undefined,
            version: action === "bell" ? versionRef.current : undefined,
        });
        setBusy(false);

        if (!res) {
            showToast("연결이 불안정해요. 다시 눌러주세요.");
            return;
        }
        if (res.state && (round === null || Number(res.round) === Number(round))) {
            applyState(res.state);
        }
        if (res.message) {
            showToast(res.message);
        }
    };

    const myIndex = state.players.findIndex((p) => p.id === myId);
    const joined = myIndex !== -1;
    const myTurn = state.status === "playing" && state.turn === myIndex;
    const turnPlayer = state.players[state.turn];

    // 최고 점수(동점 포함) → 종료 시 승자 표시.
    const topScore = Math.max(...state.players.map((p) => p.score), 0);

    return (
        <Layout>
            <Head title="할리갈리" />

            <div className="flex flex-1 flex-col px-4 py-4">
                <div className="mb-3 flex items-center gap-2">
                    <Link
                        href={backHref}
                        className="flex h-9 w-9 items-center justify-center rounded-full border border-secondary-200 bg-white text-secondary-500 shadow-sm transition active:scale-95 hover:text-primary"
                        aria-label="게임 목록으로"
                    >
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2.4"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </Link>
                    <div className="flex items-center gap-1.5">
                        <span className="text-xl">🔔</span>
                        <h1 className="text-lg font-extrabold text-secondary-700">
                            할리갈리
                        </h1>
                    </div>
                    <span className="ml-auto rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-600">
                        {badge}
                    </span>
                </div>

                {/* 상황 안내 */}
                <p className="mb-3 text-center text-sm font-bold text-secondary-600">
                    {state.log}
                </p>

                {/* 액션 영역 */}
                <div className="mb-4 flex flex-col gap-2.5">
                    {state.status === "waiting" && (
                        <>
                            {!joined && askNickname && (
                                <input
                                    type="text"
                                    value={nickname}
                                    onChange={(e) => setNickname(e.target.value)}
                                    maxLength={10}
                                    placeholder="닉네임 (비우면 자동)"
                                    className="w-full rounded-2xl border-2 border-secondary-200 bg-white px-4 py-3 text-center text-sm font-bold text-secondary-700 outline-none focus:border-emerald-400"
                                />
                            )}
                            {!joined && (
                                <button
                                    type="button"
                                    onClick={() => act("join")}
                                    className="w-full rounded-2xl bg-emerald-500 py-4 text-base font-extrabold text-white shadow-md transition active:scale-[0.98]"
                                >
                                    참가하기 🙋
                                </button>
                            )}
                            {joined && (
                                <button
                                    type="button"
                                    onClick={() => act("start")}
                                    className="w-full rounded-2xl bg-primary py-4 text-base font-extrabold text-white shadow-md transition active:scale-[0.98] hover:bg-primary-600"
                                >
                                    게임 시작 ({state.players.length}명)
                                </button>
                            )}
                            <p className="text-center text-[11px] font-medium text-secondary-400">
                                2명 이상 참가한 뒤 아무나 시작할 수 있어요. 시작
                                후에는 새로 참가할 수 없어요.
                            </p>
                        </>
                    )}

                    {state.status === "playing" && joined && (
                        <div className="grid grid-cols-2 gap-2.5">
                            <button
                                type="button"
                                onClick={() => act("bell")}
                                className="rounded-3xl bg-amber-400 py-6 text-xl font-black text-white shadow-lg transition active:scale-[0.96]"
                            >
                                🔔 종 치기!
                            </button>
                            <button
                                type="button"
                                onClick={() => act("flip")}
                                disabled={!myTurn}
                                className="rounded-3xl bg-primary px-2 py-6 text-base font-extrabold text-white shadow-lg transition active:scale-[0.96] hover:bg-primary-600 disabled:bg-secondary-200 disabled:text-secondary-400 disabled:shadow-none"
                            >
                                {myTurn ? (
                                    "🃏 카드 뒤집기"
                                ) : (
                                    <>
                                        <span className="block text-xs font-bold">
                                            {turnPlayer?.label ?? ""}
                                        </span>
                                        님 차례
                                    </>
                                )}
                            </button>
                        </div>
                    )}

                    {state.status === "playing" && !joined && (
                        <p className="text-center text-sm font-bold text-secondary-400">
                            진행 중인 게임이에요. 구경만 할 수 있어요.
                        </p>
                    )}
                </div>

                {/* 참가자 보드 */}
                <div className="grid grid-cols-2 gap-2.5">
                    {state.players.map((p, i) => {
                        const isTurn =
                            state.status === "playing" && state.turn === i;
                        const isMe = p.id === myId;
                        return (
                            <div
                                key={p.id}
                                className={
                                    "flex flex-col gap-2 rounded-2xl border-2 bg-white p-2.5 " +
                                    (isTurn
                                        ? "border-emerald-400 shadow-md"
                                        : "border-secondary-200")
                                }
                            >
                                <Card card={p.top} />
                                <div className="min-w-0">
                                    <p className="truncate text-xs font-extrabold text-secondary-700">
                                        {p.label}
                                        {isMe && (
                                            <span className="ml-1 text-primary">
                                                (나)
                                            </span>
                                        )}
                                    </p>
                                    <p className="text-lg font-black text-emerald-600">
                                        {p.score}
                                        <span className="ml-0.5 text-[10px] font-bold text-secondary-400">
                                            점
                                        </span>
                                    </p>
                                    {isTurn && (
                                        <p className="text-[10px] font-bold text-emerald-500">
                                            ▶ 차례
                                        </p>
                                    )}
                                    {state.status === "ended" &&
                                        p.score === topScore && (
                                            <p className="text-[10px] font-bold text-amber-500">
                                                👑 승리
                                            </p>
                                        )}
                                </div>
                            </div>
                        );
                    })}
                </div>

                {state.players.length === 0 && (
                    <p className="py-6 text-center text-sm font-medium text-secondary-400">
                        아직 참가자가 없어요.
                    </p>
                )}

                {state.status !== "waiting" && (
                    <p className="mt-3 text-center text-xs font-bold text-secondary-400">
                        남은 카드 {state.deckCount}장
                    </p>
                )}

                <div className="mt-3 flex flex-col gap-2.5">
                    {(state.status === "ended" || state.status === "playing") && (
                            <button
                                type="button"
                                onClick={() => {
                                    if (
                                        state.status === "ended" ||
                                        window.confirm(
                                            "게임을 처음부터 다시 시작할까요? 함께하는 모두의 화면이 초기화돼요.",
                                        )
                                    ) {
                                        act("reset");
                                    }
                                }}
                                className="w-full rounded-2xl border-2 border-secondary-200 bg-white py-3 text-sm font-bold text-secondary-500 transition active:scale-[0.98]"
                            >
                                {state.status === "ended"
                                    ? "한 판 더 🔁"
                                    : "처음부터 다시"}
                            </button>
                        )}
                </div>

                {/* 규칙 (접어두기) */}
                <details className="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50/60 p-3">
                    <summary className="cursor-pointer text-xs font-bold text-emerald-600">
                        💡 규칙 보기
                    </summary>
                    <p className="mt-2 text-xs font-medium leading-relaxed text-secondary-500">
                        내 차례에 카드를 뒤집어요. 모두의{" "}
                        <span className="font-bold">맨 위 카드</span>에서 같은
                        과일이 <span className="font-bold">딱 5개</span>가 되면
                        종을 치세요! 먼저 친 사람이 깔린 카드를 가져가고(1장 =
                        1점), 잘못 치면 -1점이에요.
                    </p>
                </details>
            </div>

            {toast && (
                <div className="pointer-events-none fixed inset-x-0 bottom-28 z-50 flex justify-center px-6">
                    <div className="rounded-full bg-black/80 px-4 py-2 text-sm font-bold text-white shadow-lg">
                        {toast}
                    </div>
                </div>
            )}
        </Layout>
    );
}
