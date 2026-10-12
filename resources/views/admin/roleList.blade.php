@php
    use App\Enums\Admin;
@endphp

@extends('layouts.app')

@section('title', '권한 목록')

@section('content')
<div class="row row-cards mt-2">

    {{-- 1. 검색 필터 영역 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">검색 필터</h3></div>
            <div class="card-body search-filter-body">
                <form id="searchForm">
                    <div class="row">

                        <div class="col-md-12 border-end">
                            <label class="form-label">접근 불가 페이지</label>
                            <div class="d-flex gap-2 flex-wrap">
                                @foreach ($deninedRoutesObjs as $obj)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="deninedRoute[]"
                                        id="deninedRoute_{{ $obj->id }}" value="{{ $obj->id }}"
                                        {{ in_array($obj->id, $deninedRoute) ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label" for="deninedRoute_{{ $obj->id }}">{{ $obj->route_name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>
                    <hr class="my-3">

                    <div class="mb-0">
                        <div class="col-12 d-flex gap-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">검색 타입</label>
                                <select class="form-select" name="searchType">
                                    @foreach ($searchTypes as $value => $label)
                                        <option value="{{ $value }}" {{ ($searchType == $value) ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col">
                                <label class="form-label">검색어</label>
                                <input type="text" class="form-control" name="searchText" value="{{ $searchText }}" placeholder="검색어 입력" onkeydown="if(event.keyCode === 13) { event.preventDefault(); $('.btn-submit').click(); }">
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-primary px-4 btn-submit">검색</button>
                                <a href="{{ request()->url() }}" class="btn btn-outline-secondary px-4">초기화</a>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- 2. 결과 목록 영역 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <h3 class="card-title m-0">권한 목록</h3>
                    <span class="page-count badge bg-secondary-lt"></span>
                </div>

                <div class="d-flex gap-2 ms-auto">
                    <button type="button" class="btn btn-danger btn-remove">
                        <i class="ti ti-trash me-1"></i> 삭제
                    </button>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                        <i class="ti ti-plus me-1"></i> 권한 생성
                    </button>
                </div>
            </div>

            <div id="example-table"></div>

        </div>
    </div>

    <div class="modal modal-blur fade" id="deniedRoutesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">접근 불가 라우트 리스트</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-body-content">
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="createPermissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">권한 생성</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="permissionForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="permissionName" class="form-label">권한명</label>
                            <input type="text" class="form-control" id="permissionName" placeholder="예: 테스트관리자" required>
                        </div>
                        <div class="mb-3">
                            <label for="permissionValue" class="form-label">권한값(영문만)</label>
                            <input type="text" class="form-control" id="permissionValue" placeholder="예: testAdmin" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="button" class="btn btn-primary btn-role-save">저장</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    $(document).ready(function(){
        var table;
        const apiUrl = "{{ route('admin.role.list') }}";

        $('.btn-role-save').click(async function(){
            const koreanEnglishRegex = /^[a-zA-Z가-힣]+$/; // 영문+한글 허용
            const englishOnlyRegex = /^[a-zA-Z]+$/;        // 영문만 허용

            let display_name = $('#permissionName').val().trim();
            let name = $('#permissionValue').val().trim();

            if (!display_name) {
                await salert({text: '권한명을 입력해주세요.', cancel: false});
                return false;
            }
            if (!koreanEnglishRegex.test(display_name)) {
                await salert({text: '권한명은 한글과 영문만 입력 가능합니다.', cancel: false});
                return false;
            }
            if (!name) {
                await salert({text: '권한값을 입력해주세요.', cancel: false});
                return false;
            }
            if (!englishOnlyRegex.test(name)) {
                await salert({text: '권한값은 영문만 입력 가능합니다.', cancel: false});
                return false;
            }

            if(await salert({text: `권한을 생성하시겠습니까?`})){
                $.ajax({
                    url: "{{ route('admin.role.store') }}",
                    type: 'POST',
                    data: {
                        display_name,
                        name
                    },
                    success: function(res) {
                        alert(res?.msg);
                        if (res?.status === 200) {
                            $('#createPermissionModal').modal('hide');
                            table.setData();
                        }
                    },
                    error: function(xhr) {
                        var res = xhr?.responseJSON;
                        alert(res?.error?.message ?? '오류가 발생했습니다.');
                    }
                });
            }

        });

        $('.btn-remove').click(async function(){
            let selectList = getSelectedIds();
            if(selectList.length < 1){
                await salert({text: '최소 1개 이상 선택해주세요.', cancel: false});
                return false;
            }

            if(await salert({text: `선택 한 ${selectList.length}개를 삭제하시겠습니까?`})){
                fn_delete(selectList);
            }
        });

        function fn_delete(ids){
            $.ajax({
                url: "{{ route('admin.role.delete') }}",
                type: 'DELETE',
                data: { ids },
                success: function(res) {
                    alert(res?.msg);
                    if (res?.status === 200) {
                        table.setData();
                    }
                },
                error: function(xhr) {
                    var res = xhr?.responseJSON;
                    alert(res?.error?.message ?? '오류가 발생했습니다.');
                }
            });
        };

        function openDeniedRoutesModal(id) {
            var $modal = $('#deniedRoutesModal');
            var $body = $modal.find('#modal-body-content');

            $.ajax({
                url: "{{ route('admin.role.info') }}" + `/${id}`,
                type: 'GET',
                success: function(res) {
                    var routes = res?.data?.denied_routes;
                    var html = '<div class="d-flex flex-wrap gap-2">';

                    if (routes && routes.length > 0) {
                        $.each(routes, function(i, item) {
                            html += `<span class="badge bg-red text-red-fg">${item.route_name}</span>`;
                        });
                    } else {
                        html = '<p class="text-muted">접근 제한된 라우트가 없습니다.</p>';
                    }

                    html += '</div>';
                    $body.html(html);
                    $modal.modal('show');
                },
                error: function(xhr) {
                    var res = xhr?.responseJSON;
                    alert(res?.error?.message ?? '오류가 발생했습니다.');
                }
            });
        }

        function initTable(params = {}) {
            table = new Tabulator("#example-table", {
                ajaxURL: apiUrl,
                initialSort:[
                    {column: "{{ $sort['field'] }}", dir: "{{ $sort['dir'] }}"},
                ],
                paginationInitialPage: parseInt("{{ $page }}") || 1,
                height: 'auto',
                columns: [
                    {
                        formatter: "rowSelection",
                        titleFormatter: "rowSelection",
                        headerSort: false,
                        width: 60,
                        clipboard: false,
                    },
                    { title: "No", field: "id", width: 80, clipboard: false, headerSort: true,
                        formatter: function(cell) {
                            var table = cell.getTable();
                            var total = window._tableTotalCount || table.getDataCount(true);
                            var page = table.getPage();
                            var pageSize = table.getPageSize();
                            var rowIndex = cell.getRow().getPosition();

                            // 현재 정렬 상태 확인
                            var sorters = table.getSorters();
                            var idSorter = sorters.find(s => s.field === "id");
                            var isAsc = idSorter && idSorter.dir === "asc";

                            if (isAsc) {
                                return (page - 1) * pageSize + rowIndex;
                            } else {
                                return total - ((page - 1) * pageSize) - rowIndex + 1;
                            }
                        }
                    },
                    { title: "권한명", field: "display_name", headerSort: true, headerMenu: window.tabulatorHeaderMenu },
                    {
                        title: "권한값<br>접근 불가 라우트",
                        field: "name",
                        headerSort: true,
                        width: 300,
                        headerMenu: window.tabulatorHeaderMenu,
                        formatter: function(cell, formatterParams, onRendered) {
                            var rowData = cell.getRow().getData();
                            var count = rowData.denied_routes_count || 0;

                            // 1. 컨테이너 생성
                            var $container = $('<div>', { class: 'd-flex flex-column align-items-center' });

                            // 2. 이름 부분 추가
                            var $name = $('<span>', { text: rowData.name, class: 'mb-1' });

                            // 3. 버튼 생성
                            var $btn = $('<button>', {
                                type: 'button',
                                class: 'btn btn-sm btn-outline-red w-auto',
                                text: '확인 (' + count + ')',
                                click: function() {
                                    openDeniedRoutesModal(rowData.id);
                                }
                            });
                            $container.append($name).append($btn);

                            return $container[0];
                        }
                    },
                    { title: "생성일", field: "created_at", width: 200, headerSort: true, headerMenu: window.tabulatorHeaderMenu,
                        formatter: function(cell) {
                            return new Date(cell.getValue()).toLocaleString('ko-KR');
                        },
                        accessorClipboard: function(value, data) {
                            return new Date(value).toLocaleString('ko-KR');
                        }
                    },
                    { title: "수정일", field: "updated_at", width: 200, headerSort: true, headerMenu: window.tabulatorHeaderMenu,
                        formatter: function(cell) {
                            return new Date(cell.getValue()).toLocaleString('ko-KR');
                        },
                        accessorClipboard: function(value, data) {
                            return new Date(value).toLocaleString('ko-KR');
                        }
                    },
                    {
                        title: "관리",
                        width: 120,
                        clipboard: false,
                        formatter: function(cell, formatterParams, onRendered) {
                            var $container = $("<div>").addClass("d-flex flex-column align-items-center gap-1 py-1");

                            // 삭제 버튼 (btn-danger, btn-sm)
                            var $deleteBtn = $("<button>")
                                .addClass("btn btn-sm btn-danger w-100")
                                .text("삭제")
                                .on("click", async function() {
                                    var data = cell.getRow().getData();
                                    if( await salert({text: `[${data.display_name}] 권한을 삭제하시겠습니까?`}) ){
                                        fn_delete([data.id]);
                                    }
                                });

                            return $container.append($deleteBtn).get(0);
                        }
                    }
                ],
            });
        }

        // 초기 로딩
        initTable();

        function getSelectedIds() {
            let selectedRows = table.getSelectedRows();
            let ids = selectedRows.map(row => row.getData().id);

            return ids;
        }

        $('.btn-submit').click(function(){
            table.setData();
        });
    })
</script>
@endsection
