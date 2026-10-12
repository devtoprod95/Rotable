// Tabulator 포커스 시 브라우저 강제 스크롤(화면 점프) 방지 패치
(function() {
    const originalFocus = HTMLElement.prototype.focus;
    HTMLElement.prototype.focus = function(options) {
        if (this.classList && (this.classList.contains('tabulator') || this.closest('.tabulator'))) {
            options = options || {};
            options.preventScroll = true;
        }
        originalFocus.call(this, options);
    };
})();

$(document).ready(function() {
    const Tooltip = window.bootstrap?.Tooltip || window.tabler?.bootstrap?.Tooltip;

    if (!Tooltip) {
        return;
    }

    // 초기 렌더된 tooltip 요소 활성화
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        Tooltip.getOrCreateInstance(el);
    });

    // 동적으로 추가된 요소도 hover/focus 시점에 자동 활성화
    document.addEventListener('mouseover', function(e) {
        const target = e.target.closest('[data-bs-toggle="tooltip"]');
        if (!target) {
            return;
        }

        Tooltip.getOrCreateInstance(target);
    });

    document.addEventListener('focusin', function(e) {
        const target = e.target.closest('[data-bs-toggle="tooltip"]');
        if (!target) {
            return;
        }

        Tooltip.getOrCreateInstance(target);
    });

    // 모든 input[type="number"] 음수(-) 및 e, E 입력 및 붙여넣기 방지
    $(document).on('keydown', 'input[type="number"]', function(e) {
        if (e.key === '-' || e.key === 'Minus') {
            e.preventDefault();
        }
    });

    $(document).on('input', 'input[type="number"]', function() {
        if (this.value && parseFloat(this.value) < 0) {
            this.value = Math.abs(this.value);
        }
    });
});

window.salert = async function(options = {}) {
    const result = await Swal.fire({
        title: options.title || '',
        text: options.text || '',
        html: options.html || null, // HTML 속성 추가
        icon: options.icon || 'warning',
        showCancelButton: options.cancel !== false,
        reverseButtons: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '확인',
        cancelButtonText: '취소',
        allowEnterKey: true,
        allowEscapeKey: true,
        didOpen: () => {
            // swal 열려있는 동안 모달 닫힘 방지
            $('.modal').on('hide.bs.modal.swal', function(e) {
                if (Swal.isVisible()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
            // 키 이벤트 처리
            $(document).on('keydown.swal', function(e) {
                if (e.key === 'Escape') {
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    Swal.clickCancel();
                }
                if (e.key === 'Enter') {
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    Swal.clickConfirm();
                }
            });
        },
        willClose: () => {
            $(document).off('keydown.swal');
            $('.modal').off('hide.bs.modal.swal');
        },
        timer: options.timer || 0, // 후에 자동으로 닫힘
        timerProgressBar: options.timer || false // 진행률 바를 보여주어 남은 시간을 시각화
    });

    return result.isConfirmed;
};

document.addEventListener("DOMContentLoaded", function () {
    const startInput = document.querySelector('input[name="startDate"]');
    const endInput = document.querySelector('input[name="endDate"]');

    let startPicker;
    let endPicker;

    if (startInput && endInput && typeof flatpickr !== 'undefined') {
        const config = {
            dateFormat: "Y-m-d",
            locale: "ko",
            altInput: true,
            altFormat: "Y년 m월 d일"
        };

        // 1. 시작일 초기화
        startPicker = flatpickr(startInput, {
            ...config,
            onChange: (selectedDates, dateStr, instance) => {
                if (endPicker) endPicker.set('minDate', dateStr);
                $(instance.element).trigger('change');
            },
            onReady: function(sd, ds, inst) {
                addControls(inst, () => startPicker, () => endPicker);
                // 초기 값 존재 시 종료일 minDate 설정
                if (ds) endPicker?.set('minDate', ds);
            }
        });

        // 2. 종료일 초기화
        endPicker = flatpickr(endInput, {
            ...config,
            onChange: (selectedDates, dateStr, instance) => {
                if (startPicker) startPicker.set('maxDate', dateStr);
                $(instance.element).trigger('change');
            },
            onReady: function(sd, ds, inst) {
                addControls(inst, () => startPicker, () => endPicker);
                // 초기 값 존재 시 시작일 maxDate 설정
                if (ds) startPicker?.set('maxDate', ds);
            }
        });

        setTimeout(() => {
            if (startInput.value) {
                endPicker.set('minDate', startInput.value);
            }
            if (endInput.value) {
                startPicker.set('maxDate', endInput.value);
            }
        }, 100);
    }

    const createdDateInput    = document.querySelector('input[name="createdDate"]');
    const createdDateEndInput = document.querySelector('input[name="createdDateEnd"]');

    let createdDatePicker;
    let createdDateEndPicker;

    if (createdDateInput && createdDateEndInput && typeof flatpickr !== 'undefined') {
        const config = {
            dateFormat: "Y-m-d",
            locale: "ko",
            altInput: true,
            altFormat: "Y년 m월 d일"
        };

        // 1. 등록일 시작 초기화
        createdDatePicker = flatpickr(createdDateInput, {
            ...config,
            onChange: (selectedDates, dateStr, instance) => {
                if (createdDateEndPicker) createdDateEndPicker.set('minDate', dateStr);
                $(instance.element).trigger('change');
            },
            onReady: function(sd, ds, inst) {
                addControls(inst, () => createdDatePicker, () => createdDateEndPicker);
                if (ds) createdDateEndPicker?.set('minDate', ds);
            }
        });

        // 2. 등록일 종료 초기화
        createdDateEndPicker = flatpickr(createdDateEndInput, {
            ...config,
            onChange: (selectedDates, dateStr, instance) => {
                if (createdDatePicker) createdDatePicker.set('maxDate', dateStr);
                $(instance.element).trigger('change');
            },
            onReady: function(sd, ds, inst) {
                addControls(inst, () => createdDatePicker, () => createdDateEndPicker);
                if (ds) createdDatePicker?.set('maxDate', ds);
            }
        });

        setTimeout(() => {
            if (createdDateInput.value) {
                createdDateEndPicker.set('minDate', createdDateInput.value);
            }
            if (createdDateEndInput.value) {
                createdDatePicker.set('maxDate', createdDateEndInput.value);
            }
        }, 100);
    }

    // --- Date Range Select Logic Start ---
    let isProgrammaticDateChange = false;

    // Helper to format Date to YYYY-MM-DD in local time zone
    const formatDateStr = (date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };

    // Calculate options and return active range matching input values
    const calculateRanges = (today) => {
        const todayStr = formatDateStr(today);
        const day = today.getDay(); // 0: Sun, 1: Mon, ..., 6: Sat
        const currentMondayDiff = (day === 0 ? -6 : 1 - day);

        const yesterday = new Date(today);
        yesterday.setDate(today.getDate() - 1);
        const yesterdayStr = formatDateStr(yesterday);

        const thisWeekMonday = new Date(today);
        thisWeekMonday.setDate(today.getDate() + currentMondayDiff);

        const last7 = new Date(today);
        last7.setDate(today.getDate() - 6);

        const lastWeekMonday = new Date(today);
        lastWeekMonday.setDate(today.getDate() + currentMondayDiff - 7);
        const lastWeekSunday = new Date(today);
        lastWeekSunday.setDate(today.getDate() + currentMondayDiff - 1);

        const last30 = new Date(today);
        last30.setDate(today.getDate() - 29);

        const thisMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);

        const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);

        return {
            'today': { start: todayStr, end: todayStr },
            'yesterday': { start: yesterdayStr, end: yesterdayStr },
            'this_week': { start: formatDateStr(thisWeekMonday), end: todayStr },
            'last_7_days': { start: formatDateStr(last7), end: todayStr },
            'last_week': { start: formatDateStr(lastWeekMonday), end: formatDateStr(lastWeekSunday) },
            'last_30_days': { start: formatDateStr(last30), end: todayStr },
            'this_month': { start: formatDateStr(thisMonthStart), end: todayStr },
            'last_month': { start: formatDateStr(lastMonthStart), end: formatDateStr(lastMonthEnd) }
        };
    };

    // Initialize all .date-range-select selectors on load
    $(document).find('.date-range-select').each(function() {
        const select = this;
        const startName = select.dataset.startName || 'startDate';
        const endName = select.dataset.endName || 'endDate';
        const form = select.closest('form') || document;
        const startEl = form.querySelector(`input[name="${startName}"]`);
        const endEl = form.querySelector(`input[name="${endName}"]`);

        if (!startEl || !endEl) return;

        const startVal = startEl.value;
        const endVal = endEl.value;

        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has(startName) && !urlParams.has(endName)) {
            select.value = 'last_7_days';
            return;
        }

        if (!startVal && !endVal) {
            select.value = 'custom';
            return;
        }

        const today = new Date();
        const ranges = calculateRanges(today);
        let matched = false;

        for (const [key, range] of Object.entries(ranges)) {
            if (startVal === range.start && endVal === range.end) {
                select.value = key;
                matched = true;
                break;
            }
        }

        if (!matched) {
            select.value = 'custom';
        }
    });

    // Handle change of .date-range-select selectors
    $(document).on('change', '.date-range-select', function() {
        const select = this;
        const val = select.value;
        const startName = select.dataset.startName || 'startDate';
        const endName = select.dataset.endName || 'endDate';
        const form = select.closest('form') || document;
        const startEl = form.querySelector(`input[name="${startName}"]`);
        const endEl = form.querySelector(`input[name="${endName}"]`);

        if (!startEl || !endEl) return;

        if (val === 'custom') {
            if (startEl._flatpickr) {
                startEl._flatpickr.clear();
            } else {
                startEl.value = '';
            }
            if (endEl._flatpickr) {
                endEl._flatpickr.clear();
            } else {
                endEl.value = '';
            }
            return;
        }

        const today = new Date();
        const ranges = calculateRanges(today);
        const selectedRange = ranges[val];

        if (selectedRange) {
            // triggerChange=false 로 onChange 이벤트 억제 → 'custom' 으로 리셋되는 순환 방지
            if (startEl._flatpickr) {
                startEl._flatpickr.setDate(selectedRange.start, false);
            } else {
                startEl.value = selectedRange.start;
            }

            if (endEl._flatpickr) {
                endEl._flatpickr.setDate(selectedRange.end, false);
            } else {
                endEl.value = selectedRange.end;
            }
        }
    });

    // Reset select to 'custom' if inputs are changed manually
    $(document).on('change', 'input[name="startDate"], input[name="endDate"], input[name="createdDate"], input[name="createdDateEnd"]', function() {
        if (isProgrammaticDateChange) return;
        const form = this.closest('form');
        if (form) {
            const select = form.querySelector('.date-range-select');
            if (select) {
                select.value = 'custom';
            }
        }
    });
    // --- Date Range Select Logic End ---
});

// 버튼 추가 로직 (이전과 동일)
function addControls(instance, getStart, getEnd) {
    const container = instance.calendarContainer;
    const today = new Date();

    const btnGroup = document.createElement("div");
    btnGroup.className = "d-flex gap-2 mt-2";

    const todayBtn = document.createElement("button");
    todayBtn.type = "button";
    todayBtn.className = "btn btn-sm btn-outline-primary w-100";
    todayBtn.innerText = "오늘";
    todayBtn.onclick = () => {
        if ((instance.config.minDate && today < instance.config.minDate) ||
            (instance.config.maxDate && today > instance.config.maxDate)) {
            alert("선택하신 기간 범위를 벗어납니다.");
        } else {
            instance.setDate(today);
        }
    };

    const resetBtn = document.createElement("button");
    resetBtn.type = "button";
    resetBtn.className = "btn btn-sm btn-outline-secondary w-100";
    resetBtn.innerText = "리셋";
    resetBtn.onclick = () => {
        instance.clear();
        getStart().set('maxDate', null);
        getEnd().set('minDate', null);
    };

    btnGroup.appendChild(todayBtn);
    btnGroup.appendChild(resetBtn);
    container.appendChild(btnGroup);
}

/**
 * 검색 필터 접기/펼치기 자동 초기화
 * - .search-filter-body 가 존재하는 페이지에서 자동 실행됩니다.
 * - card-header에 접기 버튼을 자동 삽입하고, localStorage로 상태를 유지합니다.
 * - 페이지 URL 경로를 기반으로 localStorage 키를 자동 생성합니다.
 *
 * HTML 요구사항:
 *   - card-body에 .search-filter-body 클래스만 부여하면 됩니다.
 */
$(document).ready(function() {
    const $body = $('.search-filter-body');
    if ($body.length === 0) return;

    // 모든 페이지에서 공유되는 단일 키 사용
    const storageKey = 'search_filter_collapsed';
    const isCollapsed = localStorage.getItem(storageKey) === 'true';

    // card-header에 접기 버튼 자동 삽입
    const $header = $body.closest('.card').find('.card-header .card-title');
    const btnHtml = `<button type="button" class="btn btn-sm btn-icon btn-outline-secondary rounded-circle ms-1 btn-collapse-filter" title="접기/펼치기" style="width: 26px; height: 26px; padding: 0;">
        <i class="ti ti-chevron-up icon-filter-toggle" style="font-size: 16px; line-height: 1;"></i>
    </button>`;

    $header.addClass('d-flex align-items-center gap-2').append(btnHtml);

    const $icon = $header.find('.icon-filter-toggle');

    // 초기 상태 적용
    if (isCollapsed) {
        $body.hide();
        $icon.removeClass('ti-chevron-up').addClass('ti-chevron-down');
    }

    // 클릭 핸들러
    $header.find('.btn-collapse-filter').click(function() {
        $body.slideToggle(300, function() {
            const collapsed = !$body.is(':visible');
            localStorage.setItem(storageKey, collapsed);
            if (collapsed) {
                $icon.removeClass('ti-chevron-up').addClass('ti-chevron-down');
            } else {
                $icon.removeClass('ti-chevron-down').addClass('ti-chevron-up');
            }
        });
    });
});

function getFormParams(formId) {
    var params = {};
    $(formId).find(":input").each(function() {
        var name = $(this).attr("name");
        if (!name) return;

        if ($(this).is(":checkbox")) {
            var key = name.replace("[]", "");
            if (!params[key]) params[key] = [];
            if ($(this).is(":checked")) params[key].push($(this).val());

        } else if ($(this).is(":radio")) {
            if ($(this).is(":checked")) params[name] = $(this).val();  // 체크된 것만

        } else {
            params[name] = $(this).val();
        }
    });

    return params;
}

// Tabulator 전역 기본값 설정
const tabulatorSettings = {
    // 페이지네이션
    sortMode: "remote",
    pagination: true,
    paginationMode: "remote",
    paginationSizeSelector: [30, 50, 100, 200],
    paginationSize: 30,
    paginationButtonCount: 10,
    paginationCounter: function(pageSize, currentRow, currentPage, totalRows, totalPages) {
        var total = window._tableTotalCount || totalRows;
        var pages = Math.ceil(total / pageSize);
        let text = `전체 <strong>${total}</strong>건 (<strong>${currentPage}</strong> / <strong>${pages}</strong> 페이지)`;
        let cleanText = text.replace(/<[^>]*>?/gm, '');
        $('.page-count').text(cleanText);
        return text;
    },

    // 레이아웃
    layout: "fitColumns",
    renderVertical: "virtual",
    placeholder: "데이터가 없습니다.",
    height: "600px",

    // 로케일
    locale: "ko",
    initialLocale: "ko",
    langs: {
        "ko": {
            "pagination": {
                "first": "처음", "last": "마지막", "prev": "이전", "next": "다음",
                "first_title": "첫 페이지", "last_title": "마지막 페이지", "prev_title": "이전 페이지", "next_title": "다음 페이지",
                "page_size": "페이지당 표시",
            }
        }
    },

    // 선택 / 범위
    selectableRows: undefined,
    fillHandle: "table",
    selectableRange: 1,
    selectableRangeColumns:true,
    selectableRangeRows:true,
    headerSortClickElement: "icon",

    // 클립보드
    clipboard: true,
    clipboardCopyStyled: false,
    clipboardCopyRowRange: "range",
    clipboardCopyConfig: {
        columnHeaders: false,
    },

    // 컬럼 기본값
    columnDefaults: {
        hozAlign: "center",
        headerHozAlign: "center",
        headerSort: false,
    },

    ajaxParams: function() {
        return getFormParams("#searchForm");
    },
    ajaxResponse: function(url, params, response) {
        const urlObj = new URL(window.location);
        urlObj.hash = ""; // 해시로 인한 자동 스크롤(점프) 방지

        // 중첩된 객체/배열을 평탄화하여 URL 파라미터로 변환하는 함수
        const flattenParams = (obj, prefix = '') => {
            Object.keys(obj).forEach(key => {
                const value = obj[key];
                const newKey = prefix ? `${prefix}[${key}]` : key;

                if (value !== null && typeof value === 'object') {
                    flattenParams(value, newKey); // 재귀 호출
                } else if (value !== undefined && value !== "" && value !== null) {
                    urlObj.searchParams.set(newKey, value);
                }
            });
        };

        // 기존의 모든 파라미터 초기화 후 재설정 (선택 사항)
        urlObj.search = "";

        // 파라미터 평탄화 실행
        flattenParams(params);

        window.history.replaceState({}, '', urlObj);
        window._tableTotalCount = response.total;
        return response;
    },
};
if (typeof Tabulator !== 'undefined') {
    Object.assign(Tabulator.defaultOptions, tabulatorSettings);
}

window.tabulatorHeaderMenu = function() {
    var menu = [];
    // 1. 전체 컬럼 중 headerMenu 설정이 있는 컬럼만 필터링
    var columns = this.getColumns().filter(col => col.getDefinition().headerMenu !== undefined);

    for (let column of columns) {
        // Tabler 아이콘 (ti-checkbox, ti-square)
        const iconCheck = '<i class="ti ti-checkbox"></i>';
        const iconUncheck = '<i class="ti ti-square"></i>';

        let label = document.createElement("span");
        label.classList.add("d-flex", "align-items-center", "gap-2");

        label.innerHTML = (column.isVisible() ? iconCheck : iconUncheck) +
                        `<span> ${column.getDefinition().title}</span>`;

        menu.push({
            label: label,
            action: function(e) {
                e.stopPropagation();
                column.toggle();

                let icon = label.querySelector("i");
                if (column.isVisible()) {
                    icon.className = "ti ti-checkbox";
                } else {
                    icon.className = "ti ti-square";
                }
            }
        });
    }

    return menu;
};

/**
 * 비디오 FPS 반환
 */
function detectFps(videoEl) {
    return new Promise((resolve) => {
        if (typeof videoEl.requestVideoFrameCallback !== 'function') {
            resolve(null);
            return;
        }

        let frameCount = 0;
        let startTime  = null;

        function measureFps(now) {
            if (startTime === null) startTime = now;

            frameCount++;
            const elapsed = (now - startTime) / 1000;

            if (elapsed >= 1.0) {
                resolve(Math.round(frameCount / elapsed));
                return;
            }

            videoEl.requestVideoFrameCallback(measureFps);
        }

        videoEl.requestVideoFrameCallback(measureFps);
        videoEl.play();
    });
}

function formatNumber(value) {
    var num = value.replace(/[^0-9]/g, '');
    return num ? Number(num).toLocaleString() : '';
}

function getNumberOnly(value) {
    var num = value.replace(/[^0-9]/g, '');
    return num ? parseInt(num, 10) : 0;
}
