import { Head, Link } from "@inertiajs/react";
import { useState } from "react";
import PublicLayout from "@/Layouts/PublicLayout";
import PenaltyBanner from "@/Components/Public/PenaltyBanner";
import { TRUTH_ROUNDS } from "@/truthGameQuestions";

/**
 * 진실게임 (19금) — 공개 술게임.
 *
 * 모든 라운드의 질문을 하나로 합쳐 랜덤 순서로 하나씩 출제한다.
 * 솔직하게 답하거나, 못 하면 '이번 벌칙'(상단 공용) 수행. 전적으로 클라이언트 로컬.
 */

/** 모든 라운드의 질문을 한 풀로 합친다. */
const ALL_QUESTIONS = TRUTH_ROUNDS.flatMap((round) => round.questions);

/** 배열을 섞은 새 배열을 반환한다 (Fisher–Yates). */
function shuffle(arr) {
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

/** 덱을 새로 섞되, 첫 질문이 직전 마지막 질문과 같으면 한 번 더 섞는다. */
function freshDeck(prevLast) {
    let deck = shuffle(ALL_QUESTIONS);
    if (ALL_QUESTIONS.length > 1) {
        while (deck[0] === prevLast) {
            deck = shuffle(ALL_QUESTIONS);
        }
    }
    return deck;
}

function TruthGameInner() {
    // 전체 질문을 섞은 덱에서 중복 없이 하나씩 뽑고, 다 쓰면 다시 섞는다.
    const [deck, setDeck] = useState(() => shuffle(ALL_QUESTIONS));
    const [index, setIndex] = useState(0);

    const question = deck[index];

    const handleNext = () => {
        setIndex((prevIndex) => {
            const nextIndex = prevIndex + 1;
            if (nextIndex >= deck.length) {
                setDeck(freshDeck(deck[prevIndex]));
                return 0;
            }
            return nextIndex;
        });
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
                    <span className="text-xl">🎭</span>
                    <h1 className="text-lg font-extrabold text-secondary-700">진실게임</h1>
                </div>
                <span className="ml-auto rounded-full bg-teal-100 px-2.5 py-1 text-xs font-bold text-teal-600">
                    🔞 19금
                </span>
            </div>

            <PenaltyBanner />

            <div className="flex flex-col items-center gap-4">
                <div className="w-full rounded-2xl border border-teal-200 bg-teal-50/60 p-3.5">
                    <p className="text-xs font-medium leading-relaxed text-secondary-500">
                        <span className="font-bold text-teal-600">🎭 룰</span>
                        <br />
                        돌아가며 질문에 <span className="font-bold">솔직하게</span> 답해요.
                        <span className="font-bold text-red-500"> 답 못하면 벌칙!</span> 😈
                    </p>
                </div>

                {/* 질문 카드 */}
                <div className="relative w-full rounded-3xl border-2 border-teal-200 bg-gradient-to-br from-teal-50 to-emerald-50 p-8 text-center shadow-sm">
                    <span className="mb-4 inline-flex items-center gap-1 rounded-full bg-white/70 px-3 py-1 text-xs font-bold text-teal-600 shadow-inner">
                        🎭 진실게임
                    </span>
                    <p className="text-xl font-extrabold leading-relaxed text-secondary-800">
                        {question}
                    </p>
                </div>

                <p className="text-sm font-medium text-secondary-400">
                    솔직하게 답하거나, 못 하면 벌칙! 😈
                </p>
            </div>

            <button
                type="button"
                onClick={handleNext}
                className="mt-4 w-full rounded-2xl bg-primary py-4 text-base font-extrabold text-white shadow-md transition active:scale-[0.98] hover:bg-primary-600"
            >
                다음 질문 🔀
            </button>
        </div>
    );
}

export default function TruthGame() {
    return (
        <PublicLayout>
            <Head title="진실게임 (19금)" />
            <TruthGameInner />
        </PublicLayout>
    );
}
