import useBodyScrollLock from '@/useBodyScrollLock';

function birthYearText(birthYear) {
    if (!birthYear) return null;
    return `${String(birthYear).slice(-2)}년생`;
}

/**
 * 상대 프로필 상세 모달.
 * 기본 스펙 + 추가 정보(장점/이상형/취미/TMI)를 보여준다. (모든 유형 공통)
 *
 * @param {object}  member   상대 회원 정보 (transformMember 결과)
 * @param {object=} context  { round_no, table_no } — 와인파티 등에서는 생략 가능
 * @param {'female'|'male'} tone 상대 성별 톤 (female: 여자 상대, male: 남자 상대)
 * @param {Function} onClose 닫기 핸들러
 */
export default function ProfileDetailModal({ member, context, tone, onClose }) {
    // 모달이 열려 있는 동안 배경 스크롤을 잠근다. (공용 훅)
    useBodyScrollLock(true);

    if (!member) return null;

    const specs = [
        birthYearText(member.birth_year),
        member.job,
        member.height ? `${member.height}cm` : null,
        member.mbti,
        member.region,
        member.is_smoker ? '흡연' : '비흡연',
    ].filter(Boolean);

    // 추가 정보 항목 (값이 있을 때만 노출) — 입장등록 폼과 동일한 순서
    const extras = [
        { icon: '💘', label: '이상형', value: member.ideal_type },
        { icon: '✨', label: '나의 장점', value: member.merit },
        { icon: '🎯', label: '취미 / 요즘 빠진 것', value: member.hobby },
        { icon: '💡', label: 'TMI', value: member.tmi },
    ].filter((e) => e.value);

    const headCls = tone === 'female' ? 'bg-rose-50' : 'bg-sky-100';

    // 메시지 리스트에서 열릴 때: 상대와 만난 전체 턴/테이블 이력(턴 오름차순).
    const meetings = Array.isArray(member.meetings) ? member.meetings : [];

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            onClick={onClose}
        >
            <div
                className="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl"
                onClick={(e) => e.stopPropagation()}
            >
                {/* 헤더 */}
                <div className={`px-5 py-4 ${headCls}`}>
                    <div className="flex items-center gap-2">
                        <span className="text-2xl">{tone === 'female' ? '👩' : '👨'}</span>
                        <span className="text-lg font-extrabold text-secondary-800">{member.name}</span>
                        <button
                            type="button"
                            onClick={onClose}
                            className="ml-auto flex h-8 w-8 items-center justify-center rounded-full bg-white/70 text-secondary-500 transition hover:bg-white"
                            aria-label="닫기"
                        >
                            ✕
                        </button>
                    </div>
                    {/* 현재 로테이션(턴) / 테이블 번호
                        - 여자는 자리 고정, 남자가 이동한다. 여자 시점에서는 남자가 내 자리로 찾아오므로
                          테이블 번호가 매 턴 동일하게 보인다. 따라서 남자 상대(tone==='male') 모달에서는
                          테이블 뱃지를 숨긴다.
                        - context: 단일 턴 맥락(홈). meetings: 상대와 만난 전체 턴/테이블 이력(메시지 리스트). */}
                    {context && (context.round_no != null || (tone !== 'male' && context.table_no != null)) && (
                        <div className="mt-2 flex flex-wrap gap-1.5">
                            {context.round_no != null && (
                                <span className="rounded-full bg-white/80 px-2.5 py-0.5 text-[11px] font-bold text-secondary-700">
                                    🔄 {context.round_no}턴
                                </span>
                            )}
                            {tone !== 'male' && context.table_no != null && (
                                <span className="rounded-full bg-white/80 px-2.5 py-0.5 text-[11px] font-bold text-secondary-700">
                                    🪑 {context.table_no}번 테이블
                                </span>
                            )}
                        </div>
                    )}
                    {!context && meetings.length > 0 && (
                        <div className="mt-2 flex flex-wrap gap-1.5">
                            {meetings.map((m, i) => (
                                <span key={i} className="inline-flex flex-wrap gap-1.5">
                                    {m.round_no != null && (
                                        <span className="rounded-full bg-white/80 px-2.5 py-0.5 text-[11px] font-bold text-secondary-700">
                                            🔄 {m.round_no}턴
                                        </span>
                                    )}
                                    {tone !== 'male' && m.table_no != null && (
                                        <span className="rounded-full bg-white/80 px-2.5 py-0.5 text-[11px] font-bold text-secondary-700">
                                            🪑 {m.table_no}번 테이블
                                        </span>
                                    )}
                                </span>
                            ))}
                        </div>
                    )}
                </div>

                <div className="px-5 py-4">
                    {/* 기본 스펙 */}
                    <div className="flex flex-wrap gap-1.5">
                        {specs.map((s, i) => (
                            <span
                                key={i}
                                className="rounded-md border border-secondary-200 bg-white px-2 py-0.5 text-xs font-medium text-gray-600"
                            >
                                {s}
                            </span>
                        ))}
                    </div>

                    {/* 추가 정보 */}
                    {extras.length > 0 ? (
                        <div className="mt-4 space-y-3">
                            {extras.map((e, i) => (
                                <div key={i} className="rounded-2xl border border-secondary-100 bg-secondary-50 p-3">
                                    <div className="text-xs font-bold text-primary">
                                        {e.icon} {e.label}
                                    </div>
                                    <div className="mt-1 text-sm leading-relaxed text-secondary-700">
                                        {e.value}
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="mt-4 rounded-2xl border border-dashed border-secondary-200 bg-secondary-50 p-4 text-center text-xs text-gray-400">
                            추가로 공유된 소개가 아직 없어요.
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
