import { Head, Link } from "@inertiajs/react";
import { useState } from "react";
import PublicLayout from "@/Layouts/PublicLayout";
import PenaltyBanner from "@/Components/Public/PenaltyBanner";

/**
 * 왕게임 (19금) — 공개 술게임.
 *
 * 진행 방식(현장):
 * 1) 인원수를 정하고, 각자 1~N 번호를 랜덤으로 하나씩 갖는다(제비뽑기 느낌).
 * 2) 화면에서 "왕"을 한 명 뽑고, 미션을 출제한다.
 *    미션은 번호를 지목하는 형태라 누가 왕인지는 몰라도 명령은 수행된다.
 *
 * 전적으로 클라이언트 로컬. 번호 배정은 하지 않고(각자 제비뽑기) 화면은
 * "왕 번호 + 미션"만 제시한다. 미션의 {a}, {b} 는 서로 다른 임의 번호로 치환된다.
 */

/** @type {string[]} 미션 풀. {k}=왕, {a}/{b}=임의의 서로 다른 번호 */
const MISSIONS = [
    // [19금] 시각·촉각 자극 미션
    "{a}번이 {b}번의 셔츠/옷 안으로 손을 넣어 5초간 만지기",
    "{a}번이 {b}번의 허벅지 안쪽을 손가락으로 천천히 쓰다듬기",
    "{a}번이 {b}번의 귓볼을 입술로 살짝 물고 귓가에 야한 귓속말하기",
    "{a}번이 {b}번의 엉덩이를 가볍게 3회 찰싹 때리기",
    "{a}번과 {b}번, 서로 상의 안으로 손을 넣어 체온을 느끼며 5초간 안아주기",
    "{a}번이 {b}번의 목선부터 쇄골까지 입술로 천천히 쓸어내리기",

    // [19금] 키스 & 딥 스킨십
    "{a}번과 {b}번, 서로 동의하에 딥키스 5초간 진행하기",
    "{a}번이 {b}번의 쇄골이나 목덜미에 5초간 딥키스하기",
    "{a}번과 {b}번, 입에 얼음을 물고 키스로 상대방에게 옮기기",
    "{a}번이 {b}번의 아랫입술을 살짝 물었다가 떼기",
    "{a}번과 {b}번, 눈을 가린 상태에서 5초간 키스하기",
    "{a}번과 {b}번, 눈을 뜬 상태에서 5초간 키스하기",

    // [19금] 밀착 자세 & 과감한 수위
    "{a}번이 {b}번 위에 마주보고 마운트 자세로 앉아 5초간 아이컨텍하기",
    "{a}번이 {b}번 위에 마주보고 마운트 자세로 앉아 5초간 껴안기",
    "{a}번이 {b}번을 5초간 뒤에서 꽉 끌어안기",
    "{a}번과 {b}번, 5초 동안 서로의 가슴에 손을 올리고 심장 박동 느끼기",
    "{a}번이 {b}번의 허벅지 위에 앉아 5초 동안 귀에 신음소리 내기",
    "{a}번과 {b}번, 서로 바짝 밀착한 상태에서 5초 동안 끈적한 골반 댄스 추기",
    "{a}번이 {b}번의 목덜미나 쇄골에 가볍게 키스 마크(흉내만 내기 또는 진짜로) 남기기",

    // [19금] 게임성 & 미션 수행
    "{a}번이 {b}번의 몸 특정 부위에 떨어진 물건(또는 과자)을 입으로만 줍기",
    "{a}번과 {b}번, 빼빼로나 길쭉한 과자를 1cm 남길 때까지 끊지 않고 먹기",
    "{a}번이 {b}번의 쇄골이나 목에 얼음을 문지르고, {b}번은 소리 내지 않고 참기",
    "{a}번과 {b}번, 서로 벌칙으로 의류 한 점 탈의하기 (동의하에 진행)",
    "{a}번이 {b}번의 손가락 하나를 입에 넣고 3초간 빨기",
    "{a}번과 {b}번, 서로 동의하면 가장 원하는 파격적인 스킨십 1가지 상대에게 요청해서 실행하기"
];

/** 1..n 중 서로 다른 번호 k개를 뽑는다. */
function pickDistinct(n, k) {
    const pool = Array.from({ length: n }, (_, i) => i + 1);
    for (let i = pool.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [pool[i], pool[j]] = [pool[j], pool[i]];
    }
    return pool.slice(0, Math.min(k, n));
}

function KingsGameInner() {
    const [count, setCount] = useState(4);
    const [king, setKing] = useState(null);
    const [mission, setMission] = useState("");

    const draw = () => {
        const n = Math.max(2, Math.min(20, Number(count) || 2));
        // 왕 1명 + 미션에 쓸 번호 2명 (가능하면 서로 다르게)
        const [kingNo, a, b] = pickDistinct(n, 3);
        const picked = MISSIONS[Math.floor(Math.random() * MISSIONS.length)];
        const text = picked
            .split("{k}").join(String(kingNo))
            .split("{a}").join(String(a ?? kingNo))
            .split("{b}").join(String(b ?? a ?? kingNo));
        setKing(kingNo);
        setMission(text);
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
                    <span className="text-xl">👑</span>
                    <h1 className="text-lg font-extrabold text-secondary-700">왕게임</h1>
                </div>
                <span className="ml-auto rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-600">
                    🔞 19금
                </span>
            </div>

            <PenaltyBanner />

            <div className="flex flex-col items-center gap-4">
                <div className="w-full rounded-2xl border border-amber-200 bg-amber-50/60 p-3.5">
                    <p className="text-xs font-medium leading-relaxed text-secondary-500">
                        <span className="font-bold text-amber-600">👑 룰</span>
                        <br />
                        각자 <span className="font-bold">1~N번</span> 번호를 하나씩 정하세요(가위바위보·제비뽑기 등).
                        "왕 뽑기"를 누르면 <span className="font-bold">미션</span>이 나와요. 지목된 번호는 무조건 수행! 😈
                    </p>
                </div>

                {/* 인원수 설정 */}
                <div className="flex w-full items-center justify-center gap-3 rounded-2xl border border-secondary-200 bg-white p-3">
                    <span className="text-sm font-bold text-secondary-600">인원수</span>
                    <button
                        type="button"
                        onClick={() => setCount((c) => Math.max(2, Number(c) - 1))}
                        className="flex h-9 w-9 items-center justify-center rounded-full border border-secondary-200 text-lg font-extrabold text-secondary-500 active:scale-95"
                    >
                        −
                    </button>
                    <span className="w-10 text-center text-xl font-extrabold text-primary">
                        {count}
                    </span>
                    <button
                        type="button"
                        onClick={() => setCount((c) => Math.min(20, Number(c) + 1))}
                        className="flex h-9 w-9 items-center justify-center rounded-full border border-secondary-200 text-lg font-extrabold text-secondary-500 active:scale-95"
                    >
                        +
                    </button>
                </div>

                {/* 결과 카드 */}
                <div className="relative w-full rounded-3xl border-2 border-amber-200 bg-gradient-to-br from-amber-50 to-yellow-50 p-8 text-center shadow-sm">
                    {king == null ? (
                        <p className="text-base font-bold leading-relaxed text-secondary-400">
                            아래 버튼을 눌러 왕을 뽑고
                            <br />
                            미션을 확인하세요! 👑
                        </p>
                    ) : (
                        <>
                            <span className="mb-3 inline-flex items-center gap-1 rounded-full bg-white/70 px-3 py-1 text-xs font-bold text-amber-600 shadow-inner">
                                👑 이번 왕은 {king}번!
                            </span>
                            <p className="text-xl font-extrabold leading-relaxed text-secondary-800">
                                {mission}
                            </p>
                        </>
                    )}
                </div>
            </div>

            <button
                type="button"
                onClick={draw}
                className="mt-4 w-full rounded-2xl bg-primary py-4 text-base font-extrabold text-white shadow-md transition active:scale-[0.98] hover:bg-primary-600"
            >
                {king == null ? "왕 뽑기 👑" : "다시 뽑기 🔀"}
            </button>
        </div>
    );
}

export default function KingsGame() {
    return (
        <PublicLayout>
            <Head title="왕게임" />
            <KingsGameInner />
        </PublicLayout>
    );
}
