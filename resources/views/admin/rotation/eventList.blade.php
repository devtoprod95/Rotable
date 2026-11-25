@extends('layouts.app')

@section('title', '행사 목록')

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

    /* 위험 구역(운영 → 개발 DB 덮어쓰기): 붉은 테두리 + 사선 줄무늬 머리로 일반 카드와 확실히 구분 */
    .danger-zone { border:2px solid #d63939; box-shadow:0 0 0 3px rgba(214,57,57,.12); }
    .danger-zone__head { background:repeating-linear-gradient(-45deg, #fff5f5, #fff5f5 12px, #ffe3e3 12px, #ffe3e3 24px); border-bottom:2px solid #d63939; }
    @media (max-width: 767.98px){ #syncDevBtn { width:100%; } }
    /* 덮어쓰기 확인창: 제목이 크면 모바일에서 줄 간격이 붙어 보이므로 작게 + 줄 간격 확보 */
    .swal-sync-title { font-size:1.15rem !important; line-height:1.5 !important; padding:.5rem 1rem 0 !important; }
    .swal-sync-html { margin:.75rem 1rem .5rem !important; line-height:1.6 !important; }

    /* 행사 목록 표: 엑셀(격자) 느낌 제거 — 세로 격자선 없이 일반 테이블처럼. (모바일 접기 동작은 유지) */
    #example-table.tabulator { border: 1px solid #e9ecf1; border-radius: 10px; }
    #example-table.tabulator .tabulator-header { background: #f6f8fb; border-bottom: 1px solid #e9ecf1; }
    #example-table.tabulator .tabulator-header .tabulator-col { background: transparent; border-right: none; font-weight: 700; color: #495057; }
    #example-table.tabulator .tabulator-row .tabulator-cell { border-right: none; }
    #example-table.tabulator .tabulator-row { border-bottom: 1px solid #f0f2f6; }
    #example-table.tabulator .tabulator-row.tabulator-row-even { background: #fcfcfd; }
    #example-table.tabulator .tabulator-row:hover { background: #f3f7ff; }
</style>
<div class="row row-cards mt-2">

    {{-- 공개 게임/안내 슬라이드 바로가기는 상단 헤더(사용 설명서 옆)로 이동했다. --}}


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
                       title="<b>총 방문수</b> = 입장등록 건수예요.<br>같은 사람이 3번 오면 3으로 셉니다.<br><br><b>1~6번 방문</b> = 그만큼 온 &quot;사람 수&quot;예요.<br>3번 온 사람은 &quot;3번 방문&quot; 칸에 1명으로 들어가요.<br><br>그래서 아래 칸의 사람 수를 더해도 총 방문수와 다른 게 정상이에요.<br>예) A는 1번, B·C는 3번 → 총 방문수 7, 1번 방문 1명 / 3번 방문 2명"></i>
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

                    {{-- 신청 플랫폼별 방문 수 (플랫폼 확장 대비: platform_counts JSON + platformOptions 루프) --}}
                    @php
                        $platformCounts = $visitSnapshot->platform_counts ?? [];
                        // 색상 매핑(없으면 기본색으로 폴백 — 새 플랫폼 추가 시 라벨은 자동, 색만 선택적으로 추가)
                        $platformBadgeCls = [
                            \App\Models\Member::PLATFORM_MUNTO => 'bg-orange-lt',
                            \App\Models\Member::PLATFORM_FRIP  => 'bg-cyan-lt',
                        ];
                    @endphp
                    <div class="mt-3 d-flex align-items-center gap-2 flex-wrap">
                        <span class="text-muted" style="font-size:0.9em;">신청 플랫폼별 방문</span>
                        @foreach (\App\Models\Member::platformOptions() as $opt)
                            <span class="badge {{ $platformBadgeCls[$opt['id']] ?? 'bg-secondary-lt' }}">
                                {{ $opt['label'] }} {{ number_format($platformCounts[$opt['id']] ?? 0) }}명
                            </span>
                        @endforeach
                    </div>

                    {{-- 행사 타입별 참여 인원 (1:1 소개팅 / 와인파티) — 스냅샷 시점 기준. 모바일은 가로 스크롤 --}}
                    @php
                        $typeCounts   = $visitSnapshot->type_counts ?? null;
                        $typeBadgeCls = [
                            \App\Models\EventRound::TYPE_ONE  => 'bg-purple-lt',
                            \App\Models\EventRound::TYPE_WINE => 'bg-red-lt',
                        ];
                    @endphp
                    <div class="mt-3">
                        <div class="text-muted mb-1" style="font-size:0.9em;">행사 타입별 참여 인원</div>
                        @if (! empty($typeCounts))
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-vcenter text-center mb-0" style="min-width:520px;">
                                    <thead class="bg-white">
                                        <tr>
                                            <th class="text-start">타입</th>
                                            <th>행사 수</th>
                                            <th>총</th>
                                            <th><span class="badge bg-blue-lt">남</span></th>
                                            <th><span class="badge bg-pink-lt">여</span></th>
                                            @foreach (\App\Models\Member::platformOptions() as $opt)
                                                <th><span class="badge {{ $platformBadgeCls[$opt['id']] ?? 'bg-secondary-lt' }}">{{ $opt['label'] }}</span></th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (\App\Models\EventRound::typeOptions() as $typeKey => $typeLabel)
                                            @php $tc = $typeCounts[$typeKey] ?? ['events' => 0, 'total' => 0, 'male' => 0, 'female' => 0, 'platform_counts' => []]; @endphp
                                            <tr>
                                                <td class="text-start"><span class="badge {{ $typeBadgeCls[$typeKey] ?? 'bg-azure-lt' }}">{{ $typeLabel }}</span></td>
                                                <td>{{ number_format($tc['events'] ?? 0) }}회</td>
                                                <td class="fw-bold">{{ number_format($tc['total'] ?? 0) }}</td>
                                                <td>{{ number_format($tc['male'] ?? 0) }}</td>
                                                <td>{{ number_format($tc['female'] ?? 0) }}</td>
                                                @foreach (\App\Models\Member::platformOptions() as $opt)
                                                    <td>{{ number_format($tc['platform_counts'][$opt['id']] ?? 0) }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-muted" style="font-size:0.9em;">이 스냅샷에는 타입별 기록이 없어요. 다음 자정 스냅샷부터 기록됩니다.</div>
                        @endif
                    </div>

                    {{-- 노쇼(신청했으나 미참석) — 성별·플랫폼으로 분류. 체크인 명단(event_rosters) 중 미참석 기준 --}}
                    @php
                        $noShowPlatform = $visitSnapshot->no_show_platform_counts ?? [];
                    @endphp
                    <div class="mt-3 d-flex align-items-center gap-2 flex-wrap">
                        <span class="text-muted" style="font-size:0.9em;">노쇼(미참석)</span>
                        <span class="badge bg-secondary-lt">총 {{ number_format($visitSnapshot->no_show_total) }}명</span>
                        <span class="badge bg-blue-lt">남 {{ number_format($visitSnapshot->no_show_male) }}명</span>
                        <span class="badge bg-pink-lt">여 {{ number_format($visitSnapshot->no_show_female) }}명</span>
                        <span class="text-muted" style="font-size:0.85em;">·</span>
                        @foreach (\App\Models\Member::platformOptions() as $opt)
                            <span class="badge {{ $platformBadgeCls[$opt['id']] ?? 'bg-secondary-lt' }}">
                                {{ $opt['label'] }} {{ number_format($noShowPlatform[$opt['id']] ?? 0) }}명
                            </span>
                        @endforeach
                        <i class="ti ti-info-circle text-muted ms-1"
                           data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                           style="cursor: help;"
                           title="체크인 명단에 올라왔으나 당일 참석(체크인)하지 않은 신청자 수예요.<br>성별·플랫폼은 신청 시 등록한 값 기준입니다."></i>
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
                    <div class="row g-2 align-items-end">
                        <div class="col-6 col-md-2">
                            <label class="form-label">타입</label>
                            <select class="form-select" name="type">
                                <option value="">전체</option>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" {{ $type == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">상태</label>
                            <select class="form-select" name="status">
                                <option value="">전체</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" {{ $status == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md">
                            <label class="form-label">검색어</label>
                            <input type="text" class="form-control" name="searchText" value="{{ $searchText }}" placeholder="행사명 / 참여자 이름 / 전화번호 검색" onkeydown="if(event.keyCode === 13) { event.preventDefault(); $('.btn-submit').click(); }">
                        </div>
                        <div class="col-12 col-md-auto d-flex gap-2">
                            <button type="button" class="btn btn-primary flex-fill px-4 btn-submit">검색</button>
                            <a href="{{ request()->url() }}" class="btn btn-outline-secondary flex-fill px-4">초기화</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- 목록 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
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

    {{-- ⚠️ 위험 구역 [운영 전용]: 모든 행사 데이터를 개발 DB로 옮기고 개발 DB의 행사 관련 데이터를 교체한다.
         최고관리자 · 운영 환경 · DEV_DB_* 설정 시에만 노출 --}}
    @if (! empty($canSyncToDev))
        <div class="col-12">
            <div class="card danger-zone">
                <div class="card-header danger-zone__head">
                    <h3 class="card-title mb-0 d-flex align-items-center gap-2 text-danger">
                        <i class="ti ti-alert-triangle"></i> 위험 구역
                        <span class="badge bg-danger text-white">운영 전용</span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                        <div class="flex-fill">
                            <div class="fw-bold mb-1">운영 데이터를 개발 DB로 덮어쓰기</div>
                            <ul class="text-secondary small mb-0 ps-3">
                                <li><b class="text-danger">모든 행사</b>와 참여자·체크인·좌석·투표·메시지·요청·페이백·비용을 개발 DB로 옮겨요.</li>
                                <li>개발 DB에 있던 <b class="text-danger">행사 관련 데이터는 전부 삭제</b>되고 운영 데이터로 교체돼요. 되돌릴 수 없어요.</li>
                                <li>연락처·비밀번호 등 <b>개인정보도 그대로</b> 복사돼요. 운영 DB는 변경되지 않아요.</li>
                            </ul>
                        </div>
                        <button type="button" id="syncDevBtn" class="btn btn-danger btn-lg flex-shrink-0">
                            <i class="ti ti-database-export me-1"></i> 개발 DB로 덮어쓰기
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
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
        // 타입별 뱃지 색상: 와인파티(wine)=와인색(핑크레드), 1:1 소개팅(one)=하늘색
        const TYPE_COLOR   = { 'wine': 'bg-pink-lt', 'one': 'bg-azure-lt' };
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
            layout: "fitColumns",
            // 모바일 대응: 좁은 화면에서 우선순위 낮은 컬럼을 접어 '펼치기(+)' 행으로 보여준다.
            responsiveLayout: "collapse",
            responsiveLayoutCollapseStartOpen: false,
            initialSort:[{column: "{{ $sort['field'] }}", dir: "{{ $sort['dir'] }}"}],
            paginationInitialPage: parseInt("{{ $page }}") || 1,
            columns: [
                { formatter: "rowSelection", titleFormatter: "rowSelection", headerSort: false, width: 48, responsive: 0 },
                { title: "No", field: "id", width: 70, responsive: 4 },
                { title: "행사명<br>일시", field: "title", hozAlign: "center", minWidth: 200, responsive: 0,
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
                { title: "타입<br>테이블", field: "type", hozAlign: "center", width: 100, headerSort: false, responsive: 2,
                    formatter: function(cell){
                        var d = cell.getRow().getData();
                        var typeLabel   = TYPE_LABEL[d.type] ?? d.type ?? '-';
                        var typeColor   = TYPE_COLOR[d.type] ?? 'bg-azure-lt';
                        var tableCount  = d.rotation_tables_count ?? 0;
                        return `<div><span class="badge ${typeColor}">${typeLabel}</span></div>`
                            + `<div class="mt-1"><span class="badge bg-green-lt">테이블 ${tableCount}개</span></div>`;
                    }
                },
                { title: "참여자<br>(남/여)", field: "male_count", hozAlign: "center", width: 90, headerSort: false, responsive: 2,
                    formatter: function(cell){
                        var d = cell.getRow().getData();
                        var m = d.male_count ?? 0, f = d.female_count ?? 0;
                        return `<span class="badge bg-blue-lt">남 ${m}</span><br><span class="badge bg-pink-lt mt-1">여 ${f}</span>`;
                    }
                },
                { title: "플랫폼", field: "munto_count", hozAlign: "center", width: 100, headerSort: false, responsive: 3,
                    formatter: function(cell){
                        var d = cell.getRow().getData();
                        var mu = d.munto_count ?? 0, fr = d.frip_count ?? 0;
                        return `<span class="badge bg-orange-lt">문토 ${mu}</span><br><span class="badge bg-cyan-lt mt-1">프립 ${fr}</span>`;
                    }
                },
                { title: "매칭수", field: "match_count", hozAlign: "center", width: 110, headerSort: false, responsive: 3,
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
                { title: "등록자<br>생성일/수정일", field: "created_at", width: 190, responsive: 5,
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
                { title: "관리", width: 120, headerSort: false, responsive: 1,
                    formatter: function(cell){
                        var data = cell.getRow().getData();
                        var $wrap = $("<div>").addClass("d-flex flex-column gap-1");
                        // 운영 흐름 순서로 버튼을 노출한다: 상세 → 체크인 → 로테이션 진행 → 메시지 → 요청 → 전체 매칭
                        var $edit = $("<button>")
                            .addClass("btn btn-sm btn-primary w-100")
                            .text("상세")
                            .on("click", function(){ location.href = detailBase + "/" + data.id; });
                        $wrap.append($edit);
                        var $checkin = $("<button>")
                            .addClass("btn btn-sm btn-outline-teal w-100")
                            .html('<i class="ti ti-user-check me-1"></i>체크인')
                            .on("click", function(){ location.href = detailBase + "/" + data.id + "/checkin"; });
                        $wrap.append($checkin);
                        var $registerQr = $("<button>")
                            .addClass("btn btn-sm btn-outline-dark w-100")
                            .html('<i class="ti ti-qrcode me-1"></i>입장 QR')
                            .on("click", function(){ RegisterQrModal.open(data.id, data.title ?? ''); });
                        $wrap.append($registerQr);
                        var $seating = $("<button>")
                            .addClass("btn btn-sm btn-outline-azure w-100")
                            .html('로테이션 진행')
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

        // ===== [운영 전용] 개발 DB 덮어쓰기: 확인창에서 버튼을 눌러 실행한다 =====
        $('#syncDevBtn').on('click', async function(){
            var r = await Swal.fire({
                icon: 'warning',
                title: '개발 DB를 운영 데이터로 덮어쓸까요?',
                html: '<div class="text-start small lh-base">'
                    + '<p class="mb-2"><b class="text-danger">개발 DB의 행사 관련 데이터가 전부 삭제</b>되고, 운영의 <b>모든 행사 데이터</b>로 교체돼요. 되돌릴 수 없어요.</p>'
                    + '<p class="mb-0">연락처·비밀번호 등 개인정보도 그대로 복사돼요.</p>'
                    + '</div>',
                showCancelButton: true,
                confirmButtonText: '덮어쓰기 실행',
                cancelButtonText: '취소',
                confirmButtonColor: '#d63939',
                focusCancel: true,
                customClass: { title: 'swal-sync-title', htmlContainer: 'swal-sync-html' }
            });
            if(!r.isConfirmed){ return; }

            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: "{{ route('admin.rotation.event.syncDev') }}", type: 'POST',
                success: function(res){ alert(res?.msg); },
                error: function(xhr){ alert(xhr.responseJSON?.msg || '개발 DB 덮어쓰기에 실패했습니다.'); }
            }).always(function(){ $btn.prop('disabled', false); });
        });

        $('.btn-submit').click(function(){ table.setData(); });
        $('#searchForm select').change(function(){ table.setData(); });
    });
</script>

@include('admin.rotation._messagesModal')
@include('admin.rotation._requestsModal')
@include('admin.rotation._matchesModal')
@include('admin.rotation._matchedModal')
@include('admin.rotation._registerQrModal')
@endsection
