import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Placeholder from '@/Components/Placeholder';
import ProfileDetailModal from '@/Components/ProfileDetailModal';
import echo from '@/echo';

function birthYearText(birthYear) {
    if (!birthYear) return null;
    return `${String(birthYear).slice(-2)}년생`;
}

/**
 * 매칭 후보 카드. 홈(Home.jsx)의 MemberChip과 동일한 UI 위에
 * 선택(투표) 인터랙션(체크 표시/클릭)을 얹는다.
 * - 투표중: 클릭으로 선택/해제 (최대 maxVotes명)
 * - 종료 후: 상호 매칭된 상대는 연락처(phone) 공개
 */
function CandidateCard({ candidate, tone, selected, disabled, closed, onToggle, onDetail }) {
    const [copied, setCopied] = useState(false);

    const copyPhone = (e) => {
        e.stopPropagation();
        e.preventDefault();
        const phone = candidate.phone;
        const done = () => {
            setCopied(true);
            setTimeout(() => setCopied(false), 1800);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(phone).then(done).catch(done);
        } else {
            const ta = document.createElement('textarea');
            ta.value = phone;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (err) {}
            document.body.removeChild(ta);
            done();
        }
    };

    const specs = [
        birthYearText(candidate.birth_year),
        candidate.job,
        candidate.height ? `${candidate.height}cm` : null,
        candidate.mbti,
        candidate.region,
        candidate.is_smoker ? '흡연' : '비흡연',
    ].filter(Boolean);

    const toneCls =
        tone === 'female' ? 'border-rose-100 bg-rose-50' : 'border-sky-100 bg-sky-50';

    const clickable = !closed && !(disabled && !selected);

    // 종료 후 매칭된 상대는 특별히 사랑스럽게 강조한다.
    const isMatched = closed && candidate.is_match;

    return (
        <button
            type="button"
            aria-disabled={!clickable}
            onClick={() => { if (clickable) { onToggle(candidate.id); } }}
            className={
                'w-full rounded-2xl border p-3 text-left transition ' +
                (isMatched
                    ? 'border-rose-300 bg-gradient-to-br from-rose-50 to-pink-50 shadow-md shadow-rose-100 ring-1 ring-rose-200'
                    : toneCls) +
                (selected ? ' ring-2 ring-primary ring-offset-1' : '') +
                (clickable ? ' active:scale-[0.99]' : closed ? '' : ' opacity-50')
            }
        >
            <div className="flex items-center gap-2">
                <span className="text-base">{tone === 'female' ? '👩' : '👨'}</span>
                <span className="font-bold">{candidate.name}</span>

                {onDetail && (
                    <span
                        role="button"
                        tabIndex={0}
                        onClick={(e) => {
                            e.stopPropagation();
                            e.preventDefault();
                            onDetail(candidate);
                        }}
                        className="flex shrink-0 items-center gap-1 rounded-full bg-secondary-100 px-2 py-0.5 text-[11px] font-bold text-secondary-600 transition hover:bg-secondary-200"
                    >
                        ℹ️ 상세
                    </span>
                )}

                {isMatched && (
                    <span className="inline-flex items-center gap-0.5 rounded-full bg-gradient-to-r from-rose-500 to-pink-500 px-2 py-0.5 text-[10px] font-extrabold text-white shadow-sm">
                        💘 매칭 성공
                    </span>
                )}

                {/* 상호매칭은 아니지만 나를 선택해준 상대 (1:1에서 번호 공개 대상) */}
                {closed && !candidate.is_match && candidate.voted_for_me && (
                    <span className="inline-flex items-center gap-0.5 rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-extrabold text-white shadow-sm">
                        💝 나에게 호감
                    </span>
                )}

                {closed && !candidate.is_match && !candidate.voted_for_me && (
                    <span className="inline-flex items-center gap-0.5 rounded-full bg-gray-200 px-2 py-0.5 text-[10px] font-bold text-gray-500">
                        💔 매칭 실패
                    </span>
                )}

                {/* 투표중일 때만 선택 체크 표시 (우측 정렬) */}
                {!closed && (
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

            {/* 종료 후 연락처 공개:
                - 와인파티: 상호 매칭된 상대만 (서버가 phone 을 내려줌)
                - 1:1: 상호 매칭 + 나를 선택해준 상대까지 (서버가 phone 을 내려줌)
                프론트는 서버가 공개를 결정한 candidate.phone 유무로만 판단한다. */}
            {closed && candidate.phone && (
                <div className="relative mt-2.5 flex items-center gap-2 rounded-xl bg-white px-3 py-2 shadow-sm ring-1 ring-rose-100">
                    <span className="text-base">📞</span>
                    <div className="leading-tight">
                        <p className="text-[10px] font-semibold text-rose-400">
                            {candidate.is_match ? '서로 선택했어요! 먼저 연락해 보세요!' : '당신에게 호감을 표현한 분이에요 💝'}
                        </p>
                        <span
                            role="button"
                            tabIndex={0}
                            onClick={copyPhone}
                            className="cursor-pointer text-sm font-extrabold text-rose-600"
                        >
                            {candidate.phone}
                        </span>
                        <p className="text-[9px] text-gray-400">클릭하면 자동 복사됩니다</p>
                    </div>
                    <span className="ml-auto text-lg">💌</span>

                    {/* 복사 토스트 */}
                    {copied && (
                        <span className="pointer-events-none absolute -top-2 right-2 rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white shadow-lg">
                            복사되었습니다!
                        </span>
                    )}
                </div>
            )}
        </button>
    );
}

export default function Votes() {
    const {
        auth,
        isOneToOne = false,
        canVote = false,
        isClosed = false,
        votingEnded = false,
        maxVotes = 3,
        candidates = [],
        myVotedIds = [],
        receivedCount = 0,
        coupleCount = 0,
        partnerGender,
    } = usePage().props;

    const tone = partnerGender === 'F' ? 'female' : 'male';

    const [selected, setSelected] = useState(() => myVotedIds.map(Number));
    const [saving, setSaving] = useState(false);

    // 상대 프로필 상세 모달 대상 (null 이면 닫힘)
    const [detailMember, setDetailMember] = useState(null);

    const toggle = (id) => {
        setSelected((prev) => {
            if (prev.includes(id)) {
                return prev.filter((x) => x !== id);
            }
            if (prev.length >= maxVotes) {
                return prev; // 최대 선택 수 초과 시 무시
            }
            return [...prev, id];
        });
    };

    const dirty = useMemo(() => {
        const a = [...selected].sort().join(',');
        const b = [...myVotedIds.map(Number)].sort().join(',');
        return a !== b;
    }, [selected, myVotedIds]);

    const save = () => {
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

    // 실시간: 관리자가 매칭종료(X)하면 이 화면을 리로드해 결과를 바로 보여준다.
    // (와인파티는 자동 마감 → X 전환, 1:1은 관리자가 직접 종료)
    const eventId = auth?.user?.event_id ?? null;
    useEffect(() => {
        if (!eventId) {
            return undefined;
        }
        const channelName = `event-rotation.${eventId}`;
        const channel = echo.private(channelName);

        channel.listen('.event.closed', () => {
            router.reload();
        });
        // 관리자가 '매칭 시작(V)'하면 이 화면을 리로드해 '매칭하기'를 바로 노출한다.
        channel.listen('.event.voting.opened', () => {
            router.reload();
        });
        // 로테이션 진행으로 상태/후보가 바뀌는 경우(와인파티 투표중 등)도 갱신.
        channel.listen('.rotation.updated', () => {
            router.reload();
        });

        return () => {
            echo.leave(`private-${channelName}`);
        };
    }, [eventId]);

    const matches = candidates.filter((c) => c.is_match);
    // 나를 선택해준 사람(종료 후 공개). 1:1에서 번호 공개 대상.
    const votedForMe = candidates.filter((c) => c.voted_for_me);

    // 종료 후 노출 목록을 두 그룹으로 나눈다.
    //  - myPickList : 내가 선택한 사람 (상호매칭 포함). ⭐ 섹션 위에 노출.
    //  - pickedMeList : 내가 선택하진 않았지만 "나를 선택해준 사람". ⭐ 섹션 아래에 노출(1:1).
    // 투표중(미종료)에는 전체 후보를 토글용으로 그대로 보여준다.
    const rank = (c) => (c.is_match ? 2 : c.voted_for_me ? 1 : 0);
    const myPickList = isClosed
        ? candidates
              .filter((c) => c.voted)
              .slice()
              .sort((a, b) => rank(b) - rank(a))
        : candidates;
    const pickedMeList = isClosed && isOneToOne
        ? candidates.filter((c) => c.voted_for_me && !c.voted)
        : [];

    // 매칭중(V)이지만 마감 시각이 지나 투표만 닫힌 '결과 집계 대기' 상태
    if (votingEnded) {
        return (
            <AppLayout center>
                <Head title="Rotable" />
                <div className="flex w-full flex-col bg-gray-50 p-4">
                    <Placeholder
                        icon="⏳"
                        title="매칭 마감"
                        desc={
                            <>
                                매칭 투표가 마감되었어요.<br />
                                결과를 집계하고 있으니 잠시만 기다려 주세요.<br />
                                (최대 1시간 소요)
                            </>
                        }
                    />
                </div>
            </AppLayout>
        );
    }

    // 매칭 투표가 아직 열리지 않은 상태(대기/진행)
    if (!canVote && !isClosed) {
        return (
            <AppLayout center>
                <Head title="Rotable" />
                <div className="flex w-full flex-col bg-gray-50 p-4">
                    <Placeholder
                        icon={isOneToOne ? '💌' : '💝'}
                        title={isOneToOne ? '로테이블 진행중' : '매칭하기'}
                        desc={
                            isOneToOne ? (
                                <>
                                    매칭 선택은 <b>홈</b>에서 할 수 있어요.
                                    <br />
                                    모든 로테이션이 끝나고 관리자가 매칭을 마감하면
                                    <br />
                                    이곳에서 결과를 확인할 수 있어요.
                                </>
                            ) : (
                                <>
                                    아직 매칭 투표가 열리지 않았어요.
                                    <br />
                                    로테이션이 끝나면 시작됩니다.
                                </>
                            )
                        }
                    />
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="Rotable" />

            {/* 투표중: 컨텐츠 높이만큼만 차지. 종료: 화면을 채워 위로 메시지를 중앙 배치. */}
            <div className={'flex flex-col bg-gray-50 px-4 py-5 ' + (isClosed ? 'min-h-full flex-1 ' : '') + (canVote ? 'pb-20' : '')}>
                {!isClosed && (
                    <>
                        <h1 className="text-xl font-extrabold">매칭하기 💝</h1>
                        <p className="mt-1.5 text-sm text-gray-500">
                            {`마음에 든 상대를 최대 ${maxVotes}명까지 선택하세요. 서로 선택하면 매칭이에요! 💘`}
                        </p>
                    </>
                )}

                {/* 종료(결과): 따뜻한 헤더 + 이번 행사 커플 수 안내 */}
                {isClosed && (
                    <div className="overflow-hidden rounded-3xl bg-gradient-to-br from-rose-400 via-pink-400 to-rose-500 p-5 text-center text-white shadow-lg shadow-rose-200">
                        <p className="text-3xl">{coupleCount > 0 ? '💕' : '😳'}</p>
                        <h1 className="mt-1 text-xl font-extrabold tracking-tight">
                            {coupleCount > 0 ? '매칭 결과가 나왔어요!' : '어...? 이게 무슨 일이죠?!'}
                        </h1>
                        <p className="mt-1 text-[13px] leading-relaxed text-white/90">
                            {coupleCount > 0 ? (
                                <>
                                    설레는 시간을 함께해 주셔서 고마워요.<br />
                                    이번 로테이블에서 이렇게 인연이 피어났답니다 🌸
                                </>
                            ) : (
                                <>
                                    믿기지 않지만… 이번 회차는 <b>단 한 커플도</b> 탄생하지 않았어요 🫢<br />
                                    이런 일은 정말 드문데, 큐피드가 오늘 하루 월차를 냈나 봐요 🏹💤<br />
                                    다음 로테이블에서는 분명 반전이 기다리고 있을 거예요 🔥
                                </>
                            )}
                        </p>
                        {coupleCount > 0 && (
                            <div className="mt-4 inline-flex items-center gap-2 rounded-full bg-white/20 px-5 py-2 backdrop-blur">
                                <span className="text-lg">{isOneToOne ? '💑' : '🥂'}</span>
                                <span className="text-sm font-semibold">
                                    이번 로테이블에서{' '}
                                    <span className="text-lg font-extrabold">{coupleCount}</span>
                                    커플이 탄생했어요
                                </span>
                            </div>
                        )}
                    </div>
                )}

                {/* 투표중 안내: 결과 공개 시점/규칙 안내 (현재 시스템 로직 기준) */}
                {canVote && (
                    <div className="mt-3 space-y-1.5 rounded-2xl border border-amber-200 bg-amber-50 p-3.5 text-xs leading-relaxed text-amber-800">
                        <p>📝 선택은 <b>다음 날 오전 9시 전까지</b> 자유롭게 수정할 수 있어요.</p>
                        <p>💘 <b>서로(쌍방) 선택한 상대</b>와만 매칭되어 <b>연락처가 공개</b>돼요.</p>
                        <p>⭐ 매칭이 안 되더라도 <b>나를 선택한 투표수</b>는 확인할 수 있어요.</p>
                        <p>⏰ 결과는 <b>다음 날 오전 9시 이후</b>에 공개돼요.</p>
                        <p className="text-amber-600">※ 처리 시점에 따라 공개까지 최대 1시간 정도 걸릴 수 있어요.</p>
                    </div>
                )}

                {/* 종료 후: 나의 매칭 결과 요약 (사랑스럽게) */}
                {isClosed && matches.length > 0 && (
                    <div className="mt-4 rounded-2xl border border-rose-200 bg-gradient-to-r from-rose-50 to-pink-50 p-4 text-center">
                        <p className="text-2xl">💘</p>
                        <p className="mt-1 text-base font-extrabold text-rose-600">
                            축하해요! {matches.length}명과 서로 통했어요
                        </p>
                        <p className="mt-1 text-xs leading-relaxed text-rose-500/90">
                            아래에서 연락처를 확인하고 먼저 용기 내어 연락해 보세요 📞<br />
                            좋은 인연으로 이어지길 진심으로 응원할게요!
                        </p>
                    </div>
                )}

                {/* 종료 + 내 매칭(쌍방) 없음: 위로 메시지를 중앙 배치 (매칭 0이면 무조건 노출) */}
                {isClosed && matches.length === 0 && (
                    <div className="mt-4 flex flex-1 flex-col items-center justify-center rounded-3xl border border-rose-100 bg-white px-5 py-12 text-center shadow-sm">
                        <p className="text-5xl">🍀</p>
                        <p className="mt-4 text-lg font-extrabold text-secondary-700">
                            이번엔 서로 닿지 못했지만 괜찮아요
                        </p>
                        <p className="mt-3 text-sm leading-relaxed text-gray-500">
                            설레는 마음으로 참여해 주신 것만으로 충분히 멋졌어요.<br />
                            인연은 늘 타이밍이라, 분명 더 잘 맞는 사람이<br />
                            다음 자리에서 당신을 기다리고 있을 거예요. 💛
                        </p>
                        <p className="mt-4 rounded-full bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-500">
                            다음 로테이블에서 또 만나요! 🌷
                        </p>
                    </div>
                )}

                {/* 목록(위): 투표중=전체 후보 / 종료=내가 선택한 사람 */}
                {isClosed && myPickList.length > 0 && (
                    <p className="mt-5 mb-2 text-sm font-bold text-secondary-700">
                        내가 선택한 사람 💌
                    </p>
                )}
                {myPickList.length > 0 ? (
                    <div className={'grid grid-cols-1 content-start gap-2.5 ' + (isClosed ? '' : 'mt-4')}>
                        {myPickList.map((c) => (
                            <CandidateCard
                                key={c.id}
                                candidate={c}
                                tone={tone}
                                selected={selected.includes(c.id)}
                                disabled={selected.length >= maxVotes}
                                closed={isClosed}
                                onToggle={toggle}
                                onDetail={(m) => setDetailMember(m)}
                            />
                        ))}
                    </div>
                ) : (
                    // 종료 시에는 위의 매칭 요약/위로 안내로 충분하므로 빈 목록 문구를 띄우지 않는다.
                    !isClosed && (
                        <div className="mt-4 flex flex-1 flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white px-4 py-10 text-center text-sm text-gray-400">
                            선택할 수 있는 상대가 없어요.
                        </div>
                    )
                )}

                {/* 나를 선택해준 사람 수 요약 — 와인파티에서만(1:1은 아래 제목에 인원수 표기) */}
                {isClosed && !isOneToOne && (
                    <div className="mt-5 flex items-center gap-3 rounded-2xl border border-primary/15 bg-primary/5 p-4">
                        <span className="text-2xl">⭐</span>
                        <div>
                            <p className="text-xs font-medium text-gray-500">나를 선택해준 사람</p>
                            <p className="text-lg font-extrabold text-primary">{receivedCount}명</p>
                        </div>
                    </div>
                )}

                {/* 나를 선택해준 사람 리스트 — 제목에 인원수 표기(1:1). 0명이어도 안내 노출. */}
                {isClosed && isOneToOne && (
                    <>
                        <p className="mt-5 mb-1 text-sm font-bold text-secondary-700">
                            나를 선택해준 분들 ({pickedMeList.length}명) 💝
                        </p>
                        <p className="mb-2 text-xs leading-relaxed text-gray-400">
                            ※ 서로 매칭된 분은 위 결과에 포함되어 여기서는 제외됩니다.
                        </p>
                        {pickedMeList.length > 0 ? (
                            <div className="grid grid-cols-1 content-start gap-2.5">
                                {pickedMeList.map((c) => (
                                    <CandidateCard
                                        key={c.id}
                                        candidate={c}
                                        tone={tone}
                                        selected={false}
                                        disabled={true}
                                        closed={isClosed}
                                        onToggle={toggle}
                                        onDetail={(m) => setDetailMember(m)}
                                    />
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-center text-xs leading-relaxed text-rose-500/90 shadow-sm">
                                뭐… 어차피 제 눈에 차는 분이 없었잖아요~ 😌<br />
                                보는 눈 있는 사람은 다음에 나타나는 법이죠.<br />
                                아까운 건 저 말고 선택 안 한 상대랍니다 💁
                            </div>
                        )}
                    </>
                )}

                {/* 투표중일 때만 저장 바. 하단 nav(약 5.5rem) 바로 위에 fixed 로 고정 */}
                {canVote && (
                    <div className="fixed inset-x-0 bottom-[calc(5.5rem+env(safe-area-inset-bottom))] z-40 mx-auto w-full max-w-[480px] px-4">
                        <button
                            type="button"
                            onClick={save}
                            disabled={!dirty || saving}
                            className={
                                'w-full rounded-2xl py-3 text-center text-sm font-bold shadow-lg transition ' +
                                (dirty && !saving
                                    ? 'bg-primary text-white hover:bg-primary-600 active:scale-[0.99]'
                                    : 'cursor-not-allowed bg-secondary-200 text-secondary-400')
                            }
                        >
                            {saving ? '저장 중...' : `매칭 저장 (${selected.length}/${maxVotes})`}
                        </button>
                    </div>
                )}
            </div>

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
