import { Link } from "@inertiajs/react";
import { useEffect, useState } from "react";
import { getCurrentPenalty, PENALTY_EVENT, PENALTY_FAIL } from "@/publicPenalty";

/**
 * '이번 벌칙' 배너.
 *
 * 술게임 플레이 화면 상단에 현재 선택된 공용 벌칙을 보여준다.
 * 벌칙은 게임 목록 상단의 '벌칙 룰렛'으로 정하며(sessionStorage 공유),
 * 아직 안 정했으면 목록으로 돌아가 정하라고 안내한다.
 */
export default function PenaltyBanner() {
    const [penalty, setPenalty] = useState(null);

    useEffect(() => {
        setPenalty(getCurrentPenalty());
        const onChange = (e) => setPenalty(e.detail ?? getCurrentPenalty());
        window.addEventListener(PENALTY_EVENT, onChange);
        return () => window.removeEventListener(PENALTY_EVENT, onChange);
    }, []);

    if (penalty) {
        return (
            <div className="mb-4 flex items-center gap-2 rounded-2xl border border-indigo-200 bg-indigo-50/70 px-3.5 py-3">
                <span className="text-lg leading-none">🎯</span>
                <div className="min-w-0 flex-1">
                    <p className="text-[11px] font-bold text-indigo-500">이번 미션</p>
                    <p className="truncate text-sm font-extrabold text-secondary-800">
                        {penalty.text}
                    </p>
                    <p className="text-[11px] font-bold text-red-500">
                        {PENALTY_FAIL}
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="mb-4 flex items-center gap-2 rounded-2xl border border-secondary-200 bg-secondary-50 px-3.5 py-3">
            <span className="text-lg leading-none">🎯</span>
            <p className="flex-1 text-xs font-medium leading-relaxed text-secondary-500">
                아직 벌칙을 안 정했어요.{" "}
                <Link
                    href="/public/games?tab=drink"
                    className="font-bold text-indigo-500 underline"
                >
                    목록에서 벌칙 룰렛
                </Link>
                을 돌려보세요!
            </p>
        </div>
    );
}
