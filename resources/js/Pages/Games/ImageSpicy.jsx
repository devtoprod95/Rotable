import { Head, Link } from "@inertiajs/react";
import { useState } from "react";
import AppLayout from "@/Layouts/AppLayout";

/**
 * 매운맛 이미지게임.
 *
 * 조금 더 자극적이고 아찔한 "~한 사람?" 질문이 랜덤으로 하나씩 나온다.
 * 참가자들은 질문에 가장 어울리는 이성을 손가락으로 지목하며 즐긴다.
 * (서버 왕복 없이 프론트 질문 풀에서 랜덤 출제)
 */

/** @type {string[]} 매운맛 질문 풀 */
const QUESTIONS = [
    "솔직히 지금 이 테이블에서 키스하는 상상을 해도 어색하지 않을 것 같은 사람은?",
    "애인한테 거짓말 잘 칠 것 같은 사람은?",
    "오늘 가장 먼저 손을 잡고 싶다는 생각이 들게 하는 사람은?",
    "오늘 여기서 단둘이 사라진다면 가장 궁금할 것 같은 사람은?",
    "아직 한 번도 키스 안 해봤을 것 같은 사람은?",
    "내가 꼬시면 안 넘어올 것 같은 사람은?",
    "연애할 때 스킨십을 먼저 시작할 것 같은 사람은?",
    "낮에는 순한데 밤에는 분위기가 완전히 달라질 것 같은 사람은?",
    "내일 같이 만나서 해장하고 싶은 사람은?",
    "가벼운 플러팅인 줄 알았는데 진심으로 빠져들게 할 것 같은 사람은?",
    "일주일동안 안 씻어본 적 있게 생긴 사람은?",
    "지금 같이 나가서 단 둘이 얘기하고 싶은 사람은?",
    "내 이상형이랑 가장 가까운 사람은?",
    "첫인상 제일 섹시하게 생긴 사람은?",
    "단둘이 밤 산책을 하다가 분위기가 묘해질 것 같은 사람은?",
    "상대가 먼저 다가오게 만드는 기술이 가장 좋을 것 같은 사람은?",
    "첫 키스를 가장 빨리 해봤을 것 같은 사람은?",
    "오늘 큰 기대(?)를 하고 온 것 같은 사람은?",
    "성적 취향 가장 독특할 것 같은 사람은?",
    "차에 살짝 치었을 때 많은 합의금을 뜯어낼 거 같은 사람은?",
    "단둘이 있을 때 예상보다 직진할 것 같은 사람은?",
    "약속 없으면 일주일동안 절대 안 씻을 것 같은 사람 은?",
    "사귀기전에 키스 먼저 하고 싶다는 생각이 들게 할 것 같은 사람은?",
    "입냄새가 유독 심할 것 같은 사람은?",
    "이 자리에 좋아하는 사람이 있을 것 같은 사람은?",
    "몸매가 가장 섹시할 것 같은 사람은?",
    "성욕이 가장 강할 것 같은 사람은?",
    "학창시절 일찐 출신 일 것 같은 사람은?",
    "엄마한테 방청소랑 빨래 시킬 것 같은 사람은?",
    "이 자리에서 서로 호감만 확인되면 바로 데이트하고 싶은 사람은?",
    "몰래 더 많이 쳐다봤던 사람은?"
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

export default function ImageSpicy() {
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
            <Head title="매운맛 이미지게임" />

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
                        <span className="text-xl">🌶️</span>
                        <h1 className="text-lg font-extrabold text-secondary-700">
                            매운맛 이미지게임
                        </h1>
                    </div>
                </div>

                {/* 플레이 안내 + 질문 카드 (중앙 정렬, 안내는 질문 바로 위에 붙임) */}
                <div className="flex flex-1 flex-col items-center justify-center gap-4">
                    <div className="w-full rounded-2xl border border-rose-200 bg-rose-50/60 p-3.5">
                        <p className="text-xs font-medium leading-relaxed text-secondary-500">
                            <span className="font-bold text-rose-600">💡 Tip</span>
                            <br />
                            고민하지 말고 <span className="font-bold">직감대로 빠르게</span> 골라주세요!
                            순간 느낌으로 고른 것뿐이니까, 지목받아도 오해하지 말고
                            가볍게 즐기면 돼요. 😊
                        </p>
                    </div>

                    <div className="relative w-full rounded-3xl border-2 border-rose-200 bg-gradient-to-br from-rose-50 to-red-50 p-8 text-center shadow-sm">
                        <span className="mb-4 inline-flex items-center gap-1 rounded-full bg-white/70 px-3 py-1 text-xs font-bold text-rose-600 shadow-inner">
                            🌶️🌶️🌶️ 매운맛
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
