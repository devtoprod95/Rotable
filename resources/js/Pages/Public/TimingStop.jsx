import { Head, Link } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import PublicLayout from "@/Layouts/PublicLayout";
import PenaltyBanner from "@/Components/Public/PenaltyBanner";

/**
 * 타이밍 스톱 — 공개 술게임.
 *
 * 목표 시간(예: 5.00초)이 주어지면, 숫자가 실시간으로 흘러간다.
 * 흘러가는 시간을 보며 목표에 정확히 맞춰 STOP! 돌아가며 계속 도전하고,
 * 기록이 히스토리로 쌓인다. 오차가 가장 큰 사람(꼴찌)이 벌칙!
 *
 * - 페이지를 나갔다 오면 목표/기록이 초기화된다(마운트 시 새 목표).
 * - 리셋 버튼으로 새 목표 + 기록 초기화.
 * 전적으로 클라이언트 로컬.
 */

/** 목표 시간을 랜덤 생성 (3.00 ~ 7.00초). */
function randomTarget() {
    return Math.round((3 + Math.random() * 4) * 100) / 100;
}

export default function TimingStop() {
    return (
        <PublicLayout>
            <Head title="타이밍 스톱" />
            <TimingStopInner />
        </PublicLayout>
    );
}

function TimingStopInner() {
    const [target, setTarget] = useState(() => randomTarget());
    const [running, setRunning] = useState(false);
    const [elapsed, setElapsed] = useState(0);
    /** @type {[{ id:number, time:number, diff:number }[], Function]} */
    const [history, setHistory] = useState([]);

    const startAt = useRef(0);
    const rafId = useRef(null);
    const nextId = useRef(1);

    useEffect(() => () => cancelAnimationFrame(rafId.current), []);

    const start = () => {
        setElapsed(0);
        startAt.current = performance.now();
        const loop = () => {
            setElapsed((performance.now() - startAt.current) / 1000);
            rafId.current = requestAnimationFrame(loop);
        };
        rafId.current = requestAnimationFrame(loop);
        setRunning(true);
    };

    const stop = () => {
        cancelAnimationFrame(rafId.current);
        const time = (performance.now() - startAt.current) / 1000;
        const diff = Math.round(Math.abs(time - target) * 100) / 100;
        setRunning(false);
        setHistory((prev) => [
            { id: nextId.current++, time, diff },
            ...prev,
        ]);
    };

    // 리셋: 새 목표 + 기록 초기화
    const reset = () => {
        cancelAnimationFrame(rafId.current);
        setRunning(false);
        setElapsed(0);
        setTarget(randomTarget());
        setHistory([]);
    };

    // 최고 기록(오차 최소) 찾기 — 강조/꼴찌 표시용
    const bestDiff = history.length
        ? Math.min(...history.map((h) => h.diff))
        : null;
    const worstDiff = history.length
        ? Math.max(...history.map((h) => h.diff))
        : null;

    return (
        <div className="flex flex-1 flex-col px-4 py-5">
            <div className="mb-4 flex items-center gap-2">
                <Link
                    href="/public/games?tab=drink"
                    className="flex h-9 w-9 items-center justify-center rounded-full border border-secondary-200 bg-white text-secondary-500 shadow-sm transition active:scale-95 hover:text-primary"
                    aria-label="게임 목록으로"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                </Link>
                <div className="flex items-center gap-1.5">
                    <span className="text-xl">⏱️</span>
                    <h1 className="text-lg font-extrabold text-secondary-700">타이밍 스톱</h1>
                </div>
                <button
                    type="button"
                    onClick={reset}
                    className="ml-auto flex items-center gap-1 rounded-full border border-secondary-200 bg-white px-3 py-1.5 text-xs font-bold text-secondary-500 shadow-sm transition active:scale-95 hover:text-primary"
                >
                    🔄 리셋
                </button>
            </div>

            <PenaltyBanner />

            {/* 목표 시간 */}
            <div className="mb-4 flex items-center justify-center gap-2 rounded-2xl border border-sky-200 bg-sky-50/70 py-3">
                <span className="text-sm font-bold text-sky-600">🎯 목표</span>
                <span className="text-2xl font-black text-sky-700">
                    {target.toFixed(2)}초
                </span>
            </div>

            {/* 타이머 디스플레이 */}
            <div className="flex flex-col items-center gap-4">
                <div
                    className={
                        "flex aspect-square w-full max-w-[260px] flex-col items-center justify-center rounded-full border-4 shadow-inner transition-colors " +
                        (running
                            ? "border-sky-400 bg-gradient-to-br from-sky-100 to-cyan-100"
                            : "border-secondary-200 bg-secondary-50")
                    }
                >
                    <span
                        className={
                            "text-5xl font-black tabular-nums " +
                            (running ? "text-sky-600" : "text-secondary-400")
                        }
                    >
                        {elapsed.toFixed(2)}
                    </span>
                    <span className="mt-1 text-xs font-bold text-sky-400">
                        {running ? "목표에 맞춰 멈춰!" : "시작을 눌러 도전!"}
                    </span>
                </div>

                {/* 시작 / STOP 버튼 */}
                {running ? (
                    <button
                        type="button"
                        onClick={stop}
                        className="w-full rounded-2xl bg-sky-500 py-5 text-xl font-black text-white shadow-md transition active:scale-[0.98] hover:bg-sky-600"
                    >
                        STOP! ✋
                    </button>
                ) : (
                    <button
                        type="button"
                        onClick={start}
                        className="w-full rounded-2xl bg-primary py-4 text-base font-extrabold text-white shadow-md transition active:scale-[0.98] hover:bg-primary-600"
                    >
                        ▶️ 시작
                    </button>
                )}
            </div>

            {/* 기록 히스토리 */}
            {history.length > 0 && (
                <div className="mt-5 flex flex-col gap-2">
                    <div className="flex items-center justify-between">
                        <h2 className="text-sm font-extrabold text-secondary-600">
                            📋 기록 ({history.length})
                        </h2>
                        <span className="text-[11px] font-medium text-secondary-400">
                            오차 작을수록 승리 · 가장 큰 사람 벌칙 😈
                        </span>
                    </div>
                    <div className="flex flex-col gap-1.5">
                        {history.map((h, i) => {
                            const isBest = h.diff === bestDiff;
                            const isWorst = h.diff === worstDiff && history.length > 1;
                            return (
                                <div
                                    key={h.id}
                                    className={
                                        "flex items-center gap-3 rounded-xl border px-3 py-2.5 " +
                                        (isBest
                                            ? "border-sky-300 bg-sky-50"
                                            : isWorst
                                              ? "border-red-200 bg-red-50"
                                              : "border-secondary-200 bg-white")
                                    }
                                >
                                    <span className="w-7 text-center text-sm font-black text-secondary-400">
                                        {history.length - i}
                                    </span>
                                    <span className="flex-1 text-sm font-extrabold text-secondary-800">
                                        {h.time.toFixed(2)}초
                                    </span>
                                    <span className="text-xs font-bold text-secondary-500">
                                        오차 {h.diff.toFixed(2)}
                                    </span>
                                    {isBest && (
                                        <span className="rounded-full bg-sky-500 px-2 py-0.5 text-[10px] font-bold text-white">
                                            🏆 1위
                                        </span>
                                    )}
                                    {isWorst && (
                                        <span className="rounded-full bg-red-400 px-2 py-0.5 text-[10px] font-bold text-white">
                                            😈 벌칙
                                        </span>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
