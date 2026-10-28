/**
 * 공개 술게임 공용 벌칙(penalty) 모듈.
 *
 * - 벌칙 풀(PENALTIES): 랜덤 벌칙 룰렛과 상단 '벌칙 룰렛' 버튼이 공유한다.
 * - 현재 벌칙(current penalty): 상단 룰렛으로 한 번 정해두면 sessionStorage 에 저장되어
 *   어느 술게임을 하든 "이번 벌칙"으로 공유된다. (세션 한정, 휘발성)
 */

/** @typedef {{ emoji: string, text: string }} Penalty */

/** @type {Penalty[]} 벌칙 풀 */
export const PENALTIES = [
    { emoji: "🥃", text: "원샷! 한 잔 쭉 비우기" },
    { emoji: "💕", text: "러브샷! 지목한 이성과 팔짱 끼고 한 잔" },
    { emoji: "🥰", text: "가장 마음에 드는 이성에게 애교 3종 세트!" },
    { emoji: "💋", text: "옆 사람 볼에 뽀뽀… 대신 안 하면 두 잔!" },
    { emoji: "🎤", text: "섹시 댄스 10초! 못 하면 원샷" },
    { emoji: "🍺", text: "앞사람과 가위바위보, 진 사람이 두 잔" },
    { emoji: "👀", text: "마음에 드는 사람과 10초간 눈 안 떼고 바라보기" },
    { emoji: "💘", text: "이 자리에서 제일 섹시한 사람 지목하고 이유 말하기" },
    { emoji: "🙊", text: "첫 키스 나이 공개! 비밀로 하면 원샷" },
    { emoji: "🤝", text: "양옆 사람과 어깨동무하고 건배!" },
    { emoji: "🎭", text: "진실 혹은 벌칙! 질문 하나 받고 답 못하면 원샷" },
    { emoji: "🔥", text: "본인의 '침대에서 매력' 자랑 포인트 하나 말하기" },
    { emoji: "🎁", text: "면제! 이번엔 운 좋게 통과 🍀" },
    { emoji: "🥂", text: "전원 건배! 단, 당첨자는 원샷" },
];

/** 현재 벌칙을 저장하는 sessionStorage 키. */
const STORAGE_KEY = "public-penalty";

/** 커스텀 이벤트명 — 같은 탭 내 여러 컴포넌트가 현재 벌칙 변경을 구독한다. */
export const PENALTY_EVENT = "public-penalty-changed";

/**
 * 현재 선택된 벌칙을 읽는다.
 * @returns {Penalty|null}
 */
export function getCurrentPenalty() {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return null;
        }
        const parsed = JSON.parse(raw);
        if (parsed && typeof parsed.text === "string") {
            return { emoji: parsed.emoji ?? "🎯", text: parsed.text };
        }
        return null;
    } catch {
        return null;
    }
}

/**
 * 현재 벌칙을 저장하고, 같은 탭의 구독자에게 변경을 알린다.
 * @param {Penalty} penalty
 */
export function setCurrentPenalty(penalty) {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(penalty));
    } catch {
        // 저장 실패는 무시 (이번 세션 공유만 안 될 뿐 진행에는 지장 없음)
    }
    try {
        window.dispatchEvent(
            new CustomEvent(PENALTY_EVENT, { detail: penalty }),
        );
    } catch {
        // 무시
    }
}

/** 랜덤으로 벌칙 하나를 뽑는다. */
export function randomPenalty() {
    return PENALTIES[Math.floor(Math.random() * PENALTIES.length)];
}
