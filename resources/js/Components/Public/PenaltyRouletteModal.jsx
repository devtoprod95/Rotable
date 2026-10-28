import { useEffect, useMemo, useRef, useState } from "react";
import { PENALTIES, PENALTY_FAIL, setCurrentPenalty } from "@/publicPenalty";

/**
 * 벌칙 룰렛(모달) — 세로 슬롯 슬라이드형.
 *
 * 벌칙 카드가 세로로 쌓여 translateY 로 위로 매끄럽게 스크롤되다가
 * ease-out 으로 가운데 포커스 칸에 안착한다. 멈추면 그 벌칙이 '현재 벌칙'으로
 * 즉시 설정(sessionStorage)되어 모든 술게임에서 공유된다. 닫기는 X 버튼으로.
 *
 * @param {{ onClose: () => void }} props
 */

const ITEM_H = 72; // 각 칸 높이(px)
const VISIBLE = 3; // 보이는 칸 수(홀수)

export default function PenaltyRouletteModal({ onClose }) {
    const [offset, setOffset] = useState(0);
    const [spinning, setSpinning] = useState(false);
    const [result, setResult] = useState(null);
    const [transition, setTransition] = useState("none");
    const rafRef = useRef(null);
    const doneTimer = useRef(null);

    // 스크롤용으로 벌칙 풀을 여러 번 이어 붙인다.
    const strip = useMemo(
        () => Array.from({ length: 8 }, () => PENALTIES).flat(),
        [],
    );

    const spin = () => {
        if (spinning) {
            return;
        }
        setResult(null);
        setSpinning(true);

        const finalIndex = Math.floor(Math.random() * PENALTIES.length);
        const target = PENALTIES.length * 6 + finalIndex;

        // 트랜지션 없이 맨 위로 리셋 → 다음 프레임에 ease-out 으로 이동.
        setTransition("none");
        setOffset(0);
        cancelAnimationFrame(rafRef.current);
        rafRef.current = requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                setTransition("transform 2.6s cubic-bezier(0.12, 0.7, 0.1, 1)");
                setOffset(target);
            });
        });

        clearTimeout(doneTimer.current);
        doneTimer.current = setTimeout(() => {
            const picked = PENALTIES[finalIndex];
            setResult(picked);
            setSpinning(false);
            setCurrentPenalty(picked); // 현재 벌칙으로 확정 → 모든 술게임 공유
        }, 2700);
    };

    // 열리면 자동으로 한 번 돌린다.
    useEffect(() => {
        spin();
        return () => {
            cancelAnimationFrame(rafRef.current);
            clearTimeout(doneTimer.current);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const close = () => {
        cancelAnimationFrame(rafRef.current);
        clearTimeout(doneTimer.current);
        onClose();
    };

    // 가운데 칸에 결과가 오도록 중앙 기준 보정.
    const centerShift = Math.floor(VISIBLE / 2) * ITEM_H;
    const translateY = -(offset * ITEM_H) + centerShift;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center px-6">
            <div className="absolute inset-0 bg-black/50" onClick={close} />

            <div className="relative w-full max-w-[340px] rounded-3xl bg-white p-6 text-center shadow-2xl">
                <button
                    type="button"
                    onClick={close}
                    aria-label="닫기"
                    className="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full text-secondary-400 transition active:scale-90 hover:bg-secondary-100 hover:text-secondary-600"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>

                <span className="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-500">
                    {spinning ? "🎯 벌칙 뽑는 중…" : "🎉 이번 벌칙 당첨!"}
                </span>

                {/* 슬롯 창 */}
                <div
                    className="relative mt-4 w-full overflow-hidden rounded-3xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-purple-50"
                    style={{ height: VISIBLE * ITEM_H }}
                >
                    {/* 가운데 포커스 라인 */}
                    <div
                        className="pointer-events-none absolute inset-x-2 z-10 rounded-xl border-2 border-indigo-300 bg-white/40"
                        style={{ top: centerShift, height: ITEM_H }}
                    />
                    <div style={{ transform: `translateY(${translateY}px)`, transition }}>
                        {strip.map((p, i) => (
                            <div
                                key={i}
                                className="flex items-center justify-center px-5 text-center"
                                style={{ height: ITEM_H }}
                            >
                                <span className="text-sm font-extrabold leading-snug text-secondary-700">
                                    {p.text}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>

                {result && (
                    <p className="mt-3 text-xs font-bold text-red-500">
                        {PENALTY_FAIL}
                    </p>
                )}
            </div>
        </div>
    );
}
