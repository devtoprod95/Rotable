import { Head } from "@inertiajs/react";
import { useState } from "react";
import AppLayout from "@/Layouts/AppLayout";

/**
 * 질문카드 화면 (1:1 소개팅 전용).
 * - 대화가 막힐 때 꺼내 쓸 수 있는 질문을 카테고리별 카드로 보여준다.
 * - 카테고리 칩(줄바꿈 배치)으로 필터링. 카드는 읽기 전용.
 */

/** @type {{ key: string, label: string, emoji: string, tone: string, questions: string[] }[]} */
const CATEGORIES = [
    {
        key: "ice",
        label: "가볍게 시작",
        emoji: "☕",
        tone: "from-amber-50 to-orange-50 border-amber-200",
        questions: [
            "오늘 여기까지 오는 길은 어땠어요?",
            "요즘 가장 자주 하는 일은 뭐예요?",
            "하루 중 제일 좋아하는 시간대가 언제예요?",
            "최근에 가장 잘했다 싶은 소비가 뭐예요?",
            "주말엔 보통 어떻게 보내세요?",
            "아침형 인간이세요, 저녁형 인간이세요?",
        ],
    },
    {
        key: "drink",
        label: "술 & 음식",
        emoji: "🍷",
        tone: "from-rose-50 to-red-50 border-rose-200",
        questions: [
            "주량이 어떻게 되세요?",
            "달에 몇 번 정도 술약속 있으세요?",
            "제일 좋아하는 술 종류는 뭐예요?",
            "술 마시면 어떻게 변하는 편이에요?",
            "최애 음식이랑 절대 못 먹는 음식은요?",
            "맛집 찾아다니는 편이세요, 아무거나 잘 드세요?",
            "해장은 보통 뭘로 하세요?",
        ],
    },
    {
        key: "hobby",
        label: "취미 & 일상",
        emoji: "🎬",
        tone: "from-sky-50 to-indigo-50 border-sky-200",
        questions: [
            "쉬는 날 꼭 하는 취미가 있어요?",
            "요즘 푹 빠져 있는 영화나 드라마, 유튜브 있어요?",
            "집순이/집돌이 vs 밖순이/밖돌이, 어느 쪽이세요?",
            "운동은 좋아하세요? 즐겨 하는 운동이 있어요?",
            "여행 가면 계획형이세요, 즉흥형이세요?",
            "다시 가보고 싶은 여행지가 있다면요?",
            "반려동물 좋아하세요?",
        ],
    },
    {
        key: "work",
        label: "일 & 가치관",
        emoji: "💼",
        tone: "from-emerald-50 to-teal-50 border-emerald-200",
        questions: [
            "지금 하시는 일은 어떤 점이 제일 좋아요?",
            "일과 삶의 균형, 어느 쪽에 더 무게를 두세요?",
            "5년 뒤 나는 어떤 모습이면 좋겠어요?",
            "스트레스 받을 땐 어떻게 푸세요?",
            "살면서 가장 중요하게 생각하는 가치가 뭐예요?",
            "돈 vs 시간, 하나만 고른다면요?",
        ],
    },
    {
        key: "love",
        label: "연애관",
        emoji: "💘",
        tone: "from-fuchsia-50 to-pink-50 border-fuchsia-200",
        questions: [
            "이상형이 어떻게 되세요?",
            "연애할 때 가장 중요하게 보는 건 뭐예요?",
            "연락은 자주 하는 편이세요?",
            "데이트는 어떤 스타일이 좋아요? (맛집, 여행, 집데이트 등)",
            "싸웠을 때 바로 풀어야 해요, 시간이 필요해요?",
            "연애할 때 절대 양보 못 하는 게 있다면요?",
            "첫인상이랑 지금 느낌, 어떻게 달라졌어요?",
            "결혼은 언제쯤 하고 싶으세요?",
        ],
    },
    {
        key: "fun",
        label: "TMI & 밸런스",
        emoji: "🎲",
        tone: "from-violet-50 to-purple-50 border-violet-200",
        questions: [
            "MBTI 맞혀볼까요? 본인이 생각하는 본인 성향은요?",
            "나만 아는 특이한 습관이 있다면요?",
            "친구들이 말하는 나의 첫인상은 어때요?",
            "무인도에 하나만 가져간다면 뭘 가져가요?",
            "평생 여름 vs 평생 겨울, 어느 쪽이에요?",
            "부먹 vs 찍먹, 민초 vs 반민초?",
            "로또 1등 되면 제일 먼저 뭐 하고 싶어요?",
        ],
    },
];

const ALL_KEY = "all";

/** 질문 한 장 */
function QuestionCard({ text, index, tone, emoji }) {
    return (
        <div
            className={
                "flex w-full items-center gap-3 rounded-2xl border-2 bg-gradient-to-br p-4 shadow-sm " +
                tone
            }
        >
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/80 text-lg shadow-inner">
                {emoji}
            </span>
            <span className="flex-1 text-sm font-bold leading-snug text-secondary-700">
                {text}
            </span>
            <span className="text-[10px] font-black text-secondary-300">
                {String(index + 1).padStart(2, "0")}
            </span>
        </div>
    );
}

export default function Questions() {
    const [active, setActive] = useState(ALL_KEY);

    const visible =
        active === ALL_KEY
            ? CATEGORIES
            : CATEGORIES.filter((c) => c.key === active);

    return (
        <AppLayout>
            <Head title="질문카드" />

            <div className="flex flex-1 flex-col gap-4 px-4 py-5">
                <div className="flex flex-col gap-1">
                    <h1 className="text-xl font-extrabold text-secondary-700">
                        💬 질문카드
                    </h1>
                    <p className="text-sm font-medium text-secondary-400">
                        대화가 막힐 땐 마음에 드는 질문을 골라 물어보세요.
                    </p>
                </div>

                {/* 카테고리 칩 */}
                <div className="flex flex-wrap gap-2">
                    {[{ key: ALL_KEY, label: "전체", emoji: "✨" }, ...CATEGORIES].map(
                        (c) => (
                            <button
                                key={c.key}
                                type="button"
                                onClick={() => setActive(c.key)}
                                className={
                                    "shrink-0 rounded-full border px-3 py-1.5 text-xs font-bold transition active:scale-95 " +
                                    (active === c.key
                                        ? "border-primary bg-primary text-white"
                                        : "border-secondary-200 bg-white text-secondary-500")
                                }
                            >
                                {c.emoji} {c.label}
                            </button>
                        ),
                    )}
                </div>

                {visible.map((cat) => (
                    <section key={cat.key} className="flex flex-col gap-2.5">
                        <h2 className="text-sm font-extrabold text-secondary-600">
                            {cat.emoji} {cat.label}
                        </h2>
                        {cat.questions.map((q, i) => {
                            return (
                                <QuestionCard
                                    key={`${cat.key}-${i}`}
                                    text={q}
                                    index={i}
                                    tone={cat.tone}
                                    emoji={cat.emoji}
                                />
                            );
                        })}
                    </section>
                ))}
            </div>
        </AppLayout>
    );
}
