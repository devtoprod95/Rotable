import { Head, Link, router } from "@inertiajs/react";
import { useEffect, useMemo, useRef, useState } from "react";
import AppLayout from "@/Layouts/AppLayout";
import echo from "@/echo";

/**
 * Yes or No - 테이블 익명 실시간 투표 게임.
 *
 * 같은 테이블(현재 좌석 기준) 사람들이 같은 질문에 익명으로 예/아니오를 누르면
 * 그 즉시 YES/NO 수가 모두의 화면에 집계된다.
 *
 * - 저장(DB) 없음: 투표는 테이블 private 채널로 흘려보내는 휘발성 신호일 뿐이다.
 * - 익명: 누가 무엇을 눌렀는지 어디에도 남지 않는다.
 * - 질문은 모든 기기가 "같은 인덱스 = 같은 질문" 이 되도록 고정 순서로 둔다.
 * - 집계/질문 동기화는 모두 브로드캐스트 왕복(자신 포함)으로 처리해
 *   어느 기기에서도 카운트가 어긋나지 않게 한다.
 */

/** @type {string[]} 질문 원본 풀 (표시 순서는 테이블별 시드로 섞는다)
 *  - 모두 "지금 이 테이블/오늘 이 자리/여기 있는 사람" 맥락 전용 질문.
 *    같이 앉은 사람끼리만 답할 수 있어야 한다. */
const BASE_QUESTIONS = [
    "지금 이 테이블에 마음에 드는 사람이 있다",
    "오늘 이곳에 온 거 잘했다고 생각한다",
    "지금 이 테이블에 마음에 안드는 사람이 있다",
    "지금 테이블보다 다른 테이블의 이성이 더 눈에 띈다",
    "지금 이 테이블에 나를 마음에 들어 하는 이성이 있는 것 같다",
    "지금 이 테이블에 설레는 눈맞춤이 한 번 있었다",
    "지금 이 테이블 제외하고 마음에 드는 사람이 있다",
    "지금 옆자리 사람(좌우이성)이 이성적으로 느껴진다.",
    "지금 이 테이블에 인스타를 맞팔하고 싶은 사람이 있다",
    "지금 이 테이블에 나보다 옷 못입는 사람이 있다",
    "오늘 이곳에서 나는 나름 인기가 있을 것 같다",
    "오늘 마음에 드는 이성과 따로 나가서 얘기하고 싶다",
    "지금 이 테이블에서 자리가 끝나고 단 둘이 술을 마시고 싶은 이성이 있다",
    "지금 이 테이블에 도태남(녀)가 있는 것 같다",
    "오늘 이곳이 아니면 밖에서 말도 안했을 것 같은 사람이 있다",
    "지금 이 테이블 재밌고 이성분들도 마음에 든다",
    "지금 이 테이블에 내 마음의 2순위가 있다",
    "지금 이 테이블에 내 마음의 1순위가 있다",
    "오늘 나는 이 곳에서 애인을 만들거란 각오로 왔다",
    "나는 오늘 이성에게 쪽지를 보냈다",
    "나한테 쪽지가 1개 이상 왔다",
    "나는 오늘 이 테이블의 이성에게 연락처를 직접 물어볼거다",
    "이 테이블에 외적으로 내 스타일이 있다",
    "지금 나는 빨리 집에 가고 싶다",
    "나는 와인파티가 끝나면 다른 곳에서 더 놀거다",
    "나는 평소 적극적으로 다가와주는 이성한테 호감을 느낀다",
    "나는 외모보다 몸매를 더 좋아한다",
    "지금 이 테이블에 손잡고 싶은 이성이 있다",
    "지금 이 테이블에 로테이션 전에 나만의 시그널을 보내고 싶은 이성이 있다",
    "지금 이 테이블 벗어나고싶다",
];

/**
 * 시드 기반 의사난수 생성기(mulberry32).
 * 같은 시드는 항상 같은 수열을 만든다 → 같은 테이블의 모든 기기가 동일한 셔플 결과를 얻는다.
 */
function seededRandom(seed) {
    let a = seed >>> 0;
    return function () {
        a |= 0;
        a = (a + 0x6d2b79f5) | 0;
        let t = Math.imul(a ^ (a >>> 15), 1 | a);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

/** 문자열(채널 키 등)을 32bit 정수 시드로 변환한다. */
function hashSeed(str) {
    let h = 2166136261;
    for (let i = 0; i < str.length; i++) {
        h ^= str.charCodeAt(i);
        h = Math.imul(h, 16777619);
    }
    return h >>> 0;
}

/** 시드로 결정되는 셔플(Fisher–Yates). 같은 시드 → 항상 같은 순서. */
function seededShuffle(arr, seed) {
    const rand = seededRandom(seed);
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(rand() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

/**
 * 내 투표 여부를 저장하는 sessionStorage 키.
 * 집계(YES/NO 수)와 현재 질문은 서버(Redis)가 진실의 원천이므로 저장하지 않는다.
 * "내가 이 질문에 투표했는지" 만 기기별로 기억한다. (질문이 바뀌면 키도 바뀜)
 */
function myAnswerKey(eventId, tableNo, questionIndex) {
    return `table-poll:${eventId}:${tableNo}:${questionIndex}:mine`;
}

/** 이 질문에 대한 내 투표를 불러온다. ('yes' | 'no' | null) */
function loadMyAnswer(eventId, tableNo, questionIndex) {
    try {
        const v = sessionStorage.getItem(
            myAnswerKey(eventId, tableNo, questionIndex),
        );
        return v === "yes" || v === "no" ? v : null;
    } catch {
        return null;
    }
}

export default function TablePoll({
    eventId,
    tableNo,
    seatCount = 0,
    initialState = null,
}) {
    // 테이블별로 질문 순서를 섞는다. 시드는 (eventId, tableNo) 조합이라
    // 테이블마다 순서가 다르면서도, 같은 테이블의 모든 기기는 완전히 동일한 순서를 본다.
    const questions = useMemo(
        () => seededShuffle(BASE_QUESTIONS, hashSeed(`${eventId}-${tableNo}`)),
        [eventId, tableNo],
    );

    // 현재 질문/집계는 서버(Redis)에서 받은 값으로 시작한다.
    // → 중간 합류·다른 페이지 복귀 시에도 테이블의 실제 현황이 그대로 보인다.
    const initialIndex = initialState?.questionIndex ?? 0;

    const [questionIndex, setQuestionIndex] = useState(initialIndex);
    const [counts, setCounts] = useState(
        initialState?.counts ?? { yes: 0, no: 0 },
    );
    // 이번 질문에 이미 투표했는지(내 기기 기준) — 중복 투표 방지 & 버튼 비활성화.
    const [myAnswer, setMyAnswer] = useState(() =>
        loadMyAnswer(eventId, tableNo, initialIndex),
    );
    // "아직 다 투표 안 했는데 넘어갈까요?" 확인 다이얼로그 표시 여부.
    const [confirmNext, setConfirmNext] = useState(false);

    // 콜백에서 최신 questionIndex 를 참조하기 위한 ref.
    const questionIndexRef = useRef(questionIndex);
    questionIndexRef.current = questionIndex;

    // 테이블 private 채널 구독: 같은 테이블의 투표/질문변경 신호를 수신.
    useEffect(() => {
        if (!eventId || tableNo == null) {
            return undefined;
        }

        const channelName = `table-poll.${eventId}.${tableNo}`;
        const channel = echo.private(channelName);

        // 한 표 수신(자신이 던진 표 포함) → 현재 질문의 표만 카운트.
        channel.listen(".table-poll.voted", (payload) => {
            if (Number(payload.question_index) !== questionIndexRef.current) {
                return;
            }
            setCounts((prev) =>
                payload.answer === "yes"
                    ? { ...prev, yes: prev.yes + 1 }
                    : { ...prev, no: prev.no + 1 },
            );
        });

        // 질문 변경 수신 → 해당 질문으로 동기화하고 카운트 초기화.
        // 내 투표 여부는 새 질문 기준으로 다시 불러온다(보통 아직 없음 → null).
        channel.listen(".table-poll.question", (payload) => {
            const nextIndex = Number(payload.question_index);
            setQuestionIndex(nextIndex);
            setCounts({ yes: 0, no: 0 });
            setMyAnswer(loadMyAnswer(eventId, tableNo, nextIndex));
            setConfirmNext(false);
        });

        return () => {
            echo.leave(`private-${channelName}`);
        };
    }, [eventId, tableNo]);

    // 투표: 중복 방지 후 서버로 전송. 카운트는 브로드캐스트 왕복으로 반영된다.
    const handleVote = (answer) => {
        if (myAnswer) {
            return;
        }
        setMyAnswer(answer);
        // 내 투표 여부만 기기에 기억(복귀 시 버튼 비활성 유지). 집계는 서버가 관리.
        try {
            sessionStorage.setItem(
                myAnswerKey(eventId, tableNo, questionIndex),
                answer,
            );
        } catch {
            // 저장 실패는 무시 (진행에 지장 없음).
        }
        router.post(
            "/games/table-poll/vote",
            { question_index: questionIndex, answer },
            { preserveScroll: true, preserveState: true, only: [] },
        );
    };

    // 다음 질문 버튼 클릭: 테이블 전원이 투표하지 않았으면 확인 다이얼로그를 먼저 띄운다.
    const handleNextClick = () => {
        const total = counts.yes + counts.no;
        // 인원수를 알고(>0) 아직 전원이 투표하지 않았으면 확인을 받는다.
        if (seatCount > 0 && total < seatCount) {
            setConfirmNext(true);
            return;
        }
        proceedNext();
    };

    // 실제로 다음 질문으로 넘긴다. (테이블 전체에 브로드캐스트 → 내 화면도 왕복으로 반영)
    const proceedNext = () => {
        setConfirmNext(false);
        const nextIndex = (questionIndex + 1) % questions.length;
        router.post(
            "/games/table-poll/next",
            { question_index: nextIndex },
            { preserveScroll: true, preserveState: true, only: [] },
        );
    };

    const total = counts.yes + counts.no;
    const yesPct = total === 0 ? 0 : Math.round((counts.yes / total) * 100);
    const noPct = total === 0 ? 0 : 100 - yesPct;

    return (
        <AppLayout>
            <Head title="Yes or No" />

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
                        <span className="text-xl">🙋</span>
                        <h1 className="text-lg font-extrabold text-secondary-700">
                            Yes or No
                        </h1>
                    </div>
                    <span className="ml-auto rounded-full bg-violet-100 px-2.5 py-1 text-xs font-bold text-violet-600">
                        {tableNo}번 테이블
                    </span>
                </div>

                {/* 안내 + 질문 카드 */}
                <div className="flex flex-col items-center gap-4">
                    <div className="w-full rounded-2xl border border-violet-200 bg-violet-50/60 p-3.5">
                        <p className="text-xs font-medium leading-relaxed text-secondary-500">
                            <span className="font-bold text-violet-600">💡 Tip</span>
                            <br />
                            같은 테이블끼리 <span className="font-bold">익명</span>으로 투표해요.
                            누가 뭘 골랐는지는 아무도 몰라요. 솔직하게 눌러주세요! 😉
                            <span className="font-bold text-red-500">
                                🙈 옆사람 화면 보지 말고, 내 화면도 보여주지 마세요!
                            </span>
                        </p>
                    </div>

                    <div className="relative w-full rounded-3xl border-2 border-violet-200 bg-gradient-to-br from-violet-50 to-fuchsia-50 p-8 text-center shadow-sm">
                        <span className="mb-4 inline-flex items-center gap-1 rounded-full bg-white/70 px-3 py-1 text-xs font-bold text-violet-600 shadow-inner">
                            🙋 Q{questionIndex + 1}
                        </span>
                        <p className="text-xl font-extrabold leading-relaxed text-secondary-800">
                            {questions[questionIndex]}
                        </p>
                    </div>

                    {/* 실시간 집계 바 */}
                    <div className="w-full">
                        <div className="flex overflow-hidden rounded-2xl border border-secondary-200 shadow-inner">
                            <div
                                className="flex h-10 items-center justify-start bg-blue-100 pl-3 text-xs font-bold text-blue-600 transition-all duration-300"
                                style={{ width: `${total === 0 ? 50 : yesPct}%` }}
                            >
                                YES {counts.yes}
                            </div>
                            <div
                                className="flex h-10 flex-1 items-center justify-end bg-red-100 pr-3 text-xs font-bold text-red-600 transition-all duration-300"
                            >
                                {counts.no} NO
                            </div>
                        </div>
                        <p className="mt-1.5 text-center text-[11px] font-medium text-secondary-400">
                            {total === 0
                                ? "아직 아무도 투표하지 않았어요"
                                : `지금까지 ${total}명 투표 · YES ${yesPct}% / NO ${noPct}%`}
                        </p>
                    </div>

                    {/* 투표 버튼 */}
                    <div className="flex w-full gap-3">
                        <button
                            type="button"
                            onClick={() => handleVote("yes")}
                            disabled={!!myAnswer}
                            className={
                                "flex flex-1 flex-col items-center gap-1 rounded-2xl border-2 py-4 text-base font-extrabold transition active:scale-[0.97] disabled:active:scale-100 " +
                                (myAnswer === "yes"
                                    ? "border-blue-500 bg-blue-500 text-white"
                                    : "border-blue-300 bg-white text-blue-600 disabled:opacity-50")
                            }
                        >
                            <span className="text-2xl">👍</span>
                            YES
                        </button>
                        <button
                            type="button"
                            onClick={() => handleVote("no")}
                            disabled={!!myAnswer}
                            className={
                                "flex flex-1 flex-col items-center gap-1 rounded-2xl border-2 py-4 text-base font-extrabold transition active:scale-[0.97] disabled:active:scale-100 " +
                                (myAnswer === "no"
                                    ? "border-red-500 bg-red-500 text-white"
                                    : "border-red-300 bg-white text-red-600 disabled:opacity-50")
                            }
                        >
                            <span className="text-2xl">👎</span>
                            NO
                        </button>
                    </div>
                </div>

                {/* 다음 질문 버튼 */}
                <button
                    type="button"
                    onClick={handleNextClick}
                    className="mt-4 w-full rounded-2xl bg-primary py-4 text-base font-extrabold text-white shadow-md transition active:scale-[0.98] hover:bg-primary-600"
                >
                    다음 질문 🔀
                </button>
            </div>

            {/* 전원 투표 전 '다음 질문' 확인 다이얼로그 */}
            {confirmNext && (
                <div className="fixed inset-0 z-50 flex items-center justify-center px-6">
                    {/* 딤드 배경 */}
                    <div
                        className="absolute inset-0 bg-black/40"
                        onClick={() => setConfirmNext(false)}
                    />
                    <div className="relative w-full max-w-[320px] rounded-3xl bg-white p-6 text-center shadow-2xl">
                        <span className="text-3xl">🤔</span>
                        <h2 className="mt-2 text-base font-extrabold text-secondary-800">
                            아직 다 투표하지 않았어요
                        </h2>
                        <p className="mt-1.5 text-sm font-medium leading-relaxed text-secondary-500">
                            {seatCount}명 중{" "}
                            <span className="font-bold text-primary">
                                {counts.yes + counts.no}명
                            </span>
                            이 투표했어요.
                            <br />
                            그래도 다음 질문으로 넘어갈까요?
                        </p>
                        <div className="mt-5 flex gap-2">
                            <button
                                type="button"
                                onClick={() => setConfirmNext(false)}
                                className="flex-1 rounded-2xl border-2 border-secondary-200 bg-white py-3 text-sm font-bold text-secondary-500 transition active:scale-[0.97]"
                            >
                                취소
                            </button>
                            <button
                                type="button"
                                onClick={proceedNext}
                                className="flex-1 rounded-2xl bg-primary py-3 text-sm font-bold text-white shadow-md transition active:scale-[0.97] hover:bg-primary-600"
                            >
                                확인
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
