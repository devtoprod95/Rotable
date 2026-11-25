import { Link } from "@inertiajs/react";
import { useMemo, useState } from "react";
import {
    WORLD_CUPS,
    WORLD_CUP_CATEGORIES,
    WORLD_CUP_EMOJI,
} from "@/worldCupList";

/**
 * 이상형 월드컵 모음.
 *
 * piku.co.kr 의 월드컵 목록을 보기 좋게 모아 두고, 누르면 새 탭에서 연다.
 * (piku 가 iframe 삽입을 막아 두어 새 탭으로만 열 수 있다.)
 * 같은 테이블이 한 월드컵을 정해 각자 폰으로 해 본 뒤 결과를 비교하며 즐긴다.
 */

const ALL = "전체";

/** 새 탭으로 연다. (opener 차단) */
function openWorldCup(url) {
    window.open(url, "_blank", "noopener,noreferrer");
}

export default function WorldCupBrowser({ backHref }) {
    const [category, setCategory] = useState(ALL);
    const [keyword, setKeyword]   = useState("");

    const filtered = useMemo(() => {
        const q = keyword.trim().toLowerCase();

        return WORLD_CUPS.filter(
            (item) =>
                (category === ALL || item.category === category) &&
                (q === "" || item.title.toLowerCase().includes(q)),
        );
    }, [category, keyword]);

    /** 현재 보이는 목록 중 하나를 무작위로 열어 준다. */
    const handleRandom = () => {
        if (filtered.length === 0) {
            return;
        }
        const pick = filtered[Math.floor(Math.random() * filtered.length)];
        openWorldCup(pick.url);
    };

    return (
        <div className="flex flex-1 flex-col px-4 py-5">
            {/* 상단: 뒤로가기 + 타이틀 */}
            <div className="mb-4 flex items-center gap-2">
                <Link
                    href={backHref}
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
                    <span className="text-xl">🏆</span>
                    <h1 className="text-lg font-extrabold text-secondary-700">
                        이상형 월드컵
                    </h1>
                </div>
            </div>

            {/* 안내 */}
            <div className="mb-3 rounded-2xl border border-amber-200 bg-amber-50/60 p-3.5">
                <p className="text-xs font-medium leading-relaxed text-secondary-500">
                    <span className="font-bold text-amber-600">💡 Tip</span>
                    <br />
                    테이블에서 하나를 정해 각자 폰으로 해보고, 결과를 서로
                    비교해 보세요. 누르면 <span className="font-bold">새 탭</span>
                    으로 열려요.
                </p>
            </div>

            {/* 랜덤 뽑기 */}
            <button
                type="button"
                onClick={handleRandom}
                disabled={filtered.length === 0}
                className="mb-3 w-full rounded-2xl bg-primary py-3.5 text-base font-extrabold text-white shadow-md transition active:scale-[0.98] hover:bg-primary-600 disabled:opacity-40"
            >
                🎲 아무거나 골라줘
            </button>

            {/* 검색 */}
            <input
                type="search"
                value={keyword}
                onChange={(e) => setKeyword(e.target.value)}
                placeholder="월드컵 검색 (예: 포켓몬, 음식)"
                className="mb-3 w-full rounded-2xl border border-secondary-200 bg-white px-4 py-3 text-sm font-medium text-secondary-700 shadow-sm outline-none transition placeholder:text-secondary-300 focus:border-primary"
            />

            {/* 카테고리 칩 (가로 스크롤) */}
            <div className="-mx-4 mb-3 flex gap-2 overflow-x-auto px-4 pb-1">
                {[ALL, ...WORLD_CUP_CATEGORIES].map((name) => {
                    const active = category === name;

                    return (
                        <button
                            key={name}
                            type="button"
                            onClick={() => setCategory(name)}
                            className={
                                "shrink-0 rounded-full border px-3.5 py-1.5 text-xs font-bold transition active:scale-95 " +
                                (active
                                    ? "border-primary bg-primary text-white shadow"
                                    : "border-secondary-200 bg-white text-secondary-500")
                            }
                        >
                            {name === ALL ? "🏆 " : `${WORLD_CUP_EMOJI[name]} `}
                            {name}
                        </button>
                    );
                })}
            </div>

            <p className="mb-2 text-xs font-medium text-secondary-400">
                총 {filtered.length}개
            </p>

            {/* 목록 */}
            {filtered.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-secondary-200 bg-white p-8 text-center text-sm font-medium text-secondary-400">
                    검색 결과가 없어요.
                </div>
            ) : (
                <ul className="flex flex-col gap-2">
                    {filtered.map((item, i) => (
                        <li key={`${item.url}-${i}`}>
                            <button
                                type="button"
                                onClick={() => openWorldCup(item.url)}
                                className="flex w-full items-center gap-3 rounded-2xl border border-secondary-200 bg-white p-3.5 text-left shadow-sm transition active:scale-[0.98] hover:-translate-y-0.5 hover:shadow-md"
                            >
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-2xl">
                                    {WORLD_CUP_EMOJI[item.category]}
                                </span>
                                <span className="flex min-w-0 flex-1 flex-col gap-0.5">
                                    <span className="text-sm font-extrabold leading-snug text-secondary-800">
                                        {item.title}
                                    </span>
                                    <span className="text-[11px] font-bold text-secondary-400">
                                        {item.category}
                                    </span>
                                </span>
                                <span
                                    aria-hidden="true"
                                    className="shrink-0 text-secondary-300"
                                >
                                    ↗
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
