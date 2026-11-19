import { Link, router, usePage } from "@inertiajs/react";
import { createContext, useContext, useEffect, useLayoutEffect, useRef, useState } from "react";
import { cancelPendingReload, requestReload, useRealtimeConnection, useRealtimeEvent } from "@/realtime";
import NavigationLoader from "@/Components/NavigationLoader";
import ConfirmModal from "@/Components/ConfirmModal";
import echo from "@/echo";

/**
 * 유저(회원) 영역 공통 모바일 레이아웃.
 * - 모바일 폭(max 480px) 가운데 정렬
 * - 상단 nav: 좌측 Rotable 로고, 우측 로그인(미로그인 시)
 * - 하단 nav: 4개 버튼, 스크롤해도 고정(플로팅)
 *
 * 구조: AppShell 은 app.jsx 에서 "지속 레이아웃"으로 한 번만 마운트되어 페이지를 옮겨도 header/nav/소켓 구독이
 * 다시 만들어지지 않는다(깜빡임 방지). 각 페이지가 쓰는 <AppLayout center ...> 는 화면 옵션만 AppShell 에 알리고
 * 자식을 그대로 그린다.
 */
const LayoutOptionsContext = createContext(null);

const DEFAULT_OPTIONS = { bottomNav: true, headerAction: true, center: false };

/**
 * 각 페이지가 쓰는 레이아웃 래퍼. 화면 옵션을 지속 레이아웃(AppShell)에 전달한다.
 * (AppShell 밖에서 단독으로 쓰이면 스스로 AppShell 로 감싼다.)
 */
export default function AppLayout({
    children,
    bottomNav = true,
    headerAction = true,
    center = false,
}) {
    const setOptions = useContext(LayoutOptionsContext);

    // 화면이 그려지기 전에 옵션을 반영해, 페이지 전환 때 nav/header 가 잠깐 바뀌어 보이는 현상을 막는다.
    useLayoutEffect(() => {
        setOptions?.((prev) =>
            prev.bottomNav === bottomNav && prev.headerAction === headerAction && prev.center === center
                ? prev
                : { bottomNav, headerAction, center },
        );
    }, [setOptions, bottomNav, headerAction, center]);

    if (!setOptions) {
        return (
            <AppShell initialOptions={{ bottomNav, headerAction, center }}>
                {children}
            </AppShell>
        );
    }

    return <>{children}</>;
}

/** header · 하단 nav · 컨텐츠 틀. 페이지가 바뀌어도 유지된다. */
export function AppShell({ children, initialOptions = DEFAULT_OPTIONS }) {
    const [options, setOptions] = useState(initialOptions);
    const { bottomNav, headerAction, center } = options;
    const page = usePage();
    const { auth, eventName, adminImpersonating, accessExpiresAt } = page.props;
    const url = page.url;

    // 실시간 채널 구독은 여기(지속 레이아웃) 한 곳에서만 한다. 화면들은 useRealtimeEvent 로 이벤트만 받는다.
    const eventId = auth?.user?.event_id ?? null;
    useRealtimeConnection(auth?.user?.id ?? null, eventId);

    // 게임 탭/화면 접근은 "와인파티 && 진행중(S)" 에서만 허용되고, 어필 탭은 1:1 에서 상태에 따라 열린다.
    // 관리자가 로테이션 진행·매칭 시작(V)/종료(X)로 상태를 바꾸면 nav 탭 노출 조건을 갱신한다.
    // 매칭 시작·종료 때 게임 화면에 머물러 있으면 홈으로 내보낸다. (이동하면서 새 props 를 받으므로 재조회는 취소)
    // 안읽음 수(unreadCount)도 함께 받는다. (1:1 종료를 되돌리면 어필이 다시 잠기므로 뱃지가 사라져야 한다)
    const refreshNavTabs = () => requestReload(["gameTabVisible", "noteTabVisible", "unreadCount"]);
    const closeGames = () => {
        if (window.location.pathname.startsWith("/games")) {
            cancelPendingReload();
            router.visit("/home");
            return;
        }
        refreshNavTabs();
    };
    useRealtimeEvent("rotation.updated", refreshNavTabs);
    useRealtimeEvent("event.voting.opened", closeGames);
    useRealtimeEvent("event.closed", closeGames);

    // 실시간 이벤트 유실 보정.
    // 모바일에서 화면 잠금·네트워크 전환으로 WebSocket 이 끊기면 그 사이의 이벤트(턴 진행·매칭 시작·종료 등)를
    // 받지 못한다. 이벤트는 "변경 신호"일 뿐이므로, 연결이 복구되거나 탭이 다시 보일 때
    // 서버에서 현재 페이지의 props 를 다시 조회해 화면을 따라잡는다.
    const visitingRef = useRef(false);
    const lastSyncRef = useRef(0);
    const hiddenAtRef = useRef(null);
    useEffect(() => {
        if (!eventId) {
            return undefined;
        }

        const MIN_INTERVAL_MS = 3000; // 연속 재조회 방지
        const MIN_HIDDEN_MS = 5000; // 잠깐 탭 전환은 무시

        const resync = () => {
            const now = Date.now();
            // 진행 중인 요청(폼 전송 등)을 끊지 않도록 건너뛴다.
            if (visitingRef.current || now - lastSyncRef.current < MIN_INTERVAL_MS) {
                return;
            }
            lastSyncRef.current = now;
            router.reload();
        };

        const offStart = router.on("start", () => {
            visitingRef.current = true;
        });
        const offFinish = router.on("finish", () => {
            visitingRef.current = false;
        });

        // 연결이 끊겼다가 "다시" 연결된 시점에만 재조회한다. (최초 연결은 제외)
        const connection = echo.connector?.pusher?.connection;
        let everConnected = connection?.state === "connected";
        const onStateChange = ({ current }) => {
            if (current !== "connected") {
                return;
            }
            if (everConnected) {
                resync();
            }
            everConnected = true;
        };
        connection?.bind("state_change", onStateChange);

        // 탭/앱이 다시 보일 때: 충분히 오래 가려져 있었다면 재조회.
        const onVisibility = () => {
            if (document.visibilityState === "hidden") {
                hiddenAtRef.current = Date.now();
                return;
            }
            const hiddenAt = hiddenAtRef.current;
            hiddenAtRef.current = null;
            if (hiddenAt !== null && Date.now() - hiddenAt >= MIN_HIDDEN_MS) {
                resync();
            }
        };
        document.addEventListener("visibilitychange", onVisibility);

        return () => {
            offStart();
            offFinish();
            connection?.unbind("state_change", onStateChange);
            document.removeEventListener("visibilitychange", onVisibility);
        };
    }, [eventId]);

    // 헤더의 로그아웃 버튼. 실수로 누르는 것을 막기 위해 공통 확인 모달로 한 번 확인한다.
    const [logoutOpen, setLogoutOpen] = useState(false);
    const handleLogout = () => setLogoutOpen(true);
    const confirmLogout = () => {
        setLogoutOpen(false);
        router.post("/logout");
    };

    // 페이지(라우트)가 바뀌면 항상 최상단으로 되돌린다. (body 가 스크롤 컨테이너)
    // useLayoutEffect: 새 화면이 그려지기 "전에" 스크롤을 올려, 이전 스크롤 위치에서 새 화면이 한 프레임 보였다가
    // 위로 튀는 깜빡임을 막는다. (useEffect 는 화면이 그려진 뒤에 실행된다)
    useLayoutEffect(() => {
        window.scrollTo(0, 0);
    }, [url]);

    return (
        <LayoutOptionsContext.Provider value={setOptions}>
        <div className="min-h-screen bg-secondary-100 font-sans">
            {/* 모바일 프레임. 뷰포트 높이를 고정하지 않고 body(페이지)가 스크롤되게 둔다.
                header/nav 는 position:fixed 로 뷰포트에 고정 → 모바일 Chrome 주소창/툴바 변화와 무관하게 항상 고정. */}
            <div className="relative mx-auto flex min-h-screen w-full max-w-[480px] flex-col bg-gray-50 shadow-xl">
                <ConfirmModal
                    open={logoutOpen}
                    icon="👋"
                    title="로그아웃 할까요?"
                    confirmText="로그아웃"
                    cancelText="취소"
                    onConfirm={confirmLogout}
                    onClose={() => setLogoutOpen(false)}
                />
                <FlashToast />
                <NavigationLoader />

                {/* 상단 header (뷰포트 고정) */}
                <header className="vt-header fixed inset-x-0 top-0 z-30">
                    <div className="mx-auto w-full max-w-[480px]">
                        <div
                            className={
                                "relative flex h-header items-center justify-between rounded-b-3xl border-b-2 border-primary-200 px-4 pt-[env(safe-area-inset-top)] shadow-[0_4px_16px_-6px_rgba(207,47,76,0.25)] backdrop-blur " +
                                // 접속 카운트다운 줄이 header 뒤로 이어질 때는 비쳐 보이지 않도록 불투명 흰색으로 한다.
                                (accessExpiresAt ? "bg-white" : "bg-white/95")
                            }
                        >
                            {adminImpersonating && (
                                <span className="pointer-events-none absolute left-1/2 top-[env(safe-area-inset-top)] z-10 -translate-x-1/2 whitespace-nowrap rounded-b-md bg-amber-500 px-2 py-0.5 text-[10px] font-bold leading-none text-white shadow">
                                    🔧 관리자 대리 로그인 중
                                </span>
                            )}
                            <Link href="/" className="flex items-center gap-2">
                                <img
                                    src="/images/logo-sm.png"
                                    alt="Rotable"
                                    width="36"
                                    height="36"
                                    decoding="async"
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
                                                <div className="flex items-center gap-1.5">
                                                <div
                                                    className={
                                                        "flex items-center gap-1.5 rounded-full border px-3 py-1 shadow-sm " +
                                                        toneCls
                                                    }
                                                >
                                                    <span className="text-base leading-none">
                                                        {isFemale ? "👩" : "👨"}
                                                    </span>
                                                    <span className="flex items-baseline gap-0.5">
                                                        <span className="text-sm font-bold tracking-tight">
                                                            {auth.user.name}호
                                                        </span>
                                                        <span className="text-xs font-medium opacity-70">
                                                            님
                                                        </span>
                                                    </span>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={handleLogout}
                                                    title="로그아웃"
                                                    aria-label="로그아웃"
                                                    className="flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:text-primary hover:shadow active:scale-95"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        strokeWidth="2"
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        className="h-4 w-4"
                                                        aria-hidden="true"
                                                    >
                                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                                        <path d="M16 17l5-5-5-5" />
                                                        <path d="M21 12H9" />
                                                    </svg>
                                                </button>
                                                </div>
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

                {/* 종료된 행사: 접속 가능 시간 카운트다운 (header 바로 아래 고정) */}
                {accessExpiresAt && <AccessCountdown expiresAt={accessExpiresAt} />}

                {/* 컨텐츠. fixed header/nav 에 가리지 않도록 상/하단 패딩으로 공간 확보.
                    (상단: header 높이 + safe-area-top / 하단: nav 높이 + safe-area-bottom) */}
                <main
                    className={
                        "flex flex-1 flex-col bg-gray-50 " +
                        (accessExpiresAt
                            ? "pt-[calc(var(--spacing-header)+env(safe-area-inset-top)+4.25rem)] "
                            : "pt-[calc(var(--spacing-header)+env(safe-area-inset-top))] ") +
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
        </LayoutOptionsContext.Provider>
    );
}

/**
 * 종료된 행사의 접속 가능 시간 카운트다운.
 * 서버 시각 기준으로 남은 시간을 계산해(기기 시계가 틀려도 정확) 1초마다 갱신한다.
 * 0 이 되면 서버가 접속을 막으므로 페이지를 다시 불러와 로그인 화면으로 보낸다.
 */
function AccessCountdown({ expiresAt }) {
    const { props } = usePage();
    const serverNow = props.serverNow ?? Date.now();
    const target = new Date(expiresAt).getTime();

    // 서버 시각과 기기 시각의 차이. 서버가 새 시각을 내려줄 때(serverNow 가 바뀔 때)만 다시 맞춘다.
    // (렌더 때마다 다시 계산하면 흐른 시간만큼 차이가 줄어 카운트가 멈춘 것처럼 보인다.)
    const offsetRef = useRef(serverNow - Date.now());
    useEffect(() => {
        offsetRef.current = serverNow - Date.now();
    }, [serverNow]);

    const [remaining, setRemaining] = useState(() => target - (Date.now() + offsetRef.current));

    useEffect(() => {
        const tick = () => {
            const left = target - (Date.now() + offsetRef.current);
            setRemaining(left);
            if (left <= 0) {
                clearInterval(timer);
                router.reload();
            }
        };
        const timer = setInterval(tick, 1000);
        tick();
        return () => clearInterval(timer);
    }, [target]);

    const total = Math.max(0, Math.floor(remaining / 1000));
    const days = Math.floor(total / 86400);
    const hh = String(Math.floor((total % 86400) / 3600)).padStart(2, "0");
    const mm = String(Math.floor((total % 3600) / 60)).padStart(2, "0");
    const ss = String(total % 60).padStart(2, "0");
    const urgent = total < 3600; // 1시간 미만이면 강조

    const barTone = urgent ? "bg-rose-600 text-white" : "bg-amber-100 text-amber-900";

    // header 아래 모서리가 둥글어서 바로 밑에 붙이면 모서리 뒤로 배경이 비쳐 경계가 어색하다.
    // 그래서 카운트다운 줄을 header 뒤로 1.5rem 밀어 넣고(위쪽 여백), 같은 색이 둥근 모서리 뒤를 채우게 한다.
    return (
        <div className="fixed inset-x-0 top-[calc(var(--spacing-header)+env(safe-area-inset-top)-1.5rem)] z-20">
            <div className="mx-auto w-full max-w-[480px]">
                <div className={"relative z-10 rounded-b-3xl pt-6 shadow-sm " + barTone}>
                    <div className="flex h-8 items-center justify-center gap-1.5 px-3 text-[12px] font-bold">
                        <span>⏰ 접속 가능 시간</span>
                        <span className="font-extrabold tabular-nums">
                            {days > 0 ? `${days}일 ` : ""}
                            {hh}:{mm}:{ss}
                        </span>
                        <span>남음 · 이후 접속 불가</span>
                    </div>
                </div>
                {/* 폐기 안내 줄: 위 줄 뒤로 1rem 밀어 넣어, header → 카운트다운 줄 → 안내 줄이 겹겹이 둥글게 이어지게 한다. */}
                <div className="-mt-4 rounded-b-3xl bg-white px-3 pb-2 pt-5 text-center text-[12px] font-bold text-gray-800 shadow-md">
                    🔒 마감 후 입력하신 개인정보와 모든 데이터는 안전하게 폐기될 예정이에요.
                </div>
            </div>
        </div>
    );
}

const WINE_NAV_ITEMS = [
    { href: "/home", label: "홈", icon: HeartIcon },
    { href: "/chats", label: "메시지", icon: ChatIcon },
    { href: "/votes", label: "매칭하기", icon: VoteIcon },
    { href: "/requests", label: "요청", icon: RequestIcon },
];

// 게임 탭. 와인파티가 "진행중(S)" 일 때만 노출한다.
const GAME_NAV_ITEM = { href: "/games", label: "게임", icon: GameIcon };

// 1:1 로테이션: 매칭 선택은 홈에서만, 결과는 종료 후에만.
// 메시지함/요청하기는 노출하지 않고, 매칭 탭은 "매칭 결과"로만 둔다.
const ONE_TO_ONE_NAV_ITEMS = [
    { href: "/home", label: "홈", icon: HeartIcon },
    { href: "/questions", label: "질문카드", icon: QuestionIcon },
    { href: "/votes", label: "매칭 결과", icon: VoteIcon },
];

// 어필 탭. 1:1 소개팅이면 늘 보이고, 이용은 종료(X) 뒤에만 가능하다. (매칭되지 않은 이성에게 1회, 번호표 공개)
const NOTE_NAV_ITEM = { href: "/notes", label: "어필", icon: ChatIcon };

/**
 * 하단 nav 항목을 구성한다.
 * - 1:1: 고정 2개.
 * - 와인파티: 기본 4개 + (게임 탭 노출 조건 충족 시) 게임 탭.
 */
function navItemsFor(eventType, gameTabVisible, noteTabVisible) {
    if (eventType === "one") {
        return noteTabVisible ? [...ONE_TO_ONE_NAV_ITEMS, NOTE_NAV_ITEM] : ONE_TO_ONE_NAV_ITEMS;
    }
    return gameTabVisible ? [...WINE_NAV_ITEMS, GAME_NAV_ITEM] : WINE_NAV_ITEMS;
}

function BottomNav() {
    const { url, props } = usePage();
    const navItems = navItemsFor(props.eventType, props.gameTabVisible, props.noteTabVisible);
    const sharedUnread = props.unreadCount ?? 0;

    // 서버에서 내려온 안읽음 수를 기준으로, 소켓 수신 시 실시간 증가시킨다.
    const [unreadCount, setUnreadCount] = useState(sharedUnread);
    // 메시지함(/chats)과 어필(/notes)은 같은 메시지 데이터를 쓰므로 안읽음 뱃지를 함께 다룬다.
    const onChatsPage = url.startsWith("/chats") || url.startsWith("/notes");
    // 관리자 대리 로그인 중에는 메시지가 읽음 처리되지 않으므로, 메시지함/어필 화면에서도 뱃지를 그대로 유지한다.
    const keepBadge = props.adminImpersonating === true;

    // 페이지 이동(Inertia)으로 shared prop 이 갱신되면 동기화.
    useEffect(() => {
        setUnreadCount(sharedUnread);
    }, [sharedUnread]);

    // 메시지함 화면에 있으면 뱃지는 0으로 유지. (대리 로그인 중에는 제외)
    useEffect(() => {
        if (onChatsPage && !keepBadge) {
            setUnreadCount(0);
        }
    }, [onChatsPage, keepBadge]);

    // 행사 상태가 바뀌면(로테이션 진행·되돌리기, 매칭 시작/종료·종료 되돌리기) 소켓으로 더해 둔 수를 버리고 서버 값으로 맞춘다.
    // AppShell 이 같은 신호에 unreadCount 를 다시 받으므로, 값이 같아 동기화 effect 가 안 도는 경우에도 서버 값이 맞게 남는다.
    // (예: 1:1 종료를 되돌리면 어필이 다시 잠겨 서버가 0 을 준다 → 뱃지가 사라진다)
    const sharedUnreadRef = useRef(sharedUnread);
    sharedUnreadRef.current = sharedUnread;
    const resetUnreadToServer = () => setUnreadCount(onChatsPage && !keepBadge ? 0 : sharedUnreadRef.current);
    useRealtimeEvent("rotation.updated", resetUnreadToServer);
    useRealtimeEvent("event.voting.opened", resetUnreadToServer);
    useRealtimeEvent("event.closed", resetUnreadToServer);

    // 소켓으로 새 메시지가 오면, 메시지함 밖일 때만 뱃지를 올린다. (대리 로그인 중에는 화면과 무관하게 올린다)
    useRealtimeEvent("message.sent", () => {
        if (!onChatsPage || keepBadge) {
            setUnreadCount((n) => n + 1);
        }
    });

    // '요청' 탭 뱃지 - 아직 확인하지 않은 관리자 답장(요청 id 기준, 같은 답장 수정 시 중복 카운트 방지).
    const sharedReplyIds = props.unreadReplyIds ?? [];
    const sharedReplyKey = sharedReplyIds.join(",");
    const [replyIds, setReplyIds] = useState(() => new Set(sharedReplyIds));
    const onRequestsPage = url.startsWith("/requests");

    // 페이지 이동으로 shared prop 이 갱신되면 동기화. (요청 화면 진입 시 서버가 확인 처리 → 빈 목록)
    useEffect(() => {
        setReplyIds(new Set(sharedReplyIds));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sharedReplyKey]);

    // 요청 화면에 있으면 뱃지는 0으로 유지. (대리 로그인 중에는 답장이 확인 처리되지 않으므로 유지)
    useEffect(() => {
        if (onRequestsPage && !keepBadge) {
            setReplyIds(new Set());
        }
    }, [onRequestsPage, keepBadge]);

    // 관리자가 답장(또는 수정)하면, 요청 화면 밖일 때만 뱃지를 올린다.
    useRealtimeEvent("request.replied", ({ id }) => {
        if (onRequestsPage) {
            return;
        }
        setReplyIds((prev) => new Set(prev).add(id));
    });

    return (
        <div className="vt-nav fixed inset-x-0 bottom-0 z-30">
            <div className="mx-auto w-full max-w-[480px]">
                <nav className="flex items-stretch justify-around rounded-t-3xl border-t-2 border-primary-200 bg-white px-2 pt-1.5 pb-[max(0.375rem,env(safe-area-inset-bottom))] shadow-[0_-4px_16px_-6px_rgba(207,47,76,0.25)]">
                    {navItems.map((item) => {
                        const active = url.startsWith(item.href);
                        const Icon = item.icon;
                        const badge =
                            item.href === "/chats" || item.href === "/notes"
                                ? unreadCount
                                : item.href === "/requests"
                                  ? replyIds.size
                                  : 0;
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                // 터치/클릭이 시작되는 순간(손가락을 떼기 전) 요청을 먼저 보내 전환을 빠르게 한다.
                                // click 모드는 결과를 캐시에 남기지 않고 곧바로 쓰므로, 턴·매칭 상태가 낡을 일이 없다.
                                prefetch="click"
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

function QuestionIcon({ active }) {
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
            {/* 카드 + 물음표 */}
            <rect x="4" y="3" width="16" height="18" rx="3" />
            <path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .8-1 1.5" />
            <path d="M12 16.5h.01" />
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
