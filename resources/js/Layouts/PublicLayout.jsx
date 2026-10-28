import { Link } from "@inertiajs/react";

/**
 * 공개(비로그인) 영역 공통 레이아웃.
 * - AppLayout 은 로그인 회원(auth)·이벤트 채널에 의존하므로 공개 페이지에서는 쓸 수 없다.
 * - 모바일 폭(max 480px) 가운데 정렬 + 상단 로고만 둔 경량 프레임.
 */
export default function PublicLayout({ children }) {
    return (
        <div className="min-h-screen bg-secondary-100 font-sans">
            <div className="relative mx-auto flex min-h-screen w-full max-w-[480px] flex-col bg-gray-50 shadow-xl">
                {/* 상단 header */}
                <header className="fixed inset-x-0 top-0 z-30">
                    <div className="mx-auto w-full max-w-[480px]">
                        <div className="flex h-header items-center justify-between rounded-b-3xl border-b-2 border-primary-200 bg-white/95 px-4 pt-[env(safe-area-inset-top)] shadow-[0_4px_16px_-6px_rgba(207,47,76,0.25)] backdrop-blur">
                            <Link href="/public/games" className="flex items-center gap-2">
                                <img
                                    src="/images/logo.png"
                                    alt="Rotable"
                                    className="h-9 w-9 object-contain"
                                />
                                <span className="font-logo text-xl leading-none tracking-wide text-black translate-y-[1px]">
                                    Rotable
                                </span>
                            </Link>
                            <span className="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">
                                공개 게임
                            </span>
                        </div>
                    </div>
                </header>

                {/* 컨텐츠 (상단 header 높이만큼 패딩) */}
                <main className="flex flex-1 flex-col bg-gray-50 pt-[calc(var(--spacing-header)+env(safe-area-inset-top))] pb-[env(safe-area-inset-bottom)]">
                    {children}
                </main>
            </div>
        </div>
    );
}
