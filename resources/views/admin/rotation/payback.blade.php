@extends('layouts.app')

@section('title', '페이백 관리 — ' . $event->title)

@section('content')
@php
    $isStarted = in_array($event->status, [\App\Models\EventRound::STATUS_STARTED, \App\Models\EventRound::STATUS_VOTING, \App\Models\EventRound::STATUS_CLOSED], true);
    $genders   = [\App\Models\Member::GENDER_MALE => '남', \App\Models\Member::GENDER_FEMALE => '여'];
@endphp
<style>
    /* 합계 타일 */
    .pb-stats{ display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.5rem; }
    @media (min-width: 768px){ .pb-stats{ grid-template-columns:repeat(4, minmax(0, 1fr)); } }
    .pb-tile{ border-radius:12px; padding:.6rem .75rem; background:#f8f9fa; border-left:4px solid #adb5bd; }
    .pb-tile .pb-name{ font-size:.8rem; font-weight:700; color:#495057; }
    .pb-tile .pb-num{ font-size:clamp(1.15rem, 5vw, 1.6rem); font-weight:800; line-height:1.2; overflow-wrap:anywhere; }
    .pb-tile .pb-desc{ font-size:.78rem; color:#868e96; }
    .pb-tile.is-wait{ border-left-color:#f76707; background:#fff4e6; } .pb-tile.is-wait .pb-num{ color:#d9480f; }
    .pb-tile.is-done{ border-left-color:#2fb344; background:#ebf9ee; } .pb-tile.is-done .pb-num{ color:#2fb344; }
    .pb-tile.is-noshow{ border-left-color:#d63939; background:#fdecec; } .pb-tile.is-noshow .pb-num{ color:#d63939; }
    .pb-tile.is-total{ border-left-color:#495057; }

    /* 접기/펼치기 카드 머리 */
    .pb-collapse-head{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; cursor:pointer; user-select:none; }
    .pb-collapse-head:focus-visible{ outline:3px solid #74c0fc; outline-offset:-3px; }
    .pb-head-side{ display:flex; align-items:center; flex-wrap:wrap; gap:.35rem; justify-content:flex-end; }
    .pb-head-badge{ font-size:.8rem; font-weight:700; }
    @media (max-width: 575.98px){ .pb-head-side{ width:100%; justify-content:flex-start; } }

    /* 참가비: 플랫폼별 블록 */
    .pb-fees{ display:flex; flex-direction:column; gap:.6rem; }
    .pb-fee-block{ display:grid; grid-template-columns:1fr 1fr; gap:.5rem .6rem; padding:.7rem .75rem; border:1px solid #e6e8eb; border-radius:12px; border-left:4px solid #adb5bd; }
    .pb-fee-block.is-munto{ border-left-color:#f76707; }
    .pb-fee-block.is-frip{ border-left-color:#15aabf; }
    .pb-fee-platform{ grid-column:1 / -1; font-weight:800; }
    .pb-fee-cell{ min-width:0; }
    .pb-fee-cell .form-control{ min-height:44px; }
    .pb-fee-rate-cell{ grid-column:1 / -1; }
    .pb-fee-rate-cell .input-group{ max-width:180px; }
    .pb-fee-label{ display:block; font-size:.78rem; color:#868e96; font-weight:700; margin-bottom:.2rem; }
    .pb-fee-eb{ font-size:.78rem; color:#ae3ec9; font-weight:700; margin-top:.2rem; min-height:1.1em; }
    @media (min-width: 768px){
        .pb-fee-block{ grid-template-columns:72px 1fr 1fr 160px; align-items:start; }
        .pb-fee-platform{ grid-column:auto; padding-top:1.65rem; }
        .pb-fee-rate-cell{ grid-column:auto; }
        .pb-fee-rate-cell .input-group{ max-width:none; }
    }
    .pb-fee-foot{ display:flex; flex-direction:column; gap:.5rem; margin-top:.75rem; }
    .pb-fee-foot .btn{ min-height:44px; }
    @media (min-width: 768px){ .pb-fee-foot{ flex-direction:row; justify-content:flex-end; } }

    /* 목록 상단 바 */
    .pb-bar{ display:flex; flex-direction:column; gap:.5rem; }
    .pb-bar .btn{ min-height:44px; font-weight:700; }
    @media (min-width: 768px){ .pb-bar{ flex-direction:row; align-items:center; justify-content:space-between; } .pb-filter{ max-width:480px; flex:1 1 auto; } .pb-bar > .btn{ flex:0 0 auto; } }

    /* 플랫폼 묶음 */
    .pb-section + .pb-section{ margin-top:1.25rem; }
    .pb-section-head{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; padding:.5rem .75rem; border-radius:10px; margin-bottom:.6rem; font-weight:800; }
    .pb-section-head.is-munto{ background:#fff4e6; color:#d9480f; }
    .pb-section-head.is-frip{ background:#e3fafc; color:#0c8599; }
    .pb-section-head.is-etc{ background:#f1f3f5; color:#495057; }
    .pb-section-head .pb-section-sum{ font-size:.85rem; font-weight:700; }

    /* 대상 카드 */
    .pb-grid{ display:grid; grid-template-columns:minmax(0, 1fr); gap:.75rem; }
    @media (min-width: 992px){ .pb-grid{ grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    .pb-card{ display:flex; flex-direction:column; border:1px solid #e6e8eb; border-radius:14px; background:#fff; padding:.8rem .9rem; border-left:5px solid #f76707; }
    .pb-card.is-done{ border-left-color:#2fb344; background:#fbfefb; }
    .pb-card.is-noshow{ border-left-color:#d63939; background:#fffafa; }
    .pb-head{ display:flex; align-items:center; gap:.65rem; }
    .pb-ticket{ flex:0 0 auto; width:52px; height:52px; border-radius:12px; color:#fff; background:#206bc4; display:flex; flex-direction:column; align-items:center; justify-content:center; line-height:1.05; }
    .pb-ticket.is-f{ background:#d6336c; }
    .pb-ticket.is-none{ background:#f1f3f5; color:#868e96; font-size:.72rem; font-weight:700; text-align:center; }
    .pb-ticket small{ font-size:.7rem; font-weight:700; opacity:.85; }
    .pb-ticket b{ font-size:1.2rem; font-weight:800; }
    .pb-who{ flex:1 1 auto; min-width:0; }
    .pb-who .pb-nick{ font-weight:800; font-size:1rem; overflow-wrap:anywhere; }
    .pb-who .pb-badges{ display:flex; flex-wrap:wrap; gap:.25rem; margin-top:.2rem; }
    .pb-amount{ flex:0 0 auto; text-align:right; }
    .pb-amount .pb-pay{ font-size:1.25rem; font-weight:800; color:#d9480f; white-space:nowrap; }
    .pb-card.is-done .pb-amount .pb-pay{ color:#2fb344; }
    .pb-card.is-noshow .pb-amount .pb-pay{ color:#adb5bd; text-decoration:line-through; }
    .pb-amount .pb-calc{ font-size:.75rem; color:#868e96; white-space:nowrap; }
    .pb-info{ margin:.6rem 0 0; padding:.5rem .65rem; background:#f8f9fa; border-radius:10px; }
    .pb-info > div{ display:flex; gap:.5rem; font-size:.86rem; line-height:1.6; align-items:center; }
    .pb-info dt{ flex:0 0 44px; color:#868e96; font-weight:700; margin:0; }
    .pb-info dd{ flex:1 1 auto; margin:0; color:#343a40; overflow-wrap:anywhere; }
    .pb-info .btn-copy{ flex:0 0 auto; }
    .pb-actions{ display:grid; grid-template-columns:2fr 1fr 1fr; gap:.4rem; margin-top:.6rem; }
    .pb-actions .btn{ min-height:40px; }
    .pb-empty{ padding:2rem 1rem; text-align:center; color:#868e96; word-break:keep-all; }

    /* 대상자 고르기 (추가 창): 남/여 탭 · 검색 · 눌러서 선택하는 목록 */
    .pb-pick-tabs{ display:grid; grid-template-columns:1fr 1fr; gap:.4rem; }
    .pb-pick-tab{ min-height:42px; border-radius:10px; font-weight:800; border:1px solid #dee2e6; background:#fff; color:#495057; }
    .pb-pick-tab.is-m.is-active{ background:#206bc4; border-color:#206bc4; color:#fff; }
    .pb-pick-tab.is-f.is-active{ background:#d6336c; border-color:#d6336c; color:#fff; }
    .pb-pick-list{ margin-top:6px; max-height:220px; overflow-y:auto; border:1px solid #e6e8eb; border-radius:10px; text-align:left; }
    .pb-pick-item{ display:flex; align-items:center; gap:.5rem; width:100%; min-height:46px; padding:.4rem .6rem; border:0; border-bottom:1px solid #f1f3f5; background:#fff; text-align:left; }
    .pb-pick-item:last-child{ border-bottom:0; }
    .pb-pick-item:hover{ background:#f8fafc; }
    .pb-pick-item.is-picked{ background:#fff4e6; box-shadow:inset 3px 0 0 #f76707; }
    .pb-pick-no{ flex:0 0 auto; min-width:34px; height:30px; padding:0 .3rem; border-radius:8px; background:#206bc4; color:#fff; font-weight:800; display:inline-flex; align-items:center; justify-content:center; }
    .pb-pick-no.is-f{ background:#d6336c; }
    .pb-pick-name{ flex:1 1 auto; min-width:0; font-weight:700; color:#212529; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .pb-pick-empty{ padding:1rem; text-align:center; color:#868e96; font-size:.9rem; }
    .pb-picked{ margin-top:6px; text-align:left; font-size:.88rem; color:#495057; }

    /* 추가/수정 창(Swal): 화면 높이 안에 맞추고 본문만 스크롤, 닫기(X)·하단 버튼은 항상 보이게 */
    .swal2-popup.pb-swal{ width:min(560px, calc(100vw - 1rem)); max-height:calc(100dvh - 1rem); padding:.75rem 0; }
    .pb-swal .swal2-title{ padding:.35rem 3rem .5rem 1rem; font-size:1.15rem; text-align:left; }
    .pb-swal .swal2-close{ font-size:2rem; }
    .pb-swal .pb-swal-body{ margin:0; padding:0 1rem .25rem; max-height:calc(100dvh - 10rem); overflow-y:auto; -webkit-overflow-scrolling:touch; }
    .pb-swal .swal2-validation-message{ margin:.5rem 1rem 0; }
    .pb-swal .pb-swal-actions{ width:100%; margin:.6rem 0 0; padding:.6rem 1rem 0; border-top:1px solid #eef0f3; flex-wrap:nowrap; gap:.5rem; }
    .pb-swal .pb-swal-actions button{ flex:1 1 0; margin:0; min-height:46px; }
    .pb-swal .swal2-input, .pb-swal .swal2-select{ min-height:44px; height:auto; font-size:1rem; }
    /* 페이백 비율: 버튼형 2칸 */
    .pb-seg{ display:grid; grid-template-columns:1fr 1fr; gap:.4rem; }
    .pb-seg-item{ position:relative; margin:0; cursor:pointer; }
    .pb-seg-item input{ position:absolute; opacity:0; pointer-events:none; }
    .pb-seg-item span{ display:flex; align-items:center; justify-content:center; min-height:42px; border:1px solid #dee2e6; border-radius:10px; font-weight:800; color:#495057; background:#fff; }
    .pb-seg-item input:checked + span{ background:#f76707; border-color:#f76707; color:#fff; }
    .pb-seg-item input:focus-visible + span{ outline:3px solid #74c0fc; outline-offset:1px; }
    .pb-form-2col{ display:grid; grid-template-columns:1fr 1fr; gap:0 .5rem; }
    @media (max-width: 575.98px){
        .pb-pick-list{ max-height:180px; }
        .pb-swal .pb-swal-body{ max-height:calc(100dvh - 9rem); }
    }

    /* 모바일(좁은 화면) 보정 */
    @media (max-width: 575.98px){
        /* 상태 필터 4칸: 글자 줄이고 줄바꿈 방지 */
        .pb-filter .btn{ font-size:.82rem; padding-left:.25rem; padding-right:.25rem; white-space:nowrap; }
        /* 카드 머리: 번호표·닉네임 한 줄, 금액은 아래 한 줄 전체로 */
        .pb-head{ flex-wrap:wrap; }
        .pb-amount{ flex:1 1 100%; display:flex; align-items:baseline; justify-content:space-between; gap:.5rem; text-align:left;
            padding-top:.4rem; border-top:1px dashed #e6e8eb; }
        .pb-amount .pb-pay{ order:2; }
        .pb-amount .pb-calc{ order:1; white-space:normal; }
        /* 계좌 줄: 복사 버튼은 아래로 */
        .pb-info > div{ flex-wrap:wrap; }
        .pb-info .btn-copy{ margin-left:calc(44px + .5rem); }
        /* 버튼: 입금 완료는 한 줄 전체, 수정/삭제는 반반 */
        .pb-actions{ grid-template-columns:1fr 1fr; }
        .pb-actions .btn-transfer{ grid-column:1 / -1; min-height:44px; }
        .pb-section-head{ font-size:.92rem; }
        .pb-section-head .pb-section-sum{ font-size:.8rem; }
    }
</style>

<div class="mt-2">
    <x-event-tabs :event-id="$event->id" active="payback" />
</div>

<div class="row row-cards">
    {{-- 합계 --}}
    <div class="col-12">
        <div class="card">
            {{-- 머리를 누르면 접기/펼치기 (상태 기억). 접어도 입금 대기 금액은 머리에 보인다. --}}
            <div class="card-header pb-collapse-head" role="button" tabindex="0"
                 data-bs-toggle="collapse" data-bs-target="#pbSummaryCollapse" aria-expanded="true" aria-controls="pbSummaryCollapse">
                <h3 class="card-title mb-0 d-flex align-items-center gap-1"><i class="ti ti-cash me-1"></i> 페이백 현황 <i class="ti ti-chevron-down ck-chevron" aria-hidden="true"></i></h3>
                <div class="pb-head-side">
                    <span class="badge bg-orange-lt pb-head-badge">대기 <span id="sumWaitHead">0원</span></span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="pbRefreshBtn"><i class="ti ti-refresh me-1"></i>새로고침</button>
                </div>
            </div>
            <div class="collapse show" id="pbSummaryCollapse">
            <div class="card-body">
                <div class="pb-stats" aria-live="polite">
                    <div class="pb-tile is-wait">
                        <div class="pb-name">입금 대기</div>
                        <div class="pb-num" id="sumWaitAmount">0원</div>
                        <div class="pb-desc"><span id="sumWaitCount">0</span>명 · 보내야 할 금액</div>
                    </div>
                    <div class="pb-tile is-done">
                        <div class="pb-name">입금 완료</div>
                        <div class="pb-num" id="sumDoneAmount">0원</div>
                        <div class="pb-desc"><span id="sumDoneCount">0</span>명</div>
                    </div>
                    <div class="pb-tile is-noshow">
                        <div class="pb-name">노쇼</div>
                        <div class="pb-num"><span id="sumNoShowCount">0</span>명</div>
                        <div class="pb-desc">체크인 안 함 · 합계에서 제외</div>
                    </div>
                    <div class="pb-tile is-total">
                        <div class="pb-name">페이백 대상</div>
                        <div class="pb-num"><span id="sumTotalCount">0</span>명</div>
                        <div class="pb-desc">이 행사 전체</div>
                    </div>
                </div>
                @unless ($isStarted)
                    <div class="text-secondary small mt-2"><i class="ti ti-info-circle me-1"></i>행사 시작 전이라 아직 노쇼를 판정하지 않아요. 로테이션이 시작되면 체크인 안 한 사람이 노쇼로 표시돼요.</div>
                @endunless
            </div>
            </div>
        </div>
    </div>

    {{-- 참가비: 플랫폼 × 성별 정가 + 얼리버드 할인율 --}}
    <div class="col-12">
        <div class="card">
            {{-- 머리를 누르면 접기/펼치기 (상태 기억). 접어도 저장된 참가비 요약은 머리에 보인다. --}}
            <div class="card-header pb-collapse-head" role="button" tabindex="0"
                 data-bs-toggle="collapse" data-bs-target="#pbFeeCollapse" aria-expanded="true" aria-controls="pbFeeCollapse">
                <h3 class="card-title mb-0 d-flex align-items-center gap-1"><i class="ti ti-receipt me-1"></i> 이 행사 참가비 <i class="ti ti-chevron-down ck-chevron" aria-hidden="true"></i></h3>
                <div class="pb-head-side" id="feeHeadSummary"></div>
            </div>
            <div class="collapse show" id="pbFeeCollapse">
            <div class="card-body">
                {{-- 플랫폼별 한 묶음. 모바일: 남/여 2칸 + 할인율 한 줄 / PC(md+): 플랫폼 · 남 · 여 · 할인율 한 줄 --}}
                <div class="pb-fees">
                    @foreach ($platformOptions as $opt)
                        <div class="pb-fee-block is-{{ $opt['id'] }}">
                            <div class="pb-fee-platform">{{ $opt['label'] }}</div>
                            @foreach ($genders as $gender => $label)
                                @php $price = $event->basePriceFor($opt['id'], $gender); @endphp
                                <div class="pb-fee-cell">
                                    <label class="pb-fee-label" for="fee-{{ $opt['id'] }}-{{ $gender }}">{{ $label }} 정가 (원)</label>
                                    <input type="text" class="form-control money-input fee-price" inputmode="numeric" id="fee-{{ $opt['id'] }}-{{ $gender }}"
                                           data-platform="{{ $opt['id'] }}" data-gender="{{ $gender }}"
                                           value="{{ $price !== null ? number_format($price) : '' }}" placeholder="예: 39,000">
                                    <div class="pb-fee-eb" data-eb-for="{{ $opt['id'] }}-{{ $gender }}"></div>
                                </div>
                            @endforeach
                            <div class="pb-fee-cell pb-fee-rate-cell">
                                <label class="pb-fee-label" for="fee-rate-{{ $opt['id'] }}">얼리버드 할인율</label>
                                <div class="input-group">
                                    <input type="text" class="form-control fee-rate" inputmode="numeric" maxlength="3" id="fee-rate-{{ $opt['id'] }}"
                                           data-platform="{{ $opt['id'] }}" value="{{ $event->earlyBirdRateFor($opt['id']) }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="pb-fee-foot">
                    <button type="button" class="btn btn-primary px-4" id="feeSaveBtn"><i class="ti ti-device-floppy me-1"></i>참가비 저장</button>
                </div>
                <div class="text-secondary small mt-2">
                    얼리버드는 선착순이라 사람마다 체크해요. 체크한 사람은 그 플랫폼의 할인율만큼 결제 금액이 빠져요.
                    결제 금액은 사람마다 따로 고칠 수 있고, 참가비를 바꿔도 이미 추가한 사람의 금액은 그대로예요.
                </div>
            </div>
            </div>
        </div>
    </div>

    {{-- 대상 목록 (플랫폼별) --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="ti ti-users me-1"></i> 페이백 대상</h3>
            </div>
            <div class="card-body">
                <div class="pb-bar mb-3">
                    <div class="btn-group w-100 pb-filter" role="group" aria-label="상태 보기">
                        <input type="radio" class="btn-check" name="pbFilter" id="pbFilterAll" value="all" autocomplete="off" checked>
                        <label class="btn btn-outline-secondary" for="pbFilterAll">전체</label>
                        <input type="radio" class="btn-check" name="pbFilter" id="pbFilterWait" value="wait" autocomplete="off">
                        <label class="btn btn-outline-orange" for="pbFilterWait">입금 대기</label>
                        <input type="radio" class="btn-check" name="pbFilter" id="pbFilterDone" value="done" autocomplete="off">
                        <label class="btn btn-outline-green" for="pbFilterDone">입금 완료</label>
                        <input type="radio" class="btn-check" name="pbFilter" id="pbFilterNoShow" value="noshow" autocomplete="off">
                        <label class="btn btn-outline-red" for="pbFilterNoShow">노쇼</label>
                    </div>
                    <button type="button" class="btn btn-success px-4" id="pbAddBtn"><i class="ti ti-plus me-1"></i>대상 추가</button>
                </div>
                <div id="pbList"></div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        var LIST_URL       = @json(route('admin.rotation.event.paybacks.list', $event->id));
        var ADD_URL        = @json(route('admin.rotation.event.paybacks.add', $event->id));
        var FEE_URL        = @json(route('admin.rotation.event.fees.save', $event->id));
        var ITEM_TPL       = @json(route('admin.rotation.event.paybacks.update', [$event->id, '__PID__']));
        var TRANSFER_TPL   = @json(route('admin.rotation.event.paybacks.transfer', [$event->id, '__PID__']));
        var RATE_LABEL     = @json($rateOptions);
        var BANKS          = @json($bankOptions);
        var PLATFORMS      = @json($platformOptions);
        var PLATFORM_LABEL = @json(collect($platformOptions)->pluck('label', 'id'));
        var PLATFORM_BADGE = { munto: 'bg-orange-lt', frip: 'bg-cyan-lt' };

        var state = { items: [], candidates: [], fees: { prices: {}, early_bird_rates: {} } };

        function esc(v){ return $('<div>').text(v == null ? '' : v).html(); }
        function won(n){ return (Number(n) || 0).toLocaleString('ko-KR') + '원'; }
        function digits(v){ var d = String(v == null ? '' : v).replace(/[^\d]/g, ''); return d === '' ? null : parseInt(d, 10); }
        function calc(paid, rate){ return Math.floor((Number(paid) || 0) * rate / 100); }
        function itemUrl(id){ return ITEM_TPL.replace('__PID__', id); }
        function ajaxError(xhr){ alert(xhr?.responseJSON?.error?.message ?? xhr?.responseJSON?.msg ?? '오류가 발생했습니다.'); }
        function who(r){
            var g = r.gender === 'F' ? '여' : '남';
            return (r.ticket_no ? '[' + g + ' ' + r.ticket_no + '] ' : '[' + g + '] ') + (r.name || '닉네임 없음');
        }

        // 서버(EventRound::paidPriceFor)와 같은 규칙: 플랫폼·성별 정가, 얼리버드면 그 플랫폼 할인율만큼 빼고 원 단위 내림
        function rateFor(platform){ return Number(state.fees.early_bird_rates[platform] || 0); }
        function priceFor(platform, gender, earlyBird){
            var base = (state.fees.prices[platform] || {})[gender];
            if(base == null){ return null; }
            return earlyBird ? Math.floor(Number(base) * (100 - rateFor(platform)) / 100) : Number(base);
        }

        // 금액 입력칸: 입력하면서 쉼표 표시
        $(document).on('input', '.money-input', function(){
            var n = digits(this.value);
            this.value = n === null ? '' : n.toLocaleString('ko-KR');
        });

        /* ===== 참가비 표: 얼리버드가 미리보기 ===== */
        function renderEarlyBirdHints(){
            $('.fee-price').each(function(){
                var rate = Math.min(100, digits($('.fee-rate[data-platform="' + $(this).data('platform') + '"]').val()) || 0);
                var base = digits(this.value);
                var $hint = $('[data-eb-for="' + $(this).data('platform') + '-' + $(this).data('gender') + '"]');
                $hint.text(base !== null && rate > 0 ? '얼리버드 ' + won(Math.floor(base * (100 - rate) / 100)) : '');
            });
        }
        $(document).on('input', '.fee-price, .fee-rate', renderEarlyBirdHints);
        renderEarlyBirdHints();

        $('#feeSaveBtn').on('click', function(){
            var data = {};
            $('.fee-price').each(function(){
                data['fee_prices[' + $(this).data('platform') + '][' + $(this).data('gender') + ']'] = this.value;
            });
            $('.fee-rate').each(function(){
                data['early_bird_rates[' + $(this).data('platform') + ']'] = this.value;
            });
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: FEE_URL, type: 'PUT', data: data,
                success: function(res){ alert(res?.msg); load(); },
                error: ajaxError
            }).always(function(){ $btn.prop('disabled', false); });
        });

        /* ===== 목록 ===== */
        function load(){
            $.getJSON(LIST_URL).done(function(res){
                state.items      = res.items || [];
                state.candidates = res.candidates || [];
                state.fees       = res.fees || state.fees;
                if(Array.isArray(state.fees.prices)){ state.fees.prices = {}; } // 빈 JSON 은 배열로 올 수 있다
                if(!state.fees.early_bird_rates || Array.isArray(state.fees.early_bird_rates)){ state.fees.early_bird_rates = {}; }
                renderSummary(res.summary || {});
                renderFeeHead();
                render();
            }).fail(ajaxError);
        }

        function renderSummary(s){
            $('#sumWaitHead').text(won(s.waiting_sum) + ' · ' + (s.waiting_count || 0) + '명');
            $('#sumWaitAmount').text(won(s.waiting_sum));
            $('#sumWaitCount').text(s.waiting_count || 0);
            $('#sumDoneAmount').text(won(s.done_sum));
            $('#sumDoneCount').text(s.done_count || 0);
            $('#sumNoShowCount').text(s.no_show_count || 0);
            $('#sumTotalCount').text(s.total_count || 0);
        }

        function matchFilter(r, f){
            if(f === 'wait'){ return !r.is_done && !r.is_no_show; }
            if(f === 'done'){ return r.is_done; }
            if(f === 'noshow'){ return r.is_no_show; }
            return true;
        }

        function card(r){
            var cls  = r.is_done ? 'is-done' : (r.is_no_show ? 'is-noshow' : '');
            var g    = r.gender === 'F' ? '여' : '남';
            var tick = r.ticket_no
                ? '<div class="pb-ticket ' + (r.gender === 'F' ? 'is-f' : '') + '"><small>' + g + '</small><b>' + r.ticket_no + '</b></div>'
                : '<div class="pb-ticket is-none">번호<br>미배정</div>';
            var badges = '<span class="badge ' + (r.payback_rate === 100 ? 'bg-purple-lt' : 'bg-blue-lt') + '">' + esc(RATE_LABEL[r.payback_rate] || (r.payback_rate + '%')) + '</span>'
                + (r.is_early_bird ? '<span class="badge bg-pink-lt">얼리버드</span>' : '<span class="badge bg-secondary-lt">정가</span>')
                + (r.is_done ? '<span class="badge bg-green text-white"><i class="ti ti-check me-1"></i>입금 완료</span>'
                    : (r.is_no_show ? '<span class="badge bg-red text-white"><i class="ti ti-user-x me-1"></i>노쇼</span>'
                        : '<span class="badge bg-orange-lt">입금 대기</span>'))
                + (r.checked_in ? '<span class="badge bg-teal-lt">체크인</span>' : '');

            var hasAccount = r.bank_name || r.account_number || r.account_holder;
            var account = hasAccount
                ? esc([r.bank_name, r.account_number, r.account_holder].filter(Boolean).join(' '))
                : '<span class="text-danger">미입력</span>';
            // 계좌번호가 있을 때만 복사 버튼
            var copyBtn = r.account_number
                ? '<button type="button" class="btn btn-sm btn-outline-secondary btn-copy" data-id="' + r.id + '"><i class="ti ti-copy me-1"></i>계좌번호 복사</button>' : '';

            var info = '<dl class="pb-info">'
                + '<div><dt>계좌</dt><dd>' + account + '</dd>' + copyBtn + '</div>'
                + (r.memo ? '<div><dt>메모</dt><dd>' + esc(r.memo) + '</dd></div>' : '')
                + (r.is_done ? '<div><dt>완료</dt><dd>' + esc(r.transferred_at) + (r.transferrer ? ' · ' + esc(r.transferrer) : '') + '</dd></div>' : '')
                + '</dl>';

            var toggle = r.is_done
                ? '<button type="button" class="btn btn-outline-secondary btn-transfer" data-id="' + r.id + '" data-done="0"><i class="ti ti-arrow-back-up me-1"></i>완료 취소</button>'
                : '<button type="button" class="btn btn-success btn-transfer" data-id="' + r.id + '" data-done="1"' + (r.is_no_show ? ' disabled title="노쇼는 입금 완료할 수 없어요"' : '') + '><i class="ti ti-check me-1"></i>입금 완료</button>';

            return '<div class="pb-card ' + cls + '">'
                + '<div class="pb-head">' + tick
                +   '<div class="pb-who"><div class="pb-nick">' + esc(r.name || '닉네임 없음') + '</div><div class="pb-badges">' + badges + '</div></div>'
                +   '<div class="pb-amount"><div class="pb-pay">' + won(r.payback_amount) + '</div><div class="pb-calc">결제 ' + won(r.paid_amount) + ' × ' + r.payback_rate + '%</div></div>'
                + '</div>'
                + info
                + '<div class="pb-actions">' + toggle
                +   '<button type="button" class="btn btn-outline-primary btn-edit" data-id="' + r.id + '"><i class="ti ti-edit me-1"></i>수정</button>'
                +   '<button type="button" class="btn btn-outline-danger btn-del" data-id="' + r.id + '"' + (r.is_done ? ' disabled title="입금 완료를 먼저 취소해 주세요"' : '') + '><i class="ti ti-trash me-1"></i>삭제</button>'
                + '</div>'
                + '</div>';
        }

        // 플랫폼별 묶음. 묶음 머리에 그 플랫폼의 입금 대기 금액을 보여준다.
        function section(platformId, label, rows){
            var wait    = rows.filter(function(r){ return !r.is_done && !r.is_no_show; });
            var waitSum = wait.reduce(function(sum, r){ return sum + (Number(r.payback_amount) || 0); }, 0);
            var headCls = platformId === 'munto' ? 'is-munto' : (platformId === 'frip' ? 'is-frip' : 'is-etc');
            return '<div class="pb-section">'
                + '<div class="pb-section-head ' + headCls + '"><span>' + esc(label) + ' · ' + rows.length + '명</span>'
                +   '<span class="pb-section-sum">입금 대기 ' + wait.length + '명 · ' + won(waitSum) + '</span></div>'
                + '<div class="pb-grid">' + rows.map(card).join('') + '</div>'
                + '</div>';
        }

        function render(){
            var f    = $('input[name=pbFilter]:checked').val();
            var list = state.items.filter(function(r){ return matchFilter(r, f); });
            if(list.length === 0){
                $('#pbList').html('<div class="pb-empty">'
                    + (state.items.length === 0 ? '아직 페이백 대상이 없어요. <b>대상 추가</b>로 체크인 명단에서 골라 주세요.' : '해당하는 대상이 없어요.')
                    + '</div>');
                return;
            }
            var known = PLATFORMS.map(function(p){ return p.id; });
            var html  = PLATFORMS.map(function(p){
                var rows = list.filter(function(r){ return r.platform === p.id; });
                return rows.length ? section(p.id, p.label, rows) : '';
            }).join('');
            var etc = list.filter(function(r){ return known.indexOf(r.platform) === -1; });
            if(etc.length){ html += section('etc', '기타', etc); }
            $('#pbList').html(html);
        }

        // 참가비 카드 머리 요약: 플랫폼별 "문토 남 39,000 · 여 29,000 · 얼리 20%" (저장된 값 기준)
        function renderFeeHead(){
            var html = PLATFORMS.map(function(p){
                var pr = state.fees.prices[p.id] || {};
                var parts = [];
                if(pr.M != null){ parts.push('남 ' + Number(pr.M).toLocaleString('ko-KR')); }
                if(pr.F != null){ parts.push('여 ' + Number(pr.F).toLocaleString('ko-KR')); }
                if(rateFor(p.id) > 0){ parts.push('얼리 ' + rateFor(p.id) + '%'); }
                return '<span class="badge ' + (PLATFORM_BADGE[p.id] || 'bg-secondary-lt') + ' pb-head-badge">' + esc(p.label) + ' ' + (parts.length ? esc(parts.join(' · ')) : '미설정') + '</span>';
            }).join('');
            $('#feeHeadSummary').html(html);
        }

        // 카드 접기/펼치기: 상태 기억(새로고침 후에도 유지), 머리에서 Enter/Space 로도 토글.
        // 머리 안의 버튼(새로고침)을 눌렀을 때는 접히지 않게 한다.
        document.querySelectorAll('#pbSummaryCollapse, #pbFeeCollapse').forEach(function(el){
            var KEY   = 'rotable.payback.' + el.id + '.collapsed';
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
        $('.pb-collapse-head').on('click', 'button', function(e){ e.stopPropagation(); });

        $('input[name=pbFilter]').on('change', render);
        $('#pbRefreshBtn').on('click', load);

        /* ===== 추가 / 수정 폼 (Swal) ===== */
        async function openForm(item){
            var isEdit = !!item;
            if(!isEdit && state.candidates.length === 0){
                await salert({text: '추가할 수 있는 사람이 없어요. 체크인 명단에 먼저 등록해 주세요.', cancel: false});
                return;
            }
            // 얼리버드는 선착순이라 받은 사람만 체크한다. (기본 꺼짐)
            var v = item || { payback_rate: 80, is_early_bird: false, paid_amount: null, bank_name: '', account_number: '', account_holder: '', memo: '' };
            var label = function(t, forId){ return '<label for="' + forId + '" style="font-size:.85rem;color:#475569;font-weight:700;display:block;margin:10px 0 4px;text-align:left;">' + t + '</label>'; };
            var input = function(id, val, ph, extra){ return '<input id="' + id + '" class="swal2-input" value="' + esc(val) + '" placeholder="' + ph + '" ' + (extra || '') + ' style="width:100%;margin:0;box-sizing:border-box;">'; };

            // 대상자: 남/여 탭 + 번호표·닉네임 검색 + 눌러서 고르는 목록
            var countM = state.candidates.filter(function(c){ return c.gender !== 'F'; }).length;
            var countF = state.candidates.length - countM;
            var rosterField = isEdit
                ? '<div style="text-align:left;font-weight:800;font-size:1rem;">' + esc(who(item)) + ' · ' + esc(PLATFORM_LABEL[item.platform] || '') + '</div>'
                : label('대상자 *', 'sw-search')
                  + '<div class="pb-pick-tabs" role="group" aria-label="성별">'
                  +   '<button type="button" class="pb-pick-tab is-f" data-g="F">여 ' + countF + '</button>'
                  +   '<button type="button" class="pb-pick-tab is-m" data-g="M">남 ' + countM + '</button>'
                  + '</div>'
                  + '<input id="sw-search" class="swal2-input" placeholder="번호표 또는 닉네임 검색" autocomplete="off" style="width:100%;margin:6px 0 0;box-sizing:border-box;">'
                  + '<div id="sw-roster-list" class="pb-pick-list" role="radiogroup" aria-label="대상자 목록"></div>'
                  + '<div id="sw-picked" class="pb-picked"></div>';

            // 은행 선택 (목록에 없는 은행은 '기타'를 고르고 메모에 적는다)
            var bankList = BANKS.slice();
            var bankField = label('은행', 'sw-bank')
                + '<select id="sw-bank" class="swal2-select" style="width:100%;margin:0;">'
                + '<option value="">선택 안 함</option>'
                + bankList.map(function(b){ return '<option value="' + esc(b) + '"' + (b === v.bank_name ? ' selected' : '') + '>' + esc(b) + '</option>'; }).join('')
                + '</select>';

            var rateField = label('페이백 비율 *', 'sw-rate')
                + '<div class="pb-seg">'
                + Object.keys(RATE_LABEL).sort(function(a, b){ return a - b; }).map(function(rate){ // 80% 를 왼쪽에
                    return '<label class="pb-seg-item"><input type="radio" name="sw-rate" value="' + rate + '"' + (Number(rate) === Number(v.payback_rate) ? ' checked' : '') + '><span>' + esc(RATE_LABEL[rate]) + '</span></label>';
                }).join('')
                + '</div>';

            var ebField = '<label style="display:flex;align-items:center;gap:8px;margin-top:12px;font-weight:700;cursor:pointer;text-align:left;">'
                + '<input type="checkbox" id="sw-eb"' + (v.is_early_bird ? ' checked' : '') + ' style="width:18px;height:18px;"> 얼리버드 적용 (선착순)'
                + ' <span id="sw-eb-rate" style="font-weight:600;"></span>'
                + '</label>';

            var res = await Swal.fire({
                title: isEdit ? '페이백 수정' : '페이백 대상 추가',
                html: rosterField + rateField + ebField
                    + label('결제 금액 (원)', 'sw-paid') + input('sw-paid', v.paid_amount != null ? Number(v.paid_amount).toLocaleString('ko-KR') : '', '', 'inputmode="numeric"')
                    + '<div id="sw-preview" style="text-align:left;margin-top:6px;font-size:.9rem;color:#d9480f;font-weight:800;"></div>'
                    + '<div class="pb-form-2col"><div>' + bankField + '</div><div>'
                    +   label('예금주', 'sw-holder') + input('sw-holder', v.account_holder || '', '예: 홍길동', 'maxlength="30"')
                    + '</div></div>'
                    + label('계좌번호', 'sw-account') + input('sw-account', v.account_number || '', '숫자와 - 만', 'maxlength="30" inputmode="numeric"')
                    + label('메모', 'sw-memo') + input('sw-memo', v.memo || '', '', 'maxlength="255"'),
                showCancelButton: true,
                showCloseButton: true,
                closeButtonAriaLabel: '닫기',
                reverseButtons: true,
                customClass: { popup: 'pb-swal', htmlContainer: 'pb-swal-body', actions: 'pb-swal-actions' },
                confirmButtonText: isEdit ? '저장' : '추가',
                cancelButtonText: '취소',
                didOpen: function(){
                    var $paid = $('#sw-paid').addClass('money-input');
                    // 지금 고른 사람의 플랫폼·성별
                    var pickedId = null;
                    function person(){
                        if(isEdit){ return item; }
                        return state.candidates.find(function(c){ return String(c.id) === String(pickedId); }) || {};
                    }

                    // 대상자 목록: 고른 성별 + 검색어(번호표 정확 일치 또는 닉네임 포함)
                    // 여자가 먼저 오는 경우가 많아 여자 탭부터 연다. (여자 후보가 없으면 남자)
                    var pickGender = countF > 0 ? 'F' : 'M';
                    function renderPickList(){
                        if(isEdit){ return; }
                        $('.pb-pick-tab').each(function(){ $(this).toggleClass('is-active', $(this).data('g') === pickGender); });
                        var q    = ($('#sw-search').val() || '').trim().toLowerCase();
                        var rows = state.candidates.filter(function(c){
                            if((c.gender === 'F' ? 'F' : 'M') !== pickGender){ return false; }
                            if(q === ''){ return true; }
                            return String(c.ticket_no || '') === q || (c.name || '').toLowerCase().indexOf(q) !== -1;
                        });
                        $('#sw-roster-list').html(rows.length ? rows.map(function(c){
                            var pf = PLATFORM_LABEL[c.platform] || '';
                            return '<button type="button" class="pb-pick-item' + (String(c.id) === String(pickedId) ? ' is-picked' : '') + '" data-id="' + c.id + '" role="radio" aria-checked="' + (String(c.id) === String(pickedId)) + '">'
                                + '<span class="pb-pick-no ' + (c.gender === 'F' ? 'is-f' : '') + '">' + (c.ticket_no || '-') + '</span>'
                                + '<span class="pb-pick-name">' + esc(c.name || '닉네임 없음') + '</span>'
                                + (pf ? '<span class="badge ' + (PLATFORM_BADGE[c.platform] || 'bg-secondary-lt') + '">' + esc(pf) + '</span>' : '')
                                + (c.checked_in ? '' : '<span class="badge bg-secondary-lt">미체크인</span>')
                                + '</button>';
                        }).join('') : '<div class="pb-pick-empty">해당하는 사람이 없어요.</div>');
                        var p = person();
                        $('#sw-picked').html(p.id ? '선택: <b>' + esc(who(p)) + '</b>' + (PLATFORM_LABEL[p.platform] ? ' · ' + esc(PLATFORM_LABEL[p.platform]) : '') : '아래 목록에서 눌러 선택해 주세요.');
                    }
                    $('.pb-pick-tab').on('click', function(){ pickGender = $(this).data('g'); renderPickList(); $('#sw-search').trigger('focus'); });
                    $('#sw-search').on('input', renderPickList);
                    $('#sw-roster-list').on('click', '.pb-pick-item', function(){ pickedId = $(this).data('id'); renderPickList(); preview(); });
                    renderPickList();
                    function autoPrice(eb){ var p = person(); return priceFor(p.platform, p.gender, eb); }
                    // 결제 금액이 비어 있으면 자동 금액을 placeholder 로 보여주고, 페이백 금액을 미리 계산한다.
                    function preview(){
                        var pr = rateFor(person().platform);
                        $('#sw-eb-rate').text(pr > 0 ? '(' + (PLATFORM_LABEL[person().platform] || '') + ' ' + pr + '% 할인)' : '(이 플랫폼 할인율 0%)')
                            .css('color', pr > 0 ? '#ae3ec9' : '#868e96');
                        var eb   = $('#sw-eb').is(':checked');
                        var rate = Number($('input[name=sw-rate]:checked').val());
                        var fee  = autoPrice(eb);
                        $paid.attr('placeholder', fee != null ? fee.toLocaleString('ko-KR') + ' (참가비 자동)' : '이 플랫폼·성별 참가비 미설정 · 직접 입력');
                        var paid = digits($paid.val());
                        if(paid === null){ paid = fee; }
                        $('#sw-preview').text(paid != null ? '→ 페이백 ' + won(calc(paid, rate)) : '결제 금액을 입력해 주세요.');
                    }
                    // 얼리버드를 바꿀 때, 결제 금액이 이전 자동 금액 그대로면 새 자동 금액으로 바꾼다. (직접 고친 금액은 유지)
                    $('#sw-eb').on('change', function(){
                        var before = autoPrice(!this.checked);
                        var paid   = digits($paid.val());
                        if(paid !== null && before != null && paid === before){
                            var after = autoPrice(this.checked);
                            $paid.val(after != null ? after.toLocaleString('ko-KR') : '');
                        }
                        preview();
                    });
                    $('input[name=sw-rate]').on('change', preview);
                    $paid.on('input', preview);
                    preview();
                },
                preConfirm: function(){
                    var picked = isEdit ? item.id : $('.pb-pick-item.is-picked').data('id');
                    if(!isEdit && !picked){
                        Swal.showValidationMessage('대상자를 선택해 주세요.');
                        return false;
                    }
                    var eb   = $('#sw-eb').is(':checked');
                    var paid = digits($('#sw-paid').val());
                    if(paid === null && $('#sw-paid').attr('placeholder').indexOf('미설정') !== -1){
                        Swal.showValidationMessage('결제 금액을 입력하거나 이 플랫폼·성별의 참가비를 먼저 저장해 주세요.');
                        return false;
                    }
                    var out = {
                        payback_rate:   $('input[name=sw-rate]:checked').val(),
                        is_early_bird:  eb ? 1 : 0,
                        paid_amount:    paid === null ? '' : paid,
                        bank_name:      $('#sw-bank').val() || '',
                        account_number: ($('#sw-account').val() || '').trim(),
                        account_holder: ($('#sw-holder').val() || '').trim(),
                        memo:           ($('#sw-memo').val() || '').trim()
                    };
                    if(!isEdit){ out.event_roster_id = picked; }
                    return out;
                }
            });
            if(!res.isConfirmed){ return; }

            $.ajax({
                url: isEdit ? itemUrl(item.id) : ADD_URL,
                type: isEdit ? 'PUT' : 'POST',
                data: res.value,
                success: function(out){ if(out?.status !== 200){ alert(out?.msg ?? '저장하지 못했습니다.'); } load(); },
                error: ajaxError
            });
        }

        function findItem(id){ return state.items.find(function(x){ return String(x.id) === String(id); }); }

        $('#pbAddBtn').on('click', function(){ openForm(null); });
        $(document).on('click', '.btn-edit', function(){ var it = findItem($(this).data('id')); if(it){ openForm(it); } });

        /* ===== 입금 완료 / 취소 ===== */
        $(document).on('click', '.btn-transfer', async function(){
            var it   = findItem($(this).data('id'));
            var done = String($(this).data('done')) === '1';
            if(!it){ return; }
            var text = done
                ? who(it) + '님에게 ' + won(it.payback_amount) + '을 입금 완료로 표시할까요?'
                : who(it) + '님의 입금 완료를 취소할까요?';
            if(!(await salert({text: text, icon: 'question'}))){ return; }
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: TRANSFER_TPL.replace('__PID__', it.id), type: 'PATCH', data: { done: done ? 1 : 0 },
                success: function(out){ if(out?.status !== 200){ alert(out?.msg); } load(); },
                error: function(xhr){ ajaxError(xhr); $btn.prop('disabled', false); }
            });
        });

        /* ===== 삭제 ===== */
        $(document).on('click', '.btn-del', async function(){
            var it = findItem($(this).data('id'));
            if(!it){ return; }
            if(!(await salert({text: who(it) + '님을 페이백 대상에서 뺄까요? (계좌 정보도 함께 지워져요)', icon: 'warning'}))){ return; }
            $.ajax({
                url: itemUrl(it.id), type: 'DELETE',
                success: function(out){ if(out?.status !== 200){ alert(out?.msg); } load(); },
                error: ajaxError
            });
        });

        /* ===== 계좌번호 복사 (계좌번호만) ===== */
        $(document).on('click', '.btn-copy', async function(){
            var it = findItem($(this).data('id'));
            if(!it || !it.account_number){ return; }
            var text = String(it.account_number);
            var $b = $(this);
            try {
                await navigator.clipboard.writeText(text);
                $b.html('<i class="ti ti-check me-1"></i>복사됨');
                setTimeout(function(){ $b.html('<i class="ti ti-copy me-1"></i>계좌번호 복사'); }, 1500);
            } catch(e) {
                window.prompt('아래 계좌번호를 복사해 주세요.', text);
            }
        });

        load();
    });
</script>
@endsection
