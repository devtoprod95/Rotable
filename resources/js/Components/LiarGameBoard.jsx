import { Head, Link } from "@inertiajs/react";
import { useCallback, useEffect, useRef, useState } from "react";

/** CSRF 토큰 (meta 태그에서) */
function csrfToken() {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") ?? ""
    );
}

/** 라이어 게임 액션 전송. 실패 시 null. */
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

/**
 * 라이어 게임 보드 - 테이블 게임/공개 게임이 함께 쓰는 실시간 화면.
 *
 * 라이어 1명을 뺀 모두가 같은 제시어를 받고, 라이어는 주제만 받는다.
 * 돌아가며 제시어를 설명(말로)하고, 투표로 라이어를 찾는다.
 * 공개 상태만 채널로 오고, 내 제시어/역할은 내 응답(secret)으로만 받는다.
 *
 * @param {Object}   props
 * @param {Function} props.Layout         페이지 레이아웃 컴포넌트
 * @param {string}   props.actionUrl      액션 POST 주소
 * @param {Function} props.subscribe      (onMessage) => unsubscribe. 채널 구독
 * @param {string}   props.badge          우측 상단 배지 문구
 * @param {string}   props.backHref       뒤로가기 주소
 * @param {?number}  props.round          테이블 게임의 로테이션 턴 (공개방은 null)
 * @param {string}   props.myId           내 참가자 id
 * @param {Object}   props.initialState   초기 공개 상태
 * @param {?Object}  [props.initialSecret] 초기 내 비밀 정보
 * @param {boolean}  [props.askNickname]  참가 시 닉네임 입력 여부 (공개방)
 * @param {Object}   [props.extraBody]    모든 요청에 덧붙일 값 (공개방: client_id)
 */
export default function LiarGameBoard({
    Layout,
    actionUrl,
    subscribe,
    badge,
    backHref,
    round = null,
    myId,
    initialState,
    initialSecret = null,
    askNickname = false,
    extraBody = {},
}) {
    const [state, setState] = useState(initialState);
    const [secret, setSecret] = useState(initialSecret);
    const [showSecret, setShowSecret] = useState(false);
    const [nickname, setNickname] = useState("");
    // 시작할 때 쓸 주제. null 이면 랜덤.
    const [pickedTopic, setPickedTopic] = useState(null);
    const [toast, setToast] = useState(null);
    const [busy, setBusy] = useState(false);

    // 늦게 도착한 오래된 상태를 무시하기 위한 최신 version.
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

    // 새 판이 시작되었거나(gameNo 변경) 처음 들어왔을 때 내 비밀 정보를 서버에서 받아온다.
    // 비밀 정보는 브로드캐스트에 실리지 않으므로 각자 직접 요청해야 한다.
    const gameNo = state.gameNo;
    const status = state.status;
    useEffect(() => {
        if (status === "waiting") {
            setSecret(null);
            setShowSecret(false);
            return;
        }
        let cancelled = false;
        postAction(actionUrl, { ...extraBody, action: "peek" }).then((res) => {
            if (!cancelled && res) {
                setSecret(res.secret ?? null);
            }
        });
        setShowSecret(false);
        return () => {
            cancelled = true;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [gameNo, status === "waiting"]);

    const act = async (action, target) => {
        if (busy) {
            return;
        }
        setBusy(true);
        const res = await postAction(actionUrl, {
            ...extraBody,
            action,
            target: action === "start" ? (pickedTopic ?? undefined) : target,
            label: ["join", "reset", "rename"].includes(action) ? nickname.trim() : undefined,
        });
        setBusy(false);

        if (!res) {
            showToast("연결이 불안정해요. 다시 눌러주세요.");
            return;
        }
        if (res.state && (round === null || Number(res.round) === Number(round))) {
            applyState(res.state);
        }
        if (res.secret !== undefined) {
            setSecret(res.secret);
        }
        if (res.message) {
            showToast(res.message);
        }
    };

    const players = state.players;
    const myIndex = players.findIndex((p) => p.id === myId);
    const joined = myIndex !== -1;
    const me = players[myIndex];
    const labelOf = (id) => players.find((p) => p.id === id)?.label ?? "";
    const result = state.result;
    const iAmLiar = secret?.role === "liar";

    return (
        <Layout>
            <Head title="라이어 게임" />

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
                        <span className="text-xl">🕵️</span>
                        <h1 className="text-lg font-extrabold text-secondary-700">
                            라이어 게임
                        </h1>
                    </div>
                    <span className="ml-auto rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-bold text-indigo-600">
                        {badge}
                    </span>
                </div>

                <p className="mb-3 text-center text-sm font-bold text-secondary-600">
                    {state.log}
                </p>

                {/* ===== 대기 ===== */}
                {status === "waiting" && (
                    <div className="mb-4 flex flex-col gap-2.5">
                        {!joined && askNickname && (
                            <input
                                type="text"
                                value={nickname}
                                onChange={(e) => setNickname(e.target.value)}
                                maxLength={10}
                                placeholder="닉네임 (비우면 캐릭터 이름)"
                                className="w-full rounded-2xl border-2 border-secondary-200 bg-white px-4 py-3 text-center text-sm font-bold text-secondary-700 outline-none focus:border-indigo-400"
                            />
                        )}
                        {!joined && (
                            <button
                                type="button"
                                onClick={() => act("join")}
                                className="w-full rounded-2xl bg-indigo-500 py-4 text-base font-extrabold text-white shadow-md transition active:scale-[0.98]"
                            >
                                참가하기 🙋
                            </button>
                        )}
                        {joined && (
                            <div className="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-3">
                                <p className="mb-2 text-xs font-bold text-indigo-600">
                                    주제 선택 (시작하는 사람이 정해요)
                                </p>
                                <div className="flex flex-wrap gap-1.5">
                                    {[null, ...(state.topics ?? [])].map((t) => (
                                        <button
                                            key={t ?? "random"}
                                            type="button"
                                            onClick={() => setPickedTopic(t)}
                                            className={
                                                "rounded-full border-2 px-3 py-1.5 text-xs font-bold transition active:scale-95 " +
                                                (pickedTopic === t
                                                    ? "border-indigo-500 bg-indigo-500 text-white"
                                                    : "border-indigo-200 bg-white text-indigo-600")
                                            }
                                        >
                                            {t ?? "🎲 랜덤"}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}
                        {joined && (
                            <button
                                type="button"
                                onClick={() => act("start")}
                                className="w-full rounded-2xl bg-primary py-4 text-base font-extrabold text-white shadow-md transition active:scale-[0.98] hover:bg-primary-600"
                            >
                                게임 시작 ({players.length}명)
                            </button>
                        )}
                        <p className="text-center text-[11px] font-medium text-secondary-400">
                            3명 이상 참가한 뒤 아무나 시작할 수 있어요. 시작 후에는
                            새로 참가할 수 없어요.
                        </p>
                    </div>
                )}

                {/* ===== 내 카드 (게임 중/결과) ===== */}
                {status !== "waiting" && joined && (
                    <div className="mb-3">
                        <button
                            type="button"
                            onClick={() => setShowSecret((v) => !v)}
                            className={
                                "w-full rounded-3xl border-4 px-4 py-5 text-center shadow-md transition active:scale-[0.98] " +
                                (!showSecret
                                    ? "border-indigo-300 bg-indigo-50"
                                    : iAmLiar
                                      ? "border-rose-400 bg-rose-50"
                                      : "border-emerald-400 bg-emerald-50")
                            }
                        >
                            {!showSecret || !secret ? (
                                <span className="text-base font-extrabold text-indigo-600">
                                    👀 눌러서 내 카드 확인
                                    <span className="mt-1 block text-[11px] font-medium text-secondary-400">
                                        옆사람이 못 보게 조심! 다시 누르면 가려져요
                                    </span>
                                </span>
                            ) : (
                                <span className="block">
                                    <span className="block text-xs font-bold text-secondary-500">
                                        주제
                                    </span>
                                    <span className="block text-lg font-extrabold text-secondary-700">
                                        {secret.topic}
                                    </span>
                                    {iAmLiar ? (
                                        <span className="mt-2 block text-3xl font-black text-rose-500">
                                            🕵️ 당신은 라이어!
                                            <span className="mt-1 block text-xs font-bold text-rose-400">
                                                제시어를 모릅니다. 들키지 마세요!
                                            </span>
                                        </span>
                                    ) : (
                                        <span className="mt-2 block text-3xl font-black text-emerald-600">
                                            {secret.word}
                                            <span className="mt-1 block text-xs font-bold text-emerald-500">
                                                라이어에게 들키지 않게 설명하세요
                                            </span>
                                        </span>
                                    )}
                                </span>
                            )}
                        </button>
                    </div>
                )}

                {/* ===== 설명 단계 ===== */}
                {status === "describe" && (
                    <>
                        <div className="mb-3 rounded-2xl border border-indigo-200 bg-indigo-50/60 p-3 text-center">
                            <p className="text-xs font-bold text-indigo-500">
                                주제: {state.topic}
                            </p>
                            <p className="mt-1 text-sm font-extrabold text-secondary-700">
                                카드를 확인하고 돌아가며 제시어를 설명해요
                            </p>
                        </div>
                        {joined && (
                            <button
                                type="button"
                                onClick={() => act("to_vote")}
                                className="mb-3 w-full rounded-3xl bg-primary py-5 text-lg font-black text-white shadow-lg transition active:scale-[0.97] hover:bg-primary-600"
                            >
                                설명 끝! 투표하기 🗳️
                            </button>
                        )}
                    </>
                )}

                {/* ===== 투표 단계 ===== */}
                {status === "vote" && (
                    <>
                        <div className="mb-3 grid grid-cols-2 gap-2.5">
                            {players.map((p) => {
                                const isMe = p.id === myId;
                                return (
                                    <button
                                        key={p.id}
                                        type="button"
                                        disabled={!joined || isMe}
                                        onClick={() => act("vote", p.id)}
                                        className={
                                            "rounded-2xl border-2 px-2 py-4 text-sm font-extrabold transition active:scale-[0.97] disabled:active:scale-100 " +
                                            (isMe
                                                ? "border-secondary-100 bg-white text-secondary-300"
                                                : secret?.vote === p.id
                                                  ? "border-indigo-500 bg-indigo-500 text-white"
                                                  : "border-indigo-300 bg-white text-indigo-600")
                                        }
                                    >
                                        <span className="block truncate">
                                            {p.label}
                                            {isMe && " (나)"}
                                        </span>
                                        <span
                                            className={
                                                "mt-1 block text-[10px] font-bold " +
                                                (secret?.vote === p.id
                                                    ? "text-indigo-100"
                                                    : "text-secondary-400")
                                            }
                                        >
                                            {secret?.vote === p.id
                                                ? "내 선택 ✓"
                                                : p.voted
                                                  ? "투표 완료"
                                                  : "투표 전"}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                        <p className="mb-3 text-center text-xs font-bold text-secondary-400">
                            {me?.voted
                                ? `투표했어요. 다른 사람을 눌러 바꿀 수 있어요. (${players.filter((p) => p.voted).length}/${players.length})`
                                : "라이어라고 생각하는 사람을 눌러요."}
                        </p>
                        {joined && (
                            <button
                                type="button"
                                onClick={() => {
                                    if (
                                        players.every((p) => p.voted) ||
                                        window.confirm(
                                            "아직 투표하지 않은 사람이 있어요. 지금까지의 표로 결과를 공개할까요?",
                                        )
                                    ) {
                                        act("reveal");
                                    }
                                }}
                                className={
                                    "w-full rounded-2xl py-4 text-base font-extrabold shadow-md transition active:scale-[0.98] " +
                                    (players.every((p) => p.voted)
                                        ? "bg-primary text-white hover:bg-primary-600"
                                        : "border-2 border-secondary-200 bg-white text-secondary-500")
                                }
                            >
                                결과 공개 🎉
                            </button>
                        )}
                    </>
                )}

                {/* ===== 결과 ===== */}
                {status === "result" && result && (
                    <>
                        <div
                            className={
                                "mb-3 rounded-3xl border-4 p-5 text-center shadow-md " +
                                (result.caught
                                    ? "border-emerald-400 bg-emerald-50"
                                    : "border-rose-400 bg-rose-50")
                            }
                        >
                            <p className="text-xs font-bold text-secondary-500">
                                라이어는
                            </p>
                            <p className="text-2xl font-black text-secondary-800">
                                🕵️ {labelOf(result.liarId)}
                            </p>
                            <p className="mt-2 text-xs font-bold text-secondary-500">
                                제시어 ({state.topic})
                            </p>
                            <p className="text-2xl font-black text-indigo-600">
                                {result.word}
                            </p>
                            <p className="mt-3 text-base font-extrabold text-secondary-700">
                                {result.caught
                                    ? "시민 승리! 🎉"
                                    : "라이어 승리! 😎"}
                            </p>
                        </div>
                        <ul className="mb-3 flex flex-col gap-1.5">
                            {[...players]
                                .sort((a, b) => (b.votes ?? 0) - (a.votes ?? 0))
                                .map((p) => (
                                    <li
                                        key={p.id}
                                        className="flex items-center rounded-2xl border-2 border-secondary-200 bg-white px-3 py-2 text-sm font-bold text-secondary-600"
                                    >
                                        <span className="truncate">
                                            {p.label}
                                            {p.id === result.liarId && " 🕵️"}
                                        </span>
                                        <span className="ml-auto text-indigo-600">
                                            {p.votes ?? 0}표
                                        </span>
                                    </li>
                                ))}
                        </ul>
                        <p className="mb-3 text-center text-[11px] font-medium text-secondary-400">
                            라이어가 잡혔다면, 라이어는 제시어를 맞혀서 역전할 수
                            있어요! (말로 맞혀보기)
                        </p>
                    </>
                )}

                {/* 다시하기 */}
                {status !== "waiting" && (
                    <div className="mt-2 flex flex-col gap-2.5">
                        {!joined && askNickname && status === "result" && (
                            <input
                                type="text"
                                value={nickname}
                                onChange={(e) => setNickname(e.target.value)}
                                maxLength={10}
                                placeholder="닉네임 (비우면 캐릭터 이름)"
                                className="w-full rounded-2xl border-2 border-secondary-200 bg-white px-4 py-3 text-center text-sm font-bold text-secondary-700 outline-none focus:border-indigo-400"
                            />
                        )}
                        <button
                            type="button"
                            onClick={() => {
                                if (
                                    status === "result" ||
                                    window.confirm(
                                        "이번 판을 끝내고 대기실로 돌아갈까요? 함께하는 모두의 화면이 바뀌어요.",
                                    )
                                ) {
                                    act("reset");
                                }
                            }}
                            className="w-full rounded-2xl border-2 border-secondary-200 bg-white py-3 text-sm font-bold text-secondary-500 transition active:scale-[0.98]"
                        >
                            {status === "result" ? "한 판 더 🔁" : "대기실로 돌아가기"}
                        </button>
                    </div>
                )}

                {status === "waiting" && players.length > 0 && (
                    <div className="mt-2 flex flex-wrap gap-1.5">
                        {players.map((p) => (
                            <span
                                key={p.id}
                                className="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-600"
                            >
                                {p.label}
                                {p.id === myId && " (나)"}
                            </span>
                        ))}
                    </div>
                )}


                {/* 닉네임 변경 (공개방) */}
                {askNickname && joined && status === "waiting" && (
                    <div className="mt-3 flex gap-2">
                        <input
                            type="text"
                            value={nickname}
                            onChange={(e) => setNickname(e.target.value)}
                            maxLength={10}
                            placeholder={`내 닉네임: ${me?.label ?? ""}`}
                            className="min-w-0 flex-1 rounded-2xl border-2 border-secondary-200 bg-white px-3 py-2.5 text-center text-sm font-bold text-secondary-700 outline-none focus:border-indigo-400"
                        />
                        <button
                            type="button"
                            onClick={async () => {
                                await act("rename");
                                setNickname("");
                            }}
                            className="shrink-0 rounded-2xl border-2 border-secondary-200 bg-white px-4 py-2.5 text-sm font-bold text-secondary-500 transition active:scale-[0.98]"
                        >
                            변경
                        </button>
                    </div>
                )}

                <details className="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50/60 p-3">
                    <summary className="cursor-pointer text-xs font-bold text-indigo-600">
                        💡 규칙 보기
                    </summary>
                    <p className="mt-2 text-xs font-medium leading-relaxed text-secondary-500">
                        한 명(라이어)만 제시어를 몰라요. 돌아가며 제시어를 말로
                        설명하되, 너무 직접적으로 말하면 라이어가 눈치채요! 모두
                        설명하면 라이어 같은 사람에게 투표해요. 최다 득표자가
                        라이어면 시민 승리, 아니면(동점 포함) 라이어 승리예요.
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
