import { Head, router } from "@inertiajs/react";
import PublicLayout from "@/Layouts/PublicLayout";

/**
 * 공개(비로그인) 게임 목록.
 * - 카드를 누르면 /public/games/{slug} 로 이동한다.
 */

/** @typedef {{ slug: string, title: string, emoji: string, desc: string, spice: number, tone: string }} GameItem */

/** @type {GameItem[]} */
const GAMES = [
    {
        slug: "poll",
        title: "Yes or No",
        emoji: "🙋",
        desc: "지금 이 게임에 접속한 사람끼리 익명으로 예/아니오 투표! 결과가 실시간으로 집계돼요.",
        spice: 2,
        tone: "from-violet-50 to-fuchsia-50 border-violet-200",
    },
    {
        slug: "image-mild",
        title: "순한맛 이미지게임",
        emoji: "🍯",
        desc: '"~한 사람?" 질문에 어울리는 사람을 다같이 지목! 가볍게 첫인상 몸풀기.',
        spice: 1,
        tone: "from-amber-50 to-orange-50 border-amber-200",
    },
    {
        slug: "image-spicy",
        title: "매운맛 이미지게임",
        emoji: "🌶️",
        desc: "조금 더 아찔하고 솔직한 질문으로 분위기를 확 달구는 지목 게임.",
        spice: 3,
        tone: "from-rose-50 to-red-50 border-rose-200",
    },
    {
        slug: "balance",
        title: "밸런스 게임",
        emoji: "⚖️",
        desc: "A vs B 둘 중 끌리는 쪽을 엄지로! 서로의 연애관·취향을 알아봐요.",
        spice: 2,
        tone: "from-sky-50 to-indigo-50 border-sky-200",
    },
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
    return (
        <button
            type="button"
            onClick={() => router.visit(`/public/games/${game.slug}`)}
            className={
                "group relative flex w-full items-center gap-4 overflow-hidden rounded-3xl border-2 bg-gradient-to-br p-4 text-left shadow-sm transition active:scale-[0.98] hover:-translate-y-0.5 hover:shadow-lg " +
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
                </span>
                <span className="text-xs font-medium leading-relaxed text-secondary-500">
                    {game.desc}
                </span>
                <SpiceLevel level={game.spice} />
            </span>
        </button>
    );
}

export default function Games() {
    return (
        <PublicLayout>
            <Head title="공개 게임" />

            <div className="flex flex-1 flex-col gap-4 px-4 py-5">
                <div className="flex flex-col gap-1">
                    <h1 className="text-xl font-extrabold text-secondary-700">
                        🎮 게임하기
                    </h1>
                </div>

                {/* 벌칙 안내 */}
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

                <div className="flex flex-col gap-3">
                    {GAMES.map((game, index) => (
                        <GameCard key={game.slug} game={game} index={index} />
                    ))}
                </div>
            </div>
        </PublicLayout>
    );
}
