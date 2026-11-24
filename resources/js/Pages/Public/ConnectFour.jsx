import { Head, Link } from "@inertiajs/react";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import PublicLayout from "@/Layouts/PublicLayout";
import echo from "@/echo";
import { getPublicClientId } from "@/publicClientId";

/** CSRF 토큰 (meta 태그에서) */
function csrfToken() {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") ?? ""
    );
}

/** 4목 액션 전송. 실패 시 null. */
async function postAction(body) {
    try {
        const res = await fetch("/public/games/connect-four/action", {
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

/** 참가 자리(0, 1)별 돌 색 */
const STONE = [
    { name: "빨강", fill: "bg-red-500", ring: "ring-red-300", text: "text-red-600" },
    { name: "노랑", fill: "bg-yellow-400", ring: "ring-yellow-200", text: "text-yellow-600" },
];

/**
 * 공개 4목 (비로그인, 2인 대전).
 *
 * 방은 하나, 참가 자리는 2개. 두 명이 모이면 자동 시작하고 나머지는 구경한다.
 * 가로·세로·대각선으로 같은 색 돌 4개를 먼저 이으면 승리.
 * 판 상태와 승패 판정은 서버가 관리하고, 변경될 때마다 public 채널로 전체 상태가 온다.
 */
export default function ConnectFour({ initialState }) {
    const clientId = useMemo(() => getPublicClientId(), []);

    const [state, setState] = useState(initialState);
    const [nickname, setNickname] = useState("");
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

    useEffect(() => {
        echo.channel("public-connect-four").listen(
            ".public-connect-four.updated",
            (payload) => applyState(payload.state),
        );

        return () => echo.leave("public-connect-four");
    }, [applyState]);

    const act = async (action, extra = {}) => {
        if (busy) {
            return;
        }
        setBusy(true);
        const res = await postAction({
            client_id: clientId,
            action,
            label: ["join", "rename"].includes(action) ? nickname.trim() : undefined,
            ...extra,
        });
        setBusy(false);

        if (!res) {
            showToast("연결이 불안정해요. 다시 눌러주세요.");
            return;
        }
        applyState(res.state);
        if (res.message) {
            showToast(res.message);
        }
    };

    const { players, board, status, turn, winner, winLine, lastMove } = state;
    const myIndex = players.findIndex((p) => p.id === clientId);
    const joined = myIndex !== -1;
    const myTurn = status === "playing" && joined && turn === myIndex;
    const seatsFull = players.length >= 2;

    const isWinCell = (r, c) => winLine.some(([wr, wc]) => wr === r && wc === c);
    // 열이 가득 찼는지 (맨 윗칸이 채워졌는지)
    const columnFull = (c) => board[0][c] !== 0;

    return (
        <PublicLayout>
            <Head title="커넥트 포(4목)" />

            <div className="flex flex-1 flex-col px-4 py-4">
                <div className="mb-3 flex items-center gap-2">
                    <Link
                        href="/public/games"
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
                        <span className="text-xl">🔴</span>
                        <h1 className="text-lg font-extrabold text-secondary-700">
                            커넥트 포(4목)
                        </h1>
                    </div>
                    <span className="ml-auto rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-600">
                        공개방
                    </span>
                </div>

                {/* 두 참가자 */}
                <div className="mb-3 grid grid-cols-2 gap-2.5">
                    {[0, 1].map((i) => {
                        const p = players[i];
                        const isTurn = status === "playing" && turn === i;
                        const isWinner = status === "ended" && winner === i;
                        return (
                            <div
                                key={i}
                                className={
                                    "flex items-center gap-2 rounded-2xl border-2 bg-white px-3 py-2.5 " +
                                    (isTurn
                                        ? "border-primary shadow-md"
                                        : "border-secondary-200")
                                }
                            >
                                <span
                                    className={
                                        "h-6 w-6 shrink-0 rounded-full " +
                                        STONE[i].fill
                                    }
                                />
                                <div className="min-w-0">
                                    <p className="truncate text-xs font-extrabold text-secondary-700">
                                        {p ? p.label : "빈 자리"}
                                        {p && p.id === clientId && (
                                            <span className="ml-1 text-primary">
                                                (나)
                                            </span>
                                        )}
                                    </p>
                                    <p className="text-[10px] font-bold text-secondary-400">
                                        {isWinner
                                            ? "👑 승리"
                                            : isTurn
                                              ? "▶ 차례"
                                              : STONE[i].name}
                                    </p>
                                </div>
                            </div>
                        );
                    })}
                </div>

                <p className="mb-3 text-center text-sm font-bold text-secondary-600">
                    {state.log}
                </p>

                {/* 참가 / 자리 비우기 */}
                {!joined && (
                    <div className="mb-3 flex flex-col gap-2.5">
                        {!seatsFull && (
                            <>
                                <input
                                    type="text"
                                    value={nickname}
                                    onChange={(e) => setNickname(e.target.value)}
                                    maxLength={10}
                                    placeholder="닉네임 (비우면 캐릭터 이름)"
                                    className="w-full rounded-2xl border-2 border-secondary-200 bg-white px-4 py-3 text-center text-sm font-bold text-secondary-700 outline-none focus:border-red-400"
                                />
                                <button
                                    type="button"
                                    onClick={() => act("join")}
                                    className="w-full rounded-2xl bg-red-500 py-4 text-base font-extrabold text-white shadow-md transition active:scale-[0.98]"
                                >
                                    참가하기 🙋
                                </button>
                            </>
                        )}
                        {seatsFull && (
                            <p className="text-center text-xs font-bold text-secondary-400">
                                두 명이 대결 중이에요. 구경할 수 있어요 👀
                            </p>
                        )}
                    </div>
                )}

                {/* 판 */}
                <div className="rounded-3xl bg-blue-600 p-2 shadow-lg">
                    {/* 열 선택 버튼 */}
                    <div className="mb-1 grid grid-cols-7 gap-1">
                        {Array.from({ length: 7 }).map((_, c) => (
                            <button
                                key={c}
                                type="button"
                                onClick={() => act("drop", { col: c })}
                                disabled={!myTurn || columnFull(c)}
                                className={
                                    "flex h-8 items-center justify-center rounded-lg text-sm font-black transition active:scale-95 " +
                                    (myTurn && !columnFull(c)
                                        ? "bg-white/90 text-blue-700"
                                        : "bg-white/20 text-white/40")
                                }
                                aria-label={`${c + 1}번 열에 놓기`}
                            >
                                ▼
                            </button>
                        ))}
                    </div>

                    <div className="grid grid-cols-7 gap-1">
                        {board.map((row, r) =>
                            row.map((cell, c) => {
                                const win = isWinCell(r, c);
                                const last =
                                    lastMove && lastMove[0] === r && lastMove[1] === c;
                                return (
                                    <div
                                        key={`${r}-${c}`}
                                        className="flex aspect-square items-center justify-center rounded-full bg-blue-900/60 p-[10%]"
                                    >
                                        {cell !== 0 && (
                                            <span
                                                className={
                                                    "block h-full w-full rounded-full " +
                                                    STONE[cell - 1].fill +
                                                    (win
                                                        ? " ring-4 ring-white animate-pulse"
                                                        : last
                                                          ? " ring-2 ring-white/70"
                                                          : "")
                                                }
                                            />
                                        )}
                                    </div>
                                );
                            }),
                        )}
                    </div>
                </div>

                {/* 결과/차례 안내 */}
                <p className="mt-3 text-center text-sm font-extrabold text-secondary-700">
                    {status === "playing" &&
                        joined &&
                        (myTurn ? "내 차례! 열을 골라 ▼ 를 눌러요" : "상대 차례예요")}
                    {status === "ended" &&
                        (winner === "draw"
                            ? "무승부 🤝"
                            : joined && winner === myIndex
                              ? "내가 이겼어요! 🎉"
                              : joined
                                ? "아쉽게 졌어요 😢"
                                : `${players[winner]?.label ?? ""} 님 승리`)}
                </p>

                {joined && (
                    <div className="mt-3 flex flex-col gap-2.5">
                        {(status === "ended" || (status === "playing" && seatsFull)) && (
                            <button
                                type="button"
                                onClick={() => {
                                    if (
                                        status === "ended" ||
                                        window.confirm(
                                            "진행 중인 판을 끝내고 새로 시작할까요? 선공이 바뀌어요.",
                                        )
                                    ) {
                                        act("reset");
                                    }
                                }}
                                className="w-full rounded-2xl bg-primary py-3.5 text-sm font-extrabold text-white shadow-md transition active:scale-[0.98] hover:bg-primary-600"
                            >
                                {status === "ended" ? "한 판 더 🔁" : "새로 시작"}
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={() => {
                                if (
                                    status !== "playing" ||
                                    window.confirm("자리를 비울까요? 진행 중인 판은 끝나요.")
                                ) {
                                    act("leave");
                                }
                            }}
                            className="w-full rounded-2xl border-2 border-secondary-200 bg-white py-3 text-sm font-bold text-secondary-500 transition active:scale-[0.98]"
                        >
                            자리 비우기
                        </button>
                    </div>
                )}

                {/* 닉네임 변경 (대기 중에만) */}
                {joined && status === "waiting" && (
                    <div className="mt-3 flex gap-2">
                        <input
                            type="text"
                            value={nickname}
                            onChange={(e) => setNickname(e.target.value)}
                            maxLength={10}
                            placeholder={`내 닉네임: ${players[myIndex]?.label ?? ""}`}
                            className="min-w-0 flex-1 rounded-2xl border-2 border-secondary-200 bg-white px-3 py-2.5 text-center text-sm font-bold text-secondary-700 outline-none focus:border-red-400"
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

                <details className="mt-4 rounded-2xl border border-red-200 bg-red-50/60 p-3">
                    <summary className="cursor-pointer text-xs font-bold text-red-600">
                        💡 규칙 보기
                    </summary>
                    <p className="mt-2 text-xs font-medium leading-relaxed text-secondary-500">
                        번갈아 열을 골라 돌을 떨어뜨려요. 돌은 그 열의 가장 아래
                        빈칸에 쌓여요. 가로·세로·대각선으로 같은 색 돌{" "}
                        <span className="font-bold">4개</span>를 먼저 이으면
                        승리! 판이 가득 차면 무승부예요. 한 판이 끝나면 선공이
                        바뀌어요.
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
        </PublicLayout>
    );
}
