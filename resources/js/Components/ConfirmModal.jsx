import { createPortal } from 'react-dom';
import useBodyScrollLock from '@/useBodyScrollLock';

/**
 * 확인/취소 모달. 브라우저 기본 confirm() 대신 서비스 공통 모달 스타일로 확인을 받는다.
 * body 로 포탈해 header 처럼 backdrop-filter 가 걸린 영역 안에서 열어도 화면 전체 기준으로 뜬다.
 *
 * @param {boolean}  open          열림 여부
 * @param {string}   title         제목
 * @param {string}   message       본문 (선택)
 * @param {string}   confirmText   확인 버튼 문구 (기본: '확인')
 * @param {string}   cancelText    취소 버튼 문구 (기본: '취소')
 * @param {string}   icon          제목 위 이모지 (기본: '❓')
 * @param {Function} onConfirm     확인 핸들러
 * @param {Function} onClose       취소/바깥 클릭 핸들러
 */
export default function ConfirmModal({
    open,
    title,
    message = '',
    confirmText = '확인',
    cancelText = '취소',
    icon = '❓',
    onConfirm,
    onClose,
}) {
    useBodyScrollLock(open);

    if (!open) return null;

    return createPortal(
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-6"
            onClick={onClose}
            role="dialog"
            aria-modal="true"
        >
            <div
                className="w-full max-w-sm overflow-hidden rounded-3xl bg-white shadow-2xl"
                onClick={(e) => e.stopPropagation()}
            >
                <div className="flex flex-col items-center gap-3 px-7 pt-8 pb-2 text-center">
                    <div className="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-3xl">
                        {icon}
                    </div>
                    <h2 className="text-xl font-extrabold text-secondary-800">{title}</h2>
                    {message && <p className="text-base leading-relaxed text-secondary-600">{message}</p>}
                </div>

                <div className="flex gap-2 px-7 pb-7 pt-5">
                    <button
                        type="button"
                        onClick={onClose}
                        className="w-full rounded-full bg-secondary-100 py-3 text-base font-bold text-secondary-600 transition hover:bg-secondary-200"
                    >
                        {cancelText}
                    </button>
                    <button
                        type="button"
                        onClick={onConfirm}
                        className="w-full rounded-full bg-primary py-3 text-base font-bold text-white transition hover:bg-primary-600"
                    >
                        {confirmText}
                    </button>
                </div>
            </div>
        </div>,
        document.body,
    );
}
