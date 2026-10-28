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
                                미니게임
                            </span>
                        </div>
                    </div>
                </header>

                {/* 컨텐츠 (상단 header 높이만큼 패딩) */}
                <main className="flex flex-1 flex-col bg-gray-50 pt-[calc(var(--spacing-header)+env(safe-area-inset-top))]">
                    {children}
                </main>

                {/* 푸터 — 만든이 크레딧 */}
                <footer className="bg-gray-50 px-4 pb-[calc(1.25rem+env(safe-area-inset-bottom))] pt-4 text-center">
                    <div className="mx-auto mb-3 h-px w-10 bg-secondary-200" />
                    <p className="text-[11px] font-medium tracking-wide text-secondary-400">
                        made with <span className="text-primary">♥</span> by{" "}
                        <span className="font-bold tracking-normal text-secondary-600">
                            Hando Kim
                        </span>
                    </p>
                    <a
                        href="mailto:devtoproduction@gmail.com"
                        className="mt-1 inline-block text-[11px] font-medium tracking-wide text-secondary-400 underline-offset-2 hover:text-primary hover:underline"
                    >
                        devtoproduction@gmail.com
                    </a>
                </footer>
            </div>
        </div>
    );
}
