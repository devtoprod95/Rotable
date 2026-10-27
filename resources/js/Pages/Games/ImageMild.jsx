import { Head, Link } from "@inertiajs/react";
import { useState } from "react";
import AppLayout from "@/Layouts/AppLayout";

/**
 * 순한맛 이미지게임.
 *
 * "~한 사람?" 형태의 가벼운 질문이 랜덤으로 하나씩 나온다.
 * 참가자들은 질문에 가장 어울리는 사람을 손가락으로 지목하며 즐긴다.
 * (서버 왕복 없이 프론트 질문 풀에서 랜덤 출제)
 */

/** @type {string[]} 순한맛 질문 풀 */
const QUESTIONS = [
    "지금 이 테이블에서 첫인상이 가장 좋은 사람은?",
    "같이 카페 가면 제일 편하게 수다 떨 것 같은 사람은?",
    "웃는 모습이 제일 예쁜/멋진 사람은?",
    "술자리에서 옆에 앉으면 재밌을 것 같은 사람은?",
    "오늘 패션 센스가 가장 좋은 사람은?",
    "카톡 답장이 오면 괜히 기분 좋을 것 같은 사람은?",
    "같이 여행 가면 계획을 잘 짜줄 것 같은 사람은?",
    "목소리만 듣고도 호감이 생길 것 같은 사람은?",
    "소개팅이 끝나고 집에 가는 길에 생각날 것 같은 사람은?",
    "처음 봤는데 왠지 오래 알고 지낸 것 같은 사람은?",
    "리액션이 좋아서 내 얘기를 더 하고 싶게 만드는 사람은?",
    "맛집을 추천해 달라고 하면 믿고 따라갈 것 같은 사람은?",
    "고민을 말하면 내 편부터 들어줄 것 같은 사람은?",
    "오늘 가장 의외의 매력을 발견한 사람은?",
    "센스 있는 선물로 감동을 줄 것 같은 사람은?",
    "노래방에서 듀엣곡을 부르면 재밌을 것 같은 사람은?",
    "길을 잘못 들어도 같이 있으면 웃으면서 갈 것 같은 사람은?",
    "요리를 못해도 왠지 같이 만들면 재밌을 것 같은 사람은?",
    "둘이서 대화해도 어색하지 않을 것 같은 사람은?",
    "연애하면 소소한 이벤트를 잘해줄 것 같은 사람은?",
    "오늘 가장 매너가 좋았던 사람은?",
    "무표정일 때와 웃을 때 반전 매력이 있는 사람은?",
    "영화를 보고 나서 감상평을 가장 오래 나눌 것 같은 사람은?",
    "은근히 허당미가 있을 것 같은 사람은?",
    "힘든 하루 끝에 만나면 기분이 풀릴 것 같은 사람은?",
    "대화하다가 '어? 우리 잘 맞는데?' 싶을 것 같은 사람은?",
    "주말에 갑자기 만나자고 해도 반가울 것 같은 사람은?",
    "같이 있으면 별것 아닌 일에도 웃음이 많아질 것 같은 사람은?",
    "연락을 주고받다 보면 자연스럽게 친해질 것 같은 사람은?",
    "오늘 끝나고 한 번 더 이야기해 보고 싶은 사람은?",
];

/** 배열을 섞은 새 배열을 반환한다 (Fisher–Yates). */
function shuffle(arr) {
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

/**
 * 덱을 새로 섞되, 첫 질문이 직전 마지막 질문과 같으면 한 번 더 섞어
 * 리셋 직후 같은 질문이 연속으로 나오는 것을 피한다.
 */
function freshDeck(prevLast) {
    let deck = shuffle(QUESTIONS);
    if (QUESTIONS.length > 1) {
        while (deck[0] === prevLast) {
            deck = shuffle(QUESTIONS);
        }
    }
    return deck;
}

export default function ImageMild() {
    // 질문을 한 번씩 모두 소진할 때까지 중복 없이 뽑고, 다 쓰면 다시 섞는다(리셋).
    const [deck, setDeck] = useState(() => shuffle(QUESTIONS));
    const [index, setIndex] = useState(0);

    const question = deck[index];

    const handleNext = () => {
        setIndex((prevIndex) => {
            const nextIndex = prevIndex + 1;
            if (nextIndex >= deck.length) {
                // 덱 소진 → 다시 섞어서 처음부터
                setDeck(freshDeck(deck[prevIndex]));
                return 0;
            }
            return nextIndex;
        });
    };

    return (
        <AppLayout>
            <Head title="순한맛 이미지게임" />

            <div className="flex flex-1 flex-col px-4 py-5">
                {/* 상단: 뒤로가기 + 타이틀 */}
                <div className="mb-4 flex items-center gap-2">
                    <Link
                        href="/games"
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
                        <span className="text-xl">🍯</span>
                        <h1 className="text-lg font-extrabold text-secondary-700">
                            순한맛 이미지게임
                        </h1>
                    </div>
                </div>

                {/* 플레이 안내 + 질문 카드 (중앙 정렬, 안내는 질문 바로 위에 붙임) */}
                <div className="flex flex-col items-center gap-4">
                    <div className="w-full rounded-2xl border border-amber-200 bg-amber-50/60 p-3.5">
                        <p className="text-xs font-medium leading-relaxed text-secondary-500">
                            <span className="font-bold text-amber-600">💡 Tip</span>
                            <br />
                            고민하지 말고 <span className="font-bold">직감대로 빠르게</span> 골라주세요!
                            순간 느낌으로 고른 것뿐이니까, 지목받아도 오해하지 말고
                            가볍게 즐기면 돼요. 😊
                        </p>
                    </div>

                    <div className="relative w-full rounded-3xl border-2 border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 p-8 text-center shadow-sm">
                        <span className="mb-4 inline-flex items-center gap-1 rounded-full bg-white/70 px-3 py-1 text-xs font-bold text-amber-600 shadow-inner">
                            🌶️ 순한맛
                        </span>
                        <p className="text-xl font-extrabold leading-relaxed text-secondary-800">
                            {question}
                        </p>
                    </div>

                    <p className="text-sm font-medium text-secondary-400">
                        질문에 어울리는 <span className="font-bold text-primary">이성</span>을 다같이 가리켜 보세요! 👉
                    </p>
                </div>

                {/* 다음 질문 버튼 */}
                <button
                    type="button"
                    onClick={handleNext}
                    className="mt-4 w-full rounded-2xl bg-primary py-4 text-base font-extrabold text-white shadow-md transition active:scale-[0.98] hover:bg-primary-600"
                >
                    다음 질문 🔀
                </button>
            </div>
        </AppLayout>
    );
}
