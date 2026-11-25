@extends('layouts.app')

@section('title', '매출 — ' . $event->title)

@section('content')
@php
    $genders = [\App\Models\Member::GENDER_MALE => '남', \App\Models\Member::GENDER_FEMALE => '여'];
@endphp
<style>
    /* 접기/펼치기 카드 머리 (페이백 화면과 같은 모양) */
    .rv-collapse-head{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; cursor:pointer; user-select:none; }
    .rv-collapse-head:focus-visible{ outline:3px solid #74c0fc; outline-offset:-3px; }
    .rv-head-side{ display:flex; align-items:center; flex-wrap:wrap; gap:.35rem; justify-content:flex-end; }
    .rv-head-badge{ font-size:.8rem; font-weight:700; }
    @media (max-width: 575.98px){ .rv-head-side{ width:100%; justify-content:flex-start; } }

    /* 요약 타일: 결제 → 수수료 → 페이백 → 실수입 → 비용 → 순이익 */
    .rv-stats{ display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.5rem; }
    @media (min-width: 768px){ .rv-stats{ grid-template-columns:repeat(3, minmax(0, 1fr)); } }
    @media (min-width: 1200px){ .rv-stats{ grid-template-columns:repeat(6, minmax(0, 1fr)); } }
    .rv-tile{ border-radius:12px; padding:.6rem .75rem; background:#f8f9fa; border-left:4px solid #adb5bd; min-width:0; }
    .rv-tile .rv-name{ font-size:.8rem; font-weight:700; color:#495057; }
    .rv-tile .rv-num{ font-size:clamp(1.05rem, 4.6vw, 1.45rem); font-weight:800; line-height:1.25; overflow-wrap:anywhere; }
    .rv-tile .rv-desc{ font-size:.75rem; color:#868e96; }
    .rv-tile.is-gross{ border-left-color:#206bc4; } .rv-tile.is-gross .rv-num{ color:#206bc4; }
    .rv-tile.is-minus .rv-num{ color:#d63939; }
    .rv-tile.is-income{ border-left-color:#0ca678; background:#e6f7f2; } .rv-tile.is-income .rv-num{ color:#087f5b; }
    .rv-tile.is-profit{ border-left-color:#2b8a3e; background:#ebfbee; } .rv-tile.is-profit .rv-num{ color:#2b8a3e; }
    .rv-tile.is-profit.is-loss{ border-left-color:#d63939; background:#fdecec; } .rv-tile.is-profit.is-loss .rv-num{ color:#d63939; }
    .rv-warn{ margin-top:.6rem; padding:.5rem .7rem; border-radius:10px; background:#fff4e6; color:#a6420c; font-size:.85rem; word-break:keep-all; }

    /* 플랫폼 · 성별: 플랫폼 색(문토=주황, 프립=청록) 카드 안에 성별 색(여=분홍, 남=파랑) 칸 */
    .rv-share{ margin-bottom:1rem; }
    .rv-share-label{ display:flex; justify-content:space-between; font-size:.8rem; font-weight:700; color:#868e96; margin-bottom:.3rem; }
    .rv-share-bar{ display:flex; height:14px; border-radius:999px; overflow:hidden; background:#f1f3f5; }
    .rv-share-bar > span{ display:block; height:100%; }
    .rv-share-legend{ display:flex; flex-wrap:wrap; gap:.4rem .9rem; margin-top:.4rem; font-size:.82rem; }
    .rv-share-legend i{ display:inline-block; width:.7rem; height:.7rem; border-radius:3px; margin-right:.3rem; vertical-align:-1px; }
    .rv-pf-grid{ display:grid; grid-template-columns:minmax(0, 1fr); gap:.85rem; }
    @media (min-width: 992px){ .rv-pf-grid{ grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    .rv-pf{ --pf:#868e96; --pf-soft:#f1f3f5; border:1px solid #e6e8eb; border-radius:14px; overflow:hidden; background:#fff; }
    .rv-pf.is-munto{ --pf:#f76707; --pf-soft:#fff4e6; }
    .rv-pf.is-frip{ --pf:#1098ad; --pf-soft:#e3fafc; }
    .rv-pf-head{ display:flex; align-items:center; justify-content:space-between; gap:.5rem; padding:.65rem .85rem; background:var(--pf); color:#fff; }
    .rv-pf-name{ font-weight:800; font-size:1.05rem; }
    .rv-pf-name small{ font-weight:600; opacity:.85; margin-left:.35rem; }
    .rv-pf-income{ text-align:right; line-height:1.15; }
    .rv-pf-income small{ display:block; font-size:.72rem; opacity:.85; }
    .rv-pf-income b{ font-size:1.2rem; }
    .rv-pf-body{ display:grid; grid-template-columns:1fr 1fr; gap:.5rem; padding:.65rem; background:var(--pf-soft); }
    .rv-g{ border-radius:12px; padding:.55rem .65rem; background:#fff; border-top:4px solid #adb5bd; min-width:0; }
    .rv-g.is-f{ border-top-color:#d6336c; }
    .rv-g.is-m{ border-top-color:#206bc4; }
    .rv-g-head{ display:flex; align-items:baseline; justify-content:space-between; gap:.35rem; }
    .rv-g-title{ font-weight:800; }
    .rv-g.is-f .rv-g-title{ color:#c2255c; }
    .rv-g.is-m .rv-g-title{ color:#1c5aa6; }
    .rv-g-people{ font-size:.82rem; font-weight:700; color:#495057; }
    .rv-g-price{ margin:.2rem 0 .1rem; font-size:.85rem; color:#495057; line-height:1.35; }
    .rv-g-price b{ font-size:.95rem; color:#212529; }
    .rv-g-price small{ display:block; font-size:.74rem; color:#7048e8; }
    .rv-g-price small b{ font-size:.78rem; color:#7048e8; }
    .rv-g-price.is-missing{ color:#d9480f; font-weight:700; font-size:.78rem; }
    .rv-g-chips{ display:flex; flex-wrap:wrap; gap:.2rem; margin:.25rem 0 .35rem; min-height:1.2rem; }
    .rv-g-chips .badge{ font-size:.68rem; }
    .rv-g-lines{ margin:0; font-size:.82rem; }
    .rv-g-lines > div{ display:flex; justify-content:space-between; gap:.35rem; line-height:1.6; }
    .rv-g-lines dt{ color:#868e96; font-weight:600; margin:0; }
    .rv-g-lines dd{ margin:0; font-weight:700; white-space:nowrap; }
    .rv-g-lines .is-minus dd{ color:#d63939; }
    .rv-g-lines .is-income{ margin-top:.2rem; padding-top:.25rem; border-top:1px dashed #dee2e6; }
    .rv-g-lines .is-income dt{ color:#212529; font-weight:800; }
    .rv-g-lines .is-income dd{ color:#087f5b; font-size:.95rem; font-weight:800; }
    .rv-g.is-empty{ opacity:.55; }
    .rv-pf-foot{ padding:.5rem .85rem .65rem; background:var(--pf-soft); border-top:1px solid rgba(0,0,0,.04); }
    .rv-gbar{ display:flex; height:8px; border-radius:999px; overflow:hidden; background:#e9ecef; }
    .rv-gbar .is-f{ background:#d6336c; } .rv-gbar .is-m{ background:#206bc4; }
    .rv-gbar-label{ display:flex; justify-content:space-between; font-size:.74rem; font-weight:700; margin-top:.25rem; }
    .rv-gbar-label .is-f{ color:#c2255c; } .rv-gbar-label .is-m{ color:#1c5aa6; }
    .rv-total{ display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.5rem; margin-top:.85rem; padding:.7rem .85rem; border-radius:12px; background:#212529; color:#fff; }
    @media (min-width: 768px){ .rv-total{ grid-template-columns:repeat(5, minmax(0, 1fr)); } }
    .rv-total > div small{ display:block; font-size:.72rem; opacity:.7; }
    .rv-total > div b{ font-size:1rem; }
    .rv-total .is-minus b{ color:#ffa8a8; }
    .rv-total .is-income b{ color:#8ce99a; font-size:1.1rem; }
    @media (max-width: 399.98px){ .rv-pf-body{ grid-template-columns:minmax(0, 1fr); } }

    /* 설정: 플랫폼 수수료율 */
    .rv-rate-grid{ display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.6rem; }
    @media (max-width: 399.98px){ .rv-rate-grid{ grid-template-columns:minmax(0, 1fr); } }
    .rv-rate-card{ border:1px solid #e6e8eb; border-radius:12px; padding:.6rem .75rem; border-left:4px solid #adb5bd; }
    .rv-rate-card.is-munto{ border-left-color:#f76707; }
    .rv-rate-card.is-frip{ border-left-color:#15aabf; }
    .rv-rate-card dl{ display:grid; grid-template-columns:auto 1fr; gap:.15rem .75rem; margin:0; font-size:.86rem; }
    .rv-rate-card dt{ color:#868e96; font-weight:700; }
    .rv-rate-card dd{ margin:0; text-align:right; font-weight:700; }
    .rv-foot{ display:flex; flex-direction:column; gap:.5rem; margin-top:.75rem; }
    .rv-foot .btn{ min-height:44px; }
    @media (min-width: 768px){ .rv-foot{ flex-direction:row; justify-content:flex-end; } }

    /* 비용 항목 */
    .rv-cost-row{ display:grid; grid-template-columns:minmax(0, 1fr) 140px 44px; gap:.4rem; margin-bottom:.4rem; }
    .rv-cost-row .form-control{ min-height:44px; }
    .rv-cost-row .btn{ min-height:44px; padding:0; }
    @media (max-width: 575.98px){ .rv-cost-row{ grid-template-columns:minmax(0, 1fr) 110px 44px; } }
    .rv-cost-total{ display:flex; justify-content:space-between; align-items:center; padding:.6rem .75rem; margin-top:.4rem; border-radius:10px; background:#f8f9fa; font-weight:800; }

    /* 신청자별 결제 유형 */
    .rv-people-bar{ margin-bottom:.75rem; }
    /* 남(좌) · 여(우) 두 열. 좁은 화면(lg 미만)에서는 남 → 여 순으로 위아래 */
    .rv-pcols{ display:grid; grid-template-columns:minmax(0, 1fr); gap:1rem; }
    @media (min-width: 992px){ .rv-pcols{ grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    .rv-pcol{ border:1px solid #e9ecef; border-radius:12px; overflow:hidden; background:#fff; }
    .rv-pcol-head{ display:flex; justify-content:space-between; align-items:center; gap:.5rem; width:100%; min-height:44px; padding:.55rem .85rem; border:0; text-align:left; font-weight:800; color:#fff; cursor:pointer; }
    .rv-pcol-head .rv-pcol-side{ display:inline-flex; align-items:center; gap:.4rem; }
    .rv-pcol-head .ti{ transition:transform .15s ease; }
    .rv-pcol.is-collapsed .rv-pcol-head .ti{ transform:rotate(-90deg); }
    .rv-pcol.is-collapsed .rv-pcol-body{ display:none; }
    .rv-pcol.is-m .rv-pcol-head{ background:#206bc4; }
    .rv-pcol.is-f .rv-pcol-head{ background:#d6336c; }
    .rv-pcol-head small{ font-weight:700; opacity:.9; }
    .rv-pcol-body{ padding:.25rem .75rem; }
    .rv-search{ min-height:42px; }
    .rv-person{ display:flex; flex-wrap:wrap; align-items:center; gap:.5rem .75rem; padding:.6rem .25rem; border-bottom:1px solid #f1f3f5; }
    .rv-person:last-child{ border-bottom:0; }
    .rv-pno{ flex:0 0 auto; min-width:40px; height:36px; padding:0 .35rem; border-radius:9px; background:#206bc4; color:#fff; font-weight:800; display:inline-flex; align-items:center; justify-content:center; }
    .rv-pno.is-f{ background:#d6336c; }
    .rv-pwho{ flex:1 1 180px; min-width:0; }
    .rv-pwho .rv-pname{ font-weight:800; overflow-wrap:anywhere; }
    .rv-pwho .rv-pbadges{ display:flex; flex-wrap:wrap; gap:.25rem; margin-top:.15rem; }
    .rv-pmoney{ flex:0 0 auto; text-align:right; font-size:.85rem; line-height:1.35; }
    .rv-pmoney b{ font-size:1rem; }
    .rv-pctrl{ flex:0 0 auto; display:flex; flex-direction:column; gap:.3rem; width:228px; }
    .rv-pbrate{ min-height:38px; font-size:.85rem; font-weight:700; }
    .rv-seg{ flex:0 0 auto; display:grid; grid-template-columns:repeat(3, 1fr); gap:.25rem; width:100%; }
    .rv-seg button{ min-height:38px; border-radius:9px; border:1px solid #dee2e6; background:#fff; color:#495057; font-weight:700; font-size:.85rem; padding:0 .25rem; }
    .rv-seg button.is-on[data-type="N"]{ background:#495057; border-color:#495057; color:#fff; }
    .rv-seg button.is-on[data-type="E"]{ background:#ae3ec9; border-color:#ae3ec9; color:#fff; }
    .rv-seg button.is-on[data-type="F"]{ background:#0ca678; border-color:#0ca678; color:#fff; }
    .rv-seg button:disabled{ opacity:.6; }
    @media (max-width: 575.98px){
        .rv-pmoney{ flex:1 1 auto; text-align:left; order:3; }
        .rv-pctrl{ order:4; width:100%; }
        .rv-pbrate{ min-height:42px; }
        .rv-seg button{ min-height:42px; }
    }
    .rv-empty{ padding:1.5rem 1rem; text-align:center; color:#868e96; word-break:keep-all; }
</style>

<div class="mt-2">
    <x-event-tabs :event-id="$event->id" active="revenue" />
</div>

<div class="row row-cards">
    {{-- 결과 보기 버튼 (전체 매칭 · 매칭 결과 · 메시지 · 요청) — 공통 파셜 --}}
    @include('admin.rotation._eventResultButtons', ['eventId' => $event->id, 'eventLabel' => $event->label])

    {{-- ① 매출 요약 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header rv-collapse-head" role="button" tabindex="0"
                 data-bs-toggle="collapse" data-bs-target="#rvSummaryCollapse" aria-expanded="true" aria-controls="rvSummaryCollapse">
                <h3 class="card-title mb-0 d-flex align-items-center gap-1"><i class="ti ti-chart-pie me-1"></i> 매출 요약 <i class="ti ti-chevron-down ck-chevron" aria-hidden="true"></i></h3>
                <div class="rv-head-side">
                    <span class="badge bg-green-lt rv-head-badge">순이익 <span id="rvProfitHead">0원</span></span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="rvRefreshBtn"><i class="ti ti-refresh me-1"></i>새로고침</button>
                </div>
            </div>
            <div class="collapse show" id="rvSummaryCollapse">
                <div class="card-body">
                    <div class="rv-stats" aria-live="polite">
                        <div class="rv-tile is-gross">
                            <div class="rv-name">결제 합계</div>
                            <div class="rv-num" id="tGross">0원</div>
                            <div class="rv-desc">유료 <span id="tPaid">0</span>명 · 노쇼 포함</div>
                        </div>
                        <div class="rv-tile is-minus">
                            <div class="rv-name">플랫폼 수수료</div>
                            <div class="rv-num" id="tFee">0원</div>
                            <div class="rv-desc">플랫폼별 수수료율</div>
                        </div>
                        <div class="rv-tile is-minus">
                            <div class="rv-name">페이백</div>
                            <div class="rv-num" id="tPayback">0원</div>
                            <div class="rv-desc">노쇼 페이백 제외</div>
                        </div>
                        <div class="rv-tile is-income">
                            <div class="rv-name">실수입</div>
                            <div class="rv-num" id="tIncome">0원</div>
                            <div class="rv-desc">결제 - 수수료 - 페이백</div>
                        </div>
                        <div class="rv-tile is-minus">
                            <div class="rv-name">비용</div>
                            <div class="rv-num" id="tCost">0원</div>
                            <div class="rv-desc">아래 비용 항목 합계</div>
                        </div>
                        <div class="rv-tile is-profit" id="tProfitTile">
                            <div class="rv-name">순이익</div>
                            <div class="rv-num" id="tProfit">0원</div>
                            <div class="rv-desc">실수입 - 비용</div>
                        </div>
                    </div>
                    <div class="text-secondary small mt-2" id="tPeopleLine"></div>
                    <div class="rv-warn d-none" id="tMissingWarn"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ② 플랫폼 · 성별 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header rv-collapse-head" role="button" tabindex="0"
                 data-bs-toggle="collapse" data-bs-target="#rvBreakCollapse" aria-expanded="true" aria-controls="rvBreakCollapse">
                <h3 class="card-title mb-0 d-flex align-items-center gap-1"><i class="ti ti-table me-1"></i> 플랫폼 · 성별 <i class="ti ti-chevron-down ck-chevron" aria-hidden="true"></i></h3>
            </div>
            <div class="collapse show" id="rvBreakCollapse">
                <div class="card-body">
                    <div id="rvBreak"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ③ 비용 --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header rv-collapse-head" role="button" tabindex="0"
                 data-bs-toggle="collapse" data-bs-target="#rvCostCollapse" aria-expanded="true" aria-controls="rvCostCollapse">
                <h3 class="card-title mb-0 d-flex align-items-center gap-1"><i class="ti ti-receipt-2 me-1"></i> 비용 <i class="ti ti-chevron-down ck-chevron" aria-hidden="true"></i></h3>
                <div class="rv-head-side"><span class="badge bg-red-lt rv-head-badge" id="rvCostHead">0원</span></div>
            </div>
            <div class="collapse show" id="rvCostCollapse">
                <div class="card-body">
                    <div id="rvCostRows"></div>
                    <button type="button" class="btn btn-outline-secondary w-100" id="rvCostAddBtn" style="min-height:44px;"><i class="ti ti-plus me-1"></i>항목 추가</button>
                    <div class="rv-cost-total"><span>합계</span><span id="rvCostSum">0원</span></div>
                    <div class="rv-foot">
                        <button type="button" class="btn btn-primary px-4" id="rvCostSaveBtn"><i class="ti ti-device-floppy me-1"></i>비용 저장</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ④ 플랫폼 수수료 (행사 정보에서 설정한 값, 보기만) --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header rv-collapse-head" role="button" tabindex="0"
                 data-bs-toggle="collapse" data-bs-target="#rvSettingCollapse" aria-expanded="true" aria-controls="rvSettingCollapse">
                <h3 class="card-title mb-0 d-flex align-items-center gap-1"><i class="ti ti-percentage me-1"></i> 참가비 · 수수료 <i class="ti ti-chevron-down ck-chevron" aria-hidden="true"></i></h3>
                <div class="rv-head-side" id="rvRateHead"></div>
            </div>
            <div class="collapse show" id="rvSettingCollapse">
                <div class="card-body">
                    <div class="rv-rate-grid">
                        @foreach ($platformOptions as $opt)
                            <div class="rv-rate-card is-{{ $opt['id'] }}">
                                <div class="fw-bold mb-1">{{ $opt['label'] }}</div>
                                <dl>
                                    @foreach ($genders as $gender => $label)
                                        @php $base = $event->basePriceFor($opt['id'], $gender); @endphp
                                        <dt>{{ $label }} 정가</dt><dd>{{ $base !== null ? number_format($base) . '원' : '미설정' }}</dd>
                                    @endforeach
                                    <dt>얼리버드</dt><dd>{{ $event->earlyBirdRateFor($opt['id']) }}%</dd>
                                    <dt>수수료</dt><dd>{{ $event->platformFeeRateFor($opt['id']) }}%</dd>
                                </dl>
                            </div>
                        @endforeach
                    </div>
                    <div class="rv-foot">
                        <a href="{{ route('admin.rotation.event.detail', $event->id) }}" class="btn btn-outline-primary px-4"><i class="ti ti-edit me-1"></i>행사 정보에서 수정</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ⑤ 신청자별 결제 유형 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header rv-collapse-head" role="button" tabindex="0"
                 data-bs-toggle="collapse" data-bs-target="#rvPeopleCollapse" aria-expanded="true" aria-controls="rvPeopleCollapse">
                <h3 class="card-title mb-0 d-flex align-items-center gap-1"><i class="ti ti-users me-1"></i> 신청자별 결제 유형 <i class="ti ti-chevron-down ck-chevron" aria-hidden="true"></i></h3>
                <div class="rv-head-side" id="rvPeopleHead"></div>
            </div>
            <div class="collapse show" id="rvPeopleCollapse">
                <div class="card-body">
                    <div class="rv-people-bar">
                        <input type="search" class="form-control rv-search" id="rvSearch" placeholder="번호표 또는 닉네임 검색" autocomplete="off">
                    </div>
                    <div class="text-secondary small mb-2">얼리버드는 선착순이라 받은 사람만 바꿔 주세요. 무료는 결제 0원으로 계산하고 페이백 대상이 될 수 없어요. 페이백 금액은 참가비로 자동 계산되고, 계좌는 체크인이나 페이백 화면에서 넣어요.</div>
                    <div id="rvPeople"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        var DATA_URL     = @json(route('admin.rotation.event.revenue.data', $event->id));
        var COST_URL     = @json(route('admin.rotation.event.costs.save', $event->id));
        var PAYTYPE_TPL  = @json(route('admin.rotation.event.rosters.payType', [$event->id, '__RID__']));
        var PBRATE_TPL   = @json(route('admin.rotation.event.rosters.paybackRate', [$event->id, '__RID__']));
        var RATE_LABEL   = @json($rateOptions);
        var PLATFORMS    = @json($platformOptions);
        var PAY_LABEL    = @json($payTypeOptions);
        var PLATFORM_LABEL = @json(collect($platformOptions)->pluck('label', 'id'));
        var PLATFORM_BADGE = { munto: 'bg-orange-lt', frip: 'bg-cyan-lt' };
        var COST_MAX     = {{ \App\Models\EventCost::MAX_PER_EVENT }};

        var state = { people: [], collapsed: { M: false, F: false } }; // 남/여 열 접힘 상태(다시 그려도 유지)

        function esc(v){ return $('<div>').text(v == null ? '' : v).html(); }
        function won(n){ return (Number(n) || 0).toLocaleString('ko-KR') + '원'; }
        function minus(n){ return Number(n) > 0 ? '-' + won(n) : won(0); }
        function digits(v){ var d = String(v == null ? '' : v).replace(/[^\d]/g, ''); return d === '' ? null : parseInt(d, 10); }
        function ajaxError(xhr){ alert(xhr?.responseJSON?.error?.message ?? xhr?.responseJSON?.msg ?? '오류가 발생했습니다.'); }
        function obj(v){ return v && !Array.isArray(v) ? v : {}; }

        $(document).on('input', '.money-input', function(){
            var n = digits(this.value);
            this.value = n === null ? '' : n.toLocaleString('ko-KR');
        });

        /* ===== 불러오기 ===== */
        function load(){
            $.getJSON(DATA_URL).done(function(res){
                state.people = res.people || [];
                renderTotals(res.totals || {});
                renderBreak(obj(res.by_platform), res.totals || {}, res.fees || {});
                renderRateHead(obj((res.fees || {}).platform_fee_rates));
                if(!costsDirty){ renderCosts(res.costs || []); }
                renderPeople();
            }).fail(ajaxError);
        }

        function renderTotals(t){
            $('#tGross').text(won(t.gross));
            $('#tPaid').text(t.paid_people || 0);
            $('#tFee').text(minus(t.platform_fee));
            $('#tPayback').text(minus(t.payback));
            $('#tIncome').text(won(t.income));
            $('#tCost').text(minus(t.cost));
            $('#tProfit').text((t.net_profit < 0 ? '-' : '') + won(Math.abs(t.net_profit || 0)));
            $('#tProfitTile').toggleClass('is-loss', (t.net_profit || 0) < 0);
            $('#rvProfitHead').text((t.net_profit < 0 ? '-' : '') + won(Math.abs(t.net_profit || 0)));
            $('#tPeopleLine').text('신청 ' + (t.people || 0) + '명 · 유료 ' + (t.paid_people || 0) + '명 (얼리버드 ' + (t.early_people || 0) + '명) · 무료 ' + (t.free_people || 0) + '명 · 노쇼 ' + (t.no_show || 0) + '명');
            var miss = t.missing_price || 0;
            $('#tMissingWarn').toggleClass('d-none', miss === 0)
                .html(miss ? '<i class="ti ti-alert-triangle me-1"></i>참가비가 설정되지 않은 플랫폼·성별의 신청자 <b>' + miss + '명</b>은 0원으로 계산했어요. 행사 상세의 행사 정보에서 참가비를 입력해 주세요.' : '');
        }

        /* ===== 플랫폼 · 성별 ===== */
        function cell(b){ b = b || {}; return { people: b.people || 0, free: b.free || 0, early: b.early || 0, gross: b.gross || 0, fee: b.platform_fee || 0, payback: b.payback || 0 }; }
        function sum(a, b){ return { people: a.people + b.people, free: a.free + b.free, early: a.early + b.early, gross: a.gross + b.gross, fee: a.fee + b.fee, payback: a.payback + b.payback }; }
        function income(c){ return c.gross - c.fee - c.payback; }
        function pct(part, whole){ return whole > 0 ? Math.round(part * 1000 / whole) / 10 : 0; }
        var PF_COLOR = { munto: '#f76707', frip: '#1098ad' };

        // 1인당 참가비 줄: 행사 정보에서 설정한 플랫폼·성별 정가 + (얼리버드 할인율이 있으면) 얼리버드 가격
        function priceLine(price, earlyRate){
            if(price == null){ return '<div class="rv-g-price is-missing">1인당 참가비 미설정</div>'; }
            var early = earlyRate > 0
                ? '<small>얼리버드 <b>' + won(Math.floor(price * (100 - earlyRate) / 100)) + '</b> (-' + earlyRate + '%)</small>'
                : '';
            return '<div class="rv-g-price"><span>1인당 <b>' + won(price) + '</b></span>' + early + '</div>';
        }

        // 성별 칸 1개: 1인당 참가비 · 인원·무료/얼리버드 → 결제 - 수수료 - 페이백 = 실수입
        function genderTile(gender, c, price, earlyRate){
            var label = gender === 'F' ? '여' : '남';
            var chips = (c.free ? '<span class="badge bg-teal-lt">무료 ' + c.free + '</span>' : '')
                + (c.early ? '<span class="badge bg-purple-lt">얼리 ' + c.early + '</span>' : '');
            return '<div class="rv-g ' + (gender === 'F' ? 'is-f' : 'is-m') + (c.people ? '' : ' is-empty') + '">'
                + '<div class="rv-g-head"><span class="rv-g-title">' + label + '</span><span class="rv-g-people">' + c.people + '명</span></div>'
                + priceLine(price, earlyRate)
                + '<div class="rv-g-chips">' + chips + '</div>'
                + '<dl class="rv-g-lines">'
                +   '<div><dt>결제</dt><dd>' + won(c.gross) + '</dd></div>'
                +   '<div class="is-minus"><dt>수수료</dt><dd>' + minus(c.fee) + '</dd></div>'
                +   '<div class="is-minus"><dt>페이백</dt><dd>' + minus(c.payback) + '</dd></div>'
                +   '<div class="is-income"><dt>실수입</dt><dd>' + won(income(c)) + '</dd></div>'
                + '</dl></div>';
        }

        // 플랫폼 카드 1개: 머리(플랫폼 색) · 남/여 칸 · 성비 막대
        function platformCard(p, f, m, fees){
            var sub    = sum(f, m);
            var prices = obj(obj(fees.prices)[p.id]);
            var early  = Number(obj(fees.early_bird_rates)[p.id]) || 0;
            var mw  = pct(m.people, sub.people);
            return '<div class="rv-pf is-' + esc(p.id) + '">'
                + '<div class="rv-pf-head"><span class="rv-pf-name">' + esc(p.label) + '<small>' + sub.people + '명</small></span>'
                +   '<span class="rv-pf-income"><small>실수입</small><b>' + won(income(sub)) + '</b></span></div>'
                + '<div class="rv-pf-body">' + genderTile('M', m, prices.M, early) + genderTile('F', f, prices.F, early) + '</div>'
                + '<div class="rv-pf-foot"><div class="rv-gbar" aria-hidden="true"><span class="is-m" style="width:' + mw + '%"></span><span class="is-f" style="width:' + (100 - mw) + '%"></span></div>'
                +   '<div class="rv-gbar-label"><span class="is-m">남 ' + m.people + '명 · ' + mw + '%</span><span class="is-f">여 ' + f.people + '명 · ' + Math.round((100 - mw) * 10) / 10 + '%</span></div></div>'
                + '</div>';
        }

        function renderBreak(byPlatform, t, fees){
            var known = PLATFORMS.map(function(p){ return p.id; });
            var list  = PLATFORMS.slice();
            Object.keys(byPlatform).forEach(function(id){ if(known.indexOf(id) === -1){ list.push({ id: id, label: id || '기타' }); } });

            var total = cell(), rows = [];
            list.forEach(function(p){
                var g = obj(byPlatform[p.id]);
                var f = cell(g.F), m = cell(g.M), sub = sum(f, m);
                if(sub.people === 0){ return; }
                rows.push({ p: p, f: f, m: m, sub: sub });
                total = sum(total, sub);
            });
            if(total.people === 0){
                $('#rvBreak').html('<div class="rv-empty">체크인 명단이 없어요. 체크인 탭에서 신청자를 먼저 등록해 주세요.</div>');
                return;
            }

            // 실수입 비중 막대 (플랫폼 색)
            var totalIncome = Math.max(0, income(total));
            var share = '<div class="rv-share"><div class="rv-share-label"><span>플랫폼별 실수입 비중</span><span>' + won(income(total)) + '</span></div>'
                + '<div class="rv-share-bar">' + rows.map(function(r){
                    return '<span style="width:' + pct(Math.max(0, income(r.sub)), totalIncome) + '%;background:' + (PF_COLOR[r.p.id] || '#868e96') + '"></span>';
                }).join('') + '</div>'
                + '<div class="rv-share-legend">' + rows.map(function(r){
                    return '<span><i style="background:' + (PF_COLOR[r.p.id] || '#868e96') + '"></i><b>' + esc(r.p.label) + '</b> ' + pct(Math.max(0, income(r.sub)), totalIncome) + '%</span>';
                }).join('') + '</div></div>';

            var cards = '<div class="rv-pf-grid">' + rows.map(function(r){ return platformCard(r.p, r.f, r.m, fees); }).join('') + '</div>';

            var totalBar = '<div class="rv-total">'
                + '<div><small>전체 인원</small><b>' + total.people + '명</b></div>'
                + '<div><small>결제</small><b>' + won(total.gross) + '</b></div>'
                + '<div class="is-minus"><small>수수료</small><b>' + minus(total.fee) + '</b></div>'
                + '<div class="is-minus"><small>페이백</small><b>' + minus(total.payback) + '</b></div>'
                + '<div class="is-income"><small>실수입</small><b>' + won(income(total)) + '</b></div>'
                + '</div>';

            $('#rvBreak').html(share + cards + totalBar);
        }

        /* ===== 플랫폼 수수료율 ===== */
        function renderRateHead(rates){
            $('#rvRateHead').html(PLATFORMS.map(function(p){
                return '<span class="badge ' + (PLATFORM_BADGE[p.id] || 'bg-secondary-lt') + ' rv-head-badge">' + esc(p.label) + ' ' + (Number(rates[p.id]) || 0) + '%</span>';
            }).join(''));
        }
        /* ===== 비용 ===== */
        var costsDirty = false; // 저장 전 입력 중이면 새로고침이 덮어쓰지 않게
        function costRow(c){
            return '<div class="rv-cost-row">'
                + '<input type="text" class="form-control rv-cost-name" maxlength="50" placeholder="예: 대관비" value="' + esc(c.name || '') + '" aria-label="비용 항목명">'
                + '<input type="text" class="form-control text-end money-input rv-cost-amount" inputmode="numeric" placeholder="0" value="' + (c.amount != null && c.amount !== '' ? Number(c.amount).toLocaleString('ko-KR') : '') + '" aria-label="금액">'
                + '<button type="button" class="btn btn-outline-danger rv-cost-del" aria-label="이 항목 삭제"><i class="ti ti-x"></i></button>'
                + '</div>';
        }
        function costSum(){
            var s = 0;
            $('.rv-cost-amount').each(function(){ s += digits(this.value) || 0; });
            $('#rvCostSum').text(won(s));
            $('#rvCostHead').text(won(s));
        }
        function renderCosts(costs){
            $('#rvCostRows').html((costs.length ? costs : [{ name: '', amount: '' }]).map(costRow).join(''));
            costSum();
        }
        $('#rvCostAddBtn').on('click', function(){
            if($('.rv-cost-row').length >= COST_MAX){ alert('비용 항목은 최대 ' + COST_MAX + '개까지 입력할 수 있어요.'); return; }
            $('#rvCostRows').append(costRow({ name: '', amount: '' }));
            $('.rv-cost-name').last().trigger('focus');
            costsDirty = true;
        });
        $('#rvCostRows').on('click', '.rv-cost-del', function(){
            $(this).closest('.rv-cost-row').remove();
            if($('.rv-cost-row').length === 0){ $('#rvCostRows').append(costRow({ name: '', amount: '' })); }
            costsDirty = true;
            costSum();
        });
        $('#rvCostRows').on('input', 'input', function(){ costsDirty = true; costSum(); });
        $('#rvCostSaveBtn').on('click', function(){
            var data = {};
            $('.rv-cost-row').each(function(i){
                data['costs[' + i + '][name]']   = $(this).find('.rv-cost-name').val();
                data['costs[' + i + '][amount]'] = $(this).find('.rv-cost-amount').val();
            });
            var $btn = $(this).prop('disabled', true);
            $.ajax({ url: COST_URL, type: 'PUT', data: data,
                success: function(res){ alert(res?.msg); costsDirty = false; load(); }, error: ajaxError
            }).always(function(){ $btn.prop('disabled', false); });
        });

        /* ===== 신청자별 결제 유형 ===== */
        function renderPeople(){
            var counts = { N: 0, E: 0, F: 0 };
            state.people.forEach(function(p){ counts[p.pay_type] = (counts[p.pay_type] || 0) + 1; });
            $('#rvPeopleHead').html(Object.keys(PAY_LABEL).map(function(k){
                return '<span class="badge ' + (k === 'E' ? 'bg-purple-lt' : (k === 'F' ? 'bg-teal-lt' : 'bg-secondary-lt')) + ' rv-head-badge">' + esc(PAY_LABEL[k]) + ' ' + (counts[k] || 0) + '</span>';
            }).join(''));

            var q = ($('#rvSearch').val() || '').trim().toLowerCase();
            if(state.people.length === 0){
                $('#rvPeople').html('<div class="rv-empty">체크인 명단이 없어요.</div>');
                return;
            }
            function matches(p){
                if(q === ''){ return true; }
                return String(p.ticket_no || '') === q || (p.name || '').toLowerCase().indexOf(q) !== -1;
            }
            // 남(좌) · 여(우). 성별이 F 가 아니면 남으로 본다.
            function column(cls, label, gender){
                var all  = state.people.filter(function(p){ return (p.gender === 'F') === (gender === 'F'); });
                var list = all.filter(matches);
                var fold = state.collapsed[gender];
                return '<section class="rv-pcol ' + cls + (fold ? ' is-collapsed' : '') + '">'
                    + '<button type="button" class="rv-pcol-head" data-col="' + gender + '" aria-expanded="' + (!fold) + '">'
                    +   '<span>' + label + '</span>'
                    +   '<span class="rv-pcol-side"><small>' + (q === '' ? all.length + '명' : list.length + ' / ' + all.length + '명') + '</small><i class="ti ti-chevron-down" aria-hidden="true"></i></span>'
                    + '</button>'
                    + '<div class="rv-pcol-body">'
                    + (list.length ? list.map(personHtml).join('') : '<div class="rv-empty">해당하는 사람이 없어요.</div>')
                    + '</div></section>';
            }
            $('#rvPeople').html('<div class="rv-pcols">' + column('is-m', '남', 'M') + column('is-f', '여', 'F') + '</div>');
        }

        // 신청자 한 명의 행(번호표 · 이름/뱃지 · 금액 · 결제 유형/페이백 컨트롤).
        function personHtml(p){
            var pf = PLATFORM_LABEL[p.platform] || p.platform || '';
            var badges = (pf ? '<span class="badge ' + (PLATFORM_BADGE[p.platform] || 'bg-secondary-lt') + '">' + esc(pf) + '</span>' : '')
                + (p.is_no_show ? '<span class="badge bg-red-lt">노쇼</span>' : (p.checked_in ? '<span class="badge bg-teal-lt">체크인</span>' : ''))
                + (p.payback_rate ? '<span class="badge bg-orange-lt">페이백 ' + p.payback_rate + '%' + (p.payback_done ? ' · 입금 완료' : '') + '</span>' : '')
                + (p.missing_price ? '<span class="badge bg-yellow-lt">참가비 미설정</span>' : '');
            var money = p.pay_type === 'F'
                ? '<b>0원</b><div class="text-secondary">무료</div>'
                : '<b>' + won(p.gross) + '</b>' + (p.payback ? '<div class="text-danger">페이백 ' + minus(p.payback) + '</div>' : '');
            // 페이백 대상은 무료로 못 바꾸고, 입금 완료면 유형을 못 바꾼다. (서버도 막는다)
            var seg = Object.keys(PAY_LABEL).map(function(k){
                var lock = p.payback_done || (k === 'F' && p.payback_rate);
                return '<button type="button" data-id="' + p.id + '" data-type="' + k + '" class="' + (p.pay_type === k ? 'is-on' : '') + '"'
                    + (lock && p.pay_type !== k ? ' disabled title="' + (p.payback_done ? '페이백 입금 완료' : '페이백 대상은 무료로 바꿀 수 없어요') + '"' : '')
                    + ' aria-pressed="' + (p.pay_type === k) + '">' + esc(PAY_LABEL[k]) + '</button>';
            }).join('');
            // 페이백 비율 (없음/80%/100%): 무료면 고를 수 없고, 입금 완료면 잠긴다. (서버가 같은 규칙으로 막는다)
            var pbOpts = [['', '페이백 없음']].concat(Object.keys(RATE_LABEL).sort(function(a, b){ return a - b; }).map(function(k){ return [k, RATE_LABEL[k]]; }));
            var pbSel = '<select class="form-select rv-pbrate" data-id="' + p.id + '" aria-label="페이백"'
                + (p.payback_done || p.pay_type === 'F' ? ' disabled title="' + (p.payback_done ? '페이백 입금 완료' : '무료 참가자는 페이백 불가') + '"' : '') + '>'
                + pbOpts.map(function(o){ return '<option value="' + o[0] + '"' + (String(p.payback_rate || '') === String(o[0]) ? ' selected' : '') + '>' + esc(o[1]) + '</option>'; }).join('')
                + '</select>';
            return '<div class="rv-person">'
                + '<span class="rv-pno ' + (p.gender === 'F' ? 'is-f' : '') + '">' + (p.ticket_no || '-') + '</span>'
                + '<div class="rv-pwho"><div class="rv-pname">' + esc(p.name || '닉네임 없음') + '</div><div class="rv-pbadges">' + badges + '</div></div>'
                + '<div class="rv-pmoney">' + money + '</div>'
                + '<div class="rv-pctrl">'
                +   '<div class="rv-seg" role="group" aria-label="결제 유형">' + seg + '</div>'
                +   pbSel
                + '</div>'
                + '</div>';
        }
        // 남/여 열 접기·펼치기. 목록을 다시 그리지 않고 클래스만 바꾸고, 상태는 state 에 남겨 새로고침 렌더에도 유지한다.
        $('#rvPeople').on('click', '.rv-pcol-head', function(){
            var col  = String($(this).data('col'));
            var fold = !state.collapsed[col];
            state.collapsed[col] = fold;
            $(this).attr('aria-expanded', String(!fold)).closest('.rv-pcol').toggleClass('is-collapsed', fold);
        });
        $('#rvPeople').on('change', '.rv-pbrate', async function(){
            var $s = $(this);
            var p  = state.people.find(function(x){ return String(x.id) === String($s.data('id')); });
            // 기존 페이백을 없애면 계좌 정보도 지워지므로 한 번 확인한다.
            if(p && p.payback_rate && !$s.val()){
                if(!(await salert({text: (p.name || '이 사람') + '님의 페이백을 없앨까요? (계좌 정보도 함께 지워져요)', icon: 'warning'}))){ $s.val(String(p.payback_rate)); return; }
            }
            $s.prop('disabled', true);
            $.ajax({ url: PBRATE_TPL.replace('__RID__', $s.data('id')), type: 'PATCH', data: { payback_rate: $s.val() },
                success: function(out){ if(out?.status !== 200){ alert(out?.msg); } load(); },
                error: function(xhr){ ajaxError(xhr); load(); }
            });
        });
        $('#rvSearch').on('input', renderPeople);
        $('#rvPeople').on('click', '.rv-seg button', function(){
            var $b = $(this);
            if($b.hasClass('is-on')){ return; }
            $b.closest('.rv-seg').find('button').prop('disabled', true);
            $.ajax({ url: PAYTYPE_TPL.replace('__RID__', $b.data('id')), type: 'PATCH', data: { pay_type: $b.data('type') },
                success: function(out){ if(out?.status !== 200){ alert(out?.msg); } load(); },
                error: function(xhr){ ajaxError(xhr); load(); }
            });
        });

        /* ===== 카드 접기/펼치기: 상태 기억, Enter/Space 로도 토글, 머리 안 버튼은 접지 않음 ===== */
        document.querySelectorAll('#rvSummaryCollapse, #rvBreakCollapse, #rvCostCollapse, #rvSettingCollapse, #rvPeopleCollapse').forEach(function(el){
            var KEY   = 'rotable.revenue.' + el.id + '.collapsed';
            var $head = $('[data-bs-target="#' + el.id + '"]');
            var collapsed = false;
            try { collapsed = localStorage.getItem(KEY) === '1'; } catch(e) { /* 저장소 사용 불가 시 기본(펼침) */ }
            if(collapsed){
                el.classList.remove('show');
                $head.attr('aria-expanded', 'false');
            }
            function save(v){ try { localStorage.setItem(KEY, v ? '1' : '0'); } catch(e) {} }
            el.addEventListener('hidden.bs.collapse', function(e){ if(e.target === el){ save(true); } });
            el.addEventListener('shown.bs.collapse', function(e){ if(e.target === el){ save(false); } });
            $head.on('keydown', function(e){
                if(e.target !== this){ return; }
                if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); this.click(); }
            });
        });
        $('.rv-collapse-head').on('click', 'button, a', function(e){ e.stopPropagation(); });

        $('#rvRefreshBtn').on('click', load);
        load();
    });
</script>
@endsection
