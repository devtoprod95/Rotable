@extends('layouts.app')

@section('title', '손익분기 계산기')

@section('content')
<div class="row row-cards mt-2" id="bep-app"
     data-list-url="{{ route('admin.rotation.bep.list') }}"
     data-store-url="{{ route('admin.rotation.bep.store') }}"
     data-delete-url="{{ route('admin.rotation.bep.delete') }}">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h3 class="card-title mb-0">
                    <i class="ti ti-calculator text-green me-1"></i> 손익분기 계산기 (BEP)
                    <span class="text-muted fs-5 fw-normal ms-1">— 1인당 얼마를 받아야 흑자로 전환되는지 계산합니다.</span>
                </h3>
            </div>

            <div class="card-body">
                {{-- 상단: 타입 토글 + 저장/불러오기 --}}
                <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                    <span class="text-muted me-1">행사 타입</span>
                    <div class="btn-group" role="group" aria-label="행사 타입 선택">
                        <input type="radio" class="btn-check" name="bep-type" id="bep-type-wine" value="wine" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="bep-type-wine"><i class="ti ti-glass-full me-1"></i> 와인파티</label>

                        <input type="radio" class="btn-check" name="bep-type" id="bep-type-one" value="one" autocomplete="off">
                        <label class="btn btn-outline-primary" for="bep-type-one"><i class="ti ti-users me-1"></i> 1:1 소개팅</label>
                    </div>

                    <div class="ms-auto d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="bep-reset">
                            <i class="ti ti-refresh me-1"></i> 기본값
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="bep-open-load">
                            <i class="ti ti-folder-open me-1"></i> 불러오기
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" id="bep-open-save">
                            <i class="ti ti-device-floppy me-1"></i> 저장
                        </button>
                    </div>
                </div>

                <div class="alert alert-info py-2" id="bep-type-hint"></div>
                <div class="text-muted small mb-3" id="bep-loaded-info" style="display:none;"></div>

                <div class="row g-3">
                    {{-- ===== 좌측: 입력 ===== --}}
                    <div class="col-lg-7">
                        {{-- 참가 인원 --}}
                        <div class="card card-sm mb-3">
                            <div class="card-body">
                                <div class="fw-bold mb-2"><i class="ti ti-user me-1 text-blue"></i> 참가 인원</div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">남성 인원</label>
                                        <div class="input-group input-group-flat">
                                            <input type="number" min="0" step="1" class="form-control" id="bep-men" value="15">
                                            <span class="input-group-text">명</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">여성 인원</label>
                                        <div class="input-group input-group-flat">
                                            <input type="number" min="0" step="1" class="form-control" id="bep-women" value="15">
                                            <span class="input-group-text">명</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="fw-bold mt-3 mb-2 small text-muted">
                                    <i class="ti ti-gift me-1 text-pink"></i> 무료 초대 인원 <span class="fw-normal">(참가비를 받지 않는 인원 — 비용엔 포함, 수입엔 제외)</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">무료 남성</label>
                                        <div class="input-group input-group-flat">
                                            <input type="number" min="0" step="1" class="form-control" id="bep-free-men" value="0">
                                            <span class="input-group-text">명</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">무료 여성</label>
                                        <div class="input-group input-group-flat">
                                            <input type="number" min="0" step="1" class="form-control" id="bep-free-women" value="0">
                                            <span class="input-group-text">명</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2 text-muted small">
                                    총 <b id="bep-total-head">40</b>명
                                    = 유료 <b class="text-green" id="bep-paid-head">40</b>명
                                    + 무료 <b class="text-pink" id="bep-free-head">0</b>명
                                </div>
                            </div>
                        </div>

                        {{-- 성비별 참가비 비율 --}}
                        <div class="card card-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="fw-bold"><i class="ti ti-adjustments me-1 text-purple"></i> 성별 참가비 비율</div>
                                    <label class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="bep-ratio-toggle" checked>
                                        <span class="form-check-label small text-muted">성별 다른 참가비 적용</span>
                                    </label>
                                </div>
                                <div class="row g-2" id="bep-ratio-box">
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">남성 가중치</label>
                                        <div class="input-group input-group-flat">
                                            <input type="number" min="0" step="0.05" class="form-control" id="bep-men-weight" value="1">
                                            <span class="input-group-text">배</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">여성 가중치</label>
                                        <div class="input-group input-group-flat">
                                            <input type="number" min="0" step="0.05" class="form-control" id="bep-women-weight" value="1">
                                            <span class="input-group-text">배</span>
                                        </div>
                                    </div>
                                    <div class="col-12 text-muted small">
                                        기준 참가비(가중치 1) 대비 비율입니다. 예) 남 1 / 여 0.7 → 여성은 남성의 70%만 받음.
                                    </div>
                                </div>
                                <div class="text-muted small" id="bep-ratio-off-hint" style="display:none;">꺼져 있으면 남녀 모두 동일한 참가비로 계산합니다.</div>
                            </div>
                        </div>

                        {{-- 비용 항목 (통합) --}}
                        <div class="card card-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="fw-bold"><i class="ti ti-cash-banknote me-1 text-orange"></i> 비용 항목
                                        <span class="text-muted small fw-normal">(공간 대여비·알바비·와인·안주 등 지출을 전부 여기서 관리)</span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary bep-add" data-group="cost"><i class="ti ti-plus"></i> 항목 추가</button>
                                </div>
                                <div data-list="cost"></div>
                            </div>
                        </div>

                        {{-- 기타 수입 --}}
                        <div class="card card-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="fw-bold"><i class="ti ti-coin me-1 text-green"></i> 기타 수입 <span class="text-muted small fw-normal">(참가비 외 수입 — 협찬·부스 등. 비용에서 차감)</span></div>
                                    <button type="button" class="btn btn-sm btn-outline-primary bep-add" data-group="revenue"><i class="ti ti-plus"></i> 항목 추가</button>
                                </div>
                                <div data-list="revenue"></div>
                            </div>
                        </div>

                        {{-- 목표 이익 --}}
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="fw-bold mb-2"><i class="ti ti-target-arrow me-1 text-red"></i> 목표 이익(선택)</div>
                                <div class="row g-2 align-items-end">
                                    <div class="col-7">
                                        <label class="form-label small text-muted mb-1">이 행사에서 남기고 싶은 순이익</label>
                                        <div class="input-group input-group-flat">
                                            <input type="text" inputmode="numeric" class="form-control bep-money text-end" id="bep-target-profit" value="0">
                                            <span class="input-group-text">원</span>
                                        </div>
                                    </div>
                                    <div class="col-5 text-muted small">
                                        0이면 순수 손익분기(본전) 가격을, 값이 있으면 그 이익까지 남기는 가격을 계산합니다.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ===== 우측: 결과 ===== --}}
                    <div class="col-lg-5">
                        <div class="card card-sm position-lg-sticky" style="top: 1rem;">
                            <div class="card-status-top bg-green"></div>
                            <div class="card-body">
                                <div class="fw-bold mb-3"><i class="ti ti-report-money me-1 text-green"></i> 계산 결과</div>

                                {{-- 핵심: 1인당 손익분기 참가비 --}}
                                <div class="text-center p-3 mb-3 rounded bg-green-lt">
                                    <div class="text-muted small mb-1">흑자전환 1인당 참가비 (기준가)</div>
                                    <div class="display-6 fw-bold text-green" id="bep-price">0 원</div>
                                    <div class="text-muted small mt-1" id="bep-price-sub"></div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm table-vcenter mb-0">
                                        <tbody>
                                            <tr class="fw-bold">
                                                <td>총비용</td>
                                                <td class="text-end" id="bep-sum-cost">0 원</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">(-) 기타 수입</td>
                                                <td class="text-end text-green" id="bep-sum-revenue">0 원</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">목표 이익</td>
                                                <td class="text-end" id="bep-sum-target">0 원</td>
                                            </tr>
                                            <tr class="fw-bold border-top">
                                                <td>참가비로 충당할 금액</td>
                                                <td class="text-end" id="bep-sum-need">0 원</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <hr class="my-3">

                                {{-- 성별 적용 참가비 --}}
                                <div class="mb-2 fw-bold small text-muted">성별 적용 참가비 (가중치 반영)</div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="p-2 rounded bg-blue-lt text-center">
                                            <div class="small text-muted">남성 1인당</div>
                                            <div class="fw-bold text-blue" id="bep-price-men">0 원</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 rounded bg-pink-lt text-center">
                                            <div class="small text-muted">여성 1인당</div>
                                            <div class="fw-bold text-pink" id="bep-price-women">0 원</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- 역산: 실제 책정가 입력 시 손익 (남/여 가격 별도) --}}
                                <div class="mb-2 fw-bold small text-muted"><i class="ti ti-arrows-exchange me-1"></i> 실제 책정가로 손익 확인</div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">남성 참가비</label>
                                        <div class="input-group input-group-sm input-group-flat">
                                            <input type="text" inputmode="numeric" class="form-control bep-money text-end" id="bep-actual-men" placeholder="남성가">
                                            <span class="input-group-text">원</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted mb-1">여성 참가비</label>
                                        <div class="input-group input-group-sm input-group-flat">
                                            <input type="text" inputmode="numeric" class="form-control bep-money text-end" id="bep-actual-women" placeholder="여성가">
                                            <span class="input-group-text">원</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-2 rounded text-center" id="bep-actual-box" style="display:none;">
                                    <div class="small text-muted">예상 손익</div>
                                    <div class="h3 mb-0" id="bep-actual-profit">0 원</div>
                                    <div class="small" id="bep-actual-msg"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== 저장 모달 ===== --}}
<div class="modal modal-blur fade" id="bepSaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-device-floppy me-1"></i> 견적 저장</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="닫기"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="bep-save-id">
                <label class="form-label">저장 이름</label>
                <input type="text" class="form-control" id="bep-save-name" placeholder="예) 11월 와인파티 견적" maxlength="255">
                <div class="text-muted small mt-2">현재 입력한 모든 값(인원·비용·수입·목표이익·타입)이 함께 저장됩니다.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="bep-save-confirm">저장</button>
            </div>
        </div>
    </div>
</div>

{{-- ===== 불러오기 모달 ===== --}}
<div class="modal modal-blur fade" id="bepLoadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-folder-open me-1"></i> 저장된 견적 불러오기</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="닫기"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-vcenter">
                        <thead>
                            <tr>
                                <th>이름</th>
                                <th class="w-1">타입</th>
                                <th class="w-1">저장일</th>
                                <th class="w-1 text-end">관리</th>
                            </tr>
                        </thead>
                        <tbody id="bep-load-list">
                            <tr><td colspan="4" class="text-center text-muted py-4">불러오는 중…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    'use strict';

    // 타입별 기본 프리셋 (단위: 원). 인력/변동비 구분 없이 '비용 항목' 하나로 관리.
    var PRESETS = {
        wine: {
            hint: '🍷 <b>와인파티</b> 기본값입니다. 공간 대여·음향, 와인/안주, 알바비 등 모든 지출을 아래 <b>비용 항목</b> 하나에서 관리합니다. 실제 금액에 맞게 수정하세요.',
            cost: [
                { label: '공간 대여비', amount: 500000 },
                { label: '와인/음료', amount: 50000 },
                { label: '안주/다과', amount: 50000 },
                { label: '현장 알바비', amount: 0 },
                { label: '음향/세팅/장식', amount: 0 }
            ],
            revenue: [ { label: '주류 협찬', amount: 0 } ]
        },
        one: {
            hint: '💑 <b>1:1 소개팅</b> 기본값입니다. 공간 대여, 음료/다과, 진행 스태프 비용 등을 아래 <b>비용 항목</b> 하나에서 관리합니다. 실제 금액에 맞게 수정하세요.',
            cost: [
                { label: '공간 대여비', amount: 300000 },
                { label: '음료/다과', amount: 200000 },
                { label: '진행 스태프비', amount: 80000 }
            ],
            revenue: []
        }
    };

    var $app = $('#bep-app');
    var listUrl   = $app.data('list-url');
    var storeUrl  = $app.data('store-url');
    var deleteUrl = $app.data('delete-url');

    var currentType = 'wine';
    var loadedName  = null; // 현재 불러온 견적 이름 (표시용)

    function won(n) { return (Math.round(n || 0)).toLocaleString('ko-KR') + ' 원'; }
    // 콤마가 섞인 문자열도 숫자로 안전하게 읽는다.
    function num(v) { var n = parseFloat(String(v == null ? '' : v).replace(/,/g, '')); return isFinite(n) ? n : 0; }
    // 금액을 천 단위 콤마 문자열로 (빈 값은 빈 문자열 유지).
    function fmt(v) {
        if (v === '' || v == null) { return ''; }
        return num(v).toLocaleString('ko-KR');
    }
    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }

    // ---- 비용/수입 행 생성 (라벨 + 금액) ----
    function rowHtml(group, item) {
        item = item || {};
        return '' +
        '<div class="row g-1 align-items-center mb-2 bep-row" data-group="' + group + '">' +
            '<div class="col-6 col-md-7">' +
                '<input type="text" class="form-control form-control-sm bep-label" placeholder="항목명" value="' + esc(item.label) + '">' +
            '</div>' +
            '<div class="col-5 col-md-4">' +
                '<div class="input-group input-group-sm input-group-flat">' +
                    '<input type="text" inputmode="numeric" class="form-control bep-amount bep-money text-end" placeholder="금액" value="' + fmt(item.amount != null ? item.amount : 0) + '">' +
                    '<span class="input-group-text">원</span>' +
                '</div>' +
            '</div>' +
            '<div class="col-1 d-flex align-items-center">' +
                '<button type="button" class="btn btn-sm btn-ghost-danger bep-del" title="삭제"><i class="ti ti-trash"></i></button>' +
            '</div>' +
        '</div>';
    }

    function $list(group) { return $app.find('[data-list="' + group + '"]'); }

    function addRow(group, item) {
        $list(group).append(rowHtml(group, item));
        recalc();
    }

    function sumAmount(group) {
        var total = 0;
        $list(group).find('.bep-row').each(function () {
            total += num($(this).find('.bep-amount').val());
        });
        return total;
    }

    // ---- 현재 입력값을 payload 로 직렬화 ----
    function collect() {
        function items(group) {
            var arr = [];
            $list(group).find('.bep-row').each(function () {
                arr.push({
                    label: $(this).find('.bep-label').val() || '',
                    amount: num($(this).find('.bep-amount').val())
                });
            });
            return arr;
        }
        return {
            type: currentType,
            men: num($('#bep-men').val()),
            women: num($('#bep-women').val()),
            freeMen: num($('#bep-free-men').val()),
            freeWomen: num($('#bep-free-women').val()),
            ratioOn: $('#bep-ratio-toggle').is(':checked'),
            menWeight: num($('#bep-men-weight').val()),
            womenWeight: num($('#bep-women-weight').val()),
            targetProfit: num($('#bep-target-profit').val()),
            cost: items('cost'),
            revenue: items('revenue')
        };
    }

    // ---- payload 로 화면 복원 ----
    function restore(p) {
        if (!p) { return; }
        currentType = (p.type === 'one') ? 'one' : 'wine';
        $('#bep-type-' + (currentType === 'one' ? 'one' : 'wine')).prop('checked', true);
        $('#bep-type-hint').html(PRESETS[currentType].hint);

        $('#bep-men').val(p.men != null ? p.men : 0);
        $('#bep-women').val(p.women != null ? p.women : 0);
        $('#bep-free-men').val(p.freeMen != null ? p.freeMen : 0);
        $('#bep-free-women').val(p.freeWomen != null ? p.freeWomen : 0);
        $('#bep-target-profit').val(fmt(p.targetProfit != null ? p.targetProfit : 0));

        var ratioOn = !!p.ratioOn;
        $('#bep-ratio-toggle').prop('checked', ratioOn);
        $('#bep-ratio-box').toggle(ratioOn);
        $('#bep-ratio-off-hint').toggle(!ratioOn);
        $('#bep-men-weight').val(p.menWeight != null ? p.menWeight : 1);
        $('#bep-women-weight').val(p.womenWeight != null ? p.womenWeight : 1);

        ['cost', 'revenue'].forEach(function (g) {
            $list(g).empty();
            (p[g] || []).forEach(function (item) { addRow(g, item); });
        });
        recalc();
    }

    function loadPreset(type) {
        currentType = type;
        loadedName = null;
        $('#bep-loaded-info').hide();
        $('#bep-type-hint').html(PRESETS[type].hint);
        ['cost', 'revenue'].forEach(function (g) {
            $list(g).empty();
            PRESETS[type][g].forEach(function (item) { addRow(g, item); });
        });
        recalc();
    }

    // ---- 계산 ----
    function recalc() {
        var men   = num($('#bep-men').val());
        var women = num($('#bep-women').val());
        var head  = men + women;

        // 무료 초대 인원(성별). 유료 인원 = 전체 - 무료 (음수 방지).
        var freeMen   = Math.min(num($('#bep-free-men').val()),   men);
        var freeWomen = Math.min(num($('#bep-free-women').val()), women);
        var paidMen   = Math.max(men   - freeMen,   0);
        var paidWomen = Math.max(women - freeWomen, 0);
        var freeHead  = freeMen + freeWomen;
        var paidHead  = paidMen + paidWomen;

        var ratioOn = $('#bep-ratio-toggle').is(':checked');
        var wMen   = ratioOn ? num($('#bep-men-weight').val())   : 1;
        var wWomen = ratioOn ? num($('#bep-women-weight').val()) : 1;

        var totalCost = sumAmount('cost');
        var revenue   = sumAmount('revenue');
        var target    = num($('#bep-target-profit').val());

        var need = totalCost - revenue + target;
        // 참가비를 내는(유료) 인원만 가중 집계한다.
        var weightedHead = paidMen * wMen + paidWomen * wWomen;
        var basePrice = weightedHead > 0 ? need / weightedHead : 0;

        $('#bep-total-head').text(head.toLocaleString('ko-KR'));
        $('#bep-paid-head').text(paidHead.toLocaleString('ko-KR'));
        $('#bep-free-head').text(freeHead.toLocaleString('ko-KR'));
        $('#bep-sum-cost').text(won(totalCost));
        $('#bep-sum-revenue').text('- ' + won(revenue));
        $('#bep-sum-target').text(won(target));
        $('#bep-sum-need').text(won(need));

        $('#bep-price').text(won(basePrice));
        $('#bep-price-sub').text(paidHead > 0
            ? ('유료 ' + paidHead.toLocaleString('ko-KR') + '명이 부담' + (freeHead > 0 ? (' · 무료 ' + freeHead.toLocaleString('ko-KR') + '명 제외') : ''))
            : '유료 인원이 없습니다');
        $('#bep-price-men').text(won(basePrice * wMen));
        $('#bep-price-women').text(won(basePrice * wWomen));

        // 역산: 남/여 참가비를 따로 받아, 유료 인원 수만큼 곱해 수입을 구한다.
        var menRaw   = $('#bep-actual-men').val();
        var womenRaw = $('#bep-actual-women').val();
        if ((menRaw !== '' && menRaw != null) || (womenRaw !== '' && womenRaw != null)) {
            var priceMenActual   = num(menRaw);
            var priceWomenActual = num(womenRaw);
            var feeRevenue = priceMenActual * paidMen + priceWomenActual * paidWomen;
            var profit = feeRevenue + revenue - totalCost;
            var $box = $('#bep-actual-box').show();
            var $p = $('#bep-actual-profit');
            var $m = $('#bep-actual-msg');
            $p.text((profit >= 0 ? '+' : '') + won(profit));
            var detail = '유료 남 ' + paidMen + '명×' + won(priceMenActual) + ' + 여 ' + paidWomen + '명×' + won(priceWomenActual);
            if (profit > 0) {
                $box.attr('class', 'p-2 rounded text-center bg-green-lt');
                $p.attr('class', 'h3 mb-0 text-green'); $m.attr('class', 'small text-green').text('흑자입니다 👍 (' + detail + ')');
            } else if (profit < 0) {
                $box.attr('class', 'p-2 rounded text-center bg-red-lt');
                $p.attr('class', 'h3 mb-0 text-red'); $m.attr('class', 'small text-red').text('적자입니다. 참가비를 올리거나 비용을 줄이세요.');
            } else {
                $box.attr('class', 'p-2 rounded text-center bg-azure-lt');
                $p.attr('class', 'h3 mb-0'); $m.attr('class', 'small text-muted').text('정확히 본전(손익분기)입니다.');
            }
        } else {
            $('#bep-actual-box').hide();
        }
    }

    // ---- 모달 헬퍼 ----
    function getModal(id) {
        var el = document.getElementById(id);
        var ModalClass =
            (window.tabler && window.tabler.Modal) ||
            (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) ||
            (window.bootstrap && window.bootstrap.Modal) ||
            (window.coreui && window.coreui.Modal) ||
            window.Modal;
        if (!ModalClass) { console.error('Modal 클래스를 찾을 수 없습니다.'); return null; }
        return ModalClass.getOrCreateInstance ? ModalClass.getOrCreateInstance(el) : new ModalClass(el);
    }

    // ---- 이벤트 바인딩 ----
    // 금액 입력란: 입력하는 즉시 천 단위 콤마를 붙인다. (커서 위치 보정)
    $app.on('input', '.bep-money', function () {
        var el = this;
        var raw = el.value;
        // 숫자만 남긴다. (콤마·기타 문자 제거)
        var digits = raw.replace(/[^\d]/g, '');
        if (digits === '') { el.value = ''; return; }
        // 커서 왼쪽의 숫자 개수를 세어, 포맷 후 커서를 같은 자리에 되돌린다.
        var caret = el.selectionStart || 0;
        var digitsLeft = raw.slice(0, caret).replace(/[^\d]/g, '').length;
        var formatted = parseInt(digits, 10).toLocaleString('ko-KR');
        el.value = formatted;
        // 포맷 결과에서 digitsLeft 번째 숫자 뒤로 커서 이동
        var pos = 0, seen = 0;
        while (pos < formatted.length && seen < digitsLeft) {
            if (/\d/.test(formatted[pos])) { seen++; }
            pos++;
        }
        try { el.setSelectionRange(pos, pos); } catch (e) {}
    });

    $app.on('input', 'input', recalc);
    $app.on('click', '.bep-add', function () { addRow($(this).data('group')); });
    $app.on('click', '.bep-del', function () { $(this).closest('.bep-row').remove(); recalc(); });

    $app.find('input[name="bep-type"]').on('change', function () {
        if (this.checked) { loadPreset(this.value); }
    });
    $('#bep-reset').on('click', function () { loadPreset(currentType); });

    $('#bep-ratio-toggle').on('change', function () {
        $('#bep-ratio-box').toggle(this.checked);
        $('#bep-ratio-off-hint').toggle(!this.checked);
        recalc();
    });

    // ---- 저장 ----
    $('#bep-open-save').on('click', function () {
        $('#bep-save-id').val('');
        $('#bep-save-name').val(loadedName || '');
        var m = getModal('bepSaveModal'); if (m) { m.show(); }
    });

    $('#bep-save-confirm').on('click', function () {
        var name = ($('#bep-save-name').val() || '').trim();
        if (!name) { salert({ text: '저장 이름을 입력해주세요.', cancel: false }); return; }

        $.ajax({
            url: storeUrl,
            type: 'POST',
            data: { id: $('#bep-save-id').val(), name: name, type: currentType, payload: collect() },
            success: function (res) {
                var m = getModal('bepSaveModal'); if (m) { m.hide(); }
                loadedName = name;
                $('#bep-loaded-info').html('<i class="ti ti-bookmark me-1"></i> 현재 견적: <b>' + esc(name) + '</b>').show();
                salert({ text: res && res.msg ? res.msg : '저장되었습니다.', cancel: false });
            },
            error: function (xhr) {
                salert({ text: (xhr && xhr.responseJSON && xhr.responseJSON.error && xhr.responseJSON.error.message) || '오류가 발생했습니다.', cancel: false });
            }
        });
    });

    // ---- 불러오기 ----
    var loadedCache = {}; // id -> payload

    function renderLoadList() {
        var $tbody = $('#bep-load-list');
        $tbody.html('<tr><td colspan="4" class="text-center text-muted py-4">불러오는 중…</td></tr>');
        $.ajax({
            url: listUrl,
            type: 'GET',
            success: function (res) {
                var rows = (res && res.data) || [];
                loadedCache = {};
                if (!rows.length) {
                    $tbody.html('<tr><td colspan="4" class="text-center text-muted py-4">저장된 견적이 없습니다.</td></tr>');
                    return;
                }
                var html = '';
                rows.forEach(function (r) {
                    loadedCache[r.id] = r.payload;
                    var typeLabel = r.type === 'one'
                        ? '<span class="badge bg-azure-lt">1:1</span>'
                        : '<span class="badge bg-purple-lt">와인</span>';
                    html += '' +
                    '<tr data-id="' + r.id + '" data-name="' + esc(r.name) + '">' +
                        '<td class="fw-bold">' + esc(r.name) + '</td>' +
                        '<td>' + typeLabel + '</td>' +
                        '<td class="text-muted small text-nowrap">' + esc(r.updated_at || '') + '</td>' +
                        '<td class="text-end text-nowrap">' +
                            '<button type="button" class="btn btn-sm btn-primary bep-load-apply">불러오기</button> ' +
                            '<button type="button" class="btn btn-sm btn-ghost-danger bep-load-del" title="삭제"><i class="ti ti-trash"></i></button>' +
                        '</td>' +
                    '</tr>';
                });
                $tbody.html(html);
            },
            error: function (xhr) {
                $tbody.html('<tr><td colspan="4" class="text-center text-danger py-4">목록을 불러오지 못했습니다.</td></tr>');
            }
        });
    }

    $('#bep-open-load').on('click', function () {
        renderLoadList();
        var m = getModal('bepLoadModal'); if (m) { m.show(); }
    });

    $('#bep-load-list').on('click', '.bep-load-apply', function () {
        var $tr = $(this).closest('tr');
        var id = $tr.data('id');
        var name = $tr.data('name');
        var payload = loadedCache[id];
        restore(payload);
        loadedName = name;
        $('#bep-save-id').val(id);
        $('#bep-loaded-info').html('<i class="ti ti-bookmark me-1"></i> 현재 견적: <b>' + esc(name) + '</b>').show();
        var m = getModal('bepLoadModal'); if (m) { m.hide(); }
    });

    $('#bep-load-list').on('click', '.bep-load-del', async function () {
        var $tr = $(this).closest('tr');
        var id = $tr.data('id');
        var name = $tr.data('name');
        if (!(await salert({ text: '"' + name + '" 견적을 삭제하시겠습니까?' }))) { return; }

        $.ajax({
            url: deleteUrl,
            type: 'DELETE',
            data: { ids: [id] },
            success: function (res) {
                renderLoadList();
            },
            error: function (xhr) {
                salert({ text: (xhr && xhr.responseJSON && xhr.responseJSON.error && xhr.responseJSON.error.message) || '오류가 발생했습니다.', cancel: false });
            }
        });
    });

    // 시작
    loadPreset('wine');
});
</script>
@endsection
