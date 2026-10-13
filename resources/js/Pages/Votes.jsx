import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Placeholder from '@/Components/Placeholder';

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
function CandidateCard({ candidate, tone, selected, disabled, closed, onToggle }) {
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

    return (
        <button
            type="button"
            disabled={!clickable}
            onClick={() => clickable && onToggle(candidate.id)}
            className={
                'w-full rounded-xl border p-3 text-left transition ' +
                toneCls +
                (selected ? ' ring-2 ring-primary ring-offset-1' : '') +
                (clickable ? ' active:scale-[0.99]' : closed ? '' : ' opacity-50')
            }
        >
            <div className="flex items-center gap-2">
                <span className="text-base">{tone === 'female' ? '👩' : '👨'}</span>
                <span className="font-bold">{candidate.name}</span>

                {candidate.is_match && (
                    <span className="rounded-full bg-primary px-1.5 py-0.5 text-[10px] font-bold text-white">
                        💘 매칭
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

            {/* 종료 후 상호 매칭된 상대의 연락처 공개 */}
            {closed && candidate.is_match && candidate.phone && (
                <div className="mt-2 flex items-center gap-1.5 rounded-lg bg-white/80 px-2.5 py-1.5">
                    <span className="text-sm">📞</span>
                    <a href={`tel:${candidate.phone}`} className="text-sm font-bold text-primary">
                        {candidate.phone}
                    </a>
                </div>
            )}
        </button>
    );
}

export default function Votes() {
    const {
        canVote = false,
        isClosed = false,
        votingEnded = false,
        maxVotes = 2,
        candidates = [],
        myVotedIds = [],
        receivedCount = 0,
        partnerGender,
    } = usePage().props;

    const tone = partnerGender === 'F' ? 'female' : 'male';

    const [selected, setSelected] = useState(() => myVotedIds.map(Number));
    const [saving, setSaving] = useState(false);

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

    const matches = candidates.filter((c) => c.is_match);

    // 종료 후에는 "내가 선택한 사람"만 리스트에 노출한다. (투표중에는 전체 후보)
    const displayList = isClosed ? candidates.filter((c) => c.voted) : candidates;

    // 매칭중(V)이지만 마감 시각이 지나 투표만 닫힌 '결과 집계 대기' 상태
    if (votingEnded) {
        return (
            <AppLayout>
                <Head title="Rotable" />
                <div className="flex min-h-full flex-1 flex-col bg-gray-50 p-4">
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
            <AppLayout>
                <Head title="Rotable" />
                <div className="flex min-h-full flex-1 flex-col bg-gray-50 p-4">
                    <Placeholder
                        icon="💝"
                        title="매칭하기"
                        desc="아직 매칭 투표가 열리지 않았어요. 로테이션이 끝나면 시작됩니다."
                    />
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="Rotable" />

            {/*
              하단 영역 패딩: 투표중일 때만 고정 저장 바 높이만큼 확보한다.
              (하단 nav 피하기용 pb-20 은 레이아웃의 main 에 이미 적용되어 있음)
            */}
            <div className={'flex min-h-full flex-1 flex-col bg-gray-50 px-4 py-5 ' + (canVote ? 'pb-24' : '')}>
                <h1 className="text-xl font-extrabold">
                    {isClosed ? '매칭 결과 💘' : '매칭하기 💝'}
                </h1>
                <p className="mt-1.5 text-sm text-gray-500">
                    {isClosed
                        ? '서로(쌍방) 선택한 상대와만 매칭되어 연락처가 공개돼요. 아래에서 확인하세요!'
                        : `마음에 든 상대를 최대 ${maxVotes}명까지 선택하세요. 서로 선택하면 매칭이에요! 💘`}
                </p>

                {/* 투표중 안내: 결과 공개 시점/규칙 안내 (현재 시스템 로직 기준) */}
                {canVote && (
                    <div className="mt-3 space-y-1.5 rounded-2xl border border-amber-200 bg-amber-50 p-3.5 text-xs leading-relaxed text-amber-800">
                        <p>📝 선택은 <b>마감 전까지 자유롭게 수정</b>할 수 있어요.</p>
                        <p>💘 <b>서로(쌍방) 선택한 상대</b>와만 매칭되어 <b>연락처가 공개</b>돼요.</p>
                        <p>⭐ 매칭이 안 되더라도 <b>나를 선택한 투표수</b>는 확인할 수 있어요.</p>
                        <p>⏰ 결과는 <b>행사 다음 날 오전 9시 이후</b>에 공개돼요.</p>
                        <p className="text-amber-600">※ 처리 시점에 따라 공개까지 최대 1시간 정도 걸릴 수 있어요.</p>
                    </div>
                )}

                {/* 나를 선택한 사람 수 — 매칭이 끝난(종료) 뒤에만 공개 */}
                {isClosed && (
                    <div className="mt-4 flex items-center gap-3 rounded-2xl border border-primary/15 bg-primary/5 p-4">
                        <span className="text-2xl">⭐</span>
                        <div>
                            <p className="text-xs font-medium text-gray-500">나를 선택한 사람 (매칭과 무관한 전체 투표수)</p>
                            <p className="text-lg font-extrabold text-primary">{receivedCount}명</p>
                        </div>
                    </div>
                )}

                {/* 종료 후: 매칭 요약 */}
                {isClosed && matches.length > 0 && (
                    <div className="mt-4">
                        <p className="text-sm font-bold text-secondary-700">
                            🎉 {matches.length}명과 매칭됐어요!
                        </p>
                    </div>
                )}

                {/* 종료 + 매칭 없음: 위로 메시지를 남은 공간 전체를 채워 중앙에 배치 */}
                {isClosed && matches.length === 0 && (
                    <div className="mt-4 flex flex-1 flex-col items-center justify-center rounded-2xl border border-rose-100 bg-rose-50 px-4 py-10 text-center">
                        <p className="text-4xl">🍀</p>
                        <p className="mt-3 text-base font-bold text-secondary-700">
                            이번엔 서로 닿지 못했지만 괜찮아요
                        </p>
                        <p className="mt-2 text-xs leading-relaxed text-gray-500">
                            인연은 타이밍이에요. 분명 더 잘 맞는 사람이 기다리고 있을 거예요.<br />
                            다음 로테이블에서 또 만나요! 💛
                        </p>
                    </div>
                )}

                {/* 목록: 투표중=전체 후보 / 종료=내가 선택한 사람 (홈 카드 UI와 동일) */}
                {isClosed && displayList.length > 0 && (
                    <p className="mt-5 mb-2 text-sm font-bold text-secondary-700">
                        내가 선택한 사람 💌
                    </p>
                )}
                {displayList.length > 0 ? (
                    <div className={'grid flex-1 grid-cols-1 content-start gap-2.5 ' + (isClosed ? '' : 'mt-4')}>
                        {displayList.map((c) => (
                            <CandidateCard
                                key={c.id}
                                candidate={c}
                                tone={tone}
                                selected={selected.includes(c.id)}
                                disabled={selected.length >= maxVotes}
                                closed={isClosed}
                                onToggle={toggle}
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
            </div>

            {/* 투표중일 때만 저장 바. 하단 nav(약 72px)보다 확실히 위에 배치 */}
            {canVote && (
                <div className="pointer-events-none fixed inset-x-0 bottom-24 z-40 mx-auto flex w-full max-w-[480px] justify-center px-4">
                    <button
                        type="button"
                        onClick={save}
                        disabled={!dirty || saving}
                        className={
                            'pointer-events-auto w-full rounded-2xl py-3 text-center text-sm font-bold shadow-lg transition ' +
                            (dirty && !saving
                                ? 'bg-primary text-white hover:bg-primary-600 active:scale-[0.99]'
                                : 'cursor-not-allowed bg-secondary-200 text-secondary-400')
                        }
                    >
                        {saving ? '저장 중...' : `매칭 저장 (${selected.length}/${maxVotes})`}
                    </button>
                </div>
            )}
        </AppLayout>
    );
}
