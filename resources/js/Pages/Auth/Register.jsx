import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useMemo, useRef, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import TextField from '@/Components/TextField';
import SelectField from '@/Components/SelectField';
import RadioField from '@/Components/RadioField';
import AuthErrorModal from '@/Components/AuthErrorModal';

const GENDER_OPTIONS = [
    { id: 'M', label: '남' },
    { id: 'F', label: '여' },
];

const SMOKER_OPTIONS = [
    { id: '1', label: '흡연' },
    { id: '0', label: '비흡연' },
];

/** 랜덤 유틸 */
const pick = (arr) => arr[Math.floor(Math.random() * arr.length)];
const randInt = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;

const MBTI_OPTIONS = [
    'ISTJ', 'ISFJ', 'INFJ', 'INTJ', 'ISTP', 'ISFP', 'INFP', 'INTP',
    'ESTP', 'ESFP', 'ENFP', 'ENTP', 'ESTJ', 'ESFJ', 'ENFJ', 'ENTJ',
];
const JOB_SAMPLES = ['개발자', '디자이너', '마케터', '간호사', '교사', '회계사', '요리사', '승무원', '약사', '건축가'];
const REGION_SAMPLES = ['강남구', '서초구', '송파구', '마포구', '용산구', '성동구', '분당구', '수원시', '인천 연수구', '부산 해운대구'];
const MERIT_SAMPLES = ['잘 웃고 리액션이 좋아요', '배려심이 깊어요', '성실하고 꾸준해요', '유머감각이 있어요', '긍정적이에요', '경청을 잘해요'];
const IDEAL_SAMPLES = ['유머러스하고 다정한 사람', '대화가 잘 통하는 사람', '성실한 사람', '취미가 비슷한 사람', '배려심 있는 사람', '솔직한 사람'];
const HOBBY_SAMPLES = ['요즘 러닝에 빠졌어요', '등산을 좋아해요', '전시 보러 다녀요', '요리에 재미 붙였어요', '헬스 중이에요', '여행 다니는 걸 좋아해요'];
const TMI_SAMPLES = ['민트초코 파입니다', '물을 하루 2L 마셔요', '길치예요', '커피 없으면 못 살아요', '아침형 인간입니다', '매운 걸 잘 못 먹어요'];

/**
 * 개발 편의용 자동 입력 값 생성. (production 이 아닐 때만 사용)
 * 회차(event_id)는 제외하고 나머지 필드를 매번 랜덤으로 채운다.
 * 출생연도는 실제 선택 가능한 값(birthYears)에서 뽑는다.
 */
function buildAutofill(birthYears) {
    const birthYearIds = birthYears.map((y) => (typeof y === 'object' ? y.id : y));

    return {
        // 번호표~비밀번호는 기존 고정값 유지
        phone: '01025466499',
        name: '1',
        password: 'qqqq',
        gender: 'M',
        // 출생연도~흡연 여부는 매번 랜덤
        birth_year: birthYearIds.length ? String(pick(birthYearIds)) : String(randInt(1985, 2004)),
        job: pick(JOB_SAMPLES),
        height: String(randInt(155, 190)),
        mbti: pick(MBTI_OPTIONS),
        region: pick(REGION_SAMPLES),
        is_smoker: pick(['0', '1']),
        merit: pick(MERIT_SAMPLES),
        ideal_type: pick(IDEAL_SAMPLES),
        hobby: pick(HOBBY_SAMPLES),
        tmi: pick(TMI_SAMPLES),
    };
}

export default function Register() {
    const { rounds = [], birthYears = [], tablesByEvent = {}, isProduction = true } = usePage().props;

    // 제출 전 최종 확인 모달 노출 여부.
    const [confirmOpen, setConfirmOpen] = useState(false);

    // 입장등록 실패 사유를 모달로 크게 보여준다. (input 아래 작은 텍스트만으로는 놓치기 쉬움)
    const [errorModal, setErrorModal] = useState({ open: false, messages: [] });

    // 필수 선택 누락 시 상단(회차/테이블)으로 스크롤하기 위한 ref.
    const topRef = useRef(null);

    // 자동 입력 버튼 노출 조건:
    //  - 비프로덕션 환경이거나
    //  - URL 쿼리에 ?dev=... 처럼 dev 키에 값이 있을 때 (프로덕션에서도 임시 노출용)
    const showAutofill =
        !isProduction ||
        (typeof window !== 'undefined' && !!new URLSearchParams(window.location.search).get('dev'));
    const { data, setData, post, processing, errors, setError, clearErrors } = useForm({
        event_id: '',
        phone: '',
        name: '',
        password: '',
        gender: '',
        birth_year: '',
        job: '',
        height: '',
        mbti: '',
        region: '',
        is_smoker: '',
        // 추가 프로필 (모든 유형 공통) — 장점/이상형/취미/TMI
        merit: '',
        ideal_type: '',
        hobby: '',
        tmi: '',
    });

    // 회차가 바뀌면 (별도 초기화할 테이블 선택 없음)
    function changeEvent(v) {
        setData('event_id', v);
    }

    // 성별 변경
    function changeGender(v) {
        setData('gender', v);
    }

    // 회차(event_id)는 유지하고 나머지 필드를 매번 랜덤으로 채운다.
    function autofill() {
        Object.entries(buildAutofill(birthYears)).forEach(([key, value]) => {
            setData(key, value);
        });
    }

    // 제출 버튼 → 입장 전 확인 모달을 열기 전에, 필수 항목을 모두 먼저 검증한다.
    // 빈 값이 있으면 (여러 개여도) 맨 처음 누락 항목 "하나만" 모달로 안내하고 확인 모달은 열지 않는다.
    function submit(e) {
        e.preventDefault();

        // 검증 순서 = 화면에 보이는 순서. 첫 번째로 비어 있는 항목만 안내한다.
        // scrollTop 이 true 인 항목(회차/테이블/성별)은 해당 입력 위치로 스크롤한다.
        const requiredChecks = [
            { key: 'gender',            empty: !data.gender,            message: '성별을 선택해주세요.',              field: 'gender',            scrollTop: true },
            { key: 'event_id',          empty: !data.event_id,          message: '회차를 선택해주세요.',              field: 'event_id',          scrollTop: true },
            { key: 'name',              empty: !data.name,              message: '번호표를 입력해주세요.' },
            { key: 'phone',             empty: !data.phone,             message: '핸드폰 번호를 입력해주세요.' },
            { key: 'password',          empty: !data.password,          message: '비밀번호(4자리)를 입력해주세요.' },
            { key: 'birth_year',        empty: !data.birth_year,        message: '출생연도를 선택해주세요.' },
            { key: 'job',               empty: !data.job,               message: '직업을 입력해주세요.' },
            { key: 'height',            empty: !data.height,            message: '키를 입력해주세요.' },
            { key: 'mbti',              empty: !data.mbti,              message: 'MBTI를 입력해주세요.' },
            { key: 'region',            empty: !data.region,            message: '지역을 입력해주세요.' },
            { key: 'is_smoker',         empty: data.is_smoker === '' || data.is_smoker === null || data.is_smoker === undefined, message: '흡연 여부를 선택해주세요.' },
        ];

        const firstMissing = requiredChecks.find((c) => c.empty);
        if (firstMissing) {
            if (firstMissing.field) {
                setError(firstMissing.field, firstMissing.message);
            }
            if (firstMissing.scrollTop) {
                topRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            // 여러 개가 비어 있어도 하나만 보여준다. (모달에 확인 버튼이 가려지지 않도록)
            setErrorModal({ open: true, messages: [firstMissing.message] });
            return;
        }

        clearErrors('gender', 'event_id');
        setConfirmOpen(true);
    }

    // 모달에서 최종 확인 → 실제 등록 요청.
    function confirmSubmit() {
        setConfirmOpen(false);
        post('/register', {
            onError: (errs) => {
                // 서버 검증 실패도 맨 처음 사유 "하나만" 띄운다.
                const messages = Object.values(errs || {}).filter(Boolean);
                if (messages.length) {
                    setErrorModal({ open: true, messages: [messages[0]] });
                }
            },
        });
    }

    return (
        <AppLayout bottomNav={false} headerAction={false}>
            <Head title="Rotable" />
            <div className="flex flex-1 flex-col bg-secondary-50 p-6">
                <div className="m-auto w-full max-w-md rounded-3xl border border-secondary-100 bg-white p-8 shadow-2xl shadow-black/5 sm:p-10">
                    <div className="flex items-center justify-center gap-3">
                        <Link href="/" className="flex items-center gap-2">
                            <img src="/images/logo.png" alt="Rotable" className="h-11 w-11 object-contain" />
                            <span className="font-logo text-3xl leading-none tracking-wide text-black translate-y-[2px]">Rotable</span>
                        </Link>
                        <span className="h-7 w-px bg-secondary-200"></span>
                        <h1 className="text-2xl font-extrabold text-secondary-800">입장등록</h1>
                    </div>

                    {rounds.length === 0 ? (
                        <div className="mt-7">
                            <div className="flex flex-col items-center gap-3 text-center">
                                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-secondary-100 text-3xl">
                                    🗓️
                                </div>
                                <div>
                                    <p className="text-base font-bold text-secondary-800">
                                        지금 등록할 수 있는 회차가 없어요
                                    </p>
                                    <p className="mt-1.5 text-sm leading-relaxed text-secondary-400">
                                        현재 진행 중인 행사가 없어요.<br />
                                        궁금한 점이 있으면 운영진에게 문의해 주세요.
                                    </p>
                                </div>
                            </div>
                            <Link
                                href="/login"
                                className="mt-6 block w-full rounded-full bg-primary py-3 text-center text-base font-bold text-white transition hover:bg-primary-600"
                            >
                                입장하기
                            </Link>
                            <Link
                                href="/contact-us"
                                className="mt-3 block w-full rounded-full border border-secondary-200 bg-white py-3 text-center text-base font-bold text-secondary-600 transition hover:bg-secondary-50"
                            >
                                문의하기
                            </Link>
                        </div>
                    ) : (
                    <>
                    <p className="mt-1.5 text-center text-sm text-secondary-400">
                        회차를 선택하고 정보를 입력해 등록하세요.
                    </p>

                    <form onSubmit={submit} autoComplete="off" className="mt-7 flex flex-col gap-4">
                        <div ref={topRef} aria-hidden="true"></div>
                        {showAutofill && (
                            <button
                                type="button"
                                onClick={autofill}
                                className="self-end rounded-full border border-dashed border-primary/40 bg-primary/5 px-3 py-1.5 text-xs font-bold text-primary transition hover:bg-primary/10"
                            >
                                ⚡ 자동 입력 (회차 제외)
                            </button>
                        )}
                        <RadioField
                            label="성별"
                            value={data.gender}
                            onChange={(v) => changeGender(v)}
                            options={GENDER_OPTIONS}
                            error={errors.gender}
                        />
                        <SelectField
                            label="회차"
                            value={data.event_id}
                            onChange={(v) => changeEvent(v)}
                            options={rounds}
                            placeholder="회차를 선택해주세요"
                            error={errors.event_id}
                            autoFocus
                        />
                        <TextField
                            label="번호표"
                            value={data.name}
                            onChange={(v) => setData('name', v)}
                            placeholder="받으신 번호표를 입력해주세요."
                            digitsOnly
                            maxLength={10}
                            error={errors.name}
                        />
                        <TextField
                            label="핸드폰 번호"
                            type="tel"
                            value={data.phone}
                            onChange={(v) => setData('phone', v)}
                            placeholder="01012345678 (로그인할 때 필요합니다)"
                            error={errors.phone}
                        />
                        <TextField
                            label="비밀번호"
                            type="password"
                            value={data.password}
                            onChange={(v) => setData('password', v)}
                            placeholder="비밀번호 4자리 (로그인할 때 필요합니다)"
                            error={errors.password}
                            maxLength={4}
                        />
                        <SelectField
                            label="출생연도"
                            value={data.birth_year}
                            onChange={(v) => setData('birth_year', v)}
                            options={birthYears}
                            placeholder="출생연도를 선택해주세요"
                            error={errors.birth_year}
                        />
                        <TextField
                            label="지역/거주지"
                            value={data.region}
                            onChange={(v) => setData('region', v)}
                            placeholder="예: 강남구"
                            error={errors.region}
                        />
                        <TextField
                            label="키 (cm)"
                            type="number"
                            value={data.height}
                            onChange={(v) => setData('height', v)}
                            placeholder="예: 170"
                            error={errors.height}
                        />
                        <TextField
                            label="직업"
                            value={data.job}
                            onChange={(v) => setData('job', v)}
                            placeholder="예: 개발자"
                            error={errors.job}
                        />
                        <TextField
                            label="MBTI"
                            value={data.mbti}
                            onChange={(v) => setData('mbti', v.toUpperCase())}
                            placeholder="예: ENFP"
                            error={errors.mbti}
                        />
                        <TextField
                            label="이상형"
                            value={data.ideal_type}
                            onChange={(v) => setData('ideal_type', v)}
                            placeholder="예: 유머러스하고 다정한 사람"
                            error={errors.ideal_type}
                        />
                        <TextField
                            label="나의 장점"
                            value={data.merit}
                            onChange={(v) => setData('merit', v)}
                            placeholder="예: 잘 웃고 리액션이 좋아요"
                            error={errors.merit}
                        />
                        <TextField
                            label="취미 / 요즘 빠진 것"
                            value={data.hobby}
                            onChange={(v) => setData('hobby', v)}
                            placeholder="예: 요즘 러닝에 빠졌어요"
                            error={errors.hobby}
                        />
                        <TextField
                            label="TMI"
                            value={data.tmi}
                            onChange={(v) => setData('tmi', v)}
                            placeholder="예: 민트초코 파입니다 🍫"
                            error={errors.tmi}
                        />
                        <RadioField
                            label="흡연 여부"
                            value={data.is_smoker}
                            onChange={(v) => setData('is_smoker', v)}
                            options={SMOKER_OPTIONS}
                            error={errors.is_smoker}
                        />

                        <button
                            type="submit"
                            disabled={processing}
                            className="mt-2 rounded-full bg-primary py-3 text-base font-bold text-white transition hover:bg-primary-600 disabled:opacity-60"
                        >
                            {processing ? '입장 중…' : '입장'}
                        </button>
                    </form>

                    <p className="mt-6 text-center text-sm text-secondary-400">
                        이미 등록하셨나요?{' '}
                        <Link href="/login" className="font-semibold text-primary">
                            입장
                        </Link>
                    </p>
                    <p className="mt-2 text-center text-sm text-secondary-400">
                        궁금한 점이 있으신가요?{' '}
                        <Link href="/contact-us" className="font-semibold text-primary">
                            문의하기
                        </Link>
                    </p>
                    </>
                    )}
                </div>
            </div>

            {/* 제출 전 최종 확인 모달 — 번호표/테이블을 다시 확인시킨다. */}
            {confirmOpen && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-6"
                    onClick={() => setConfirmOpen(false)}
                >
                    <div
                        className="w-full max-w-sm rounded-3xl bg-white p-7 shadow-2xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <h2 className="text-center text-xl font-extrabold text-secondary-800">입장 전 확인</h2>
                        <p className="mt-4 text-center text-base leading-relaxed text-secondary-600">
                            {data.gender && (
                                <span
                                    className={
                                        'mr-1 inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-sm font-bold ' +
                                        (data.gender === 'F'
                                            ? 'bg-rose-50 text-rose-600'
                                            : 'bg-sky-50 text-sky-600')
                                    }
                                >
                                    {data.gender === 'F' ? '👩 여성' : '👨 남성'}
                                </span>
                            )}
                            <span className="font-bold text-primary">{data.name || '회원'}호</span>님,
                            <br />
                            번호표가 맞으신가요?
                        </p>
                        <p className="mt-2 text-center text-sm text-secondary-400">
                            자리(테이블)는 호스트가 안내해 드린 자리로 자동 배정됩니다.
                        </p>
                        <div className="mt-6 flex gap-3">
                            <button
                                type="button"
                                onClick={() => setConfirmOpen(false)}
                                className="flex-1 rounded-full border border-secondary-200 py-3 text-base font-bold text-secondary-600 transition hover:bg-secondary-50"
                            >
                                다시 확인
                            </button>
                            <button
                                type="button"
                                onClick={confirmSubmit}
                                disabled={processing}
                                className="flex-1 rounded-full bg-primary py-3 text-base font-bold text-white transition hover:bg-primary-600 disabled:opacity-60"
                            >
                                {processing ? '입장 중…' : '네, 맞아요'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            <AuthErrorModal
                open={errorModal.open}
                title="입장등록을 할 수 없어요"
                messages={errorModal.messages}
                onClose={() => setErrorModal({ open: false, messages: [] })}
            />
        </AppLayout>
    );
}
