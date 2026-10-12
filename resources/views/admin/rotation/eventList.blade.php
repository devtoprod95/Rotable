@extends('layouts.app')

@section('title', '로테이션 행사 관리')

@section('content')
<div class="row row-cards mt-2">

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

        const TYPE_LABEL   = @json($types);
        const STATUS_LABEL = @json($statuses);
        const detailBase   = "{{ url('admin/rotation/event') }}";

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
                { title: "행사명<br>일시", field: "title", minWidth: 220,
                    formatter: function(cell){
                        var d = cell.getRow().getData();
                        var t = d.event_time ? new Date(d.event_time).toLocaleString('ko-KR') : '-';
                        return `<div class="fw-bold">${d.title ?? '-'}</div><span style="font-size: 0.9em; color: #888;">${t}</span>`;
                    }
                },
                { title: "타입", field: "type", hozAlign: "center", width: 110,
                    formatter: function(cell){ return TYPE_LABEL[cell.getValue()] ?? cell.getValue(); }
                },
                { title: "테이블 수", field: "rotation_tables_count", hozAlign: "center", width: 90 },
                { title: "참여자<br>(남/여)", field: "male_count", hozAlign: "center", width: 90, headerSort: false,
                    formatter: function(cell){
                        var d = cell.getRow().getData();
                        var m = d.male_count ?? 0, f = d.female_count ?? 0;
                        return `<span class="badge bg-blue-lt">남 ${m}</span><br><span class="badge bg-pink-lt mt-1">여 ${f}</span>`;
                    }
                },
                { title: "상태", field: "status", hozAlign: "center", width: 80,
                    formatter: function(cell){ return STATUS_LABEL[cell.getValue()] ?? cell.getValue(); }
                },
                { title: "생성일<br>수정일", field: "created_at", width: 180,
                    formatter: function(cell){
                        var d = cell.getRow().getData();
                        var c = d.created_at ? new Date(d.created_at).toLocaleString('ko-KR') : '-';
                        var u = d.updated_at ? new Date(d.updated_at).toLocaleString('ko-KR') : '-';
                        return `<div>${c}</div><span style="font-size: 0.9em; color: #888;">${u}</span>`;
                    }
                },
                { title: "관리", width: 110, headerSort: false,
                    formatter: function(cell){
                        var data = cell.getRow().getData();
                        var $edit = $("<button>")
                            .addClass("btn btn-sm btn-primary w-100")
                            .text("상세")
                            .on("click", function(){ location.href = detailBase + "/" + data.id; });
                        return $edit.get(0);
                    }
                },
            ],
        });

        function getSelectedIds(){ return table.getSelectedRows().map(row => row.getData().id); }

        $('.btn-submit').click(function(){ table.setData(); });
        $('#searchForm select').change(function(){ table.setData(); });
    });
</script>
@endsection
