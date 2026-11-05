{{--
    행사 운영 화면 공용 탭바 — 행사 상세 · 체크인 · 로테이션 진행.

    사용법: <x-event-tabs :event-id="$event->id" active="info|checkin|progress" />

    - 행사 선택: 고르면 "지금 보던 탭" 그대로 다른 행사로 이동한다. (목록으로 돌아갈 필요 없음)
    - 탭 배지: 체크인은 참석/전체, 로테이션 진행은 현재 턴을 한눈에 보여준다.
    - 주소/라우트 이름은 그대로 쓰므로 페이지 권한 설정에 영향이 없다.
--}}
@props(['eventId', 'active' => 'info'])

@php
    $ctx     = app(\App\Services\Rotation\EventContextService::class);
    $events  = $ctx->selectableEvents();
    $event   = $events->firstWhere('id', (int) $eventId);
    $sum     = $event ? $ctx->summary($event->id) : ['checkin_total' => 0, 'checkin_in' => 0];

    $statusLabel = \App\Models\EventRound::statusOptions();
    $typeLabel   = \App\Models\EventRound::typeOptions();
    // 상태 뱃지 색: 대기=노랑, 진행=초록, 매칭중=분홍, 종료=검정
    $statusCls   = ['P' => 'bg-yellow-lt', 'S' => 'bg-green-lt', 'V' => 'bg-pink-lt', 'X' => 'bg-dark text-white'];
    // 유형 뱃지 색: 와인파티=주황, 1:1=보라 (만남 이력 화면과 동일)
    $typeCls     = ['wine' => 'tab-type-wine', 'one' => 'tab-type-one'];

    $weekdays = ['일', '월', '화', '수', '목', '금', '토'];
    $fmtDate  = fn ($d) => $d ? ($d->format('n.j') . '(' . $weekdays[$d->dayOfWeek] . ') ' . $d->format('H:i')) : '일정 미정';

    // 탭 URL. 행사 선택 시 같은 탭으로 이동하기 위해 __ID__ 자리표시자 버전도 만든다.
    $tabUrl = function (string $key, $id): string {
        return match ($key) {
            'info'     => route('admin.rotation.event.detail', ['id' => $id]),
            'checkin'  => route('admin.rotation.event.checkin', ['id' => $id]),
            default    => route('admin.rotation.seating.index', ['eventId' => $id]),
        };
    };
    $tabDefs = [
        'info'     => ['label' => '행사 상세'],
        'checkin'  => ['label' => '체크인'],
        'progress' => ['label' => '로테이션 진행'],
    ];
    $activeKey = array_key_exists($active, $tabDefs) ? $active : 'info';
@endphp

@if ($event)
<style>
    /* 탭을 "버튼"처럼 보이게 + 탭마다 고유 색.
       안 눌린 탭: 해당 색의 연한 배경·테두리·글자 / 눌린 탭: 해당 색으로 꽉 채움 */
    .event-tabs .event-tabs__nav { display: flex; flex-wrap: wrap; gap: .5rem; margin: 0; padding: 0; list-style: none; }
    .event-tabs .event-tabs__btn {
        --tab-solid: #206bc4; --tab-soft: #e7f0fb; --tab-border: #b9d3f0; --tab-hover: #d6e6f8; --tab-dark: #1a5aa6;
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .5rem 1rem; border-radius: 8px;
        font-weight: 700; font-size: .95rem; line-height: 1.2; text-decoration: none;
        color: var(--tab-dark); background: var(--tab-soft); border: 1px solid var(--tab-border);
        transition: background .12s ease, border-color .12s ease, color .12s ease;
    }
    .event-tabs .event-tabs__btn:hover { background: var(--tab-hover); }
    .event-tabs .event-tabs__btn.is-active {
        color: #fff; background: var(--tab-solid); border-color: var(--tab-solid);
        box-shadow: 0 2px 6px rgba(0,0,0,.22);
    }
    .event-tabs .event-tabs__btn.is-active:hover { background: var(--tab-dark); border-color: var(--tab-dark); }
    /* 행사 상세=파랑 / 체크인=청록 / 로테이션 진행=보라 */
    .event-tabs .tab-info     { --tab-solid: #206bc4; --tab-soft: #e7f0fb; --tab-border: #b9d3f0; --tab-hover: #d6e6f8; --tab-dark: #1a5aa6; }
    .event-tabs .tab-checkin  { --tab-solid: #0ca678; --tab-soft: #e3f6ef; --tab-border: #a9e2cf; --tab-hover: #cfeee2; --tab-dark: #087f5b; }
    .event-tabs .tab-progress { --tab-solid: #ae3ec9; --tab-soft: #f5e8fa; --tab-border: #dcb4ea; --tab-hover: #ecd3f5; --tab-dark: #8a2fa1; }
    .event-tabs .event-tabs__btn .badge { font-weight: 600; }
    .event-tabs .event-tabs__btn.is-active .badge { background: rgba(255,255,255,.22) !important; color: #fff !important; }
    .tab-type-wine { background: #fdeee3; color: #c2410c; }
    .tab-type-one  { background: #e9e4fb; color: #5b21b6; }
    .event-tabs__picker { min-width: 260px; max-width: 440px; flex: 1 1 280px; }
</style>

<div class="card mb-3 event-tabs">
    <div class="card-body py-2">
        <div class="d-flex align-items-center flex-wrap gap-2 gap-md-3">

            {{-- 행사 선택 --}}
            <div class="event-tabs__picker">
                <label class="visually-hidden" for="eventTabsSelect">행사 선택</label>
                <select class="form-select" id="eventTabsSelect">
                    @foreach ($events as $e)
                        <option value="{{ $e->id }}" {{ $e->id === $event->id ? 'selected' : '' }}>
                            [{{ $statusLabel[$e->status] ?? $e->status }}] {{ $e->title }} · {{ $fmtDate($e->event_time) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 현재 행사 상태/유형 --}}
            <div class="d-flex align-items-center gap-1">
                <span class="badge {{ $statusCls[$event->status] ?? 'bg-secondary-lt' }}"
                      id="eventTabsStatusBadge"
                      data-event-id="{{ $event->id }}">{{ $statusLabel[$event->status] ?? $event->status }}</span>
                <span class="badge {{ $typeCls[$event->type] ?? 'bg-azure-lt' }}">{{ $typeLabel[$event->type] ?? $event->type }}</span>
            </div>

            {{-- 탭 (버튼형, 아이콘 없음) --}}
            <ul class="event-tabs__nav ms-md-auto" aria-label="행사 운영 화면">
                @foreach ($tabDefs as $key => $tab)
                    <li>
                        <a class="event-tabs__btn tab-{{ $key }} {{ $activeKey === $key ? 'is-active' : '' }}"
                           href="{{ $tabUrl($key, $event->id) }}"
                           @if ($activeKey === $key) aria-current="page" @endif>
                            {{ $tab['label'] }}
                            @if ($key === 'checkin')
                                <span class="badge bg-green-lt">참석 {{ $sum['checkin_in'] }}/{{ $sum['checkin_total'] }}</span>
                            @elseif ($key === 'progress')
                                <span class="badge bg-azure-lt" data-event-tabs-round>
                                    @if ($event->status === \App\Models\EventRound::STATUS_CLOSED)
                                        종료
                                    @elseif ($event->status === \App\Models\EventRound::STATUS_VOTING)
                                        매칭중
                                    @else
                                        {{ (int) $event->current_round > 0 ? ((int) $event->current_round . '턴') : '진행 전' }}
                                    @endif
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

<script>
    // 행사 선택 → 지금 보던 탭 그대로 다른 행사로 이동
    (function(){
        var tabUrlTpl = @json($tabUrl($activeKey, '__ID__'));
        var sel = document.getElementById('eventTabsSelect');
        if(!sel || !tabUrlTpl){ return; }
        sel.addEventListener('change', function(){
            if(sel.value){ location.href = tabUrlTpl.replace('__ID__', encodeURIComponent(sel.value)); }
        });
    })();
</script>
@endif
