import { Head, Link } from "@inertiajs/react";
import { useRef, useState } from "react";
import PublicLayout from "@/Layouts/PublicLayout";
import PenaltyBanner from "@/Components/Public/PenaltyBanner";

/**
 * 병돌리기 (스핀 더 보틀, 19금) — 공개 술게임.
 *
 * 화면 중앙의 병을 돌리면 랜덤 각도로 여러 바퀴 돌다가 멈춘다.
 * 병이 가리킨 사람과 무엇을 할지는 함께하는 사람끼리 자유롭게! (못 하면 상단 공용 벌칙)
 * 전적으로 클라이언트 로컬.
 */

function SpinBottleInner() {
    // 누적 회전각(deg). 매번 더 큰 값으로 올려 '여러 바퀴 + 랜덤 멈춤'을 만든다.
    const [angle, setAngle] = useState(0);
    const [spinning, setSpinning] = useState(false);
    const spinTimer = useRef(null);

    const spin = () => {
        if (spinning) {
            return;
        }
        setSpinning(true);

        // 5~8바퀴 + 랜덤 추가 각도.
        const turns = 5 + Math.floor(Math.random() * 4);
        const extra = Math.floor(Math.random() * 360);
        setAngle((prev) => prev + turns * 360 + extra);

        clearTimeout(spinTimer.current);
        spinTimer.current = setTimeout(() => {
            setSpinning(false);
        }, 2900);
    };

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
                    <span className="text-xl">🍾</span>
                    <h1 className="text-lg font-extrabold text-secondary-700">병돌리기</h1>
                </div>
                <span className="ml-auto rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-500">
                    🔞 19금
                </span>
            </div>

            <PenaltyBanner />

            <div className="flex flex-1 flex-col items-center justify-center gap-6">
                <div className="w-full rounded-2xl border border-rose-200 bg-rose-50/60 p-3.5">
                    <p className="text-xs font-medium leading-relaxed text-secondary-500">
                        <span className="font-bold text-rose-600">🍾 룰</span>
                        <br />
                        다같이 둥글게 앉아 폰을 가운데 두고 병을 돌려요. 병이{" "}
                        <span className="font-bold">가리킨 사람</span>과 무엇을 할지는 자유롭게!
                        <span className="font-bold text-red-500"> 못 하면 벌칙 😈</span>
                    </p>
                </div>

                {/* 병 (크게) */}
                <div className="relative flex aspect-square w-full max-w-[340px] items-center justify-center rounded-full border-2 border-dashed border-rose-200 bg-gradient-to-br from-rose-50 to-pink-50">
                    <div
                        className="flex h-full w-full items-center justify-center"
                        style={{
                            transform: `rotate(${angle}deg)`,
                            transition: spinning
                                ? "transform 2.8s cubic-bezier(0.17, 0.67, 0.12, 0.99)"
                                : "none",
                        }}
                    >
                        {/* 병(위쪽이 입구=가리키는 방향) */}
                        <span className="text-[9rem] leading-none" aria-hidden="true">
                            🍾
                        </span>
                    </div>
                    {/* 중앙 축 */}
                    <span className="absolute h-4 w-4 rounded-full bg-rose-400 shadow" />
                </div>
            </div>

            <button
                type="button"
                onClick={spin}
                disabled={spinning}
                className="mt-5 w-full rounded-2xl bg-primary py-4 text-base font-extrabold text-white shadow-md transition active:scale-[0.98] hover:bg-primary-600 disabled:opacity-60 disabled:active:scale-100"
            >
                {spinning ? "돌리는 중… 🍾" : "병 돌리기 🍾"}
            </button>
        </div>
    );
}

export default function SpinBottle() {
    return (
        <PublicLayout>
            <Head title="병돌리기 (19금)" />
            <SpinBottleInner />
        </PublicLayout>
    );
}
