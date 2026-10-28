import { Head, Link } from "@inertiajs/react";
import { useRef, useState } from "react";
import PublicLayout from "@/Layouts/PublicLayout";
import { PENALTIES, setCurrentPenalty } from "@/publicPenalty";

/**
 * 랜덤 벌칙 룰렛 (19금) — 공개 술게임.
 *
 * 버튼을 누르면 벌칙 목록을 빠르게 훑다가 하나에 멈춘다(슬롯머신 느낌).
 * 멈춘 벌칙은 공용 '현재 벌칙'으로도 설정되어 다른 술게임에서 공유된다.
 * 벌칙 풀은 @/publicPenalty 공용 모듈을 쓴다.
 */

function PenaltyRouletteInner() {
    const [current, setCurrent] = useState(0);
    const [spinning, setSpinning] = useState(false);
    const [result, setResult] = useState(null);
    const timerRef = useRef(null);

    const spin = () => {
        if (spinning) {
            return;
        }
        setResult(null);
        setSpinning(true);

        const finalIndex = Math.floor(Math.random() * PENALTIES.length);
        // 총 스핀 횟수(한 바퀴 이상 돌다가 finalIndex 에서 멈추도록)
        const totalTicks = PENALTIES.length * 2 + finalIndex + 5;
        let tick = 0;
        let delay = 45;

        const step = () => {
            setCurrent((c) => (c + 1) % PENALTIES.length);
            tick += 1;

            if (tick >= totalTicks) {
                setCurrent(finalIndex);
                setResult(PENALTIES[finalIndex]);
                setSpinning(false);
                setCurrentPenalty(PENALTIES[finalIndex]); // 공용 현재 벌칙으로 공유
                return;
            }
            // 끝으로 갈수록 느려지는 감속 효과
            if (tick > totalTicks - 8) {
                delay += 55;
            }
            timerRef.current = setTimeout(step, delay);
        };

        timerRef.current = setTimeout(step, delay);
    };

    const shown = result ?? PENALTIES[current];

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
                    <span className="text-xl">🎯</span>
                    <h1 className="text-lg font-extrabold text-secondary-700">
                        랜덤 벌칙 룰렛
                    </h1>
                </div>
                <span className="ml-auto rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-bold text-indigo-500">
                    🔞 19금
                </span>
            </div>

            <div className="flex flex-col items-center gap-4">
                <div className="w-full rounded-2xl border border-indigo-200 bg-indigo-50/60 p-3.5">
                    <p className="text-xs font-medium leading-relaxed text-secondary-500">
                        <span className="font-bold text-indigo-600">🎯 룰</span>
                        <br />
                        벌칙 대상자가 <span className="font-bold">룰렛을 돌려</span> 당첨된 벌칙을 수행해요.
                        <span className="font-bold text-red-500"> 못 하면 원샷!</span> 😈
                    </p>
                </div>

                {/* 룰렛 디스플레이 */}
                <div
                    className={
                        "relative w-full rounded-3xl border-2 p-8 text-center shadow-sm transition-colors " +
                        (result
                            ? "border-indigo-400 bg-gradient-to-br from-indigo-100 to-purple-100"
                            : "border-indigo-200 bg-gradient-to-br from-indigo-50 to-purple-50")
                    }
                >
                    <span className="mb-3 inline-flex items-center gap-1 rounded-full bg-white/70 px-3 py-1 text-xs font-bold text-indigo-600 shadow-inner">
                        {spinning ? "두구두구…" : result ? "🎉 당첨!" : "🎯 벌칙 룰렛"}
                    </span>
                    <div className="text-5xl">{shown.emoji}</div>
                    <p
                        className={
                            "mt-3 font-extrabold leading-relaxed text-secondary-800 " +
                            (result ? "text-xl" : "text-base opacity-80")
                        }
                    >
                        {shown.text}
                    </p>
                </div>
            </div>

            <button
                type="button"
                onClick={spin}
                disabled={spinning}
                className="mt-4 w-full rounded-2xl bg-primary py-4 text-base font-extrabold text-white shadow-md transition active:scale-[0.98] hover:bg-primary-600 disabled:opacity-60 disabled:active:scale-100"
            >
                {spinning ? "돌리는 중… 🎡" : result ? "다시 돌리기 🔀" : "룰렛 돌리기 🎯"}
            </button>
        </div>
    );
}

export default function PenaltyRoulette() {
    return (
        <PublicLayout>
            <Head title="랜덤 벌칙 룰렛" />
            <PenaltyRouletteInner />
        </PublicLayout>
    );
}
