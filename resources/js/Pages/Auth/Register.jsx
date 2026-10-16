import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import TextField from '@/Components/TextField';
import SelectField from '@/Components/SelectField';
import RadioField from '@/Components/RadioField';

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
        // 닉네임~비밀번호는 기존 고정값 유지
        phone: '01025466499',
        name: '김도한',
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
    const { rounds = [], birthYears = [], isProduction = true } = usePage().props;

    // 자동 입력 버튼 노출 조건:
    //  - 비프로덕션 환경이거나
    //  - URL 쿼리에 ?dev=... 처럼 dev 키에 값이 있을 때 (프로덕션에서도 임시 노출용)
    const showAutofill =
        !isProduction ||
        (typeof window !== 'undefined' && !!new URLSearchParams(window.location.search).get('dev'));
    const { data, setData, post, processing, errors } = useForm({
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

    // 회차(event_id)는 유지하고 나머지 필드를 매번 랜덤으로 채운다.
    function autofill() {
        Object.entries(buildAutofill(birthYears)).forEach(([key, value]) => {
            setData(key, value);
        });
    }

    function submit(e) {
        e.preventDefault();
        post('/register');
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
                    <p className="mt-1.5 text-center text-sm text-secondary-400">
                        회차를 선택하고 정보를 입력해 등록하세요.
                    </p>

                    <form onSubmit={submit} className="mt-7 flex flex-col gap-4">
                        {showAutofill && (
                            <button
                                type="button"
                                onClick={autofill}
                                className="self-end rounded-full border border-dashed border-primary/40 bg-primary/5 px-3 py-1.5 text-xs font-bold text-primary transition hover:bg-primary/10"
                            >
                                ⚡ 자동 입력 (회차 제외)
                            </button>
                        )}
                        <SelectField
                            label="회차"
                            value={data.event_id}
                            onChange={(v) => setData('event_id', v)}
                            options={rounds}
                            placeholder="회차를 선택해주세요"
                            error={errors.event_id}
                            autoFocus
                        />
                        <RadioField
                            label="성별"
                            value={data.gender}
                            onChange={(v) => setData('gender', v)}
                            options={GENDER_OPTIONS}
                            error={errors.gender}
                        />
                        <TextField
                            label="닉네임"
                            value={data.name}
                            onChange={(v) => setData('name', v)}
                            placeholder="닉네임을 입력해주세요."
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
                </div>
            </div>
        </AppLayout>
    );
}
