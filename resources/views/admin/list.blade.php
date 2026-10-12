@php
    use App\Enums\Admin;
@endphp

@extends('layouts.app')

@section('title', '관리자 목록')

@section('content')
<div class="row row-cards mt-2">

    {{-- 1. 검색 필터 영역 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">검색 필터</h3></div>
            <div class="card-body search-filter-body">
                <form id="searchForm">
                    <div class="row">
                        <div class="col-md-6 border-end">
                            <label class="form-label">권한 등급</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role"
                                    id="role_all" value=""
                                    {{ empty($role) ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label" for="role_all">전체</label>
                                </div>
                                @foreach ($roles as $obj)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="role"
                                        id="role_{{ $obj->value }}" value="{{ $obj->value }}"
                                        {{ $role == $obj->value ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label" for="role_{{ $obj->value }}">{{ $obj->label() }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">활성화 여부</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="isActive"
                                    id="isActive_all" value=""
                                    {{ $isActive === '' || $isActive === null ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label" for="isActive_all">전체</label>
                                </div>
                                @foreach ($actives as $value => $obj)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="isActive"
                                        id="isActive_{{ $value }}" value="{{ $value }}"
                                        {{ $isActive !== '' && $isActive !== null && $isActive == $value ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label" for="isActive_{{ $value }}">{{ $obj }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <hr class="my-3">

                    <div class="row">
                        <div class="col-md-6 border-end">
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

                        <div class="col-md-6">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">회원가입 시작일</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /><path d="M11 15h1" /><path d="M12 15v3" /></svg>
                                        </span>
                                        <input class="form-control datepicker" name="startDate" placeholder="YYYY-MM-DD" value='{{ $startDate }}' />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">회원가입 종료일</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /><path d="M11 15h1" /><path d="M12 15v3" /></svg>
                                        </span>
                                        <input class="form-control datepicker" name="endDate" placeholder="YYYY-MM-DD" value='{{ $endDate }}' />
                                    </div>
                                </div>
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
                    <span class="page-count badge bg-secondary-lt"></span>
                </div>

                <div class="d-flex gap-2 ms-auto">
                    <button type="button" class="btn btn-danger btn-remove">
                        <i class="ti ti-trash me-1"></i> 삭제
                    </button>
                    <button type="button" class="btn btn-primary btn-active-modal">
                        <i class="ti ti-clipboard-check me-1"></i> 활성처리
                    </button>
                    <a href="{{ route('admin.view') }}" class="btn btn-success">
                        <i class="ti ti-plus me-1"></i> 관리자 생성
                    </a>
                </div>
            </div>

            <div id="example-table"></div>

        </div>
    </div>

    <div class="modal modal-blur fade" id="activeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">활성화 변경</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <label class="form-label mb-0 fw-bold">활성 여부</label>

                        <div class="form-selectgroup selectgroup-pills">
                            @foreach (Admin::isActives() as $value => $label)
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="is_active_modal" value="{{ $value }}" class="form-selectgroup-input">
                                    <span class="form-selectgroup-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary btn-active-save">적용하기</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    $(document).ready(function(){
        var table;
        const apiUrl = "{{ route('admin.list') }}";

        $('.btn-active-modal').click(async function(){
            let selectList = getSelectedIds();
            if(selectList.length < 1){
                await salert({text: '최소 1개 이상 선택해주세요.', cancel: false});
                return false;
            }

            $('#activeModal').modal('show');
        })

        $('.btn-active-save').click(async function(){
            let selectList = getSelectedIds();
            if(selectList.length < 1){
                await salert({text: '최소 1개 이상 선택해주세요.', cancel: false});
                return false;
            }

            let active = $('input[name=is_active_modal]:checked').val();
            if( !active ){
                await salert({text: '활성 여부를 선택해주세요.', cancel: false});
                return false;
            }

            var selectedLabel = $('input[name=is_active_modal]:checked').siblings('span').text().trim();
            if(await salert({text: `선택 한 ${selectList.length}개를 [${selectedLabel}] 처리 하시겠습니까?`})){
                fn_active(selectList, active);
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

        function fn_active(ids, active){
            $.ajax({
                url: "{{ route('admin.updateActive') }}",
                type: 'PATCH',
                data: {
                    ids,
                    is_active: active
                },
                success: function(res) {
                    alert(res?.msg);
                    if (res?.status === 200) {
                        $('#activeModal').modal('hide');
                        table.setData();
                    }
                },
                error: function(xhr) {
                    var res = xhr?.responseJSON;
                    alert(res?.error?.message ?? '오류가 발생했습니다.');
                }
            });
        };

        function fn_delete(ids){
            $.ajax({
                url: "{{ route('admin.delete') }}",
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

        function initTable(params = {}) {
            table = new Tabulator("#example-table", {
                ajaxURL: apiUrl,
                initialSort:[
                    {column: "{{ $sort['field'] }}", dir: "{{ $sort['dir'] }}"},
                ],
                paginationInitialPage: parseInt("{{ $page }}") || 1,
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
                    { title: "아이디", field: "username", headerSort: true, headerMenu: window.tabulatorHeaderMenu },
                    { title: "이름", field: "name", headerSort: true, headerMenu: window.tabulatorHeaderMenu },
                    {
                        title: "권한",
                        field: "roles",
                        headerSort: true,
                        headerMenu: window.tabulatorHeaderMenu,
                        formatter: function(cell) {
                            const roles = cell.getValue();
                            if (!roles || !Array.isArray(roles) || roles.length === 0) {
                                return '<span class="text-muted">-</span>';
                            }
                            return roles.map(role => {
                                return `<div class="mb-1"><span class="badge bg-blue-lt">${role.display_name}</span></div>`;
                            }).join("");
                        },
                        accessorClipboard: function(value, data) {
                            return value.map(role => role.display_name).join(", ");
                        }
                    },
                    { title: "활성여부", field: "is_active", hozAlign: "center", formatter: "tickCross", width: 130, headerSort: true, headerMenu: window.tabulatorHeaderMenu },
                    { title: "가입일", field: "created_at", width: 200, headerSort: true, headerMenu: window.tabulatorHeaderMenu,
                        formatter: function(cell) {
                            return new Date(cell.getValue()).toLocaleString('ko-KR');
                        },
                        accessorClipboard: function(value, data) {
                            return new Date(value).toLocaleString('ko-KR');
                        }
                    },
                    { title: "마지막 로그인일<br>마지막 로그인IP", field: "last_login_at", width: 200, headerSort: true, headerMenu: window.tabulatorHeaderMenu,
                        formatter: function(cell) {
                            var rowData = cell.getRow().getData();
                            var loginAt = rowData.last_login_at ? new Date(rowData.last_login_at).toLocaleString('ko-KR') : '-';
                            var loginIp = rowData.last_login_ip || '-';
                            return `<div>${loginAt}</div><span style="font-size: 0.9em;">${loginIp}</span>`;
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
                            var data = cell.getRow().getData();
                            var currentAdminId = {{ auth('admin')->id() ?? 0 }};
                            var isSuperAdmin = {{ auth('admin')->user()?->hasRole(\App\Enums\Role::SuperAdmin->value) ? 'true' : 'false' }};
                            var $container = $("<div>").addClass("d-flex flex-column align-items-center gap-1 py-1");

                            if (isSuperAdmin && data.id !== currentAdminId && data.is_active == 1) {
                                var $loginBtn = $("<button>")
                                    .addClass("btn btn-sm btn-teal w-100")
                                    .html('<i class="ti ti-login me-1"></i>로그인')
                                    .on("click", async function() {
                                        $.ajax({
                                            url: "{{ route('admin.impersonate', ['admin' => '__ID__']) }}".replace('__ID__', data.id),
                                            type: 'POST',
                                            success: function(res) {
                                                if (res?.status === 200) {
                                                    location.href = "/admin/dashboard";
                                                } else {
                                                    alert(res?.msg);
                                                }
                                            },
                                            error: function(xhr) {
                                                var res = xhr?.responseJSON;
                                                alert(res?.error?.message ?? '오류가 발생했습니다.');
                                            }
                                        });
                                    });
                                $container.append($loginBtn);
                            }

                            var $editBtn = $("<button>")
                                .addClass("btn btn-sm btn-primary w-100") // w-100으로 너비 통일
                                .text("수정")
                                .on("click", function() {
                                    location.href = "{{ route('admin.view') }}/" + data.id;
                                });

                            // 삭제 버튼 (btn-danger, btn-sm)
                            var $deleteBtn = $("<button>")
                                .addClass("btn btn-sm btn-danger w-100")
                                .text("삭제")
                                .on("click", async function() {
                                    if( await salert({text: `${data.name} 회원을 삭제하시겠습니까?`}) ){
                                        fn_delete([data.id]);
                                    }
                                });

                            return $container.append($editBtn, $deleteBtn).get(0);
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

        // 필터 변경 시 자동 검색
        $('#searchForm input[type=radio], #searchForm input[type=checkbox], #searchForm select, .datepicker').change(function(){
            table.setData();
        });
    })
</script>
@endsection
