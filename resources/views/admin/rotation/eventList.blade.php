@extends('layouts.app')

@section('title', '로테이션 행사 관리')

@section('content')
<style>
    /* 방문 통계 안내 툴팁: 설명이 길어 넓게 + 밝고 읽기 편한 색상으로 표시 */
    .visit-stat-tooltip {
        opacity: 1 !important;
    }
    .visit-stat-tooltip .tooltip-inner {
        max-width: 440px;
        text-align: left;
        line-height: 1.6;
        padding: 12px 14px;
        font-size: 0.9rem;
        background-color: #374151;   /* gray-700: 차분한 다크 그레이 */
        color: #f9fafb;              /* 거의 흰색 글씨 */
        border-radius: 8px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }
    .visit-stat-tooltip .tooltip-arrow::before {
        border-right-color: #374151;
        border-left-color: #374151;
        border-top-color: #374151;
        border-bottom-color: #374151;
    }
</style>
<div class="row row-cards mt-2">

    {{-- 방문 통계 (매일 자정 스냅샷) --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h3 class="card-title mb-0 d-flex align-items-center">
                    <i class="ti ti-chart-bar me-1"></i> 방문 통계
                    <i class="ti ti-info-circle text-blue ms-2"
                       id="visitStatNotice"
                       style="cursor: help; font-size: 1.4rem; line-height: 1;"
                       data-bs-toggle="tooltip"
                       data-bs-placement="right"
                       data-bs-html="true"
                       data-bs-custom-class="visit-stat-tooltip"
                       title="‘총 방문수’는 방문 횟수, ‘1~6번 방문’ 칸은 사람 수 기준이에요.<br>그래서 같은 사람이 여러 번 방문하면 두 숫자가 다를 수 있습니다.<br>예) 혼자 3번 온 사람 1명 → 사람은 1명, 방문수는 3회로 집계"></i>
                </h3>
                @if ($visitSnapshot)
                    <span class="text-muted" style="font-size: 0.9em;">
                        {{ \Illuminate\Support\Carbon::parse($visitSnapshot->snapshot_date)->format('Y-m-d') }} 자정까지의 데이터 (매일 자정 집계)
                    </span>
                @else
                    <span class="text-muted" style="font-size: 0.9em;">아직 집계된 스냅샷이 없습니다. (매일 자정 자동 집계)</span>
                @endif
            </div>
            <div class="card-body">
                @if ($visitSnapshot)
                    <div class="table-responsive">
                        <table class="table table-bordered table-vcenter text-center mb-0">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="align-middle">구분</th>
                                    <th rowspan="2" class="align-middle">총 방문수</th>
                                    <th colspan="6">방문 횟수별 인원</th>
                                </tr>
                                <tr>
                                    <th>1번 방문</th>
                                    <th>2번 방문</th>
                                    <th>3번 방문</th>
                                    <th>4번 방문</th>
                                    <th>5번 방문</th>
                                    <th>6번 이상</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th class="text-start">전체</th>
                                    <td><span class="badge bg-secondary-lt">{{ number_format($visitSnapshot->total_visits) }}명</span></td>
                                    <td>{{ number_format($visitSnapshot->visit_1) }}명</td>
                                    <td>{{ number_format($visitSnapshot->visit_2) }}명</td>
                                    <td>{{ number_format($visitSnapshot->visit_3) }}명</td>
                                    <td>{{ number_format($visitSnapshot->visit_4) }}명</td>
                                    <td>{{ number_format($visitSnapshot->visit_5) }}명</td>
                                    <td><span class="badge bg-orange-lt">{{ number_format($visitSnapshot->visit_6_plus) }}명</span></td>
                                </tr>
                                <tr>
                                    <th class="text-start"><span class="badge bg-blue-lt">남</span></th>
                                    <td><span class="badge bg-blue-lt">{{ number_format($visitSnapshot->total_male) }}명</span></td>
                                    <td>{{ number_format($visitSnapshot->visit_1_male) }}명</td>
                                    <td>{{ number_format($visitSnapshot->visit_2_male) }}명</td>
                                    <td>{{ number_format($visitSnapshot->visit_3_male) }}명</td>
                                    <td>{{ number_format($visitSnapshot->visit_4_male) }}명</td>
                                    <td>{{ number_format($visitSnapshot->visit_5_male) }}명</td>
                                    <td>{{ number_format($visitSnapshot->visit_6_plus_male) }}명</td>
                                </tr>
                                <tr>
                                    <th class="text-start"><span class="badge bg-pink-lt">여</span></th>
                                    <td><span class="badge bg-pink-lt">{{ number_format($visitSnapshot->total_female) }}명</span></td>
                                    <td>{{ number_format($visitSnapshot->visit_1_female) }}명</td>
                                    <td>{{ number_format($visitSnapshot->visit_2_female) }}명</td>
                                    <td>{{ number_format($visitSnapshot->visit_3_female) }}명</td>
                                    <td>{{ number_format($visitSnapshot->visit_4_female) }}명</td>
                                    <td>{{ number_format($visitSnapshot->visit_5_female) }}명</td>
                                    <td>{{ number_format($visitSnapshot->visit_6_plus_female) }}명</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-3">표시할 통계 데이터가 없습니다.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- 검색 필터 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">검색 필터</h3></div>
            <div class="card-body">
                <form id="searchForm">
                    <div class="col-12 d-flex gap-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">타입</label>
                            <select class="form-select" name="type">
                                <option value="">전체</option>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" {{ $type == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">상태</label>
                            <select class="form-select" name="status">
                                <option value="">전체</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" {{ $status == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label">검색어</label>
                            <input type="text" class="form-control" name="searchText" value="{{ $searchText }}" placeholder="행사명 / 참여자 이름 / 전화번호 검색" onkeydown="if(event.keyCode === 13) { event.preventDefault(); $('.btn-submit').click(); }">
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-primary px-4 btn-submit">검색</button>
                            <a href="{{ request()->url() }}" class="btn btn-outline-secondary px-4">초기화</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- 목록 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="page-count badge bg-secondary-lt"></span>
                </div>
                <div class="d-flex gap-2 ms-auto">
                    <button type="button" class="btn btn-danger btn-remove">
                        <i class="ti ti-trash me-1"></i> 삭제
                    </button>
                    <a href="{{ route('admin.rotation.event.create') }}" class="btn btn-success">
                        <i class="ti ti-plus me-1"></i> 행사 생성
                    </a>
                </div>
            </div>
            <div id="example-table"></div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        var table;

        // 방문 통계 제목 옆 notice 아이콘 툴팁 초기화.
        // Tabler(=Bootstrap5 기반) 번들은 data-bs-toggle="tooltip" 를 자동 초기화하지 않으므로 수동 초기화한다.
        // 번들이 전역에 노출하는 Tooltip 위치가 환경마다 다를 수 있어 가능한 경로를 모두 탐색한다.
        (function(){
            var el = document.getElementById('visitStatNotice');
            if (!el) { return; }
            var Tooltip =
                (window.bootstrap && window.bootstrap.Tooltip) ||
                (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Tooltip) ||
                window.Tooltip;
            if (typeof Tooltip === 'function') { new Tooltip(el); }
            // Tooltip 클래스를 못 찾아도 네이티브 title 속성으로 hover 설명은 보장된다.
        })();

        const TYPE_LABEL   = @json($types);
        const STATUS_LABEL = @json($statuses);
        const STATUS_COLOR = { 'P': 'bg-secondary', 'S': 'bg-blue', 'V': 'bg-green', 'X': 'bg-dark' };
        const detailBase   = "{{ url('admin/rotation/event') }}";
        const seatingBase  = "{{ route('admin.rotation.seating.index') }}";

        $('.btn-remove').click(async function(){
            let ids = getSelectedIds();
            if(ids.length < 1){ await salert({text: '최소 1개 이상 선택해주세요.', cancel: false}); return false; }
            if(await salert({text: `선택한 ${ids.length}개 행사를 삭제하시겠습니까? (테이블/좌석도 함께 삭제됩니다)`})){ fn_delete(ids); }
        });

        function fn_delete(ids){
            $.ajax({
                url: "{{ route('admin.rotation.event.delete') }}",
                type: 'DELETE',
                data: { ids },
                success: function(res){ alert(res?.msg); if(res?.status === 200){ table.setData(); } },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        }

        table = new Tabulator("#example-table", {
            ajaxURL: "{{ route('admin.rotation.event.list') }}",
            initialSort:[{column: "{{ $sort['field'] }}", dir: "{{ $sort['dir'] }}"}],
            paginationInitialPage: parseInt("{{ $page }}") || 1,
            columns: [
                { formatter: "rowSelection", titleFormatter: "rowSelection", headerSort: false, width: 60 },
                { title: "No", field: "id", width: 80 },
                { title: "행사명<br>일시", field: "title", hozAlign: "center", minWidth: 240,
                    formatter: function(cell){
                        var d = cell.getRow().getData();
                        var t = d.event_time ? new Date(d.event_time).toLocaleString('ko-KR') : '-';
                        var statusLabel = STATUS_LABEL[d.status] ?? d.status ?? '-';
                        var statusColor = STATUS_COLOR[d.status] ?? 'bg-secondary';
                        var name = $('<div>').text(d.title ?? '-').html();
                        return `<div class="d-flex align-items-center justify-content-center gap-2">`
                                 + `<span class="fw-bold">${name}</span>`
                                 + `<span class="badge ${statusColor} text-white">${statusLabel}</span>`
                             + `</div>`
                             + `<div style="font-size: 0.9em; color: #888;">${t}</div>`;
                    }
                },
                { title: "타입<br>테이블", field: "type", hozAlign: "center", width: 100, headerSort: false,
                    formatter: function(cell){
                        var d = cell.getRow().getData();
                        var typeLabel   = TYPE_LABEL[d.type] ?? d.type ?? '-';
                        var tableCount  = d.rotation_tables_count ?? 0;
                        return `<div><span class="badge bg-azure-lt">${typeLabel}</span></div>`
                            + `<div class="mt-1"><span class="badge bg-green-lt">테이블 ${tableCount}개</span></div>`;
                    }
                },
                { title: "참여자<br>(남/여)", field: "male_count", hozAlign: "center", width: 90, headerSort: false,
                    formatter: function(cell){
                        var d = cell.getRow().getData();
                        var m = d.male_count ?? 0, f = d.female_count ?? 0;
                        return `<span class="badge bg-blue-lt">남 ${m}</span><br><span class="badge bg-pink-lt mt-1">여 ${f}</span>`;
                    }
                },
                { title: "매칭수", field: "match_count", hozAlign: "center", width: 110, headerSort: false,
                    formatter: function(cell){
                        var v = cell.getValue() ?? 0;
                        if(v > 0){
                            return `<button type="button" class="btn btn-outline-pink">${v}쌍</button>`;
                        }
                        return '<span class="text-muted">0쌍</span>';
                    },
                    cellClick: function(e, cell){
                        var d = cell.getRow().getData();
                        if((d.match_count ?? 0) > 0){
                            MatchedModal.open(d.id, d.title ?? '');
                        }
                    }
                },
                { title: "등록자<br>생성일/수정일", field: "created_at", width: 190,
                    formatter: function(cell){
                        var d = cell.getRow().getData();
                        var name = d.creator && d.creator.name ? $('<div>').text(d.creator.name).html() : '-';
                        var c = d.created_at ? new Date(d.created_at).toLocaleString('ko-KR') : '-';
                        var u = d.updated_at ? new Date(d.updated_at).toLocaleString('ko-KR') : '-';
                        return `<div><span class="badge bg-secondary-lt">${name}</span></div>`
                            + `<div class="mt-1">${c}</div>`
                            + `<span style="font-size: 0.9em; color: #888;">${u}</span>`;
                    }
                },
                { title: "관리", width: 110, headerSort: false,
                    formatter: function(cell){
                        var data = cell.getRow().getData();
                        var $wrap = $("<div>").addClass("d-flex flex-column gap-1");
                        var $edit = $("<button>")
                            .addClass("btn btn-sm btn-primary w-100")
                            .text("상세")
                            .on("click", function(){ location.href = detailBase + "/" + data.id; });
                        $wrap.append($edit);
                        var $seating = $("<button>")
                            .addClass("btn btn-sm btn-outline-azure w-100")
                            .html('로테이션 설정')
                            .on("click", function(){ location.href = seatingBase + "?eventId=" + data.id; });
                        $wrap.append($seating);
                        if((data.messages_count ?? 0) >= 1){
                            var $msg = $("<button>")
                                .addClass("btn btn-sm btn-outline-primary w-100")
                                .text("메시지 " + data.messages_count)
                                .on("click", function(){ MessagesModal.open(data.id, data.title ?? ''); });
                            $wrap.append($msg);
                        }
                        if((data.admin_requests_count ?? 0) >= 1){
                            var $req = $("<button>")
                                .addClass("btn btn-sm btn-outline-yellow w-100")
                                .text("요청 " + data.admin_requests_count)
                                .on("click", function(){ RequestsModal.open(data.id, data.title ?? ''); });
                            $wrap.append($req);
                        }
                        var $match = $("<button>")
                            .addClass("btn btn-sm btn-outline-pink w-100")
                            .text("전체 매칭")
                            .on("click", function(){ MatchesModal.open(data.id, data.title ?? ''); });
                        $wrap.append($match);
                        return $wrap.get(0);
                    }
                },
            ],
        });

        function getSelectedIds(){ return table.getSelectedRows().map(row => row.getData().id); }

        $('.btn-submit').click(function(){ table.setData(); });
        $('#searchForm select').change(function(){ table.setData(); });
    });
</script>

@include('admin.rotation._messagesModal')
@include('admin.rotation._requestsModal')
@include('admin.rotation._matchesModal')
@include('admin.rotation._matchedModal')
@endsection
