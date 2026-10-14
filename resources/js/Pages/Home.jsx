import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { SendModal } from '@/Components/MessageSender';

const ROUND_STATUS = {
    P: { label: '대기', cls: 'bg-amber-100 text-amber-700' },
    S: { label: '진행중', cls: 'bg-green-100 text-green-700' },
    V: { label: '매칭중', cls: 'bg-pink-100 text-pink-700' },
    X: { label: '종료', cls: 'bg-secondary-700 text-white' },
};

function birthYearText(birthYear) {
    if (!birthYear) return null;
    return `${String(birthYear).slice(-2)}년생`;
}

/** 한 명의 회원 칩 (members 전체 정보 노출) */
function MemberChip({ member, tone, onSend, sendDisabled }) {
    const specs = [
        birthYearText(member.birth_year),
        member.job,
        member.height ? `${member.height}cm` : null,
        member.mbti,
        member.region,
        member.is_smoker ? '흡연' : '비흡연',
    ].filter(Boolean);

    const toneCls =
        tone === 'female'
            ? 'border-rose-100 bg-rose-50'
            : 'border-sky-100 bg-sky-50';

    return (
        <div className={`rounded-xl border p-3 ${toneCls}`}>
            <div className="flex items-center gap-2">
                <span className="text-base">{tone === 'female' ? '👩' : '👨'}</span>
                <span className="font-bold">{member.name}</span>
                {onSend && (
                    <button
                        type="button"
                        onClick={() => onSend(member)}
                        disabled={sendDisabled}
                        className="flex shrink-0 items-center gap-1 rounded-full bg-primary px-2.5 py-1 text-[11px] font-bold text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        💌 전달
                    </button>
                )}
            </div>
            <div className="mt-1.5 flex flex-wrap gap-1">
                {specs.map((s, i) => (
                    <span
                        key={i}
                        className="rounded-md border border-secondary-200 bg-white px-1.5 py-0.5 text-[11px] font-medium text-gray-600"
                    >
                        {s}
                    </span>
                ))}
            </div>
        </div>
    );
}

/** 로테이션 턴 섹션: 이 턴에 만난 상대들을 카드 그룹으로 표시 */
function RoundSection({ round, partnerGender, onSend, sendDisabled, showTable = false }) {
    const status = ROUND_STATUS[round.status] ?? ROUND_STATUS.P;
    const tone = partnerGender === 'F' ? 'female' : 'male';
    const partners = round.partners ?? [];

    return (
        <section className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            {/* 턴 헤더: 좌측 컬러바 + 턴 번호 + 상태 뱃지 */}
            <div className="flex items-center justify-between border-b border-gray-100 bg-gray-50/80 px-4 py-3">
                <div className="flex items-center gap-2.5">
                    <span className="flex h-8 min-w-8 items-center justify-center rounded-lg bg-primary px-2 text-sm font-extrabold text-white">
                        {round.is_last ? '마지막 로테이션' : `${round.round_no}번 째 로테이션`}
                        {showTable && round.table_no ? ` (${round.table_no}번 테이블)` : ''}
                    </span>
                    {/* <span className="text-base font-extrabold text-gray-800">{round.round_no}턴</span> */}
                </div>
                <div className="flex items-center gap-2">
                    <span className="text-xs font-medium text-gray-400">
                        만난 상대 {partners.length}명
                    </span>
                    {/* <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${status.cls}`}>
                        {status.label}
                    </span> */}
                </div>
            </div>

            {/* 턴 본문 */}
            <div className="p-3">
                {partners.length > 0 ? (
                    <div className="grid grid-cols-1 gap-2.5">
                        {partners.map((m) => (
                            <MemberChip
                                key={m.id}
                                member={m}
                                tone={tone}
                                onSend={onSend}
                                sendDisabled={sendDisabled}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="rounded-xl border border-dashed border-gray-200 bg-gray-50 py-6 text-center text-sm text-gray-400">
                        이 턴에 만난 상대가 없어요.
                    </div>
                )}
            </div>
        </section>
    );
}

export default function Home() {
    const { auth, rotations = [], partnerGender, sendLimit, eventTitle, eventTime, eventStatus, canSendMessage = false } = usePage().props;

    const tone = partnerGender === 'F' ? 'female' : 'male';
    const remaining = sendLimit?.remaining ?? null;
    const limitReached = typeof remaining === 'number' && remaining <= 0;

    const [target, setTarget] = useState(null);

    return (
        <AppLayout>
            <Head title="Rotable" />

            <div className="flex min-h-full flex-col bg-gray-50 px-4 py-5">
                {eventTitle && (
                    <div className="mb-2 flex flex-wrap items-center gap-1.5">
                        <span className="inline-flex items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">
                            <span className="text-sm">❤️</span>
                            {eventTitle}
                        </span>
                        {eventTime && (
                            <span className="inline-flex items-center gap-1 rounded-full bg-secondary-100 px-2.5 py-1 text-xs font-semibold text-secondary-700">
                                <span>🗓️</span>
                                {eventTime}
                            </span>
                        )}
                        {eventStatus && (
                            <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${(ROUND_STATUS[eventStatus] ?? ROUND_STATUS.P).cls}`}>
                                {(ROUND_STATUS[eventStatus] ?? ROUND_STATUS.P).label}
                            </span>
                        )}
                    </div>
                )}
                <h1 className="mt-2 text-xl font-extrabold">
                    {auth?.user?.name}님, 로테이션 기록표예요 👋
                </h1>
                <p className="mt-1.5 text-sm text-gray-500">
                    {canSendMessage ? (
                        <>
                            만난 상대 카드의 <b className="text-primary">💌 전달</b> 버튼으로 익명 메시지를 보낼 수 있어요.
                        </>
                    ) : (
                        <span>로테이션 진행 중부터 매칭 기간까지 만난 상대에게<br />익명 메시지를 보낼 수 있어요.</span>
                    )}
                </p>
                {canSendMessage && sendLimit && (
                    <div className="mt-2">
                        <span
                            className={
                                'inline-block shrink-0 rounded-full px-2.5 py-1 text-xs font-bold ' +
                                (limitReached ? 'bg-gray-100 text-gray-400' : 'bg-primary/10 text-primary')
                            }
                        >
                            남은 전송 횟수 {remaining}/{sendLimit.max}회
                        </span>
                    </div>
                )}

                {rotations.length > 0 ? (
                    <div className="mt-5 space-y-5">
                        {rotations.map((round) => (
                            <RoundSection
                                key={round.id}
                                round={round}
                                partnerGender={partnerGender}
                                showTable={auth?.user?.gender === 'M'}
                                onSend={canSendMessage ? setTarget : null}
                                sendDisabled={limitReached}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="mt-5 flex flex-1 flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white px-4 py-10 text-center">
                        <p className="text-4xl">🪑</p>
                        <p className="mt-3 text-base font-bold text-secondary-700">
                            아직 로테이션 기록이 없어요
                        </p>
                        <p className="mt-1.5 text-xs leading-relaxed text-gray-500">
                            행사가 시작되고 자리가 배정되면<br />
                            여기에서 만난 상대를 확인할 수 있어요.
                        </p>
                    </div>
                )}
            </div>

            <SendModal
                target={target}
                tone={tone}
                remaining={remaining}
                onClose={() => setTarget(null)}
            />
        </AppLayout>
    );
}
