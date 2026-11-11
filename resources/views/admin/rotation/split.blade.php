@extends('layouts.app')

@section('title', 'N빵 계산기')

@section('content')
<div class="row row-cards mt-2" id="split-app">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="ti ti-arrows-split text-green me-1"></i> N빵 계산기
                    <div class="fs-5 fw-normal mt-1" id="split-desc"></div>
                </h3>
            </div>

            <div class="card-body">
                {{-- 모드 토글 + 액션 (모바일: 한 줄 2개씩 꽉 차게) --}}
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-6">
                        <div class="btn-group w-100" role="group" aria-label="정산 방식 선택">
                            <input type="radio" class="btn-check" name="split-mode" id="split-mode-income" value="income" autocomplete="off" checked>
                            <label class="btn btn-outline-primary w-50" for="split-mode-income"><i class="ti ti-coin me-1"></i> 수입 N빵</label>

                            <input type="radio" class="btn-check" name="split-mode" id="split-mode-expense" value="expense" autocomplete="off">
                            <label class="btn btn-outline-primary w-50" for="split-mode-expense"><i class="ti ti-receipt me-1"></i> 지출 N빵</label>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <button type="button" class="btn btn-primary w-100" id="split-copy">
                            <i class="ti ti-link me-1"></i> 링크 복사
                        </button>
                    </div>
                    <div class="col-6 col-md-3">
                        <button type="button" class="btn btn-outline-secondary w-100" id="split-reset">
                            <i class="ti ti-refresh me-1"></i> 초기화
                        </button>
                    </div>
                </div>

                <div class="row g-3">
                    {{-- ===== 좌측: 입력 ===== --}}
                    <div class="col-lg-7">
                        {{-- 총액 --}}
                        <div class="card card-sm mb-3">
                            <div class="card-body">
                                <div class="fw-bold mb-2" id="split-total-title"></div>
                                <div class="input-group input-group-flat">
                                    <input type="text" inputmode="numeric" class="form-control split-money text-end" id="split-total" placeholder="0">
                                    <span class="input-group-text">원</span>
                                </div>
                                <div class="small mt-2" id="split-total-hint"></div>
                            </div>
                        </div>

                        {{-- 참여자 (모바일 고려: 표 대신 카드형 행) --}}
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="fw-bold mb-2"><i class="ti ti-users me-1 text-blue"></i> 참여자</div>

                                {{-- PC 전용 헤더 (모바일에선 각 입력칸 위에 라벨 표시) --}}
                                <div class="split-row-head d-none d-md-block">
                                    <div class="row g-2 small fw-bold text-center">
                                        <div class="split-col-name">이름</div>
                                        <div class="split-col-paid" id="split-paid-th"></div>
                                        <div class="split-col-share">지분</div>
                                        <div class="split-col-holder split-income-only">보관</div>
                                        <div class="split-col-del">삭제</div>
                                    </div>
                                </div>

                                <div id="split-people"></div>

                                <button type="button" class="btn btn-outline-primary w-100 mt-1" id="split-add"><i class="ti ti-plus me-1"></i> 참여자 추가</button>
                                <div class="small mt-2" id="split-people-hint"></div>
                            </div>
                        </div>
                    </div>

                    {{-- ===== 우측: 결과 ===== --}}
                    <div class="col-lg-5">
                        <div class="card card-sm position-lg-sticky" style="top: 1rem;">
                            <div class="card-status-top bg-green"></div>
                            <div class="card-body">
                                <div class="fw-bold mb-3"><i class="ti ti-report-money me-1 text-green"></i> 정산 결과</div>

                                <table class="table table-sm table-vcenter mb-3">
                                    <tbody id="split-summary"></tbody>
                                </table>

                                <div class="table-responsive">
                                    <table class="table table-sm table-vcenter table-bordered text-nowrap mb-0">
                                        <thead id="split-result-head"></thead>
                                        <tbody id="split-result"></tbody>
                                    </table>
                                </div>

                                <div class="mt-3" id="split-transfer-box" style="display:none;">
                                    <div class="fw-bold mb-2"><i class="ti ti-transfer me-1 text-blue"></i> 송금 안내</div>
                                    <ul class="list-unstyled mb-0" id="split-transfer"></ul>
                                </div>

                                <div class="alert alert-danger py-2 mt-3 mb-0" id="split-warn" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* 모바일: 참여자마다 카드 / PC(md 이상): 한 줄짜리 표 형태 */
    #split-people .split-row { border: 1px solid var(--tblr-border-color, #e6e7e9); border-radius: 6px; padding: .5rem; margin-bottom: .5rem; }
    @media (min-width: 768px) {
        #split-people .split-row { border: 0; border-bottom: 1px solid var(--tblr-border-color, #e6e7e9); border-radius: 0; padding: .4rem 0; margin-bottom: 0; }
        .split-row-head { border-bottom: 1px solid var(--tblr-border-color, #e6e7e9); padding-bottom: .4rem; }
        #split-app .split-col-name   { flex: 0 0 auto; width: 28%; }
        #split-app .split-col-paid   { flex: 0 0 auto; width: 34%; }
        #split-app .split-col-share  { flex: 0 0 auto; width: 14%; }
        #split-app .split-col-holder { flex: 0 0 auto; width: 12%; }
        #split-app .split-col-del    { flex: 0 0 auto; width: 12%; }
        /* 지출 모드: 보관 칸이 없으므로 나머지가 자리 채움 */
        #split-app.is-expense .split-col-name { width: 32%; }
        #split-app.is-expense .split-col-paid { width: 40%; }
    }
</style>

<script>
$(function () {
    'use strict';

    /**
     * 수입 N빵: 총수입에서 각자 먼저 낸 지출을 돌려주고, 남은 순이익을 지분대로 나눈다.
     * 지출 N빵: 총지출을 지분대로 나눠 1인 부담액을 정하고, 이미 낸 금액과의 차액(더 낼 돈 / 돌려받을 돈)을 계산한다.
     */
    var MODE_TEXT = {
        income: {
            desc: '— 각자 낸 지출을 먼저 돌려받고, 남은 순이익을 지분대로 나눕니다.',
            totalTitle: '<i class="ti ti-coin me-1 text-green"></i> 총수입',
            totalHint: '지출을 빼기 전, 실제로 들어온 돈 전체를 입력하세요.',
            paidTh: '내가 낸 지출',
            peopleHint: '지분은 비율입니다. 모두 1이면 똑같이 나누고, 2 : 1 이면 2배를 가져갑니다. 수입을 가지고 있는 사람을 <b>돈 보관</b>으로 선택하면 송금 안내가 나옵니다.'
        },
        expense: {
            desc: '— 총지출을 지분대로 나누고, 이미 낸 금액을 빼서 각자 더 낼 돈 / 돌려받을 돈을 계산합니다.',
            totalTitle: '<i class="ti ti-receipt me-1 text-red"></i> 총지출',
            totalHint: '전체 비용을 입력하세요. 비워두면 참여자들이 낸 금액의 합계를 총지출로 봅니다.',
            paidTh: '이미 낸 금액',
            peopleHint: '지분은 비율입니다. 모두 1이면 똑같이 부담하고, 2 : 1 이면 2배를 부담합니다. 아직 아무도 결제하지 않은 금액은 <b>결제처에 낼 금액</b>으로 안내됩니다.'
        }
    };

    var DEFAULT_STATE = {
        mode: 'income',
        total: '',
        holder: -1,
        people: [
            { name: '김도한', paid: 0, share: 1 },
            { name: '김준우', paid: 0, share: 1 }
        ]
    };

    var $app = $('#split-app');
    var $people = $('#split-people');
    var currentMode = 'income';
    var rowSeq = 0; // 라디오/label id 를 고유하게 만들기 위한 증가값
    var MIN_PEOPLE = 2; // N빵 최소 인원

    function num(v) { var n = parseFloat(String(v == null ? '' : v).replace(/,/g, '')); return isFinite(n) ? n : 0; }
    function fmt(v) { return (v === '' || v == null) ? '' : num(v).toLocaleString('ko-KR'); }
    function won(n) { return Math.round(n || 0).toLocaleString('ko-KR') + ' 원'; }
    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function nameOf(p, i) { return p.name || ('참여자 ' + (i + 1)); }

    function rowHtml(p, idx) {
        var paidLabel = MODE_TEXT[currentMode].paidTh;
        return '' +
        '<div class="split-row">' +
            '<div class="row g-2 align-items-end align-items-md-center">' +
                '<div class="col-12 split-col-name">' +
                    '<label class="form-label small mb-1 d-md-none">이름</label>' +
                    '<input type="text" class="form-control split-name" placeholder="이름" value="' + esc(p.name) + '">' +
                '</div>' +
                '<div class="col-8 split-col-paid">' +
                    '<label class="form-label small mb-1 d-md-none split-paid-label">' + paidLabel + '</label>' +
                    '<div class="input-group input-group-flat">' +
                        '<input type="text" inputmode="numeric" class="form-control split-money split-paid text-end" placeholder="0" value="' + fmt(p.paid) + '">' +
                        '<span class="input-group-text">원</span>' +
                    '</div>' +
                '</div>' +
                '<div class="col-4 split-col-share">' +
                    '<label class="form-label small mb-1 d-md-none">지분</label>' +
                    '<input type="number" inputmode="decimal" min="0" step="0.5" class="form-control split-share text-center" value="' + (p.share != null ? p.share : 1) + '">' +
                '</div>' +
                '<div class="col-6 split-col-holder split-income-only">' +
                    '<input type="radio" class="btn-check split-holder" name="split-holder" id="split-holder-' + idx + '" value="' + idx + '" autocomplete="off">' +
                    '<label class="btn btn-outline-blue w-100 split-holder-label" for="split-holder-' + idx + '" title="돈 보관"><i class="ti ti-wallet"></i><span class="ms-1 d-md-none">돈 보관</span></label>' +
                '</div>' +
                '<div class="col-6 split-col-del split-del-col">' +
                    '<button type="button" class="btn btn-outline-danger w-100 split-del" title="삭제"><i class="ti ti-trash"></i><span class="ms-1 d-md-none">삭제</span></button>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    function collect() {
        var people = [];
        var holder = -1;
        $people.find('.split-row').each(function (i) {
            people.push({
                name: $(this).find('.split-name').val() || '',
                paid: num($(this).find('.split-paid').val()),
                share: num($(this).find('.split-share').val())
            });
            if ($(this).find('.split-holder').is(':checked')) { holder = i; }
        });
        var rawTotal = $('#split-total').val();
        return { mode: currentMode, total: rawTotal === '' ? '' : num(rawTotal), holder: holder, people: people };
    }

    function applyModeText() {
        var t = MODE_TEXT[currentMode];
        $('#split-desc').text(t.desc);
        $('#split-total-title').html(t.totalTitle);
        $('#split-total-hint').text(t.totalHint);
        $('#split-paid-th').text(t.paidTh);
        $people.find('.split-paid-label').text(t.paidTh);
        $('#split-people-hint').html(t.peopleHint);
        $app.find('.split-income-only').toggle(currentMode === 'income');
        $app.toggleClass('is-expense', currentMode !== 'income');
        // 모바일 지출 모드에선 '돈 보관' 이 없으므로 삭제 버튼을 한 줄 꽉 차게.
        $people.find('.split-del-col').toggleClass('col-6', currentMode === 'income').toggleClass('col-12', currentMode !== 'income');
    }

    function render(state) {
        currentMode = state.mode === 'expense' ? 'expense' : 'income';
        $('#split-mode-' + currentMode).prop('checked', true);
        // 이전 버전 호환: revenue/expense 키
        var total = state.total != null ? state.total : (state.revenue != null ? state.revenue : '');
        $('#split-total').val(fmt(total));
        $people.empty();
        (state.people || []).forEach(function (p) {
            $people.append(rowHtml({ name: p.name, paid: p.paid != null ? p.paid : p.expense, share: p.share }, rowSeq++));
        });
        reindexHolder(state.holder);
        applyModeText();
        recalc();
    }

    // 행 추가/삭제 후 라디오 value 를 행 순서에 맞게 재정렬한다.
    function reindexHolder(checkedIdx) {
        $people.find('.split-row').each(function (i) {
            var $r = $(this).find('.split-holder').val(i);
            if (checkedIdx != null) { $r.prop('checked', i === checkedIdx); }
            $r.data('was', $r.is(':checked'));
        });
    }

    function syncUrl(state) {
        var params = new URLSearchParams(window.location.search);
        params.set('d', JSON.stringify(state));
        history.replaceState(null, '', window.location.pathname + '?' + params.toString());
    }

    // 금액을 지분 비율로 나눈다. 원 단위 반올림 오차는 마지막 사람에게 몰아 합계를 정확히 맞춘다.
    function distribute(amount, people) {
        var totalShare = people.reduce(function (s, p) { return s + p.share; }, 0);
        var parts = [];
        var acc = 0;
        people.forEach(function (p, i) {
            var part;
            if (i === people.length - 1) {
                part = amount - acc;
            } else {
                part = totalShare > 0 ? Math.round(amount * p.share / totalShare) : 0;
                acc += part;
            }
            parts.push(part);
        });
        return parts;
    }

    function summaryRow(label, value, cls) {
        return '<tr class="' + (cls || '') + '"><td>' + label + '</td><td class="text-end">' + value + '</td></tr>';
    }

    function recalc() {
        // N빵이므로 최소 2명 유지: 2명 이하이면 삭제 버튼 비활성화.
        $people.find('.split-del').prop('disabled', $people.find('.split-row').length <= MIN_PEOPLE);

        var state = collect();
        var people = state.people;
        var totalShare = people.reduce(function (s, p) { return s + p.share; }, 0);
        var sumPaid = people.reduce(function (s, p) { return s + p.paid; }, 0);
        var warn = [];

        if (currentMode === 'income') {
            recalcIncome(state, people, sumPaid, warn);
        } else {
            recalcExpense(state, people, sumPaid, warn);
        }

        if (!people.length) {
            $('#split-result').html('<tr><td colspan="4" class="text-center py-3">참여자를 추가하세요.</td></tr>');
        }
        if (people.length && totalShare <= 0) { warn.push('지분 합계가 0입니다. 지분을 입력하세요.'); }
        $('#split-warn').html(warn.join('<br>')).toggle(warn.length > 0);

        syncUrl(state);
    }

    function recalcIncome(state, people, sumPaid, warn) {
        var revenue = num(state.total);
        var profit = revenue - sumPaid;
        var shares = distribute(profit, people);

        $('#split-summary').html(
            summaryRow('총수입', won(revenue)) +
            summaryRow('(-) 지출 합계', '<span class="text-red">- ' + won(sumPaid) + '</span>') +
            summaryRow('순이익', '<span class="' + (profit < 0 ? 'text-red' : 'text-green') + '">' + won(profit) + '</span>', 'fw-bold border-top')
        );
        $('#split-result-head').html('<tr><th class="text-center">이름</th><th class="text-center">지출 환급</th><th class="text-center">이익 몫</th><th class="text-center">받을 금액</th></tr>');

        var html = '';
        people.forEach(function (p, i) {
            html += '' +
            '<tr>' +
                '<td class="text-center fw-bold">' + esc(nameOf(p, i)) + '</td>' +
                '<td class="text-end">' + won(p.paid) + '</td>' +
                '<td class="text-end ' + (shares[i] < 0 ? 'text-red' : '') + '">' + won(shares[i]) + '</td>' +
                '<td class="text-end fw-bold text-green">' + won(p.paid + shares[i]) + '</td>' +
            '</tr>';
        });
        $('#split-result').html(html);

        // 송금 안내: 보관자가 나머지 사람에게 각자 받을 금액을 보낸다.
        if (state.holder >= 0 && people[state.holder]) {
            var holderName = esc(nameOf(people[state.holder], state.holder));
            var items = '';
            people.forEach(function (p, i) {
                if (i === state.holder) { return; }
                var amount = p.paid + shares[i];
                var who = esc(nameOf(p, i));
                if (amount >= 0) {
                    items += '<li class="mb-1"><b>' + holderName + '</b> → <b>' + who + '</b> : <b class="text-blue">' + won(amount) + '</b></li>';
                } else {
                    items += '<li class="mb-1"><b>' + who + '</b> → <b>' + holderName + '</b> : <b class="text-red">' + won(-amount) + '</b></li>';
                }
            });
            var keep = people[state.holder].paid + shares[state.holder];
            items += '<li class="mt-2 pt-2 border-top"><b>' + holderName + '</b> 최종 보유 : <b class="text-green">' + won(keep) + '</b></li>';
            $('#split-transfer').html(items);
            $('#split-transfer-box').show();
        } else {
            $('#split-transfer-box').hide();
        }

        if (profit < 0) { warn.push('지출이 수입보다 많습니다. 적자 ' + won(-profit) + '을 지분대로 나눠 부담합니다.'); }
    }

    function recalcExpense(state, people, sumPaid, warn) {
        // 총지출을 비워두면 낸 금액 합계를 총지출로 본다.
        var total = state.total === '' ? sumPaid : num(state.total);
        var unpaid = total - sumPaid; // 아직 결제처에 안 낸 금액
        var owes = distribute(total, people);

        $('#split-summary').html(
            summaryRow('총지출', won(total)) +
            summaryRow('이미 낸 금액 합계', won(sumPaid)) +
            summaryRow('아직 결제 안 된 금액', '<span class="' + (unpaid > 0 ? 'text-red' : '') + '">' + won(Math.max(unpaid, 0)) + '</span>', 'fw-bold border-top')
        );
        $('#split-result-head').html('<tr><th class="text-center">이름</th><th class="text-center">내 부담액</th><th class="text-center">이미 낸 금액</th><th class="text-center">정산</th></tr>');

        // balance > 0 : 더 내야 함, balance < 0 : 돌려받아야 함
        var balances = [];
        var html = '';
        people.forEach(function (p, i) {
            var bal = owes[i] - p.paid;
            balances.push(bal);
            var settle;
            if (bal > 0) {
                settle = '<span class="text-red fw-bold">' + won(bal) + ' 더 내기</span>';
            } else if (bal < 0) {
                settle = '<span class="text-green fw-bold">' + won(-bal) + ' 돌려받기</span>';
            } else {
                settle = '<span class="fw-bold">정산 완료</span>';
            }
            html += '' +
            '<tr>' +
                '<td class="text-center fw-bold">' + esc(nameOf(p, i)) + '</td>' +
                '<td class="text-end">' + won(owes[i]) + '</td>' +
                '<td class="text-end">' + won(p.paid) + '</td>' +
                '<td class="text-end">' + settle + '</td>' +
            '</tr>';
        });
        $('#split-result').html(html);

        // 송금 안내: 더 낼 사람이 먼저 많이 낸 사람에게 보내고, 남는 금액은 결제처에 낸다.
        var debtors = [], creditors = [];
        balances.forEach(function (b, i) {
            if (b > 0) { debtors.push({ i: i, amt: b }); }
            if (b < 0) { creditors.push({ i: i, amt: -b }); }
        });
        debtors.sort(function (a, b) { return b.amt - a.amt; });
        creditors.sort(function (a, b) { return b.amt - a.amt; });

        var items = '';
        var ci = 0;
        debtors.forEach(function (d) {
            while (d.amt > 0 && ci < creditors.length) {
                var c = creditors[ci];
                var amt = Math.min(d.amt, c.amt);
                items += '<li class="mb-1"><b>' + esc(nameOf(people[d.i], d.i)) + '</b> → <b>' + esc(nameOf(people[c.i], c.i)) + '</b> : <b class="text-blue">' + won(amt) + '</b></li>';
                d.amt -= amt;
                c.amt -= amt;
                if (c.amt <= 0) { ci++; }
            }
            if (d.amt > 0) {
                items += '<li class="mb-1"><b>' + esc(nameOf(people[d.i], d.i)) + '</b> → <b>결제처</b> : <b class="text-red">' + won(d.amt) + '</b></li>';
            }
        });
        $('#split-transfer').html(items);
        $('#split-transfer-box').toggle(items !== '');

        if (unpaid < 0) { warn.push('이미 낸 금액 합계가 총지출보다 ' + won(-unpaid) + ' 많습니다. 총지출을 확인하세요.'); }
    }

    // 금액 입력: 천 단위 콤마 자동 (커서 위치 보정)
    $app.on('input', '.split-money', function () {
        var el = this;
        var raw = el.value;
        var digits = raw.replace(/[^\d]/g, '');
        if (digits === '') { el.value = ''; return; }
        var caret = el.selectionStart || 0;
        var digitsLeft = raw.slice(0, caret).replace(/[^\d]/g, '').length;
        var formatted = parseInt(digits, 10).toLocaleString('ko-KR');
        el.value = formatted;
        var pos = 0, seen = 0;
        while (pos < formatted.length && seen < digitsLeft) {
            if (/\d/.test(formatted[pos])) { seen++; }
            pos++;
        }
        try { el.setSelectionRange(pos, pos); } catch (e) {}
    });

    $app.on('input change', '#split-total, #split-people input:not(.split-holder)', recalc);

    // 돈 보관: 이미 선택된 걸 다시 누르면 해제된다.
    $app.on('click', '.split-holder', function () {
        if ($(this).data('was')) { this.checked = false; }
        var self = this;
        $people.find('.split-holder').each(function () { $(this).data('was', this === self && self.checked); });
        recalc();
    });

    // 링크 복사: 현재 입력값이 담긴 URL 을 클립보드에 복사한다.
    $('#split-copy').on('click', function () {
        recalc();
        var url = window.location.href;
        function done() { salert({ text: '현재 계산 링크가 복사되었습니다.', cancel: false }); }
        function fallback() {
            var $t = $('<textarea readonly>').val(url).css({ position: 'fixed', top: 0, left: 0, opacity: 0 }).appendTo('body');
            $t[0].select();
            $t[0].setSelectionRange(0, url.length);
            var ok = false;
            try { ok = document.execCommand('copy'); } catch (e) {}
            $t.remove();
            if (ok) { done(); } else { window.prompt('아래 링크를 복사하세요.', url); }
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(done, fallback);
        } else {
            fallback();
        }
    });

    $app.find('input[name="split-mode"]').on('change', function () {
        if (!this.checked) { return; }
        currentMode = this.value;
        applyModeText();
        recalc();
    });

    $('#split-add').on('click', function () {
        var checked = collect().holder;
        $people.append(rowHtml({ name: '', paid: 0, share: 1 }, rowSeq++));
        reindexHolder(checked);
        applyModeText();
        recalc();
    });

    $app.on('click', '.split-del', function () {
        if ($people.find('.split-row').length <= MIN_PEOPLE) { return; }
        var removeIdx = $people.find('.split-row').index($(this).closest('.split-row'));
        var checked = collect().holder;
        $(this).closest('.split-row').remove();
        // 보관자 인덱스 보정
        var next = checked === removeIdx ? -1 : (checked > removeIdx ? checked - 1 : checked);
        reindexHolder(next);
        recalc();
    });

    $('#split-reset').on('click', function () {
        var s = JSON.parse(JSON.stringify(DEFAULT_STATE));
        s.mode = currentMode; // 현재 모드는 유지
        render(s);
    });

    // 시작: 쿼리파라미터(d)로 복원
    var initial = null;
    try { initial = JSON.parse(new URLSearchParams(window.location.search).get('d') || 'null'); } catch (e) { initial = null; }
    if (!initial || !Array.isArray(initial.people)) { initial = JSON.parse(JSON.stringify(DEFAULT_STATE)); }
    // URL 로 2명 미만이 들어와도 최소 인원까지 채운다.
    while (initial.people.length < MIN_PEOPLE) { initial.people.push({ name: '', paid: 0, share: 1 }); }
    render(initial);
});
</script>
@endsection
