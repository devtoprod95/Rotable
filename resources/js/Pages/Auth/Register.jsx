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

/**
 * 개발 편의용 자동 입력 값. (production 이 아닐 때만 사용)
 * 회차(event_id)는 제외하고 나머지 필드만 채운다. 값은 자유롭게 커스텀하면 된다.
 */
const AUTOFILL = {
    phone: '01025466499',
    name: '김도한',
    password: 'qqqq',
    gender: 'M',
    birth_year: '1995',
    job: '개발자',
    height: '181',
    mbti: 'ENFP',
    region: '강남구',
    is_smoker: '0',
};

export default function Register() {
    const { rounds = [], birthYears = [], isProduction = true } = usePage().props;
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
    });

    // 회차(event_id)는 유지하고 나머지 필드를 AUTOFILL 값으로 채운다.
    function autofill() {
        Object.entries(AUTOFILL).forEach(([key, value]) => {
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
                        {!isProduction && (
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
                        <SelectField
                            label="출생연도"
                            value={data.birth_year}
                            onChange={(v) => setData('birth_year', v)}
                            options={birthYears}
                            placeholder="출생연도를 선택해주세요"
                            error={errors.birth_year}
                        />
                        <TextField
                            label="핸드폰 번호"
                            type="tel"
                            value={data.phone}
                            onChange={(v) => setData('phone', v)}
                            placeholder="01012345678"
                            error={errors.phone}
                        />
                        <TextField
                            label="비밀번호"
                            type="password"
                            value={data.password}
                            onChange={(v) => setData('password', v)}
                            placeholder="4자리 비밀번호"
                            error={errors.password}
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
