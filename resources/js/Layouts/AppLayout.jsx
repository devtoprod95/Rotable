import { Link, router, usePage } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import useRealtimeChat from "@/useRealtimeChat";
import NavigationLoader from "@/Components/NavigationLoader";
import echo from "@/echo";

/**
 * 유저(회원) 영역 공통 모바일 레이아웃.
 * - 모바일 폭(max 480px) 가운데 정렬
 * - 상단 nav: 좌측 Rotable 로고, 우측 로그인(미로그인 시)
 * - 하단 nav: 4개 버튼, 스크롤해도 고정(플로팅)
 */
export default function AppLayout({
    children,
    bottomNav = true,
    headerAction = true,
    center = false,
}) {
    const page = usePage();
    const { auth, eventName } = page.props;
    const url = page.url;

    // 게임 탭/화면 접근은 "와인파티 && 진행중(S)" 에서만 허용된다.
    // 관리자가 매칭 시작(V)/종료(X) 등으로 상태를 바꾸면 실시간으로 반영해야 하므로,
    // 행사 채널을 구독해 상태 변동 시 nav 상태를 갱신하고,
    // 게임 화면에 머물러 있으면 홈으로 내보낸다.
    const eventId = auth?.user?.event_id ?? null;
    useEffect(() => {
        if (!eventId) {
            return undefined;
        }

        const channelName = `event-rotation.${eventId}`;
        const channel = echo.private(channelName);

        // 매칭 시작(S→V)·종료(→X): 더 이상 진행중(S)이 아니므로 게임을 닫는다.
        const closeGames = () => {
            // nav 의 게임 탭 노출 조건(gameTabVisible)을 최신화.
            router.reload({ only: ["gameTabVisible"] });
            // 게임 관련 화면에 있으면 즉시 홈으로 이동.
            if (window.location.pathname.startsWith("/games")) {
                router.visit("/home");
            }
        };

        channel.listen(".event.voting.opened", closeGames);
        channel.listen(".event.closed", closeGames);
        // 로테이션 진행(P→S 포함)으로 상태가 바뀌면 탭 노출 조건만 갱신.
        channel.listen(".rotation.updated", () => {
            router.reload({ only: ["gameTabVisible"] });
        });

        return () => {
            echo.leave(`private-${channelName}`);
        };
    }, [eventId]);

    // 헤더의 "~님" 버튼을 2번 연속 터치하면 로그아웃되는 숨김 제스처.
    const logoutTapRef = useRef({ count: 0, timer: null });
    const handleNameTap = () => {
        const s = logoutTapRef.current;
        s.count += 1;
        if (s.count >= 2) {
            s.count = 0;
            router.post("/logout");
            return;
        }
    };

    // 페이지(라우트)가 바뀌면 항상 최상단으로 되돌린다. (body 가 스크롤 컨테이너)
    useEffect(() => {
        window.scrollTo({ top: 0 });
    }, [url]);

    return (
        <div className="min-h-screen bg-secondary-100 font-sans">
            {/* 모바일 프레임. 뷰포트 높이를 고정하지 않고 body(페이지)가 스크롤되게 둔다.
                header/nav 는 position:fixed 로 뷰포트에 고정 → 모바일 Chrome 주소창/툴바 변화와 무관하게 항상 고정. */}
            <div className="relative mx-auto flex min-h-screen w-full max-w-[480px] flex-col bg-gray-50 shadow-xl">
                <FlashToast />
                <NavigationLoader />

                {/* 상단 header (뷰포트 고정) */}
                <header className="fixed inset-x-0 top-0 z-30">
                    <div className="mx-auto w-full max-w-[480px]">
                        <div className="flex h-header items-center justify-between rounded-b-3xl border-b-2 border-primary-200 bg-white/95 px-4 pt-[env(safe-area-inset-top)] shadow-[0_4px_16px_-6px_rgba(207,47,76,0.25)] backdrop-blur">
                            <Link href="/" className="flex items-center gap-2">
                                <img
                                    src="/images/logo.png"
                                    alt="Rotable"
                                    className="h-9 w-9 object-contain"
                                />
                                <span className="flex flex-col leading-none">
                                    <span className="font-logo text-xl leading-none tracking-wide text-black translate-y-[1px]">
                                        Rotable
                                    </span>
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
                                            const isFemale =
                                                auth.user.gender === "F";
                                            const toneCls = isFemale
                                                ? "border-rose-200 bg-rose-50 text-rose-600"
                                                : "border-sky-200 bg-sky-50 text-sky-600";
                                            return (
                                                <button
                                                    onClick={handleNameTap}
                                                    className={
                                                        "flex items-center gap-1.5 rounded-full border px-3 py-1 shadow-sm transition hover:shadow active:scale-95 " +
                                                        toneCls
                                                    }
                                                >
                                                    <span className="text-base leading-none">
                                                        {isFemale ? "👩" : "👨"}
                                                    </span>
                                                    <span className="flex items-baseline gap-0.5">
                                                        <span className="text-sm font-bold tracking-tight">
                                                            {auth.user.name}
                                                        </span>
                                                        <span className="text-xs font-medium opacity-70">
                                                            님
                                                        </span>
                                                    </span>
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
                        </div>
                    </div>
                </header>

                {/* 컨텐츠. fixed header/nav 에 가리지 않도록 상/하단 패딩으로 공간 확보.
                    (상단: header 높이 + safe-area-top / 하단: nav 높이 + safe-area-bottom) */}
                <main
                    className={
                        "flex flex-1 flex-col bg-gray-50 pt-[calc(var(--spacing-header)+env(safe-area-inset-top))] " +
                        (bottomNav
                            ? "pb-[calc(5.5rem+env(safe-area-inset-bottom))] "
                            : "") +
                        // center 는 pt(header) 와 pb(nav+safe) 가 비대칭이라 그냥 justify-center 하면
                        // 아래로 쏠려 보인다. 하단 보정분의 절반만큼 위로 당겨 시각적 정중앙을 맞춘다.
                        (center
                            ? "justify-center [&>*]:-mt-[calc((5.5rem+env(safe-area-inset-bottom))/2)]"
                            : "")
                    }
                >
                    {children}
                </main>

                {/* 하단 nav (뷰포트 고정) */}
                {bottomNav && <BottomNav />}
            </div>
        </div>
    );
}

const WINE_NAV_ITEMS = [
    { href: "/home", label: "홈", icon: HeartIcon },
    { href: "/chats", label: "쪽지", icon: ChatIcon },
    { href: "/votes", label: "매칭하기", icon: VoteIcon },
    { href: "/requests", label: "요청", icon: RequestIcon },
];

// 게임 탭. 와인파티가 "진행중(S)" 일 때만 노출한다.
const GAME_NAV_ITEM = { href: "/games", label: "게임", icon: GameIcon };

// 1:1 로테이션: 매칭 선택은 홈에서만, 결과는 종료 후에만.
// 메시지함/요청하기는 노출하지 않고, 매칭 탭은 "매칭 결과"로만 둔다.
const ONE_TO_ONE_NAV_ITEMS = [
    { href: "/home", label: "홈", icon: HeartIcon },
    { href: "/votes", label: "매칭 결과", icon: VoteIcon },
];

/**
 * 하단 nav 항목을 구성한다.
 * - 1:1: 고정 2개.
 * - 와인파티: 기본 4개 + (게임 탭 노출 조건 충족 시) 게임 탭.
 */
function navItemsFor(eventType, gameTabVisible) {
    if (eventType === "one") {
        return ONE_TO_ONE_NAV_ITEMS;
    }
    return gameTabVisible ? [...WINE_NAV_ITEMS, GAME_NAV_ITEM] : WINE_NAV_ITEMS;
}

function BottomNav() {
    const { url, props } = usePage();
    const navItems = navItemsFor(props.eventType, props.gameTabVisible);
    const sharedUnread = props.unreadCount ?? 0;
    const memberId = props.auth?.user?.id ?? null;

    // 서버에서 내려온 안읽음 수를 기준으로, 소켓 수신 시 실시간 증가시킨다.
    const [unreadCount, setUnreadCount] = useState(sharedUnread);
    const onChatsPage = url.startsWith("/chats");

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
        <div className="fixed inset-x-0 bottom-0 z-30">
            <div className="mx-auto w-full max-w-[480px]">
                <nav className="flex items-stretch justify-around rounded-t-3xl border-t-2 border-primary-200 bg-white px-2 pt-1.5 pb-[max(0.375rem,env(safe-area-inset-bottom))] shadow-[0_-4px_16px_-6px_rgba(207,47,76,0.25)]">
                    {navItems.map((item) => {
                        const active = url.startsWith(item.href);
                        const Icon = item.icon;
                        const badge = item.href === "/chats" ? unreadCount : 0;
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
                                aria-current={active ? "page" : undefined}
                                className={
                                    "relative flex flex-1 flex-col items-center gap-0.5 rounded-2xl py-1.5 text-[11px] transition " +
                                    (active
                                        ? "cursor-default font-extrabold text-primary"
                                        : "font-semibold text-secondary-400 hover:text-primary-400")
                                }
                            >
                                <span className="relative flex h-6 w-6 items-center justify-center">
                                    <Icon active={active} />
                                    {badge > 0 && (
                                        <span className="absolute -right-2.5 -top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold leading-none text-white">
                                            {badge > 99 ? "99+" : badge}
                                        </span>
                                    )}
                                </span>
                                <span>{item.label}</span>
                                {/* active 인디케이터 점 */}
                                <span
                                    className={
                                        "h-1 w-1 rounded-full transition " +
                                        (active
                                            ? "bg-primary"
                                            : "bg-transparent")
                                    }
                                />
                            </Link>
                        );
                    })}
                </nav>
            </div>
        </div>
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
        setToast({ message, type: flash?.error ? "error" : "success" });
        const timer = setTimeout(() => setToast(null), 2500);
        return () => clearTimeout(timer);
    }, [flash?.success, flash?.error, flash?.ts]);

    if (!toast) return null;

    const cls = toast.type === "error" ? "bg-red-500" : "bg-primary";

    return (
        <div className="pointer-events-none fixed inset-x-0 top-header z-50 mx-auto mt-3 flex w-full max-w-[480px] justify-center px-4">
            <div
                className={`pointer-events-auto rounded-full px-3 py-1.5 text-xs font-semibold text-white shadow-lg ${cls}`}
            >
                {toast.message}
            </div>
        </div>
    );
}

/* ---- 아이콘 (currentColor 사용) ---- */

function HeartIcon({ active }) {
    return (
        <svg
            width={active ? 24 : 23}
            height={active ? 24 : 23}
            viewBox="0 0 24 24"
            fill={active ? "currentColor" : "none"}
            stroke="currentColor"
            strokeWidth={active ? 2 : 1.9}
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
        </svg>
    );
}

function ChatIcon({ active }) {
    return (
        <svg
            width={active ? 24 : 23}
            height={active ? 24 : 23}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={active ? 2.4 : 1.9}
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
        </svg>
    );
}

function VoteIcon({ active }) {
    return (
        <svg
            width={active ? 24 : 23}
            height={active ? 24 : 23}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={active ? 2.4 : 1.9}
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            {/* 투표함 + 하트 (사랑 투표 느낌) */}
            <path d="M3 9h18v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9z" />
            <path
                d="M12 13.3l-.9-.86a1.8 1.8 0 0 0-2.5 2.56L12 18l3.4-3a1.8 1.8 0 0 0-2.5-2.56l-.9.86z"
                fill="currentColor"
                stroke="none"
            />
            <path d="M8 9V6a4 4 0 0 1 8 0v3" />
        </svg>
    );
}

function GameIcon({ active }) {
    return (
        <svg
            width={active ? 24 : 23}
            height={active ? 24 : 23}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={active ? 2.4 : 1.9}
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            {/* 게임패드 */}
            <path d="M6 12h4m-2-2v4" />
            <path d="M15 11.5h.01M18 13.5h.01" />
            <path d="M17.5 6h-11A4.5 4.5 0 0 0 2 10.5v3A4.5 4.5 0 0 0 6.5 18c1.5 0 2.3-.6 3-1.4l.6-.6h3.8l.6.6c.7.8 1.5 1.4 3 1.4a4.5 4.5 0 0 0 4.5-4.5v-3A4.5 4.5 0 0 0 17.5 6z" />
        </svg>
    );
}

function RequestIcon({ active }) {
    return (
        <svg
            width={active ? 24 : 23}
            height={active ? 24 : 23}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={active ? 2.4 : 1.9}
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <path d="M3 11v2a1 1 0 0 0 1 1h2l4 4V6L6 10H4a1 1 0 0 0-1 1z" />
            <path d="M14 7a5 5 0 0 1 0 10" />
            <path d="M7 18v2a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-2" />
        </svg>
    );
}
