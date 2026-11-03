import useBodyScrollLock from '@/useBodyScrollLock';

/**
 * 입장/입장등록 실패 안내 모달.
 * input 아래 작은 에러 텍스트 대신, 실패 사유를 모달로 크게 띄워 사용자가 명확히 인지하게 한다.
 *
 * @param {boolean}  open      열림 여부
 * @param {string}   title     제목 (기본: '입장할 수 없어요')
 * @param {string[]} messages  표시할 실패 메시지 목록
 * @param {Function} onClose   닫기 핸들러
 */
export default function AuthErrorModal({ open, title = '입장할 수 없어요', messages = [], onClose }) {
    useBodyScrollLock(open);

    if (!open) return null;

    return (
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
                {/* 헤더 */}
                <div className="flex flex-col items-center gap-3 px-7 pt-8 pb-4 text-center">
                    <div className="flex h-16 w-16 items-center justify-center rounded-full bg-rose-50 text-3xl">
                        ⚠️
                    </div>
                    <h2 className="text-xl font-extrabold text-secondary-800">{title}</h2>
                </div>

                {/* 메시지 본문 */}
                <div className="px-7 pb-2">
                    {messages.length <= 1 ? (
                        <p className="text-center text-base leading-relaxed text-secondary-600">
                            {messages[0] || '입력하신 정보를 다시 확인해 주세요.'}
                        </p>
                    ) : (
                        <ul className="flex flex-col gap-2">
                            {messages.map((msg, i) => (
                                <li
                                    key={i}
                                    className="flex items-start gap-2 rounded-xl bg-secondary-50 px-3 py-2 text-sm leading-relaxed text-secondary-700"
                                >
                                    <span className="mt-0.5 text-rose-500">•</span>
                                    <span>{msg}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                {/* 닫기 */}
                <div className="px-7 pb-7 pt-5">
                    <button
                        type="button"
                        onClick={onClose}
                        className="w-full rounded-full bg-primary py-3 text-base font-bold text-white transition hover:bg-primary-600"
                    >
                        확인
                    </button>
                </div>
            </div>
        </div>
    );
}
