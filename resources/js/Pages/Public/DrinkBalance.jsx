import { Head, Link } from "@inertiajs/react";
import { useState } from "react";
import PublicLayout from "@/Layouts/PublicLayout";
import PenaltyBanner from "@/Components/Public/PenaltyBanner";

/**
 * 밸런스 게임 (19금) — 공개 술게임.
 *
 * 수위를 올린 "A vs B" 선택지. 끌리는 쪽이 위면 엄지 업(👍), 아래면 엄지 다운(👎).
 * 소개팅 밸런스 게임과 동일한 방식이며, 선택지만 19금 톤. 전적으로 클라이언트 로컬.
 */

/** @type {{ a: string, b: string }[]} 19금 밸런스 선택지 풀 */
const PAIRS = [
    { a: "술 취하면 과감해지는 사람", b: "술 취하면 조용해지는 사람" },
    { a: "첫날밤부터 가능", b: "최소 3번은 만나봐야" },
    { a: "스킨십은 내가 먼저", b: "상대가 먼저 해주길" },
    { a: "불 켜고", b: "불 끄고" },
    { a: "남친/여친의 전 애인 수가 많은 게 낫다", b: "적은 게 낫다" },
    { a: "하룻밤 불장난도 OK", b: "무조건 진지한 연애만" },
    { a: "키스는 격렬하게", b: "키스는 부드럽게" },
    { a: "연락 안 되면 집으로 찾아가기", b: "쿨하게 기다리기" },
    { a: "공공장소에서 손잡기 OK", b: "둘만 있을 때만" },
    { a: "모텔 vs 호텔, 모텔", b: "모텔 vs 호텔, 호텔" },
    { a: "전 애인과 재회 가능", b: "지나간 사람은 절대 안 봐" },
    { a: "바람은 한 번은 용서", b: "한 번도 절대 용서 못 함" },
    { a: "애인 핸드폰 몰래 보기 OK", b: "죽어도 안 봄" },
    { a: "하루 종일 붙어서 애정행각", b: "각자 할 거 하다 밤에만" },
    { a: "연상이 좋다", b: "연하가 좋다" },
    { a: "과감한 노출 패션 애인 OK", b: "나만 볼 수 있게 가려줬으면" },
    { a: "술 먹고 전화해서 보고싶다 고백", b: "맨정신에도 절대 티 안 냄" },
    { a: "첫 경험 상대를 기억한다", b: "기억 안 난다/비밀" },
    { a: "원나잇 경험 있음", b: "전혀 없음" },
    { a: "애인과 야한 영상 같이 보기 OK", b: "절대 못 봄" },
    { a: "스킨십 진도 빠른 게 좋다", b: "천천히 쌓아가는 게 좋다" },
    { a: "질투심 많은 애인", b: "쿨한 애인" },
    { a: "밤새 통화하다 잠들기", b: "할 말만 하고 끊기" },
    { a: "낮져밤이(낮에 지고 밤에 이기는)", b: "낮이밤져" },
    { a: "연인끼리 커플 속옷 OK", b: "오글거려서 싫다" },
    { a: "취향 맞는 사람과 가볍게", b: "안 맞아도 사랑하면 극복" },
    { a: "체격 좋은 사람", b: "날씬한 사람" },
    { a: "진한 향수 쓰는 사람", b: "비누향 나는 사람" },
    { a: "먼저 유혹하는 편", b: "유혹당하는 편" },
    { a: "자기 전 꼭 연락", b: "굳이 매일 안 해도 됨" },
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
    let deck = shuffle(PAIRS);
    if (PAIRS.length > 1) {
        while (deck[0] === prevLast) {
            deck = shuffle(PAIRS);
        }
    }
    return deck;
}

function DrinkBalanceGame() {
    const [deck, setDeck] = useState(() => shuffle(PAIRS));
    const [index, setIndex] = useState(0);

    const pair = deck[index];

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
                    <span className="text-xl">⚖️</span>
                    <h1 className="text-lg font-extrabold text-secondary-700">
                        밸런스 게임
                    </h1>
                </div>
                <span className="ml-auto rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-500">
                    🔞 19금
                </span>
            </div>

            <PenaltyBanner />

            <div className="flex flex-col items-center gap-4">
                <div className="w-full rounded-2xl border border-rose-200 bg-rose-50/60 p-3.5">
                    <p className="text-xs font-medium leading-relaxed text-secondary-500">
                        <span className="font-bold text-rose-600">💡 Tip</span>
                        <br />
                        정답은 없어요! 끌리는 쪽이 <span className="font-bold">위면 엄지 업 👍</span>,
                        <span className="font-bold"> 아래면 엄지 다운 👎</span>
                    </p>
                </div>

                <div className="flex w-full flex-col gap-2">
                    <div className="rounded-3xl border-2 border-rose-200 bg-white p-6 text-center shadow-sm">
                        <span className="mb-2 inline-flex items-center gap-1 rounded-full bg-rose-500 px-2.5 py-0.5 text-xs font-extrabold text-white shadow">
                            👍 업
                        </span>
                        <p className="text-lg font-extrabold leading-relaxed text-secondary-800">
                            {pair.a}
                        </p>
                    </div>

                    <div className="flex items-center justify-center">
                        <span className="flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-primary text-xs font-black text-white shadow-md">
                            VS
                        </span>
                    </div>

                    <div className="rounded-3xl border-2 border-rose-200 bg-white p-6 text-center shadow-sm">
                        <span className="mb-2 inline-flex items-center gap-1 rounded-full bg-red-500 px-2.5 py-0.5 text-xs font-extrabold text-white shadow">
                            👎 다운
                        </span>
                        <p className="text-lg font-extrabold leading-relaxed text-secondary-800">
                            {pair.b}
                        </p>
                    </div>
                </div>
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

export default function DrinkBalance() {
    return (
        <PublicLayout>
            <Head title="밸런스 게임 (19금)" />
            <DrinkBalanceGame />
        </PublicLayout>
    );
}
