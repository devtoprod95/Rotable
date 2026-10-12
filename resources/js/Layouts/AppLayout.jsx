import { Link, router, usePage } from '@inertiajs/react';

/**
 * 유저(회원) 영역 공통 모바일 레이아웃.
 * - 모바일 폭(max 480px) 가운데 정렬
 * - 상단 nav: 좌측 Rotable 로고, 우측 로그인(미로그인 시)
 * - 하단 nav: 4개 버튼, 스크롤해도 고정(플로팅)
 */
export default function AppLayout({ children, bottomNav = true, headerAction = true }) {
    const { auth } = usePage().props;

    return (
        <div className="flex min-h-screen justify-center bg-secondary-100 font-sans">
            {/* 모바일 프레임 */}
            <div className="relative flex min-h-screen w-full max-w-[480px] flex-col bg-white shadow-xl">
                {/* 상단 nav (고정) */}
                <header className="sticky top-0 z-20 flex h-header items-center justify-between border-b border-secondary-100 bg-white/95 px-4 backdrop-blur">
                    <Link href="/" className="flex items-center gap-2">
                        <img
                            src="/images/logo.png"
                            alt="Rotable"
                            className="h-9 w-9 object-contain"
                        />
                        <span className="text-lg font-extrabold tracking-wide text-primary">Rotable</span>
                    </Link>

                    {headerAction && (
                        <div className="flex items-center">
                            {auth?.user ? (
                                <button
                                    onClick={() => router.post('/logout')}
                                    className="text-sm font-medium text-secondary-600 transition hover:text-primary"
                                >
                                    {auth.user.name}님
                                </button>
                            ) : (
                                <Link
                                    href="/login"
                                    className="rounded-full bg-primary px-4 py-1.5 text-sm font-semibold text-white transition hover:bg-primary-600"
                                >
                                    로그인
                                </Link>
                            )}
                        </div>
                    )}
                </header>

                {/* 컨텐츠 (스크롤 영역). 하단 nav 높이만큼 패딩 확보 */}
                <main className={'flex-1 overflow-y-auto ' + (bottomNav ? 'pb-20' : '')}>
                    {children}
                </main>

                {/* 하단 nav (플로팅 고정) */}
                {bottomNav && <BottomNav />}
            </div>
        </div>
    );
}

const NAV_ITEMS = [
    { href: '/home', label: '리스트', icon: ListIcon },
    { href: '/chats', label: '채팅함', icon: ChatIcon },
    { href: '/votes', label: '투표하기', icon: VoteIcon },
    { href: '/requests', label: '요청하기', icon: RequestIcon },
];

function BottomNav() {
    const { url } = usePage();

    return (
        <nav className="pointer-events-none fixed inset-x-0 bottom-0 z-30 flex justify-center pb-3">
            <div className="pointer-events-auto flex w-[92%] max-w-[440px] items-stretch justify-around rounded-2xl border border-secondary-100 bg-white/95 px-2 py-2 shadow-[0_8px_30px_rgba(0,0,0,0.12)] backdrop-blur">
                {NAV_ITEMS.map((item) => {
                    const active = url.startsWith(item.href);
                    const Icon = item.icon;
                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={
                                'flex flex-1 flex-col items-center gap-1 rounded-xl py-1.5 text-[11px] font-semibold transition ' +
                                (active ? 'text-primary' : 'text-secondary-400 hover:text-secondary-600')
                            }
                        >
                            <Icon active={active} />
                            {item.label}
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}

/* ---- 아이콘 (currentColor 사용) ---- */

function ListIcon() {
    return (
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <line x1="8" y1="6" x2="21" y2="6" />
            <line x1="8" y1="12" x2="21" y2="12" />
            <line x1="8" y1="18" x2="21" y2="18" />
            <line x1="3" y1="6" x2="3.01" y2="6" />
            <line x1="3" y1="12" x2="3.01" y2="12" />
            <line x1="3" y1="18" x2="3.01" y2="18" />
        </svg>
    );
}

function ChatIcon() {
    return (
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
        </svg>
    );
}

function VoteIcon() {
    return (
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M9 11l3 3L22 4" />
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
        </svg>
    );
}

function RequestIcon() {
    return (
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M12 5v14" />
            <path d="M5 12h14" />
        </svg>
    );
}
