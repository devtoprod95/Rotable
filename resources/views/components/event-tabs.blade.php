{{--
    행사 운영 화면 공용 탭바 — 행사 상세 · 체크인 · 로테이션 진행 · 페이백 · 매출.

    사용법: <x-event-tabs :event-id="$event->id" active="info|checkin|progress|payback|revenue" />

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
    // 상태/유형 칩 색은 아래 CSS 의 data-status / data-type 으로 정한다.

    $weekdays = ['일', '월', '화', '수', '목', '금', '토'];
    $fmtDate  = fn ($d) => $d ? ($d->format('n.j') . '(' . $weekdays[$d->dayOfWeek] . ') ' . $d->format('H:i')) : '일정 미정';

    // 탭 URL. 행사 선택 시 같은 탭으로 이동하기 위해 __ID__ 자리표시자 버전도 만든다.
    $tabUrl = function (string $key, $id): string {
        return match ($key) {
            'info'    => route('admin.rotation.event.detail', ['id' => $id]),
            'checkin' => route('admin.rotation.event.checkin', ['id' => $id]),
            'payback' => route('admin.rotation.event.payback', ['id' => $id]),
            'revenue' => route('admin.rotation.event.revenue', ['id' => $id]),
            default   => route('admin.rotation.seating.index', ['eventId' => $id]),
        };
    };
    $tabDefs = [
        'info'     => ['label' => '행사 상세'],
        'checkin'  => ['label' => '체크인'],
        'progress' => ['label' => '로테이션 진행'],
        'payback'  => ['label' => '페이백'],
        'revenue'  => ['label' => '매출'],
    ];
    $activeKey = array_key_exists($active, $tabDefs) ? $active : 'info';
@endphp

@if ($event)
<style>
    /* 공용 탭바: ① 행사(선택 · 상태/유형 · 입장 QR)  ② 운영 화면(4단계 탭). 각 줄 앞에 영역 이름.
       PC(lg+): 영역 이름은 왼쪽 칸, 내용은 오른쪽 / 모바일: 영역 이름이 내용 위에 */
    .event-tabs .card-body { display: flex; flex-direction: column; gap: .75rem; padding: .85rem 1rem; }
    .event-tabs__row { display: flex; flex-direction: column; gap: .4rem; }
    .event-tabs__row + .event-tabs__row { padding-top: .75rem; border-top: 1px solid #eef0f3; }
    .event-tabs__label { display: flex; align-items: center; gap: .3rem; font-size: .78rem; font-weight: 800; color: #868e96; white-space: nowrap; }
    .event-tabs__body { min-width: 0; flex: 1 1 auto; }
    @media (min-width: 992px) {
        .event-tabs__row { flex-direction: row; align-items: center; gap: 1rem; }
        .event-tabs__label { flex: 0 0 84px; }
    }

    /* ① 행사 줄: 선택(넓게) · 상태/유형 칩 · 입장 QR */
    .event-tabs__event { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    .event-tabs__picker { flex: 1 1 320px; min-width: 0; }
    .event-tabs__picker .form-select { min-height: 42px; font-weight: 600; }
    .event-tabs__meta { display: flex; align-items: center; gap: .4rem; }
    .event-tabs__chip {
        display: inline-flex; align-items: center; gap: .4rem;
        min-height: 42px; padding: .3rem .75rem; border-radius: 999px;
        font-size: .9rem; line-height: 1.2; color: #fff; background: #667382; white-space: nowrap;
    }
    .event-tabs__chip b { font-weight: 800; }
    .event-tabs__chip-label { font-size: .72rem; font-weight: 700; opacity: .85; padding-right: .4rem; border-right: 1px solid rgba(255,255,255,.45); }
    /* 상태: 대기=주황 / 진행=초록 / 매칭중=분홍 / 종료=검정 */
    .event-tabs__status[data-status="P"] { background: #f59f00; }
    .event-tabs__status[data-status="S"] { background: #2fb344; }
    .event-tabs__status[data-status="V"] { background: #d6336c; }
    .event-tabs__status[data-status="X"] { background: #1e293b; }
    /* 유형: 1:1=보라 / 와인파티=주황빛 빨강 */
    .event-tabs__type[data-type="one"]  { background: #7048e8; }
    .event-tabs__type[data-type="wine"] { background: #e8590c; }
    .event-tabs__qr {
        display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
        min-height: 42px; padding: .4rem 1rem; border-radius: 8px; border: 1px solid #1e293b;
        font-weight: 700; font-size: .92rem; color: #fff; background: #1e293b; white-space: nowrap; cursor: pointer;
    }
    .event-tabs__qr:hover { background: #0f172a; border-color: #0f172a; }

    /* ② 운영 화면 줄: 행사 상세 → 체크인 → 로테이션 진행 → 페이백 → 매출 (같은 폭, 번호로 순서 표시) */
    .event-tabs__nav { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .5rem; margin: 0; padding: 0; list-style: none; }
    .event-tabs__nav > li { display: flex; min-width: 0; }
    .event-tabs__btn {
        --tab-solid: #206bc4; --tab-soft: #e7f0fb; --tab-border: #b9d3f0; --tab-hover: #d6e6f8; --tab-dark: #1a5aa6;
        display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: .3rem .4rem;
        width: 100%; min-height: 46px; padding: .45rem .6rem; border-radius: 10px;
        font-weight: 700; font-size: .95rem; line-height: 1.2; text-align: center; text-decoration: none; word-break: keep-all;
        color: var(--tab-dark); background: var(--tab-soft); border: 1px solid var(--tab-border);
        transition: background .12s ease, border-color .12s ease, color .12s ease;
    }
    .event-tabs__btn:hover { background: var(--tab-hover); color: var(--tab-dark); }
    .event-tabs__btn.is-active { color: #fff; background: var(--tab-solid); border-color: var(--tab-solid); box-shadow: 0 2px 6px rgba(0,0,0,.22); }
    .event-tabs__btn.is-active:hover { color: #fff; background: var(--tab-dark); border-color: var(--tab-dark); }
    .event-tabs__step {
        display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto;
        width: 1.3rem; height: 1.3rem; border-radius: 50%; font-size: .72rem; font-weight: 800;
        color: #fff; background: var(--tab-solid);
    }
    .event-tabs__btn.is-active .event-tabs__step { color: var(--tab-solid); background: #fff; }
    .event-tabs__btn .badge { font-weight: 600; }
    .event-tabs__btn.is-active .badge { background: rgba(255,255,255,.22) !important; color: #fff !important; }
    /* 행사 상세=파랑 / 체크인=청록 / 로테이션 진행=보라 / 페이백=주황 / 매출=초록 */
    .event-tabs .tab-info     { --tab-solid: #206bc4; --tab-soft: #e7f0fb; --tab-border: #b9d3f0; --tab-hover: #d6e6f8; --tab-dark: #1a5aa6; }
    .event-tabs .tab-checkin  { --tab-solid: #0ca678; --tab-soft: #e3f6ef; --tab-border: #a9e2cf; --tab-hover: #cfeee2; --tab-dark: #087f5b; }
    .event-tabs .tab-progress { --tab-solid: #ae3ec9; --tab-soft: #f5e8fa; --tab-border: #dcb4ea; --tab-hover: #ecd3f5; --tab-dark: #8a2fa1; }
    .event-tabs .tab-payback  { --tab-solid: #f76707; --tab-soft: #fff0e6; --tab-border: #fcc8a3; --tab-hover: #ffe0cc; --tab-dark: #d9480f; }
    .event-tabs .tab-revenue  { --tab-solid: #2b8a3e; --tab-soft: #ebfbee; --tab-border: #b2f2bb; --tab-hover: #d3f9d8; --tab-dark: #237032; }

    /* 태블릿 이하: 행사 선택은 한 줄 전체, 칩·QR 은 다음 줄에 나란히 */
    @media (max-width: 767.98px) {
        .event-tabs .card-body { padding: .75rem; }
        .event-tabs__picker { flex: 1 1 100%; }
        .event-tabs__meta { flex: 1 1 auto; }
        .event-tabs__chip { flex: 1 1 0; justify-content: center; min-height: 40px; padding: .3rem .5rem; font-size: .85rem; }
        .event-tabs__qr { min-height: 40px; padding: .35rem .75rem; }
        .event-tabs__btn { flex-direction: column; min-height: 58px; padding: .4rem .25rem; font-size: .85rem; gap: .2rem; }
        .event-tabs__btn .badge { font-size: .68rem; padding: .18rem .35rem; }
        .event-tabs__step { width: 1.15rem; height: 1.15rem; font-size: .66rem; }
    }
    /* 태블릿 이하: 탭 5개를 3 + 2 로 (6칸 격자에서 윗줄 2칸씩, 아랫줄 3칸씩) */
    @media (max-width: 767.98px) {
        .event-tabs__nav { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        .event-tabs__nav > li { grid-column: span 2; }
        .event-tabs__nav > li:nth-child(n+4) { grid-column: span 3; }
    }
    /* 아주 좁은 화면(400px 미만): 2칸씩, 마지막 하나는 한 줄 전체. 가로 배치 */
    @media (max-width: 399.98px) {
        .event-tabs__nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .event-tabs__nav > li, .event-tabs__nav > li:nth-child(n+4) { grid-column: auto; }
        .event-tabs__nav > li:last-child:nth-child(odd) { grid-column: 1 / -1; }
        .event-tabs__btn { flex-direction: row; min-height: 48px; }
    }
</style>

<div class="card mb-3 event-tabs">
    <div class="card-body">

        {{-- ① 행사: 선택 · 상태/유형 · 입장 QR --}}
        <div class="event-tabs__row">
            <div class="event-tabs__label"><i class="ti ti-calendar-event"></i> 행사</div>
            <div class="event-tabs__body event-tabs__event">
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

                {{-- 상태/유형 칩. (로테이션 진행 화면은 data-status 와 안쪽 <b> 글자만 실시간 갱신) --}}
                <div class="event-tabs__meta">
                    <span class="event-tabs__chip event-tabs__status" id="eventTabsStatusBadge"
                          data-event-id="{{ $event->id }}" data-status="{{ $event->status }}">
                        <span class="event-tabs__chip-label">상태</span>
                        <b>{{ $statusLabel[$event->status] ?? $event->status }}</b>
                    </span>
                    <span class="event-tabs__chip event-tabs__type" data-type="{{ $event->type }}">
                        <span class="event-tabs__chip-label">유형</span>
                        <b>{{ $typeLabel[$event->type] ?? $event->type }}</b>
                    </span>
                </div>

                {{-- 입장등록 QR — 모든 운영 화면 공통 --}}
                <button type="button" class="event-tabs__qr" id="eventTabsQrBtn"
                        data-event-id="{{ $event->id }}" data-event-label="{{ $event->label }}" aria-label="입장등록 QR 보기">
                    <i class="ti ti-qrcode"></i> 입장 QR
                </button>
            </div>
        </div>

        {{-- ② 운영 화면: 순서대로 1~4 --}}
        <div class="event-tabs__row">
            <div class="event-tabs__label"><i class="ti ti-layout-navbar"></i> 운영 화면</div>
            <ul class="event-tabs__body event-tabs__nav" aria-label="행사 운영 화면">
                @foreach ($tabDefs as $key => $tab)
                    <li>
                        <a class="event-tabs__btn tab-{{ $key }} {{ $activeKey === $key ? 'is-active' : '' }}"
                           href="{{ $tabUrl($key, $event->id) }}"
                           @if ($activeKey === $key) aria-current="page" @endif>
                            <span class="d-inline-flex align-items-center gap-1">
                                <span class="event-tabs__step" aria-hidden="true">{{ $loop->iteration }}</span>{{ $tab['label'] }}
                            </span>
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

@include('admin.rotation._registerQrModal')

<script>
    // 입장 QR 버튼
    (function(){
        var btn = document.getElementById('eventTabsQrBtn');
        if(!btn){ return; }
        btn.addEventListener('click', function(){
            if(window.RegisterQrModal){ RegisterQrModal.open(btn.dataset.eventId, btn.dataset.eventLabel || ''); }
        });
    })();

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
