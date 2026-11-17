/**
 * 폼 제출(입장·입장등록·문의 등) 처리 중 화면 가운데에 띄우는 "처리 중" 로딩 UI.
 * - show 가 true 인 동안 화면 전체를 덮어 중복 클릭을 막는다.
 * - 페이지 전환용 NavigationLoader(GET 전용)와 달리 POST 처리 중에 사용한다.
 */
export default function LoadingOverlay({ show, message = '처리 중…' }) {
    if (!show) return null;

    return (
        <div
            role="status"
            aria-live="polite"
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/20"
        >
            <div className="flex flex-col items-center gap-3 rounded-2xl bg-white/95 px-7 py-6 shadow-xl backdrop-blur">
                <span className="block h-9 w-9 animate-spin rounded-full border-[3px] border-secondary-200 border-t-primary" />
                <span className="text-sm font-semibold text-secondary-600">{message}</span>
            </div>
        </div>
    );
}
