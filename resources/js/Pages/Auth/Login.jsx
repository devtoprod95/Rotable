import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import TextField from '@/Components/TextField';
import SelectField from '@/Components/SelectField';

export default function Login() {
    const { rounds = [] } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        event_id: '',
        phone: '',
        password: '',
    });

    function submit(e) {
        e.preventDefault();
        post('/login');
    }

    return (
        <AppLayout bottomNav={false} headerAction={false}>
            <Head title="Rotable" />
            <div className="flex min-h-full items-center justify-center bg-secondary-50 p-6">
                <div className="w-full max-w-md rounded-3xl border border-secondary-100 bg-white p-8 shadow-2xl shadow-black/5 sm:p-10">
                    <div className="flex items-center justify-center gap-3">
                        <Link href="/" className="flex items-center gap-2">
                            <img src="/images/logo.png" alt="Rotable" className="h-11 w-11 object-contain" />
                            <span className="font-logo text-3xl leading-none tracking-wide text-black translate-y-[2px]">Rotable</span>
                        </Link>
                        <span className="h-7 w-px bg-secondary-200"></span>
                        <h1 className="text-2xl font-extrabold text-secondary-800">입장</h1>
                    </div>
                    <p className="mt-1.5 text-center text-sm text-secondary-400">
                        회차를 선택하고 핸드폰 번호와 비밀번호를 입력해주세요.
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
                            placeholder="비밀번호"
                            error={errors.password}
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
                        아직 등록하지 않으셨나요?{' '}
                        <Link href="/register" className="font-semibold text-primary">
                            입장등록
                        </Link>
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
