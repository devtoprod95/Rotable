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

export default function Register() {
    const { rounds = [], birthYears = [] } = usePage().props;
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

    function submit(e) {
        e.preventDefault();
        post('/register');
    }

    return (
        <AppLayout bottomNav={false} headerAction={false}>
            <Head title="입장등록" />
            <div className="flex min-h-full items-center justify-center bg-secondary-50 p-6">
                <div className="w-full max-w-md rounded-3xl border border-secondary-100 bg-white p-8 shadow-2xl shadow-black/5 sm:p-10">
                    <Link href="/" className="block text-center text-xl font-extrabold tracking-wide text-primary">
                        Rotable
                    </Link>
                    <h1 className="mt-4 text-center text-2xl font-extrabold text-secondary-800">입장등록</h1>
                    <p className="mt-1.5 text-center text-sm text-secondary-400">
                        회차를 선택하고 정보를 입력해 등록하세요.
                    </p>

                    <form onSubmit={submit} className="mt-7 flex flex-col gap-4">
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
                            label="핸드폰번호"
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
                            {processing ? '등록 중…' : '입장등록'}
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
