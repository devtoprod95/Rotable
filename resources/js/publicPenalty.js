/**
 * 공개 술게임 공용 벌칙(penalty) 모듈.
 *
 * - 벌칙 풀(PENALTIES): 랜덤 벌칙 룰렛과 상단 '벌칙 룰렛' 버튼이 공유한다.
 * - 현재 벌칙(current penalty): 상단 룰렛으로 한 번 정해두면 sessionStorage 에 저장되어
 *   어느 술게임을 하든 "이번 벌칙"으로 공유된다. (세션 한정, 휘발성)
 */

/** @typedef {{ text: string }} Penalty */

/** @type {Penalty[]} 벌칙 풀 (미션형. 실패 시 한 잔으로 통일) */
export const PENALTIES = [
    { text: "입술을 살짝 깨물며 5초간 치명적인 표정 짓기" },
    { text: "19금 영화 주인공처럼 침대에 눕기 직전의 유혹적인 표정 연기하기" },
    { text: "소개팅에서 마음에 드는 사람을 발견한 순간을 표정만으로 연기하기" },
    { text: "양손으로 얼굴을 감싸고 세상에서 가장 귀여운 척하기" },
    { text: "섹시한 표정으로 시작해서 마지막에 갑자기 망가지는 표정 짓기" },
    { text: "보이지 않는 카메라를 향해 속옷 광고 모델처럼 포즈 3개 취하기" },
    { text: "술에 취해 전 애인에게 전화하려는 사람 연기하기" },
    { text: "상상 속 연인에게 삐진 뒤 애교 없이 화해를 유도하는 표정 연기하기" },
    { text: "머리카락을 천천히 넘기며 청순함에서 섹시함으로 표정 바꾸기" },
    { text: "보이지 않는 상대에게 손키스를 날린 뒤 아무렇지 않은 척하기" },
    { text: "첫날밤을 앞둔 드라마 주인공처럼 수줍고 설레는 표정 연기하기" },
    { text: "세상에서 가장 느끼한 윙크와 미소를 동시에 보여주기" },
    { text: "상상 속 거울을 보며 본인에게 반한 나르시시스트 연기하기" },
    { text: "상대에게 플러팅을 시도하다가 철벽을 당한 사람의 표정 3단계 보여주기" },
    { text: "입술을 오므렸다 풀면서 키스 직전의 과장된 표정 연기하기" },
    { text: "술에 취해 갑자기 사랑에 빠진 사람처럼 주변을 바라보며 하트 만들기" },
    { text: "도도한 재벌 2세처럼 앉은 자세와 표정만으로 10초간 분위기 잡기" },
    { text: "상상 속 연인에게 질투하는 모습을 표정과 몸짓만으로 연기하기" },
    { text: "섹시한 표정으로 시작해 5초 안에 세상에서 가장 못생긴 표정 짓기" },
    { text: "야릇한 분위기의 드라마 주인공처럼 입술을 만지며 슬로 모션 연기하기" },

    { text: "옆 이성 목에 키스하기" },
    { text: "옆 이성과 진하게 포옹하기" },
    { text: "옆 이성 입술에 뽀뽀하기" },
    { text: "옆 이성과 손깍지 끼고 30초 버티기" },
    { text: "옆 이성 볼에 연속으로 뽀뽀 3번 하기" },
    { text: "옆 이성 무릎에 10초간 앉아 있기" },
    { text: "옆 이성 귓가에 입 맞추기" },
    { text: "옆 이성 손등에 키스하고 손깍지 끼기" },
    { text: "옆 이성과 서로 허리를 감싸고 포옹하기" },
    { text: "옆 이성 이마에 천천히 키스하기" },
    { text: "옆 이성 어깨에 기대 10초간 있기" },
    { text: "옆 이성과 얼굴을 맞대고 10초간 포즈 유지하기" },
    { text: "옆 이성 손에 키스하고 손을 놓지 않기" },
    { text: "옆 이성과 서로 볼을 맞대고 셀카 찍기" },
    { text: "옆 이성에게 백허그하기" },
    { text: "옆 이성과 팔짱을 끼고 건배하기" },
    { text: "옆 이성의 손을 잡고 손바닥에 키스하기" },
    { text: "옆 이성과 15초간 밀착 포옹하기" },
    { text: "옆 이성의 어깨에 손을 올리고 커플 포즈 취하기" },
    { text: "옆 이성과 손을 맞잡고 서로 얼굴 가까이 대기" },
];

/** 벌칙 실패(미수행) 시 공통 페널티 문구. */
export const PENALTY_FAIL = "미션 실패 시 한 잔 마시기 🍺";

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
            return { text: parsed.text };
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
