import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { useForm } from '@inertiajs/react';

function ageText(birthYear) {
    if (!birthYear) return null;
    const now = new Date().getFullYear();
    return `${now - birthYear + 1}세`;
}

function memberSpecs(member) {
    return [
        ageText(member.birth_year),
        member.job,
        member.height ? `${member.height}cm` : null,
        member.mbti,
        member.region,
        member.is_smoker ? '흡연' : '비흡연',
    ].filter(Boolean);
}

/** 이성 회원 한 명. 전달 버튼을 이름 바로 오른쪽에 붙인다. */
function PartnerRow({ member, tone, onSelect, disabled }) {
    const toneCls = tone === 'female' ? 'border-rose-100 bg-rose-50' : 'border-sky-100 bg-sky-50';
    const specs = memberSpecs(member);

    return (
        <div className={`rounded-xl border p-3 ${toneCls}`}>
            <div className="flex items-center gap-2">
                <span className="text-base">{tone === 'female' ? '👩' : '👨'}</span>
                <span className="truncate font-bold">{member.name}</span>
                <button
                    type="button"
                    onClick={() => onSelect(member)}
                    disabled={disabled}
                    className="flex shrink-0 items-center gap-1 rounded-full bg-primary px-3 py-1.5 text-xs font-bold text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    💌 전달
                </button>
            </div>
            <div className="mt-1.5 flex flex-wrap gap-1">
                {specs.map((s, i) => (
                    <span
                        key={i}
                        className="rounded-md bg-white/70 px-1.5 py-0.5 text-[11px] font-medium text-gray-600"
                    >
                        {s}
                    </span>
                ))}
            </div>
        </div>
    );
}

/** 메시지 전달 모달 (세로 정중앙, 익명 안내 포함) */
export function SendModal({ target, tone, remaining, onClose, onSent }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        receiver_id: target?.id ?? '',
        body: '',
    });

    const panelRef = useRef(null);
    const [confirming, setConfirming] = useState(false);

    useEffect(() => {
        setData('receiver_id', target?.id ?? '');
        setConfirming(false);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [target?.id]);

    // 모달이 열려 있는 동안 배경 스크롤만 차단한다.
    // 레이아웃을 건드리지 않으므로(position:fixed 미사용) 배경 스크롤 위치가 유지된다.
    // 모달 패널 내부에서 발생한 스크롤(textarea 등)은 허용한다.
    useEffect(() => {
        if (!target) return undefined;

        const isInsidePanel = (node) => !!panelRef.current && panelRef.current.contains(node);

        const blockIfOutside = (e) => {
            if (!isInsidePanel(e.target)) {
                e.preventDefault();
            }
        };

        // touchmove / wheel 을 non-passive 로 등록해야 preventDefault 가 동작한다.
        document.addEventListener('touchmove', blockIfOutside, { passive: false });
        document.addEventListener('wheel', blockIfOutside, { passive: false });

        return () => {
            document.removeEventListener('touchmove', blockIfOutside);
            document.removeEventListener('wheel', blockIfOutside);
        };
    }, [target]);

    if (!target) return null;

    function submit(e) {
        e.preventDefault();
        post('/chats/messages', {
            // SPA 부분 갱신: 전체 페이지 재요청/깜빡임 없이 필요한 prop 만 교체한다.
            preserveScroll: true,
            preserveState: true,
            only: ['received', 'sent', 'sendLimit', 'flash'],
            onSuccess: () => {
                reset('body');
                setConfirming(false);
                onSent?.();
                onClose();
            },
        });
    }

    // 모달을 body 로 포탈 → <main> 의 overflow 컨텍스트에서 벗어나 화면 전체 기준으로 뜬다.
    return createPortal(
        <div
            className="fixed inset-0 z-50 flex items-center justify-center overscroll-contain bg-black/40 p-4"
            onClick={onClose}
        >
            <div
                ref={panelRef}
                className="w-full max-w-[420px] rounded-3xl bg-white p-5 shadow-2xl"
                onClick={(e) => e.stopPropagation()}
            >
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-extrabold">메시지 전달</h2>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-full p-1 text-gray-400 transition hover:text-gray-700"
                        aria-label="닫기"
                    >
                        ✕
                    </button>
                </div>

                <div className="mt-3 flex items-center gap-2 rounded-xl bg-gray-50 p-3">
                    <span className="text-base">{tone === 'female' ? '👩' : '👨'}</span>
                    <span className="font-bold">{target.name}</span>
                    <span className="text-xs text-gray-400">님에게</span>
                </div>

                {/* 익명 전송 안내 */}
                <div className="mt-3 flex items-start gap-2 rounded-xl border border-primary/20 bg-primary/5 p-3 text-xs leading-relaxed text-primary-700">
                    <span className="text-sm">🙈</span>
                    <span>
                        메시지는 <b>익명</b>으로 전달돼요. 상대방에게 내 이름/프로필은 보이지 않아요.
                        {typeof remaining === 'number' && (
                            <>
                                <br />
                                남은 전송 횟수: <b>{remaining}회</b>
                            </>
                        )}
                    </span>
                </div>

                {!confirming ? (
                    /* 1단계: 작성 */
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (data.body.trim()) {
                                setConfirming(true);
                            }
                        }}
                        className="mt-4 flex flex-col gap-3"
                    >
                        <textarea
                            value={data.body}
                            onChange={(e) => setData('body', e.target.value)}
                            rows={4}
                            maxLength={1000}
                            autoFocus
                            placeholder="전달할 메시지를 입력하세요."
                            className="w-full resize-none rounded-xl border border-secondary-100 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-primary"
                        />
                        {errors.body && <p className="text-xs font-medium text-red-500">{errors.body}</p>}

                        <div className="flex gap-2">
                            <button
                                type="button"
                                onClick={onClose}
                                className="flex-1 rounded-full border border-secondary-100 py-3 text-sm font-bold text-secondary-600 transition hover:bg-secondary-50"
                            >
                                취소
                            </button>
                            <button
                                type="submit"
                                disabled={!data.body.trim()}
                                className="flex-[2] rounded-full bg-primary py-3 text-sm font-bold text-white transition hover:bg-primary-600 disabled:opacity-60"
                            >
                                다음
                            </button>
                        </div>
                    </form>
                ) : (
                    /* 2단계: 전송 전 마지막 확인 */
                    <form onSubmit={submit} className="mt-4 flex flex-col gap-3">
                        <div className="rounded-xl border border-secondary-100 bg-gray-50 p-3">
                            <p className="text-[11px] font-bold text-gray-400">보낼 메시지</p>
                            <p className="mt-1 whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-700">
                                {data.body}
                            </p>
                        </div>
                        <p className="text-center text-sm font-semibold text-secondary-700">
                            <b className="text-primary">{target.name}</b>님에게 이대로 전달할까요?
                        </p>
                        <p className="text-center text-xs text-gray-400">
                            전달 후에는 취소할 수 없어요.
                        </p>

                        <div className="flex gap-2">
                            <button
                                type="button"
                                onClick={() => setConfirming(false)}
                                disabled={processing}
                                className="flex-1 rounded-full border border-secondary-100 py-3 text-sm font-bold text-secondary-600 transition hover:bg-secondary-50 disabled:opacity-60"
                            >
                                이전
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="flex-[2] rounded-full bg-primary py-3 text-sm font-bold text-white transition hover:bg-primary-600 disabled:opacity-60"
                            >
                                {processing ? '전달 중…' : '익명으로 전달하기'}
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </div>,
        document.body,
    );
}

/**
 * 메시지 보내기 영역 (이성 회원 리스트 + 전달 모달).
 * Home(기록)과 Chats(메시지함)에서 공통으로 사용한다.
 */
export default function MessageSender({ partners = [], partnerGender, sendLimit, onSent }) {
    const tone = partnerGender === 'F' ? 'female' : 'male';
    const [target, setTarget] = useState(null);

    const remaining = sendLimit?.remaining ?? null;
    const limitReached = typeof remaining === 'number' && remaining <= 0;

    return (
        <section>
            <div className="flex items-end justify-between">
                <div>
                    <h2 className="text-lg font-extrabold">메시지 보내기</h2>
                    <p className="mt-1.5 text-sm text-gray-500">
                        마음에 드는 상대에게 익명으로 메시지를 전달해보세요.
                    </p>
                </div>
                {sendLimit && (
                    <span
                        className={
                            'shrink-0 rounded-full px-2.5 py-1 text-xs font-bold ' +
                            (limitReached ? 'bg-gray-100 text-gray-400' : 'bg-primary/10 text-primary')
                        }
                    >
                        남은 전송 횟수 {remaining}/{sendLimit.max}회
                    </span>
                )}
            </div>

            {limitReached && (
                <div className="mt-3 rounded-xl border border-dashed border-gray-200 bg-gray-50 p-3 text-center text-xs font-medium text-gray-500">
                    전송 가능한 횟수({sendLimit.max}회)를 모두 사용했어요.
                </div>
            )}

            <div className="mt-4 space-y-2.5">
                {partners.length > 0 ? (
                    partners.map((m) => (
                        <PartnerRow
                            key={m.id}
                            member={m}
                            tone={tone}
                            onSelect={setTarget}
                            disabled={limitReached}
                        />
                    ))
                ) : (
                    <div className="rounded-2xl border border-dashed border-gray-200 bg-white py-10 text-center text-sm text-gray-400">
                        아직 참여한 상대가 없어요.
                    </div>
                )}
            </div>

            <SendModal
                target={target}
                tone={tone}
                remaining={remaining}
                onClose={() => setTarget(null)}
                onSent={onSent}
            />
        </section>
    );
}
