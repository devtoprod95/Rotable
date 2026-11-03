import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import TextField from '@/Components/TextField';
import SelectField from '@/Components/SelectField';
import ContactForm from '@/Components/ContactForm';
import AuthErrorModal from '@/Components/AuthErrorModal';

/**
 * 개발 편의용 자동 입력 값. (production 이 아닐 때만 사용)
 * 회차(event_id)는 제외하고 나머지 필드만 채운다. 값은 자유롭게 커스텀하면 된다.
 */
const AUTOFILL = {
    phone: '01025466499',
    password: 'qqqq',
};

export default function Login() {
    const { rounds = [], isProduction = true } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        event_id: '',
        phone: '',
        password: '',
    });

    // 실패 사유를 모달로 크게 보여준다. (input 아래 작은 텍스트만으로는 놓치기 쉬움)
    const [errorModal, setErrorModal] = useState({ open: false, messages: [] });

    // 회차(event_id)는 유지하고 나머지 필드를 AUTOFILL 값으로 채운다.
    function autofill() {
        Object.entries(AUTOFILL).forEach(([key, value]) => {
            setData(key, value);
        });
    }

    function submit(e) {
        e.preventDefault();
        post('/login', {
            onError: (errs) => {
                const messages = Object.values(errs || {}).filter(Boolean);
                if (messages.length) {
                    setErrorModal({ open: true, messages });
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
                        <h1 className="text-2xl font-extrabold text-secondary-800">입장</h1>
                    </div>

                    {rounds.length === 0 ? (
                        <div className="mt-7">
                            <div className="flex flex-col items-center gap-3 text-center">
                                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-secondary-100 text-3xl">
                                    🗓️
                                </div>
                                <div>
                                    <p className="text-base font-bold text-secondary-800">
                                        지금 입장할 수 있는 회차가 없어요
                                    </p>
                                    <p className="mt-1.5 text-sm leading-relaxed text-secondary-400">
                                        현재 진행 중인 행사가 없어요.<br />
                                        궁금한 점이 있으면 운영진에게 문의해 주세요.
                                    </p>
                                </div>
                            </div>
                            <ContactForm />
                        </div>
                    ) : (
                    <>
                    <p className="mt-1.5 text-center text-sm text-secondary-400">
                        회차를 선택하고 핸드폰 번호와<br></br>비밀번호를 입력해주세요.
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
                            placeholder="비밀번호 4자리"
                            error={errors.password}
                            maxLength={4}
                        />

                        <button
                            type="submit"
                            disabled={processing}
                            className="mt-2 rounded-full bg-primary py-3 text-base font-bold text-white transition hover:bg-primary-600 disabled:opacity-60"
                        >
                            {processing ? '입장 중…' : '입장'}
                        </button>
                    </form>
                    </>
                    )}

                    <p className="mt-6 text-center text-sm text-secondary-400">
                        아직 등록하지 않으셨나요?{' '}
                        <Link href="/register" className="font-semibold text-primary">
                            입장등록
                        </Link>
                    </p>
                    <p className="mt-2 text-center text-sm text-secondary-400">
                        궁금한 점이 있으신가요?{' '}
                        <Link href="/contact-us" className="font-semibold text-primary">
                            문의하기
                        </Link>
                    </p>
                </div>
            </div>

            <AuthErrorModal
                open={errorModal.open}
                title="입장할 수 없어요"
                messages={errorModal.messages}
                onClose={() => setErrorModal({ open: false, messages: [] })}
            />
        </AppLayout>
    );
}
