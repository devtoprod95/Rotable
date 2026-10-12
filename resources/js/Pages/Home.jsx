import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

const ROUND_STATUS = {
    P: { label: '대기', cls: 'bg-secondary-100 text-secondary-600' },
    S: { label: '진행중', cls: 'bg-green-100 text-green-700' },
    X: { label: '종료', cls: 'bg-gray-100 text-gray-500' },
};

function ageText(birthYear) {
    if (!birthYear) return null;
    const now = new Date().getFullYear();
    return `${now - birthYear + 1}세`;
}

/** 한 명의 회원 칩 (members 전체 정보 노출) */
function MemberChip({ member, tone }) {
    const specs = [
        ageText(member.birth_year),
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

/** 로테이션 턴 섹션: 이 턴에 만난 상대들을 카드 그룹으로 표시 */
function RoundSection({ round, partnerGender }) {
    const status = ROUND_STATUS[round.status] ?? ROUND_STATUS.P;
    const tone = partnerGender === 'F' ? 'female' : 'male';
    const partners = round.partners ?? [];

    return (
        <section className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            {/* 턴 헤더: 좌측 컬러바 + 턴 번호 + 상태 뱃지 */}
            <div className="flex items-center justify-between border-b border-gray-100 bg-gray-50/80 px-4 py-3">
                <div className="flex items-center gap-2.5">
                    <span className="flex h-8 min-w-8 items-center justify-center rounded-lg bg-primary px-2 text-sm font-extrabold text-white">
                        {round.round_no}번 째 로테이션
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
                    <div className="grid grid-cols-2 gap-2.5">
                        {partners.map((m) => (
                            <MemberChip key={m.id} member={m} tone={tone} />
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
    const { auth, rotations = [], partnerGender, eventTitle, eventTime, eventStatus } = usePage().props;

    return (
        <AppLayout>
            <Head title="기록" />

            <div className="min-h-full bg-gray-50 px-4 py-5">
                {eventTitle && (
                    <div className="mb-2 flex flex-wrap items-center gap-1.5">
                        <span className="inline-flex items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">
                            <span className="text-sm">❤️</span>
                            {eventTitle}
                        </span>
                        {eventStatus && (
                            <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${(ROUND_STATUS[eventStatus] ?? ROUND_STATUS.P).cls}`}>
                                {(ROUND_STATUS[eventStatus] ?? ROUND_STATUS.P).label}
                            </span>
                        )}
                        {eventTime && (
                            <span className="inline-flex items-center gap-1 text-xs font-medium text-gray-500">
                                <span>🗓️</span>
                                {eventTime}
                            </span>
                        )}
                    </div>
                )}
                <h1 className="mt-2 text-xl font-extrabold">
                    {auth?.user?.name}님, 로테이션 기록표예요 👋
                </h1>
                <p className="mt-1.5 text-sm text-gray-500">
                    로테이션마다 어느 테이블에서 누구와 만났는지 확인할 수 있어요.
                </p>

                <div className="mt-5 space-y-5">
                    {rotations.length > 0 ? (
                        rotations.map((round) => <RoundSection key={round.id} round={round} partnerGender={partnerGender} />)
                    ) : (
                        <div className="rounded-2xl border border-dashed border-gray-200 bg-white py-10 text-center text-sm text-gray-400">
                            아직 로테이션 기록이 없습니다.
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
