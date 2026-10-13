import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import useRealtimeChat from '@/useRealtimeChat';
import NavigationLoader from '@/Components/NavigationLoader';

/**
 * 유저(회원) 영역 공통 모바일 레이아웃.
 * - 모바일 폭(max 480px) 가운데 정렬
 * - 상단 nav: 좌측 Rotable 로고, 우측 로그인(미로그인 시)
 * - 하단 nav: 4개 버튼, 스크롤해도 고정(플로팅)
 */
export default function AppLayout({ children, bottomNav = true, headerAction = true }) {
    const page = usePage();
    const { auth, bypassRedirectName, isProduction, eventName } = page.props;
    const url = page.url;
    const mainRef = useRef(null);

    // 페이지(라우트)가 바뀌면 스크롤 영역을 항상 최상단으로 되돌린다.
    useEffect(() => {
        mainRef.current?.scrollTo({ top: 0 });
    }, [url]);

    return (
        <div className="flex min-h-screen justify-center bg-secondary-100 font-sans">
            {/* 모바일 프레임 */}
            <div className="relative flex h-screen w-full max-w-[480px] flex-col overflow-hidden bg-white shadow-xl">
                <FlashToast />
                <NavigationLoader />
                {/* 상단 nav (고정) */}
                <header className="sticky top-0 z-20 flex h-header shrink-0 items-center justify-between border-b border-secondary-100 bg-white/95 px-4 backdrop-blur">
                    <Link href="/" className="flex items-center gap-2">
                        <img
                            src="/images/logo.png"
                            alt="Rotable"
                            className="h-9 w-9 object-contain"
                        />
                        <span className="flex flex-col leading-none">
                            <span className="font-logo text-xl leading-none tracking-wide text-black translate-y-[1px]">Rotable</span>
                            {eventName && (
                                <span className="mt-0.5 max-w-[150px] truncate text-[11px] font-medium leading-none text-secondary-400">
                                    {eventName}
                                </span>
                            )}
                        </span>
                    </Link>

                    {headerAction && (
                        <div className="flex items-center">
                            {auth?.user ? (
                                (() => {
                                    const isFemale = auth.user.gender === 'F';
                                    const toneCls = isFemale
                                        ? 'border-rose-200 bg-rose-50 text-rose-600'
                                        : 'border-sky-200 bg-sky-50 text-sky-600';
                                    return (
                                        <button
                                            onClick={() => {
                                                if (auth.user.name === bypassRedirectName || !isProduction) {
                                                    router.post('/logout');
                                                }
                                            }}
                                            className={
                                                'flex items-center gap-1.5 rounded-full border px-3 py-1 shadow-sm transition hover:shadow active:scale-95 ' +
                                                toneCls
                                            }
                                        >
                                            <span className="text-base leading-none">{isFemale ? '👩' : '👨'}</span>
                                            <span className="text-sm font-bold tracking-tight">{auth.user.name}</span>
                                            <span className="text-xs font-medium opacity-70">님</span>
                                        </button>
                                    );
                                })()
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
                <main ref={mainRef} className={'flex flex-1 flex-col overflow-y-auto bg-gray-50 ' + (bottomNav ? 'pb-20' : '')}>
                    {children}
                </main>

                {/* 하단 nav (플로팅 고정) */}
                {bottomNav && <BottomNav />}
            </div>
        </div>
    );
}

const NAV_ITEMS = [
    { href: '/home', label: '홈', icon: HeartIcon },
    { href: '/chats', label: '메시지함', icon: ChatIcon },
    { href: '/votes', label: '매칭하기', icon: VoteIcon },
    { href: '/requests', label: '요청하기', icon: RequestIcon },
];

function BottomNav() {
    const { url, props } = usePage();
    const sharedUnread = props.unreadCount ?? 0;
    const memberId = props.auth?.user?.id ?? null;

    // 서버에서 내려온 안읽음 수를 기준으로, 소켓 수신 시 실시간 증가시킨다.
    const [unreadCount, setUnreadCount] = useState(sharedUnread);
    const onChatsPage = url.startsWith('/chats');

    // 페이지 이동(Inertia)으로 shared prop 이 갱신되면 동기화.
    useEffect(() => {
        setUnreadCount(sharedUnread);
    }, [sharedUnread]);

    // 메시지함 화면에 있으면 뱃지는 0으로 유지.
    useEffect(() => {
        if (onChatsPage) {
            setUnreadCount(0);
        }
    }, [onChatsPage]);

    // 소켓으로 새 메시지가 오면, 메시지함 밖일 때만 뱃지를 올린다.
    useRealtimeChat(memberId, () => {
        if (!onChatsPage) {
            setUnreadCount((n) => n + 1);
        }
    });

    return (
        <nav className="pointer-events-none fixed inset-x-0 bottom-0 z-30 mx-auto flex w-full max-w-[480px] justify-center pb-3">
            <div className="pointer-events-auto flex w-[92%] max-w-[440px] items-stretch justify-around rounded-2xl border border-secondary-100 bg-white/95 px-2 py-2 shadow-[0_8px_30px_rgba(0,0,0,0.12)] backdrop-blur">
                {NAV_ITEMS.map((item) => {
                    const active = url.startsWith(item.href);
                    const Icon = item.icon;
                    const badge = item.href === '/chats' ? unreadCount : 0;
                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            // 이미 active 인 탭을 다시 누르면 아무 동작도 하지 않는다.
                            onClick={(e) => {
                                if (active) {
                                    e.preventDefault();
                                }
                            }}
                            aria-current={active ? 'page' : undefined}
                            className={
                                'relative flex flex-1 flex-col items-center gap-1 rounded-xl py-1.5 text-[11px] font-semibold transition ' +
                                (active
                                    ? 'cursor-default text-primary'
                                    : 'text-secondary-400 hover:text-secondary-600')
                            }
                        >
                            <span className="relative">
                                <Icon active={active} />
                                {badge > 0 && (
                                    <span className="absolute -right-2.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold leading-none text-white">
                                        {badge > 99 ? '99+' : badge}
                                    </span>
                                )}
                            </span>
                            {item.label}
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}

/**
 * 전역 flash 토스트. 모든 회원 페이지에서 success/error flash 를 상단에 잠깐 노출한다.
 */
function FlashToast() {
    const { flash } = usePage().props;
    const [toast, setToast] = useState(null);

    useEffect(() => {
        const message = flash?.success || flash?.error;
        if (!message) return undefined;
        setToast({ message, type: flash?.error ? 'error' : 'success' });
        const timer = setTimeout(() => setToast(null), 2500);
        return () => clearTimeout(timer);
    }, [flash?.success, flash?.error]);

    if (!toast) return null;

    const cls = toast.type === 'error' ? 'bg-red-500' : 'bg-primary';

    return (
        <div className="pointer-events-none fixed inset-x-0 top-header z-50 mx-auto mt-3 flex w-full max-w-[480px] justify-center px-4">
            <div className={`pointer-events-auto rounded-full px-3 py-1.5 text-xs font-semibold text-white shadow-lg ${cls}`}>
                {toast.message}
            </div>
        </div>
    );
}

/* ---- 아이콘 (currentColor 사용) ---- */

function HeartIcon() {
    return (
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
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
            {/* 투표함 + 하트 (사랑 투표 느낌) */}
            <path d="M3 9h18v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9z" />
            <path d="M12 13.3l-.9-.86a1.8 1.8 0 0 0-2.5 2.56L12 18l3.4-3a1.8 1.8 0 0 0-2.5-2.56l-.9.86z" fill="currentColor" stroke="none" />
            <path d="M8 9V6a4 4 0 0 1 8 0v3" />
        </svg>
    );
}

function RequestIcon() {
    return (
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M3 11v2a1 1 0 0 0 1 1h2l4 4V6L6 10H4a1 1 0 0 0-1 1z" />
            <path d="M14 7a5 5 0 0 1 0 10" />
            <path d="M7 18v2a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-2" />
        </svg>
    );
}
