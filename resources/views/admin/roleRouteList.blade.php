@php
    use App\Enums\Admin;
@endphp

@extends('layouts.app')

@section('title', '페이지 권한 목록')

@section('content')
<div class="row row-cards mt-2">

    {{-- 1. 검색 필터 영역 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">검색 필터</h3></div>
            <div class="card-body search-filter-body">
                <form id="searchForm">

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
                    <h3 class="card-title m-0">페이지 권한 목록</h3>
                    <span class="page-count badge bg-secondary-lt"></span>
                </div>

                <div class="d-flex gap-2 ms-auto">
                    <button type="button" class="btn btn-danger btn-remove">
                        <i class="ti ti-trash me-1"></i> 삭제
                    </button>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                        <i class="ti ti-plus me-1"></i> 페이지 권한 생성
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
                    <h5 class="modal-title">접근 불가 권한 리스트</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-body-content">
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="createPermissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">페이지 권한 생성</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="permissionForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">적용 페이지</label>
                            <select class="form-select choices-select" id="routeSelect">
                                @foreach($routeList as $route)
                                    <option value="{{ $route['name'] }}">{{ $route['uri'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="pageName" class="form-label">페이지명</label>
                            <input type="text" class="form-control" id="pageName" placeholder="예: 페이지 권한 목록" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">접근 불가 권한</label>
                            <select class="form-select choices-select" id="roleSelect" multiple>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                                @endforeach
                            </select>
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

    <div class="modal modal-blur fade" id="deniedRoutesModiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">페이지 권한 수정</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">적용 페이지</label>
                        <select class="form-select" id="routeSelectModi" disabled>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="pageName" class="form-label">페이지명</label>
                        <input type="text" class="form-control" id="pageNameModi" placeholder="예: 페이지 권한 목록" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">접근 불가 권한</label>
                        <select class="form-select choices-select-modi" id="roleSelectModi" multiple>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary btn-role-modi">수정</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    $(document).ready(function(){
        var table;
        const apiUrl = "{{ route('admin.role.route.list') }}";

        // 초기 로딩
        initTable();
        initChoices();

        function initChoices(selector = '.choices-select') {
            const commonOptions = {
                removeItemButton: true,
                searchEnabled: true,
                duplicateItemsAllowed: false,
                noResultsText: '검색 결과가 없습니다.',
                noChoicesText: '선택할 항목이 없습니다.',
                itemSelectText: '선택하려면 클릭하세요.',
                placeholderValue: '🔍 검색하거나 선택하세요.',
                classNames: {
                    listItems: ['choices__list--multiple', 'danger'],
                },
            };

            $(selector).each(function() {
                const $el = $(this);
                if (!$el.data('choices')) {
                    $el.data('choices', new Choices(this, commonOptions));
                }
            });
        }

        $('.btn-role-modi').click(async function(){
            let   id            = $('#routeSelectModi').data('id');
            let   route_name    = $('#pageNameModi').val().trim();
            const roleChoices   = $('#roleSelectModi').data('choices');
            const selectedRoles = roleChoices.getValue(true);

            if( !route_name ){
                await salert({text: '페이지명을 선택해주세요.', cancel: false});
                return false;
            }
            if( selectedRoles.length < 1 ){
                await salert({text: '접근 불가 권한을 최소 1개 선택해주세요.', cancel: false});
                return false;
            }

            if(await salert({text: `선택한 권한들에 대해 지정된 페이지의 접근을 차단하시겠습니까?`})){
                $.ajax({
                    url: "{{ route('admin.role.route.update') }}",
                    type: 'PATCH',
                    data: {
                        id,
                        route_name,
                        roles: selectedRoles
                    },
                    success: function(res) {
                        alert(res?.msg);
                        if (res?.status === 200) {
                            $('#deniedRoutesModiModal').modal('hide');
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

        $('.btn-role-save').click(async function(){
            const routeChoices = $('#routeSelect').data('choices');
            const roleChoices = $('#roleSelect').data('choices');

            // 2. 값만 배열로 추출 (true 옵션 사용 시 value 값만 배열로 반환)
            const route = routeChoices.getValue(true);
            const selectedRoles = roleChoices.getValue(true);

            let pageName = $('#pageName').val().trim();

            if( !route ){
                await salert({text: '적용 페이지를 선택해주세요.', cancel: false});
                return false;
            }
            if( !pageName ){
                await salert({text: '페이지명을 선택해주세요.', cancel: false});
                return false;
            }
            if( selectedRoles.length < 1 ){
                await salert({text: '접근 불가 권한을 최소 1개 선택해주세요.', cancel: false});
                return false;
            }

            if(await salert({text: `선택한 권한들에 대해 지정된 페이지의 접근을 차단하시겠습니까?`})){
                $.ajax({
                    url: "{{ route('admin.role.route.store') }}",
                    type: 'POST',
                    data: {
                        route,
                        route_name: pageName,
                        roles: selectedRoles
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
                url: "{{ route('admin.role.route.delete') }}",
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
                url: "{{ route('admin.role.route.info') }}" + `/${id}`,
                type: 'GET',
                success: function(res) {
                    var roles = res?.data?.roles;
                    var html = '<div class="d-flex flex-wrap gap-2">';

                    if (roles && roles.length > 0) {
                        $.each(roles, function(i, item) {
                            html += `<span class="badge bg-red text-red-fg">${item.display_name}</span>`;
                        });
                    } else {
                        html = '<p class="text-muted">접근 불가 권한이 없습니다.</p>';
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

        function openDeniedRoutesModiModal(id) {
            var $modal = $('#deniedRoutesModiModal').modal('show');

            $.ajax({
                url: "{{ route('admin.role.route.info') }}" + `/${id}`,
                type: 'GET',
                success: function(res) {
                    var data = res?.data;
                    var roles = data?.roles;

                    $('#routeSelectModi').attr('data-id', `${data.id}`);
                    $('#routeSelectModi').html(`<option value=${data.route}>${data.route_url}</option>`);
                    $('#pageNameModi').val(`${data.route_name}`);

                    initChoices('#roleSelectModi');
                    const choicesInstance = $('#roleSelectModi').data('choices');
                    if (roles && roles.length > 0 && choicesInstance) {
                        choicesInstance.removeActiveItems();

                        const valuesToSelect = roles.map(function(item) {
                            return String(item.id);
                        });

                        valuesToSelect.forEach(function(value) {
                            choicesInstance.setChoiceByValue(value);
                        });
                    }
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
                    { title: "페이지 명", field: "route_name", headerSort: true, headerMenu: window.tabulatorHeaderMenu },
                    {
                        title: "페이지 URL<br>접근 불가 권한",
                        field: "route_url",
                        headerSort: true,
                        width: 300,
                        headerMenu: window.tabulatorHeaderMenu,
                        formatter: function(cell, formatterParams, onRendered) {
                            var rowData = cell.getRow().getData();
                            var count = rowData.roles_count || 0;

                            // 1. 컨테이너 생성
                            var $container = $('<div>', { class: 'd-flex flex-column align-items-center' });

                            // 2. 이름 부분 추가
                            var $name = $('<span>', { text: rowData.route_url, class: 'mb-1' });

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

                            var $editBtn = $("<button>")
                                .addClass("btn btn-sm btn-primary w-100") // w-100으로 너비 통일
                                .text("수정")
                                .on("click", function() {
                                    var data = cell.getRow().getData();
                                    openDeniedRoutesModiModal(data.id);
                                });

                            var $deleteBtn = $("<button>")
                                .addClass("btn btn-sm btn-danger w-100")
                                .text("삭제")
                                .on("click", async function() {
                                    var data = cell.getRow().getData();
                                    if( await salert({text: `[${data.route_name}] 페이지를 삭제하시겠습니까?`}) ){
                                        fn_delete([data.id]);
                                    }
                                });

                            return $container.append($editBtn, $deleteBtn).get(0);
                        }
                    }
                ],
            });
        }

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
