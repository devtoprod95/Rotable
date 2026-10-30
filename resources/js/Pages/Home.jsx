import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { SendModal } from '@/Components/MessageSender';
import ProfileDetailModal from '@/Components/ProfileDetailModal';
import echo from '@/echo';

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

/**
 * 한 명의 회원 칩 (members 전체 정보 노출).
 *
 * - 와인파티: onSend(💌 전달) 버튼 노출.
 * - 1:1: selectable 이면 매칭 선택 토글(✓) 노출. 종료 전까지 자유롭게 선택/해제.
 */
function MemberChip({
    member,
    tone,
    onSend,
    sendDisabled,
    selectable = false,
    selected = false,
    selectDisabled = false,
    onToggleSelect,
    votedBadge = false,
    onDetail,
}) {
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

    // 1:1 선택 가능 카드: 카드 전체 클릭으로 토글. 선택 시 primary 링 강조.
    const clickable = selectable && !(selectDisabled && !selected);
    const Wrapper = selectable ? 'button' : 'div';
    const wrapperProps = selectable
        ? {
              type: 'button',
              'aria-disabled': !clickable,
              onClick: () => {
                  if (clickable) {
                      onToggleSelect(member.id);
                  }
              },
          }
        : {};

    return (
        <Wrapper
            {...wrapperProps}
            className={
                `block w-full rounded-xl border p-3 text-left transition ${toneCls}` +
                (selectable && selected ? ' ring-2 ring-primary ring-offset-1' : '') +
                (!selectable && votedBadge ? ' ring-2 ring-primary ring-offset-1' : '') +
                (selectable ? (clickable ? ' active:scale-[0.99]' : ' opacity-50') : '')
            }
        >
            <div className="flex items-center gap-2">
                <span className="text-base">{tone === 'female' ? '👩' : '👨'}</span>
                <span className="font-bold">{member.name}</span>
                {onDetail && (
                    <span
                        role="button"
                        tabIndex={0}
                        onClick={(e) => {
                            e.stopPropagation();
                            onDetail(member);
                        }}
                        className="flex shrink-0 items-center gap-1 rounded-full bg-secondary-100 px-2 py-0.5 text-[11px] font-bold text-secondary-600 transition hover:bg-secondary-200"
                    >
                        ℹ️ 상세
                    </span>
                )}
                {/* 종료 후: 내가 선택했던 상대 표시 */}
                {!selectable && votedBadge && (
                    <span className="inline-flex items-center gap-0.5 rounded-full bg-primary px-2 py-0.5 text-[10px] font-extrabold text-white shadow-sm">
                        💘 내가 선택함
                    </span>
                )}
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
                {selectable && (
                    <span
                        className={
                            'ml-auto flex h-6 w-6 items-center justify-center rounded-full border text-sm font-bold ' +
                            (selected
                                ? 'border-primary bg-primary text-white'
                                : 'border-secondary-200 text-transparent')
                        }
                    >
                        ✓
                    </span>
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
        </Wrapper>
    );
}

/** 로테이션 턴 섹션: 이 턴에 만난 상대들을 카드 그룹으로 표시 */
function RoundSection({
    round,
    partnerGender,
    onSend,
    sendDisabled,
    showTable = false,
    hideCount = false,
    selectable = false,
    selectedIds = [],
    selectDisabled = false,
    onToggleSelect,
    votedIds = [],
    onDetail,
}) {
    const tone = partnerGender === 'F' ? 'female' : 'male';
    const partners = round.partners ?? [];

    return (
        <section className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            {/* 턴 헤더: 좌측 컬러바 + 턴 번호 */}
            <div className="flex items-center justify-between border-b border-gray-100 bg-gray-50/80 px-4 py-3">
                <div className="flex items-center gap-2.5">
                    <span className="flex h-8 min-w-8 items-center justify-center rounded-lg bg-primary px-2 text-sm font-extrabold text-white">
                        {round.is_last ? '마지막 로테이션' : `${round.round_no}번 째 로테이션`}
                        {showTable && round.table_no ? ` (${round.table_no}번 테이블)` : ''}
                    </span>
                </div>
                <div className="flex items-center gap-2">
                    {!hideCount && (
                        <span className="text-xs font-medium text-gray-400">
                            만난 상대 {partners.length}명
                        </span>
                    )}
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
                                selectable={selectable}
                                selected={selectedIds.includes(m.id)}
                                selectDisabled={selectDisabled}
                                onToggleSelect={onToggleSelect}
                                votedBadge={!selectable && votedIds.includes(m.id)}
                                onDetail={onDetail}
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
    const {
        auth,
        rotations = [],
        partnerGender,
        sendLimit,
        eventTitle,
        eventTime,
        eventStatus,
        canSendMessage = false,
        // 1:1 매칭 선택용
        isOneToOne = false,
        matchSelectable = false,
        myVotedIds = [],
        maxVotes = 3,
    } = usePage().props;

    const tone = partnerGender === 'F' ? 'female' : 'male';
    const remaining = sendLimit?.remaining ?? null;
    const limitReached = typeof remaining === 'number' && remaining <= 0;

    const [target, setTarget] = useState(null);

    // 상대 프로필 상세 모달 대상 (null 이면 닫힘)
    const [detailMember, setDetailMember] = useState(null);

    // ── 1:1 매칭 선택 상태 ──
    const [selected, setSelected] = useState(() => myVotedIds.map(Number));
    const [saving, setSaving] = useState(false);

    // 서버 선택값이 바뀌면(저장 후 리로드/실시간 갱신) 동기화.
    useEffect(() => {
        setSelected(myVotedIds.map(Number));
    }, [myVotedIds.join(',')]);

    const toggleSelect = (id) => {
        setSelected((prev) => {
            if (prev.includes(id)) {
                return prev.filter((x) => x !== id);
            }
            if (prev.length >= maxVotes) {
                return prev;
            }
            return [...prev, id];
        });
    };

    const dirty = useMemo(() => {
        const a = [...selected].sort().join(',');
        const b = [...myVotedIds.map(Number)].sort().join(',');
        return a !== b;
    }, [selected, myVotedIds]);

    const saveMatch = () => {
        setSaving(true);
        router.post(
            '/votes',
            { target_ids: selected },
            {
                preserveScroll: true,
                onFinish: () => setSaving(false),
            },
        );
    };

    // ── 실시간: 관리자가 로테이션 진행/종료 시 홈을 부분 리로드 ──
    const eventId = auth?.user?.event_id ?? null;
    useEffect(() => {
        if (!eventId) {
            return undefined;
        }
        const channelName = `event-rotation.${eventId}`;
        const channel = echo.private(channelName);

        // 좌석/턴이 갱신되면 홈 데이터만 다시 받아온다. (전체 새로고침 없이)
        // 로테이션 시작(P→S) 시 와인파티 '💌 전달' 버튼이 바로 보이도록
        // canSendMessage/sendLimit 도 함께 갱신한다.
        channel.listen('.rotation.updated', () => {
            router.reload({
                only: [
                    'rotations',
                    'eventStatus',
                    'matchSelectable',
                    'myVotedIds',
                    'canSendMessage',
                    'sendLimit',
                ],
            });
        });
        // 매칭이 종료되면 상태만 갱신(선택 UI가 닫히고 "매칭 결과" 탭에서 결과 확인).
        channel.listen('.event.closed', () => {
            router.reload({ only: ['eventStatus', 'matchSelectable'] });
        });
        // 관리자가 '매칭 시작(V)'하면 선택 UI가 열리도록 상태를 갱신한다.
        // 와인파티는 V 상태에서도 메시지 전송이 가능하므로 함께 갱신한다.
        channel.listen('.event.voting.opened', () => {
            router.reload({
                only: [
                    'eventStatus',
                    'matchSelectable',
                    'myVotedIds',
                    'canSendMessage',
                    'sendLimit',
                ],
            });
        });

        return () => {
            echo.leave(`private-${channelName}`);
        };
    }, [eventId]);

    const showSelectBar = isOneToOne && matchSelectable;

    return (
        <AppLayout>
            <Head title="Rotable" />

            <div
                className={
                    'flex min-h-full flex-col bg-gray-50 px-4 py-5 ' +
                    (showSelectBar ? 'pb-24' : '')
                }
            >
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

                {/* 안내 문구: 1:1(매칭 선택) vs 와인파티(메시지 전달) */}
                {isOneToOne ? (
                    <p className="mt-1.5 text-sm text-gray-500">
                        {matchSelectable ? (
                            <>
                                만난 상대 카드를 눌러 <b className="text-primary">마음에 든 상대</b>를 최대{' '}
                                <b className="text-primary">{maxVotes}명</b>까지 선택하세요.
                                <br />
                                로테이션이 종료되기 전까지 자유롭게 바꿀 수 있어요. 💘
                            </>
                        ) : (
                            <span>
                                매칭 결과는 모든 로테이션이 끝난 뒤
                                <br />
                                <b className="text-primary">매칭 결과</b> 탭에서 확인할 수 있어요.
                            </span>
                        )}
                    </p>
                ) : (
                    <p className="mt-1.5 text-sm text-gray-500">
                        {canSendMessage ? (
                            <>
                                만난 상대 카드의 <b className="text-primary">💌 전달</b> 버튼으로 익명 메시지를 보낼 수 있어요.
                            </>
                        ) : (
                            <span>로테이션 진행 중부터 매칭 기간까지 만난 상대에게<br />익명 메시지를 보낼 수 있어요.</span>
                        )}
                    </p>
                )}

                {/* 1:1 선택 수 안내 */}
                {showSelectBar && (
                    <div className="mt-2">
                        <span className="inline-block shrink-0 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-bold text-primary">
                            선택한 상대 {selected.length}/{maxVotes}명
                        </span>
                    </div>
                )}

                {/* 1:1 연락처 공개 유의 안내 */}
                {showSelectBar && (
                    <div className="mt-2.5 flex items-start gap-2 rounded-2xl border border-rose-200 bg-rose-50 p-3 text-xs leading-relaxed text-rose-600">
                        <span className="text-sm leading-none">⚠️</span>
                        <span>
                            <b>유의하세요!</b> 내가 선택한 상대에게는 <b>서로 매칭되지 않더라도</b> 내 연락처가 공개될 수 있어요.
                            신중하게 선택해 주세요. 🙏
                        </span>
                    </div>
                )}

                {/* 와인파티 메시지 전송 횟수 안내 */}
                {!isOneToOne && canSendMessage && sendLimit && (
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
                                hideCount={isOneToOne}
                                onSend={!isOneToOne && canSendMessage ? setTarget : null}
                                sendDisabled={limitReached}
                                selectable={showSelectBar}
                                selectedIds={selected}
                                selectDisabled={selected.length >= maxVotes}
                                onToggleSelect={toggleSelect}
                                votedIds={isOneToOne ? myVotedIds.map(Number) : []}
                                onDetail={(m) => setDetailMember(m)}
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

            {/* 1:1: 매칭 선택 저장 바 (하단 nav 바로 위 고정) */}
            {showSelectBar && (
                <div className="fixed inset-x-0 bottom-[calc(5.5rem+env(safe-area-inset-bottom))] z-40 mx-auto w-full max-w-[480px] px-4">
                    <button
                        type="button"
                        onClick={saveMatch}
                        disabled={!dirty || saving}
                        className={
                            'w-full rounded-2xl py-3 text-center text-sm font-bold shadow-lg transition ' +
                            (dirty && !saving
                                ? 'bg-primary text-white hover:bg-primary-600 active:scale-[0.99]'
                                : 'cursor-not-allowed bg-secondary-200 text-secondary-400')
                        }
                    >
                        {saving ? '저장 중...' : `매칭 선택 저장 (${selected.length}/${maxVotes})`}
                    </button>
                </div>
            )}

            {/* 와인파티 메시지 전송 모달 */}
            {!isOneToOne && (
                <SendModal
                    target={target}
                    tone={tone}
                    remaining={remaining}
                    onClose={() => setTarget(null)}
                />
            )}

            {/* 상대 프로필 상세 모달 */}
            {detailMember && (
                <ProfileDetailModal
                    member={detailMember}
                    tone={tone}
                    onClose={() => setDetailMember(null)}
                />
            )}
        </AppLayout>
    );
}
