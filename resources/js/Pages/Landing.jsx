import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Landing() {
    const { auth } = usePage().props;

    return (
        <AppLayout>
            <Head title="두근두근 — 새로운 인연의 시작" />

            <div className="flex min-h-full flex-col bg-gradient-to-b from-primary-50 via-primary-100 to-primary-200 text-gray-800">
                <main className="flex flex-1 flex-col items-center justify-center px-6 py-10 text-center">
                    <h1 className="text-4xl font-extrabold leading-tight sm:text-5xl">
                        운명 같은 인연,
                        <br />
                        지금 시작하세요
                    </h1>
                    <p className="mt-5 text-base leading-relaxed text-gray-500 sm:text-lg">
                        가볍게 시작하는 진지한 만남. 핸드폰 번호 하나로 바로 가입하고
                        <br className="hidden sm:block" />
                        나와 꼭 맞는 상대를 만나보세요.
                    </p>

                    <div className="mt-9 flex flex-wrap justify-center gap-3.5">
                        {auth?.user ? (
                            <Link
                                href="/home"
                                className="rounded-full bg-primary px-8 py-3.5 text-base font-bold text-white shadow-lg shadow-primary/30 transition hover:bg-primary-700"
                            >
                                추천 상대 보러가기
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href="/register"
                                    className="rounded-full bg-primary px-8 py-3.5 text-base font-bold text-white shadow-lg shadow-primary/30 transition hover:bg-primary-700"
                                >
                                    무료로 시작하기
                                </Link>
                                <Link
                                    href="/login"
                                    className="rounded-full bg-white px-8 py-3.5 text-base font-bold text-primary shadow transition hover:bg-primary-50"
                                >
                                    이미 회원이에요
                                </Link>
                            </>
                        )}
                    </div>

                    <div className="mt-14 flex flex-wrap justify-center gap-6">
                        <Feature icon="💘" title="맞춤 추천" desc="취향 기반으로 꼭 맞는 상대를 추천해요." />
                        <Feature icon="💬" title="실시간 채팅" desc="마음이 통하면 바로 대화를 시작하세요." />
                        <Feature icon="🔒" title="안심 인증" desc="핸드폰 번호 인증으로 안전하게." />
                    </div>

                    <footer className="py-5 text-center text-sm text-gray-400">
                        © {new Date().getFullYear()} 두근두근
                    </footer>
                </main>
            </div>
        </AppLayout>
    );
}

function Feature({ icon, title, desc }) {
    return (
        <div className="w-52 rounded-2xl bg-white/70 p-6 backdrop-blur">
            <div className="text-3xl">{icon}</div>
            <div className="mt-2.5 text-base font-bold">{title}</div>
            <div className="mt-1.5 text-sm leading-relaxed text-gray-500">{desc}</div>
        </div>
    );
}
