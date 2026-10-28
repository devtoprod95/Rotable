import { Head, router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import PublicLayout from "@/Layouts/PublicLayout";
import PenaltyRouletteModal from "@/Components/Public/PenaltyRouletteModal";
import { getCurrentPenalty, PENALTY_EVENT } from "@/publicPenalty";

/**
 * 공개(비로그인) 게임 목록.
 * - 상단 탭으로 소개팅 / 술게임 분류를 전환한다.
 * - 카드를 누르면 /public/games/{slug} 로 이동한다.
 * - 술게임(🔞)은 자극적인 성인 콘텐츠로, 진입 시 성인 확인을 거친다.
 */

/**
 * @typedef {Object} GameItem
 * @property {string} slug
 * @property {"dating"|"drink"} category
 * @property {string} title
 * @property {string} players 적정 인원 표기 (예: "2인 이상")
 * @property {string} emoji
 * @property {string} desc
 * @property {number} spice   매운맛 1~3 (술게임은 모두 3=19금)
 * @property {string} tone    카드 배경 그라데이션/보더 클래스
 * @property {boolean} [ready] false 면 "준비중" (클릭 불가)
 */

/** @type {GameItem[]} */
const GAMES = [
    // ===== 소개팅 =====
    {
        slug: "poll",
        category: "dating",
        title: "Yes or No",
        players: "2인 이상",
        emoji: "🙋",
        desc: "지금 이 게임에 접속한 사람끼리 익명으로 예/아니오 투표! 결과가 실시간으로 집계돼요.",
        spice: 2,
        tone: "from-violet-50 to-fuchsia-50 border-violet-200",
        ready: true,
    },
    {
        slug: "image-mild",
        category: "dating",
        title: "순한맛 이미지게임",
        players: "3인 이상",
        emoji: "🍯",
        desc: '"~한 사람?" 질문에 어울리는 사람을 다같이 지목! 가볍게 첫인상 몸풀기.',
        spice: 1,
        tone: "from-amber-50 to-orange-50 border-amber-200",
        ready: true,
    },
    {
        slug: "image-spicy",
        category: "dating",
        title: "매운맛 이미지게임",
        players: "3인 이상",
        emoji: "🌶️",
        desc: "조금 더 아찔하고 솔직한 질문으로 분위기를 확 달구는 지목 게임.",
        spice: 3,
        tone: "from-rose-50 to-red-50 border-rose-200",
        ready: true,
    },
    {
        slug: "balance",
        category: "dating",
        title: "밸런스 게임",
        players: "2인 이상",
        emoji: "⚖️",
        desc: "A vs B 둘 중 끌리는 쪽을 엄지로! 서로의 연애관·취향을 알아봐요.",
        spice: 2,
        tone: "from-sky-50 to-indigo-50 border-sky-200",
        ready: true,
    },

    // ===== 술게임 (🔞 성인) =====
    {
        slug: "drink-balance",
        category: "drink",
        title: "밸런스 게임 🔞",
        players: "2인 이상",
        emoji: "⚖️",
        desc: "A vs B 중 끌리는 쪽을 엄지로! 수위를 확 올린 19금 밸런스 게임.",
        spice: 3,
        tone: "from-rose-100 to-red-100 border-rose-300",
        ready: true,
    },
    {
        slug: "kings-game",
        category: "drink",
        title: "왕게임 🔞",
        players: "3인 이상",
        emoji: "👑",
        desc: "번호를 뽑아 왕이 되면 미션을 내려요! \"○번과 ○번, ~하기\" 자극적인 명령 게임.",
        spice: 3,
        tone: "from-amber-100 to-yellow-100 border-amber-300",
        ready: true,
    },
    {
        slug: "drink-image",
        category: "drink",
        title: "이미지게임 (취했을 때) 🔞",
        players: "3인 이상",
        emoji: "🥵",
        desc: "수위 확 올린 \"~할 것 같은 사람?\" 지목 게임. 지목 1위가 벌주 한 잔!",
        spice: 3,
        tone: "from-fuchsia-100 to-pink-100 border-fuchsia-300",
        ready: true,
    },
    {
        slug: "penalty-roulette",
        category: "drink",
        title: "랜덤 벌칙 룰렛 🔞",
        players: "2인 이상",
        emoji: "🎯",
        desc: "룰렛을 돌려 당첨된 벌칙 수행! 원샷·러브샷·애교·흑기사… 무엇이 걸릴까?",
        spice: 3,
        tone: "from-indigo-100 to-purple-100 border-indigo-300",
        ready: true,
    },
    {
        slug: "tension-talk",
        category: "drink",
        title: "텐션 토크 🔞",
        players: "2인 이상",
        emoji: "💬",
        desc: "수위 높은 질문에 솔직하게! 답 못하면 벌주. (준비중)",
        spice: 3,
        tone: "from-rose-100 to-orange-100 border-rose-300",
        ready: false,
    },
    {
        slug: "truth-or-dare",
        category: "drink",
        title: "진실 혹은 벌칙 🔞",
        players: "2인 이상",
        emoji: "🎭",
        desc: "진실 or 도전 중 택1! 못 하면 원샷. (준비중)",
        spice: 3,
        tone: "from-teal-100 to-emerald-100 border-teal-300",
        ready: false,
    },
];

/** @type {{ key: "dating"|"drink", label: string, emoji: string }[]} */
const TABS = [
    { key: "dating", label: "소개팅", emoji: "💘" },
    { key: "drink", label: "술게임", emoji: "🍻" },
];

/** 매운맛 정도를 고추 이모지로 표현 (1~3) */
function SpiceLevel({ level }) {
    return (
        <span className="flex items-center gap-0.5" title={`매운맛 ${level}단계`}>
            {[1, 2, 3].map((n) => (
                <span
                    key={n}
                    className={
                        "text-xs leading-none " +
                        (n <= level ? "opacity-100" : "opacity-20 grayscale")
                    }
                >
                    🌶️
                </span>
            ))}
        </span>
    );
}

function GameCard({ game, index }) {
    const disabled = game.ready === false;

    return (
        <button
            type="button"
            disabled={disabled}
            onClick={() => {
                if (disabled) {
                    return;
                }
                router.visit(`/public/games/${game.slug}`);
            }}
            className={
                "group relative flex w-full items-center gap-4 overflow-hidden rounded-3xl border-2 bg-gradient-to-br p-4 text-left shadow-sm transition " +
                (disabled
                    ? "cursor-not-allowed opacity-60 "
                    : "active:scale-[0.98] hover:-translate-y-0.5 hover:shadow-lg ") +
                game.tone
            }
        >
            <span
                aria-hidden="true"
                className="pointer-events-none absolute -bottom-4 -right-3 text-7xl opacity-10 transition group-hover:scale-110"
            >
                {game.emoji}
            </span>

            <span className="relative z-10 flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/80 text-3xl shadow-inner">
                {game.emoji}
            </span>

            <span className="relative z-10 flex min-w-0 flex-1 flex-col gap-1.5">
                <span className="flex items-center gap-2">
                    <span className="rounded-md bg-white/70 px-1.5 py-0.5 text-[10px] font-black text-secondary-400 shadow-inner">
                        {String(index + 1).padStart(2, "0")}
                    </span>
                    <span className="truncate text-lg font-extrabold leading-tight text-secondary-800">
                        {game.title}
                    </span>
                    {disabled && (
                        <span className="shrink-0 rounded-full bg-secondary-200 px-2 py-0.5 text-[10px] font-bold text-secondary-500">
                            준비중
                        </span>
                    )}
                </span>
                <span className="text-xs font-medium leading-relaxed text-secondary-500">
                    {game.desc}
                </span>
                <span className="flex items-center gap-2">
                    <SpiceLevel level={game.spice} />
                    {game.players && (
                        <span className="inline-flex items-center gap-0.5 rounded-full bg-white/70 px-2 py-0.5 text-[10px] font-bold text-secondary-500 shadow-inner">
                            👥 {game.players}
                        </span>
                    )}
                </span>
            </span>
        </button>
    );
}

export default function Games() {
    // 탭 상태는 로컬 state 가 아니라 Inertia 가 관리하는 현재 URL 쿼리(?tab=)에서 파생한다.
    // → 게임 상세로 갔다가 뒤로가기로 목록에 돌아오면 Inertia 가 ?tab=drink URL 로
    //   복원하므로 보던 탭이 그대로 유지된다. (로컬 state + replaceState 는 복원과 안 맞음)
    const { url } = usePage();
    const tab = (() => {
        const q = url.split("?")[1] ?? "";
        return new URLSearchParams(q).get("tab") === "drink" ? "drink" : "dating";
    })();

    // 탭 전환: Inertia 라우팅으로 URL 쿼리를 바꾼다(히스토리에 반영).
    // 같은 페이지 재방문이므로 상태/스크롤은 유지하고, 히스토리는 교체한다.
    const changeTab = (key) => {
        if (key === tab) {
            return;
        }
        router.get(
            "/public/games",
            key === "dating" ? {} : { tab: key },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const visibleGames = GAMES.filter((game) => game.category === tab);
    const isDrink = tab === "drink";

    // 공용 '현재 벌칙' — 상단 룰렛으로 정하면 모든 술게임에서 공유된다.
    const [showPenalty, setShowPenalty] = useState(false);
    const [penalty, setPenalty] = useState(null);

    useEffect(() => {
        setPenalty(getCurrentPenalty());
        const onChange = (e) => setPenalty(e.detail ?? getCurrentPenalty());
        window.addEventListener(PENALTY_EVENT, onChange);
        return () => window.removeEventListener(PENALTY_EVENT, onChange);
    }, []);

    return (
        <PublicLayout>
            <Head title="공개 게임" />

            <div className="flex flex-1 flex-col gap-4 px-4 py-5">
                <div className="flex flex-col gap-1">
                    <h1 className="text-xl font-extrabold text-secondary-700">
                        🎮 게임하기
                    </h1>
                </div>

                {/* 분류 탭 */}
                <div className="flex gap-2 rounded-2xl bg-secondary-100 p-1">
                    {TABS.map((t) => {
                        const active = tab === t.key;
                        return (
                            <button
                                key={t.key}
                                type="button"
                                onClick={() => changeTab(t.key)}
                                className={
                                    "flex flex-1 items-center justify-center gap-1.5 rounded-xl py-2.5 text-sm font-extrabold transition active:scale-[0.98] " +
                                    (active
                                        ? "bg-white text-primary shadow-sm"
                                        : "text-secondary-400")
                                }
                            >
                                <span>{t.emoji}</span>
                                {t.label}
                            </button>
                        );
                    })}
                </div>

                {/* 분류별 안내 (소개팅 탭에서만) */}
                {!isDrink && (
                    <div className="rounded-2xl border border-primary/15 bg-primary/5 p-3.5">
                        <div className="flex items-start gap-2">
                            <span className="text-base leading-none">🍷</span>
                            <p className="text-xs font-medium leading-relaxed text-secondary-500">
                                <span className="font-bold text-primary">벌칙 안내</span>
                                <br />
                                벌칙은 함께하는 사람끼리 상의해서 자유롭게 정해주세요.
                                가볍게 한 잔 하셔도 좋고요! 🍷
                            </p>
                        </div>
                    </div>
                )}

                {/* 공용 벌칙 (술게임 탭 전용) — 룰렛으로 정해두면 모든 술게임에서 공유 */}
                {isDrink && (
                    <div className="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-3.5">
                        <div className="flex items-center gap-2">
                            <span className="text-lg leading-none">🎯</span>
                            <div className="min-w-0 flex-1">
                                <p className="text-[11px] font-bold text-indigo-500">
                                    이번 벌칙
                                </p>
                                {penalty ? (
                                    <p className="truncate text-sm font-extrabold text-secondary-800">
                                        {penalty.emoji} {penalty.text}
                                    </p>
                                ) : (
                                    <p className="text-sm font-medium text-secondary-400">
                                        아직 안 정했어요. 룰렛을 돌려보세요!
                                    </p>
                                )}
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={() => setShowPenalty(true)}
                            className="mt-3 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 py-3 text-sm font-extrabold text-white shadow-md transition active:scale-[0.98] hover:brightness-105"
                        >
                            🎯 {penalty ? "벌칙 다시 돌리기" : "벌칙 룰렛 돌리기"}
                        </button>
                    </div>
                )}

                <div className="flex flex-col gap-3">
                    {visibleGames.map((game, index) => (
                        <GameCard key={game.slug} game={game} index={index} />
                    ))}
                </div>
            </div>

            {showPenalty && (
                <PenaltyRouletteModal onClose={() => setShowPenalty(false)} />
            )}
        </PublicLayout>
    );
}
