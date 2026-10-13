import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';

/**
 * 페이지 전환(Inertia) 중 화면 가운데에 부드러운 로딩 스피너를 띄운다.
 * - 상단 progress 바 대신 사용(app.jsx 에서 progress:false).
 * - 아주 빠른 전환에서는 깜빡임을 막기 위해 DELAY(ms) 이후에만 노출한다.
 */
const DELAY = 300;

export default function NavigationLoader() {
    const [visible, setVisible] = useState(false);
    const timerRef = useRef(null);

    useEffect(() => {
        const clearTimer = () => {
            if (timerRef.current) {
                clearTimeout(timerRef.current);
                timerRef.current = null;
            }
        };

        const onStart = (event) => {
            // 페이지 전환(GET)에서만 스피너를 띄운다.
            // 메시지 전송/읽음 처리 같은 POST·부분요청에서는 띄우지 않는다.
            const visit = event?.detail?.visit;
            const method = visit?.method ?? 'get';
            const isPartial = Array.isArray(visit?.only) && visit.only.length > 0;
            if (method !== 'get' || isPartial) {
                return;
            }
            clearTimer();
            timerRef.current = setTimeout(() => setVisible(true), DELAY);
        };
        const onFinish = () => {
            clearTimer();
            setVisible(false);
        };

        const offStart = router.on('start', onStart);
        const offFinish = router.on('finish', onFinish);

        return () => {
            clearTimer();
            offStart();
            offFinish();
        };
    }, []);

    if (!visible) return null;

    return (
        <div className="pointer-events-none fixed inset-0 z-50 flex items-center justify-center">
            <div className="pointer-events-auto flex flex-col items-center gap-3 rounded-2xl bg-white/90 px-6 py-5 shadow-xl backdrop-blur">
                <span className="block h-9 w-9 animate-spin rounded-full border-[3px] border-secondary-200 border-t-primary" />
                <span className="text-xs font-semibold text-secondary-500">불러오는 중…</span>
            </div>
        </div>
    );
}
