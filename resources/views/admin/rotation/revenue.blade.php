@extends('layouts.app')

@section('title', '매출 — ' . $event->title)

@section('content')
@php
    $genders = [\App\Models\Member::GENDER_FEMALE => '여', \App\Models\Member::GENDER_MALE => '남'];
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

    /* 플랫폼·성별 표: PC 는 표, 모바일은 카드 */
    .rv-break{ width:100%; border-collapse:collapse; font-size:.9rem; }
    .rv-break th, .rv-break td{ padding:.5rem .6rem; border-bottom:1px solid #eef0f3; text-align:right; white-space:nowrap; }
    .rv-break th:first-child, .rv-break td:first-child{ text-align:left; }
    .rv-break thead th{ font-size:.78rem; color:#868e96; font-weight:700; background:#f8f9fa; }
    .rv-break tr.is-sub td{ font-weight:800; background:#fbfcfd; }
    .rv-break tr.is-total td{ font-weight:800; background:#f1f3f5; border-bottom:0; }
    .rv-break .is-minus{ color:#d63939; }
    .rv-break .is-income{ color:#087f5b; font-weight:800; }
    .rv-break-cards{ display:none; }
    @media (max-width: 767.98px){
        .rv-break{ display:none; }
        .rv-break-cards{ display:grid; gap:.6rem; }
    }
    .rv-bcard{ border:1px solid #e6e8eb; border-radius:12px; padding:.65rem .75rem; border-left:4px solid #adb5bd; }
    .rv-bcard.is-munto{ border-left-color:#f76707; }
    .rv-bcard.is-frip{ border-left-color:#15aabf; }
    .rv-bcard-head{ display:flex; justify-content:space-between; align-items:baseline; font-weight:800; margin-bottom:.35rem; }
    .rv-bcard dl{ display:grid; grid-template-columns:auto 1fr; gap:.15rem .75rem; margin:0; font-size:.86rem; }
    .rv-bcard dt{ color:#868e96; font-weight:700; }
    .rv-bcard dd{ margin:0; text-align:right; }

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
    .rv-people-bar{ display:flex; flex-direction:column; gap:.5rem; margin-bottom:.75rem; }
    @media (min-width: 768px){ .rv-people-bar{ flex-direction:row; align-items:center; } .rv-people-bar .rv-gtabs{ flex:0 0 260px; } .rv-people-bar .rv-search{ flex:1 1 auto; } }
    .rv-gtabs{ display:grid; grid-template-columns:repeat(3, 1fr); gap:.35rem; }
    .rv-gtab{ min-height:42px; border-radius:10px; font-weight:800; border:1px solid #dee2e6; background:#fff; color:#495057; }
    .rv-gtab.is-active{ background:#495057; border-color:#495057; color:#fff; }
    .rv-gtab.is-f.is-active{ background:#d6336c; border-color:#d6336c; }
    .rv-gtab.is-m.is-active{ background:#206bc4; border-color:#206bc4; }
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
    .rv-seg{ flex:0 0 auto; display:grid; grid-template-columns:repeat(3, 1fr); gap:.25rem; width:228px; }
    .rv-seg button{ min-height:38px; border-radius:9px; border:1px solid #dee2e6; background:#fff; color:#495057; font-weight:700; font-size:.85rem; padding:0 .25rem; }
    .rv-seg button.is-on[data-type="N"]{ background:#495057; border-color:#495057; color:#fff; }
    .rv-seg button.is-on[data-type="E"]{ background:#ae3ec9; border-color:#ae3ec9; color:#fff; }
    .rv-seg button.is-on[data-type="F"]{ background:#0ca678; border-color:#0ca678; color:#fff; }
    .rv-seg button:disabled{ opacity:.6; }
    @media (max-width: 575.98px){
        .rv-pmoney{ flex:1 1 auto; text-align:left; order:3; }
        .rv-seg{ order:4; width:100%; }
        .rv-seg button{ min-height:42px; }
    }
    .rv-empty{ padding:1.5rem 1rem; text-align:center; color:#868e96; word-break:keep-all; }
</style>

<div class="mt-2">
    <x-event-tabs :event-id="$event->id" active="revenue" />
</div>

<div class="row row-cards">
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
                    <div class="table-responsive">
                        <table class="rv-break">
                            <thead>
                                <tr><th>구분</th><th>인원</th><th>무료</th><th>얼리버드</th><th>결제</th><th>수수료</th><th>페이백</th><th>실수입</th></tr>
                            </thead>
                            <tbody id="rvBreakBody"></tbody>
                        </table>
                    </div>
                    <div class="rv-break-cards" id="rvBreakCards"></div>
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
                                    @foreach ([\App\Models\Member::GENDER_FEMALE => '여', \App\Models\Member::GENDER_MALE => '남'] as $gender => $label)
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
                        <div class="rv-gtabs" role="group" aria-label="성별">
                            <button type="button" class="rv-gtab is-f" data-g="F">여</button>
                            <button type="button" class="rv-gtab is-m" data-g="M">남</button>
                            <button type="button" class="rv-gtab" data-g="">전체</button>
                        </div>
                        <input type="search" class="form-control rv-search" id="rvSearch" placeholder="번호표 또는 닉네임 검색" autocomplete="off">
                    </div>
                    <div class="text-secondary small mb-2">얼리버드는 선착순이라 받은 사람만 바꿔 주세요. 무료는 결제 0원으로 계산하고 페이백 대상이 될 수 없어요.</div>
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
        var PLATFORMS    = @json($platformOptions);
        var PAY_LABEL    = @json($payTypeOptions);
        var PLATFORM_LABEL = @json(collect($platformOptions)->pluck('label', 'id'));
        var PLATFORM_BADGE = { munto: 'bg-orange-lt', frip: 'bg-cyan-lt' };
        var COST_MAX     = {{ \App\Models\EventCost::MAX_PER_EVENT }};

        var state = { people: [], gender: 'F' };

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
                renderBreak(obj(res.by_platform), res.totals || {});
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
        function row(label, c, cls){
            return '<tr class="' + (cls || '') + '"><td>' + label + '</td><td>' + c.people + '명</td><td>' + c.free + '</td><td>' + c.early + '</td>'
                + '<td>' + won(c.gross) + '</td><td class="is-minus">' + minus(c.fee) + '</td><td class="is-minus">' + minus(c.payback) + '</td><td class="is-income">' + won(income(c)) + '</td></tr>';
        }
        function bcard(label, c, platformId){
            return '<div class="rv-bcard is-' + esc(platformId) + '"><div class="rv-bcard-head"><span>' + label + '</span><span class="text-success">' + won(income(c)) + '</span></div>'
                + '<dl><dt>인원</dt><dd>' + c.people + '명 (무료 ' + c.free + ' · 얼리 ' + c.early + ')</dd>'
                + '<dt>결제</dt><dd>' + won(c.gross) + '</dd><dt>수수료</dt><dd class="text-danger">' + minus(c.fee) + '</dd>'
                + '<dt>페이백</dt><dd class="text-danger">' + minus(c.payback) + '</dd></dl></div>';
        }
        function renderBreak(byPlatform, t){
            var rows = '', cards = '';
            var known = PLATFORMS.map(function(p){ return p.id; });
            var list  = PLATFORMS.slice();
            Object.keys(byPlatform).forEach(function(id){ if(known.indexOf(id) === -1){ list.push({ id: id, label: id || '기타' }); } });
            var total = cell();
            list.forEach(function(p){
                var g = obj(byPlatform[p.id]);
                var f = cell(g.F), m = cell(g.M), sub = sum(f, m);
                if(sub.people === 0){ return; }
                rows  += row(esc(p.label) + ' · 여', f) + row(esc(p.label) + ' · 남', m) + row(esc(p.label) + ' 소계', sub, 'is-sub');
                cards += bcard(esc(p.label) + ' · 여', f, p.id) + bcard(esc(p.label) + ' · 남', m, p.id);
                total = sum(total, sub);
            });
            if(total.people === 0){
                $('#rvBreakBody').html('<tr><td colspan="8" class="rv-empty">체크인 명단이 없어요. 체크인 탭에서 신청자를 먼저 등록해 주세요.</td></tr>');
                $('#rvBreakCards').html('<div class="rv-empty">체크인 명단이 없어요. 체크인 탭에서 신청자를 먼저 등록해 주세요.</div>');
                return;
            }
            rows  += row('합계', total, 'is-total');
            cards += bcard('합계', total, 'total');
            $('#rvBreakBody').html(rows);
            $('#rvBreakCards').html(cards);
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
            $('.rv-gtab').each(function(){ $(this).toggleClass('is-active', String($(this).data('g')) === state.gender); });
            var countF = state.people.filter(function(p){ return p.gender === 'F'; }).length;
            var countM = state.people.length - countF;
            $('.rv-gtab[data-g="F"]').text('여 ' + countF);
            $('.rv-gtab[data-g="M"]').text('남 ' + countM);
            $('.rv-gtab[data-g=""]').text('전체 ' + state.people.length);

            var counts = { N: 0, E: 0, F: 0 };
            state.people.forEach(function(p){ counts[p.pay_type] = (counts[p.pay_type] || 0) + 1; });
            $('#rvPeopleHead').html(Object.keys(PAY_LABEL).map(function(k){
                return '<span class="badge ' + (k === 'E' ? 'bg-purple-lt' : (k === 'F' ? 'bg-teal-lt' : 'bg-secondary-lt')) + ' rv-head-badge">' + esc(PAY_LABEL[k]) + ' ' + (counts[k] || 0) + '</span>';
            }).join(''));

            var q = ($('#rvSearch').val() || '').trim().toLowerCase();
            var list = state.people.filter(function(p){
                if(state.gender && p.gender !== state.gender){ return false; }
                if(q === ''){ return true; }
                return String(p.ticket_no || '') === q || (p.name || '').toLowerCase().indexOf(q) !== -1;
            });
            if(list.length === 0){
                $('#rvPeople').html('<div class="rv-empty">' + (state.people.length ? '해당하는 사람이 없어요.' : '체크인 명단이 없어요.') + '</div>');
                return;
            }
            $('#rvPeople').html(list.map(function(p){
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
                return '<div class="rv-person">'
                    + '<span class="rv-pno ' + (p.gender === 'F' ? 'is-f' : '') + '">' + (p.ticket_no || '-') + '</span>'
                    + '<div class="rv-pwho"><div class="rv-pname">' + esc(p.name || '닉네임 없음') + '</div><div class="rv-pbadges">' + badges + '</div></div>'
                    + '<div class="rv-pmoney">' + money + '</div>'
                    + '<div class="rv-seg" role="group" aria-label="결제 유형">' + seg + '</div>'
                    + '</div>';
            }).join(''));
        }
        $('.rv-gtab').on('click', function(){ state.gender = String($(this).data('g')); renderPeople(); });
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
