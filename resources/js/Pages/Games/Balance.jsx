import { Head, Link } from "@inertiajs/react";
import { useState } from "react";
import AppLayout from "@/Layouts/AppLayout";

/**
 * 밸런스 게임.
 *
 * "A vs B" 둘 중 하나를 고르는 게임. 랜덤으로 한 쌍씩 나온다.
 * 테이블에서 각자 손을 들어 어느 쪽이 많은지 다같이 확인하며 즐긴다.
 * (서버 왕복 없이 프론트 질문 풀에서 덱 셔플 방식으로 출제)
 */

/** @typedef {{ a: string, b: string }} BalancePair */

/** @type {BalancePair[]} 밸런스 질문 풀 */
/** @type {{ a: string, b: string }[]} 연애 밸런스 게임 */
const PAIRS = [
    // 순한맛: 취향과 첫인상
    { a: "얼굴이 내 이상형인 사람", b: "내가 좋아하는 얼굴상은 아닌데 묘하게 끌리는 사람" },
    { a: "나를 더 좋아해 주는 사람", b: "내가 더 좋아하는 사람" },
    { a: "첫눈에 불꽃 튀는 연애", b: "친구처럼 편하다가 시작되는 연애" },
    { a: "매일 만나도 안 질리는 연애", b: "자주 못 만나도 만날 때마다 설레는 연애" },
    { a: "말 안 해도 내 마음을 알아채 주는 애인", b: "솔직하게 말하면 확실하게 맞춰 주는 애인" },
    { a: "데이트 계획을 완벽하게 짜는 애인", b: "매번 예상 밖의 재미를 주는 애인" },
    { a: "연락은 짧아도 바로바로 답하는 애인", b: "답장은 좀 느려도 늘 길고 다정하게 보내는 애인" },
    { a: "친구처럼 장난치는 연애", b: "서로 존댓말도 쓸 만큼 설레는 연애" },
    { a: "내 취향을 완벽하게 아는 애인", b: "매번 새로운 취향을 알려주는 애인" },
    { a: "내 친구들이 다 좋아하는 애인", b: "내가 볼 때만 매력 넘치는 애인" },

    // 중간맛: 현실적인 연애와 가치관
    { a: "매일 30분씩 통화하기", b: "일주일에 한 번 3시간 통화하기" },
    { a: "이성 친구가 많아도 나를 늘 안심시켜 주는 애인", b: "이성 친구는 거의 없지만 표현에는 서툰 애인" },
    { a: "기념일마다 제대로 챙기는 애인", b: "평소에 잘해 주고 기념일은 소소하게 보내는 애인" },
    { a: "싸우면 그날 무조건 화해하기", b: "감정이 가라앉을 때까지 기다렸다 화해하기" },
    { a: "내 모든 친구와 친해지는 애인", b: "내 친구들과는 적당히 거리 두는 애인" },
    { a: "데이트 비용은 무조건 반반", b: "형편에 맞춰 번갈아 내기" },
    { a: "연애하는 걸 SNS에 자주 올리기", b: "연애 사실만 공개하고 사진은 안 올리기" },
    { a: "취미와 관심사가 완전히 같은 애인", b: "취미는 다르지만 서로의 세계를 존중하는 애인" },
    { a: "매일 붙어 다니며 뜨겁게 보내는 연애", b: "자주 못 만나도 각자 시간을 충분히 갖는 연애" },
    { a: "나와 성격은 정반대라 끌리는 사람", b: "나와 성격이 비슷해서 편안한 사람" },

    // 살짝 매운맛: 설렘과 밀당
    { a: "먼저 손잡아 주는 애인", b: "손잡고 싶게 만드는 애인" },
    { a: "사람들 앞에서 대놓고 애정 표현", b: "둘만 있을 때 과감한 애정 표현" },
    { a: "하루 종일 장난치다가 갑자기 설레게 하는 애인", b: "평소엔 다정하다가 가끔 치명적으로 변하는 애인" },
    { a: "내가 먼저 키스하기", b: "상대가 먼저 키스하기" },
    { a: "말로 설레게 하는 애인", b: "행동으로 설레게 하는 애인" },
    { a: "질투 나면 솔직하게 말하는 애인", b: "질투 나도 티 안 내는 애인" },
    { a: "연애 초반부터 직진하는 사람", b: "밀당하다가 확신이 들면 직진하는 사람" },
    { a: "데이트 내내 눈을 못 뗄 만큼 잘생기고 예쁜 사람", b: "대화할수록 점점 더 매력적으로 보이는 사람" },
    { a: "첫 데이트부터 손잡기", b: "세 번째 데이트까지 손도 안 잡기" },
    { a: "편안하지만 설렘은 잔잔한 사람", b: "설레지만 아직 서로를 알아가는 사람" },

    // 매운맛: 도발적인 선택
    { a: "내가 좋아하는 걸 티 내는 사람", b: "좋아하는지 헷갈리게 만드는 사람" },
    { a: "낮에는 장난꾸러기, 둘만 있으면 다정한 애인", b: "낮에는 무심한데 둘만 있으면 적극적인 애인" },
    { a: "키스하기 전에 눈을 오래 마주치는 사람", b: "아무 말 없이 자연스럽게 다가오는 사람" },
    { a: "밖에서는 손만 잡는 애인", b: "둘만 있으면 먼저 꼭 안아 주는 애인" },
    { a: "내가 먼저 유혹하기", b: "상대가 나를 유혹하기" },
    { a: "친구들이 다 아는 공개 연애", b: "우리 둘만 아는 비밀 연애" },
    { a: "나만 보면 무장해제되는 사람", b: "나를 만날 때마다 긴장하게 만드는 사람" },
    { a: "한 번의 강렬한 키스로 시작하는 연애", b: "오래 쌓인 긴장 끝에 시작하는 연애" },
    { a: "나를 볼 때마다 귀엽다고 하는 애인", b: "나를 볼 때마다 섹시하다고 하는 애인" },
    { a: "지금 당장 끌리는 사람과 가볍게 데이트하기", b: "서로 마음을 확인한 사람과 밤늦게까지 깊은 대화하기" },
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
 * 덱을 새로 섞되, 첫 쌍이 직전 마지막 쌍과 같으면 한 번 더 섞어
 * 리셋 직후 같은 질문이 연속으로 나오는 것을 피한다.
 */
function freshDeck(prevLast) {
    let deck = shuffle(PAIRS);
    if (PAIRS.length > 1) {
        while (deck[0] === prevLast) {
            deck = shuffle(PAIRS);
        }
    }
    return deck;
}

export default function Balance() {
    // 쌍을 한 번씩 모두 소진할 때까지 중복 없이 뽑고, 다 쓰면 다시 섞는다(리셋).
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
        <AppLayout>
            <Head title="밸런스 게임" />

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
                        <span className="text-xl">⚖️</span>
                        <h1 className="text-lg font-extrabold text-secondary-700">
                            밸런스 게임
                        </h1>
                    </div>
                </div>

                {/* 안내 + 선택지 (중앙 정렬, 안내는 선택지 바로 위에 붙임) */}
                <div className="flex flex-col items-center gap-4">
                    <div className="w-full rounded-2xl border border-sky-200 bg-sky-50/60 p-3.5">
                        <p className="text-xs font-medium leading-relaxed text-secondary-500">
                            <span className="font-bold text-sky-600">💡 Tip</span>
                            <br />
                            정답은 없어요! 끌리는 쪽이 <span className="font-bold">위면 엄지 업 👍</span>,
                            <span className="font-bold"> 아래면 엄지 다운 👎</span>
                        </p>
                    </div>

                    <div className="flex w-full flex-col gap-2">
                        {/* A (위 = 👍 업) */}
                        <div className="rounded-3xl border-2 border-secondary-200 bg-white p-6 text-center shadow-sm">
                            <span className="mb-2 inline-flex items-center gap-1 rounded-full bg-sky-500 px-2.5 py-0.5 text-xs font-extrabold text-white shadow">
                                👍 업
                            </span>
                            <p className="text-lg font-extrabold leading-relaxed text-secondary-800">
                                {pair.a}
                            </p>
                        </div>

                        {/* VS */}
                        <div className="flex items-center justify-center">
                            <span className="flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-primary text-xs font-black text-white shadow-md">
                                VS
                            </span>
                        </div>

                        {/* B (아래 = 👎 다운) */}
                        <div className="rounded-3xl border-2 border-secondary-200 bg-white p-6 text-center shadow-sm">
                            <span className="mb-2 inline-flex items-center gap-1 rounded-full bg-indigo-500 px-2.5 py-0.5 text-xs font-extrabold text-white shadow">
                                👎 다운
                            </span>
                            <p className="text-lg font-extrabold leading-relaxed text-secondary-800">
                                {pair.b}
                            </p>
                        </div>
                    </div>
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
