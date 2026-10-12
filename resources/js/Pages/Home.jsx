import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Home() {
    const { auth } = usePage().props;

    return (
        <AppLayout>
            <Head title="리스트" />

            <div className="px-4 py-5">
                <h1 className="text-xl font-extrabold">
                    환영합니다, {auth?.user?.name}님 👋
                </h1>
                <p className="mt-1.5 text-sm text-gray-500">
                    오늘의 추천 상대예요. 마음에 드는 상대에게 투표하거나 요청해보세요.
                </p>

                <div className="mt-5 grid grid-cols-2 gap-3">
                    {[1, 2, 3, 4, 5, 6].map((i) => (
                        <div
                            key={i}
                            className="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
                        >
                            <div className="flex aspect-[4/5] items-center justify-center bg-gradient-to-br from-primary-100 to-secondary-200 text-5xl">
                                🙂
                            </div>
                            <div className="p-3">
                                <div className="font-bold">추천 상대 {i}</div>
                                <div className="mt-0.5 text-xs text-gray-400">프로필 준비 중</div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
