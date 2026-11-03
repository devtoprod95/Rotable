import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import ContactForm from '@/Components/ContactForm';

/**
 * 문의하기 전용 페이지.
 * 입장/입장등록 화면에서만 진입하며, 기존 문의 폼(ContactForm)을 그대로 보여준다.
 */
export default function Contact() {
    return (
        <AppLayout bottomNav={false} headerAction={false}>
            <Head title="문의하기 · Rotable" />
            <div className="flex flex-1 flex-col bg-secondary-50 p-6">
                <div className="m-auto w-full max-w-md rounded-3xl border border-secondary-100 bg-white p-8 shadow-2xl shadow-black/5 sm:p-10">
                    <div className="flex items-center justify-center gap-3">
                        <Link href="/" className="flex items-center gap-2">
                            <img src="/images/logo.png" alt="Rotable" className="h-11 w-11 object-contain" />
                            <span className="font-logo text-3xl leading-none tracking-wide text-black translate-y-[2px]">Rotable</span>
                        </Link>
                        <span className="h-7 w-px bg-secondary-200"></span>
                        <h1 className="text-2xl font-extrabold text-secondary-800">문의하기</h1>
                    </div>

                    <p className="mt-1.5 text-center text-sm text-secondary-400">
                        궁금한 점을 남겨주시면<br />운영진이 확인 후 연락드릴게요.
                    </p>

                    <ContactForm />

                    <p className="mt-6 text-center text-sm text-secondary-400">
                        <Link href="/login" className="font-semibold text-primary">입장</Link>
                        {' · '}
                        <Link href="/register" className="font-semibold text-primary">입장등록</Link>
                        {' 하러 가기'}
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
