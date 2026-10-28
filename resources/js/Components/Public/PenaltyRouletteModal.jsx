import { useEffect, useRef, useState } from "react";
import { PENALTIES, setCurrentPenalty } from "@/publicPenalty";

/**
 * 벌칙 룰렛(모달).
 *
 * 벌칙 풀을 슬롯머신처럼 빠르게 훑다가 감속하며 하나에 멈춘다.
 * 멈추면 그 벌칙이 "현재 벌칙"으로 설정(sessionStorage)되어, 모든 술게임에서 공유된다.
 *
 * @param {{ onClose: () => void }} props
 */
export default function PenaltyRouletteModal({ onClose }) {
    const [index, setIndex] = useState(0);
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
        const totalTicks = PENALTIES.length * 2 + finalIndex + 5;
        let tick = 0;
        let delay = 45;

        const step = () => {
            setIndex((c) => (c + 1) % PENALTIES.length);
            tick += 1;

            if (tick >= totalTicks) {
                setIndex(finalIndex);
                const picked = PENALTIES[finalIndex];
                setResult(picked);
                setSpinning(false);
                setCurrentPenalty(picked); // 현재 벌칙으로 확정 → 모든 술게임 공유
                return;
            }
            if (tick > totalTicks - 8) {
                delay += 55;
            }
            timerRef.current = setTimeout(step, delay);
        };

        timerRef.current = setTimeout(step, delay);
    };

    // 모달이 열리면 자동으로 한 번 돌린다.
    useEffect(() => {
        spin();
        return () => clearTimeout(timerRef.current);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const close = () => {
        clearTimeout(timerRef.current);
        onClose();
    };

    const shown = result ?? PENALTIES[index];

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center px-6">
            <div className="absolute inset-0 bg-black/50" onClick={close} />

            <div className="relative w-full max-w-[340px] rounded-3xl bg-white p-6 text-center shadow-2xl">
                <span className="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-500">
                    {spinning ? "🎯 벌칙 뽑는 중…" : "🎉 이번 벌칙 당첨!"}
                </span>

                <div
                    className={
                        "mt-4 rounded-3xl border-2 p-6 transition-colors " +
                        (result
                            ? "border-indigo-400 bg-gradient-to-br from-indigo-50 to-purple-50"
                            : "border-secondary-200 bg-secondary-50")
                    }
                >
                    <div
                        className={
                            "text-6xl transition-transform " +
                            (spinning ? "scale-90 opacity-80" : "scale-100")
                        }
                    >
                        {shown.emoji}
                    </div>
                    <p
                        className={
                            "mt-3 font-extrabold leading-relaxed text-secondary-800 " +
                            (result ? "text-lg" : "text-base opacity-80")
                        }
                    >
                        {shown.text}
                    </p>
                </div>

                {result ? (
                    <div className="mt-5 flex gap-2">
                        <button
                            type="button"
                            onClick={spin}
                            className="flex-1 rounded-2xl border-2 border-secondary-200 bg-white py-3 text-sm font-bold text-secondary-500 transition active:scale-[0.97]"
                        >
                            다시 돌리기 🔀
                        </button>
                        <button
                            type="button"
                            onClick={close}
                            className="flex-[1.4] rounded-2xl bg-primary py-3 text-sm font-extrabold text-white shadow-md transition active:scale-[0.97] hover:bg-primary-600"
                        >
                            이 벌칙으로! ✅
                        </button>
                    </div>
                ) : (
                    <p className="mt-4 text-xs font-medium text-secondary-400">
                        어떤 벌칙이 걸릴까요? 😈
                    </p>
                )}
            </div>
        </div>
    );
}
