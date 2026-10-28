import { Head, Link } from "@inertiajs/react";
import { useEffect, useMemo, useRef, useState } from "react";
import PublicLayout from "@/Layouts/PublicLayout";
import echo from "@/echo";

/**
 * 공개 Yes or No (비로그인).
 *
 * 로그인/테이블 없이 "지금 이 페이지에 접속해 있는 사람들"을 한 방으로 묶어
 * 같은 질문에 익명으로 예/아니오를 누르면 실시간으로 집계된다.
 *
 * - 집계/질문 동기화: public 채널 'public-poll' (게스트도 인증 없이 구독)
 * - 접속자 수: 주기적 하트비트(POST) → 서버가 Redis 로 집계해 브로드캐스트
 * - 질문은 고정 순서(인덱스 동기화). 집계/현재 질문의 진실의 원천은 서버(Redis).
 */

/** @type {string[]} 고정 순서 질문 풀 (비공개 테이블 Yes/No 와 동일) */
const QUESTIONS = [
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

/** CSRF 토큰 (meta 태그에서) */
function csrfToken() {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") ?? ""
    );
}

/** 공개 게임 API 로 JSON POST. */
async function postJson(url, body) {
    try {
        const res = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken(),
                Accept: "application/json",
            },
            body: JSON.stringify(body),
            keepalive: true,
        });
        return res.ok ? res.json() : null;
    } catch {
        return null;
    }
}

/** 내 투표 여부 저장 키 (질문별) */
function myAnswerKey(questionIndex) {
    return `public-poll:${questionIndex}:mine`;
}
function loadMyAnswer(questionIndex) {
    try {
        const v = sessionStorage.getItem(myAnswerKey(questionIndex));
        return v === "yes" || v === "no" ? v : null;
    } catch {
        return null;
    }
}

export default function Poll({ initialState = null, initialCount = 0 }) {
    const initialIndex = initialState?.questionIndex ?? 0;

    const [questionIndex, setQuestionIndex] = useState(initialIndex);
    const [counts, setCounts] = useState(
        initialState?.counts ?? { yes: 0, no: 0 },
    );
    const [myAnswer, setMyAnswer] = useState(() => loadMyAnswer(initialIndex));
    const [presence, setPresence] = useState(initialCount);
    // "아직 다 투표 안 했는데 넘어갈까요?" 확인 다이얼로그 표시 여부.
    const [confirmNext, setConfirmNext] = useState(false);

    // 이 브라우저를 식별하는 임의 ID (접속자 수 집계용, 익명).
    const clientId = useMemo(
        () => Math.random().toString(36).slice(2) + Date.now().toString(36),
        [],
    );

    const questionIndexRef = useRef(questionIndex);
    questionIndexRef.current = questionIndex;

    // public 채널 구독 (게스트도 인증 없이 가능).
    useEffect(() => {
        const channel = echo.channel("public-poll");

        channel.listen(".public-poll.voted", (payload) => {
            if (Number(payload.question_index) !== questionIndexRef.current) {
                return;
            }
            setCounts((prev) =>
                payload.answer === "yes"
                    ? { ...prev, yes: prev.yes + 1 }
                    : { ...prev, no: prev.no + 1 },
            );
        });

        channel.listen(".public-poll.question", (payload) => {
            const nextIndex = Number(payload.question_index);
            setQuestionIndex(nextIndex);
            setCounts({ yes: 0, no: 0 });
            setMyAnswer(loadMyAnswer(nextIndex));
            setConfirmNext(false);
        });

        channel.listen(".public-poll.presence", (payload) => {
            setPresence(Number(payload.count) || 0);
        });

        return () => {
            echo.leave("public-poll");
        };
    }, []);

    // 접속자 하트비트/집계.
    //
    // 핵심 원칙: "떠난 사람"을 남은 사람 화면에 빨리 반영하는 가장 견고한 방법은
    // beacon 도달 여부에 의존하지 않고, 남아있는 각 클라이언트가 짧은 주기로
    // 서버에 핑을 보내 "지금 살아있는 접속자 수"를 응답으로 직접 받는 것이다.
    // 서버는 핑마다 만료된(TTL 지난) 접속자를 청소하므로, 떠난 사람은 TTL 내 자연 소멸한다.
    //
    // - 하트비트 간격: 2초 (TTL 4초 → 한 번 놓쳐도 유지, 완전히 떠나면 ~4초 내 소멸)
    // - 응답 count 로 내 화면 즉시 갱신 (브로드캐스트를 못 받아도 정확)
    // - 화면 숨김(백그라운드): 핑 중단 → 서버에서 TTL 로 곧 빠짐. beacon 은 즉시성 보너스.
    useEffect(() => {
        let timer = null;

        const ping = async () => {
            const res = await postJson("/public/games/poll/heartbeat", {
                client_id: clientId,
            });
            if (res && typeof res.count === "number") {
                setPresence(res.count);
            }
        };

        const sendLeave = () => {
            try {
                const blob = new Blob(
                    [JSON.stringify({ client_id: clientId, leaving: true })],
                    { type: "application/json" },
                );
                navigator.sendBeacon?.("/public/games/poll/heartbeat", blob);
            } catch {
                // 무시 — 못 보내도 TTL 로 자연 소멸한다.
            }
        };

        const startHeartbeat = () => {
            if (timer) {
                return;
            }
            ping();
            timer = setInterval(ping, 2000);
        };

        const stopHeartbeat = () => {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        };

        // 화면 가시성에 따라 접속 상태를 토글한다.
        // - 숨김: 핑 중단(+ 즉시 이탈 신호) → 서버 TTL 로 곧 접속자에서 빠짐.
        // - 다시 보임: 핑 재개(= 재접속으로 집계).
        const handleVisibility = () => {
            if (document.visibilityState === "hidden") {
                stopHeartbeat();
                sendLeave();
            } else {
                startHeartbeat();
            }
        };

        if (document.visibilityState !== "hidden") {
            startHeartbeat();
        }

        document.addEventListener("visibilitychange", handleVisibility);
        window.addEventListener("pagehide", sendLeave);

        return () => {
            stopHeartbeat();
            document.removeEventListener("visibilitychange", handleVisibility);
            window.removeEventListener("pagehide", sendLeave);
            sendLeave();
        };
    }, [clientId]);

    const handleVote = (answer) => {
        if (myAnswer) {
            return;
        }
        setMyAnswer(answer);
        try {
            sessionStorage.setItem(myAnswerKey(questionIndex), answer);
        } catch {
            // 무시
        }
        postJson("/public/games/poll/vote", {
            question_index: questionIndex,
            answer,
        });
    };

    // 다음 질문 버튼 클릭: 접속자 전원이 투표하지 않았으면 확인 다이얼로그를 먼저 띄운다.
    // (공개 버전은 좌석 인원 대신 "지금 접속 중인 사람 수" 를 기준으로 삼는다.)
    const handleNext = () => {
        const total = counts.yes + counts.no;
        if (presence > 0 && total < presence) {
            setConfirmNext(true);
            return;
        }
        proceedNext();
    };

    // 실제로 다음 질문으로 넘긴다. (접속자 전체에 브로드캐스트 → 내 화면도 왕복으로 반영)
    const proceedNext = () => {
        setConfirmNext(false);
        const nextIndex = (questionIndex + 1) % QUESTIONS.length;
        postJson("/public/games/poll/next", { question_index: nextIndex });
    };

    const total = counts.yes + counts.no;
    const yesPct = total === 0 ? 0 : Math.round((counts.yes / total) * 100);
    const noPct = total === 0 ? 0 : 100 - yesPct;

    return (
        <PublicLayout>
            <Head title="Yes or No" />

            <div className="flex flex-1 flex-col px-4 py-5">
                <div className="mb-4 flex items-center gap-2">
                    <Link
                        href="/public/games"
                        className="flex h-9 w-9 items-center justify-center rounded-full border border-secondary-200 bg-white text-secondary-500 shadow-sm transition active:scale-95 hover:text-primary"
                        aria-label="게임 목록으로"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </Link>
                    <div className="flex items-center gap-1.5">
                        <span className="text-xl">🙋</span>
                        <h1 className="text-lg font-extrabold text-secondary-700">
                            Yes or No
                        </h1>
                    </div>
                    <span className="ml-auto flex items-center gap-1 rounded-full bg-violet-100 px-2.5 py-1 text-xs font-bold text-violet-600">
                        🟢 {presence}명 접속 중
                    </span>
                </div>

                <div className="flex flex-col items-center gap-4">
                    <div className="w-full rounded-2xl border border-violet-200 bg-violet-50/60 p-3.5">
                        <p className="text-xs font-medium leading-relaxed text-secondary-500">
                            <span className="font-bold text-violet-600">💡 Tip</span>
                            <br />
                            지금 <b>이 게임에 접속한 사람끼리</b> 익명으로 투표해요.
                            누가 뭘 골랐는지는 아무도 몰라요. 솔직하게 눌러주세요! 😉
                            <br />
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
                            {QUESTIONS[questionIndex]}
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
                            <div className="flex h-10 flex-1 items-center justify-end bg-red-100 pr-3 text-xs font-bold text-red-600 transition-all duration-300">
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

                <button
                    type="button"
                    onClick={handleNext}
                    className="mt-4 w-full rounded-2xl bg-primary py-4 text-base font-extrabold text-white shadow-md transition active:scale-[0.98] hover:bg-primary-600"
                >
                    다음 질문 🔀
                </button>
            </div>

            {/* 접속자 전원 투표 전 '다음 질문' 확인 다이얼로그 */}
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
                            접속 중 {presence}명 중{" "}
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
        </PublicLayout>
    );
}
