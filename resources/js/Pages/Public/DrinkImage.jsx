import { Head, Link } from "@inertiajs/react";
import { useState } from "react";
import PublicLayout from "@/Layouts/PublicLayout";
import PenaltyBanner from "@/Components/Public/PenaltyBanner";

/**
 * 이미지게임 (취했을 때 버전, 19금) — 공개 술게임.
 *
 * 매운맛보다 수위를 더 올린 "~할 것 같은 사람?" 지목 게임.
 * 다같이 동시에 지목하고, 가장 많이 지목된 사람이 벌주 한 잔. 클라이언트 로컬.
 */

/** @type {string[]} 19금 지목 질문 풀 */
const QUESTIONS = [
    "오늘 술 취하면 가장 돌변할 것 같은 사람은?",
    "여기서 원나잇 경험이 가장 많을 것 같은 사람은?",
    "술만 들어가면 스킨십이 과감해질 것 같은 사람은?",
    "지금 가장 야한 생각을 하고 있을 것 같은 사람은?",
    "모텔 들어가자고 가장 먼저 꼬실 것 같은 사람은?",
    "연애할 때 밤이 가장 뜨거울 것 같은 사람은?",
    "첫 경험이 가장 빨랐을 것 같은 사람은?",
    "오늘 끝나고 누군가에게 '자고 갈래?' 물어볼 것 같은 사람은?",
    "겉보기와 달리 침대에서 반전일 것 같은 사람은?",
    "이 중에 제일 밝힐(?) 것 같은 사람은?",
    "술 취해서 전 애인한테 전화할 것 같은 사람은?",
    "가장 많은 사람과 썸을 타봤을 것 같은 사람은?",
    "지금 옆 사람과 눈 맞으면 바로 키스할 것 같은 사람은?",
    "클럽에서 제일 잘 놀 것 같은 사람은?",
    "하룻밤 상대로 가장 인기 많을 것 같은 사람은?",
    "은근히 밝히는데 아닌 척할 것 같은 사람은?",
    "술 게임에서 벌칙을 가장 세게 수행할 것 같은 사람은?",
    "연락처 물어보면 바로 재워줄(?) 것 같은 사람은?",
    "가장 대담한 데이트를 해봤을 것 같은 사람은?",
    "오늘 누군가를 집에 데려갈 것 같은 사람은?",
    "술 취하면 아무한테나 사랑한다고 할 것 같은 사람은?",
    "가장 섹시한 매력을 숨기고 있을 것 같은 사람은?",
    "이 자리에서 제일 먼저 선 넘을 것 같은 사람은?",
    "연애 경험이 가장 화려할 것 같은 사람은?",
    "오늘 가장 '위험한' 밤을 보낼 것 같은 사람은?",
];

function shuffle(arr) {
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

function freshDeck(prevLast) {
    let deck = shuffle(QUESTIONS);
    if (QUESTIONS.length > 1) {
        while (deck[0] === prevLast) {
            deck = shuffle(QUESTIONS);
        }
    }
    return deck;
}

function DrinkImageGame() {
    const [deck, setDeck] = useState(() => shuffle(QUESTIONS));
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
                    <span className="text-xl">🥵</span>
                    <h1 className="text-lg font-extrabold text-secondary-700">
                        이미지게임 (취했을 때)
                    </h1>
                </div>
                <span className="ml-auto rounded-full bg-fuchsia-100 px-2.5 py-1 text-xs font-bold text-fuchsia-500">
                    🔞 19금
                </span>
            </div>

            <PenaltyBanner />

            <div className="flex flex-col items-center gap-4">
                <div className="w-full rounded-2xl border border-fuchsia-200 bg-fuchsia-50/60 p-3.5">
                    <p className="text-xs font-medium leading-relaxed text-secondary-500">
                        <span className="font-bold text-fuchsia-600">🥵 룰</span>
                        <br />
                        하나 둘 셋! 하면 질문에 어울리는 사람을 <span className="font-bold">동시에 지목</span>하세요.
                        <span className="font-bold text-red-500"> 가장 많이 지목된 사람이 벌주 한 잔!</span> 😈
                    </p>
                </div>

                <div className="relative w-full rounded-3xl border-2 border-fuchsia-200 bg-gradient-to-br from-fuchsia-50 to-pink-50 p-8 text-center shadow-sm">
                    <span className="mb-4 inline-flex items-center gap-1 rounded-full bg-white/70 px-3 py-1 text-xs font-bold text-fuchsia-600 shadow-inner">
                        🥵 취했을 때
                    </span>
                    <p className="text-xl font-extrabold leading-relaxed text-secondary-800">
                        {question}
                    </p>
                </div>

                <p className="text-sm font-medium text-secondary-400">
                    셋 세면 동시에 손가락으로 가리켜 주세요! 👉
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

export default function DrinkImage() {
    return (
        <PublicLayout>
            <Head title="이미지게임 (취했을 때)" />
            <DrinkImageGame />
        </PublicLayout>
    );
}
