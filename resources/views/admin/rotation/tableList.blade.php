@extends('layouts.app')

@section('title', '로테이션 테이블 관리')

@section('content')
<div class="row row-cards mt-2">

    {{-- 테이블 벌크 생성 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">테이블 생성</h3></div>
            <div class="card-body">
                <form id="bulkForm">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label required">행사</label>
                            <select class="form-select" name="event_id" required>
                                <option value="">선택</option>
                                @foreach ($pendingEvents as $event)
                                    <option value="{{ $event->id }}" {{ $eventId == $event->id ? 'selected' : '' }}>{{ $event->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">생성 개수</label>
                            <input type="number" min="1" max="100" class="form-control" name="count" placeholder="예: 10" required>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-success px-4 btn-bulk-create"><i class="ti ti-plus me-1"></i> 생성</button>
                        </div>
                    </div>
                    <small class="form-hint text-muted mt-2 d-block">※ 기존 테이블 다음 번호부터 입력한 개수만큼 추가 생성됩니다.</small>
                </form>
            </div>
        </div>
    </div>

    {{-- 검색 필터 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form id="searchForm">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">행사</label>
                            <select class="form-select" name="eventId">
                                <option value="">전체</option>
                                @foreach ($events as $event)
                                    <option value="{{ $event->id }}" {{ $eventId == $event->id ? 'selected' : '' }}>{{ $event->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label">테이블 번호</label>
                            <input type="text" class="form-control" name="searchText" value="{{ $searchText }}" placeholder="테이블 번호 검색" onkeydown="if(event.keyCode === 13) { event.preventDefault(); $('.btn-submit').click(); }">
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
                <span class="page-count badge bg-secondary-lt"></span>
                <button type="button" class="btn btn-danger btn-remove ms-auto"><i class="ti ti-trash me-1"></i> 삭제</button>
            </div>
            <div id="example-table"></div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        var table;

        $('.btn-bulk-create').click(async function(){
            let eventId = $('#bulkForm select[name=event_id]').val();
            let count   = $('#bulkForm input[name=count]').val();
            if(!eventId){ await salert({text: '행사를 선택해주세요.', cancel: false}); return false; }
            if(!count || count < 1){ await salert({text: '생성 개수를 입력해주세요.', cancel: false}); return false; }

            if(await salert({text: `${count}개의 테이블을 생성하시겠습니까?`})){
                $.ajax({
                    url: "{{ route('admin.rotation.table.bulkStore') }}",
                    type: 'POST',
                    data: { event_id: eventId, count: count },
                    success: function(res){
                        alert(res?.msg);
                        if(res?.status === 200){ $('#bulkForm input[name=count]').val(''); table.setData(); }
                    },
                    error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
                });
            }
        });

        $('.btn-remove').click(async function(){
            let ids = getSelectedIds();
            if(ids.length < 1){ await salert({text: '최소 1개 이상 선택해주세요.', cancel: false}); return false; }
            if(await salert({text: `선택 한 ${ids.length}개를 삭제하시겠습니까?`})){ fn_delete(ids); }
        });

        function fn_delete(ids){
            $.ajax({
                url: "{{ route('admin.rotation.table.delete') }}",
                type: 'DELETE',
                data: { ids },
                success: function(res) { alert(res?.msg); if (res?.status === 200) { table.setData(); } },
                error: function(xhr) { alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        }

        table = new Tabulator("#example-table", {
            ajaxURL: "{{ route('admin.rotation.table.list') }}",
            initialSort:[{column: "{{ $sort['field'] }}", dir: "{{ $sort['dir'] }}"}],
            paginationInitialPage: parseInt("{{ $page }}") || 1,
            columns: [
                { formatter: "rowSelection", titleFormatter: "rowSelection", headerSort: false, width: 60 },
                { title: "No", field: "id", width: 80 },
                { title: "행사", field: "event_round", headerSort: false,
                    formatter: function(cell){ var r = cell.getRow().getData().event_round; return r ? r.title : '-'; }
                },
                { title: "테이블 번호", field: "table_no", hozAlign: "center" },
                { title: "등록일", field: "created_at", width: 200,
                    formatter: function(cell){ return cell.getValue() ? new Date(cell.getValue()).toLocaleString('ko-KR') : '-'; }
                },
                { title: "관리", width: 100,
                    formatter: function(cell){
                        var data = cell.getRow().getData();
                        var $del = $("<button>").addClass("btn btn-sm btn-danger").text("삭제")
                            .on("click", async function(){ if(await salert({text: `테이블 ${data.table_no}을 삭제하시겠습니까?`})){ fn_delete([data.id]); } });
                        return $del.get(0);
                    }
                },
            ],
        });

        function getSelectedIds(){ return table.getSelectedRows().map(row => row.getData().id); }
        $('.btn-submit').click(function(){ table.setData(); });
        $('#searchForm select, #searchForm input').change(function(){ table.setData(); });
    });
</script>
@endsection
