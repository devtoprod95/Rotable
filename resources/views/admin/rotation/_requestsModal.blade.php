{{--
    재사용 "회원 요청" 조회 모달.
    - 페이지 이동 없이 모달에서 특정 행사의 회원 요청을 조회/확인한다.
    - 사용법(JS): RequestsModal.open(eventId, eventLabel)
    - list 라우트는 eventId 필터를 지원하므로 그대로 사용한다.
--}}
@php
    $reqListUrl = route('admin.requests.list');
    $reqReadBase = url('admin/requests');
    $reqReadAllUrl = route('admin.requests.readAll');
@endphp

<div class="modal modal-blur fade" id="requestsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3 px-4 align-items-start">
                <div>
                    <h5 class="modal-title mb-0">회원 요청</h5>
                    <small class="text-muted" id="rm-eventLabel"></small>
                </div>
                <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="rm-searchForm" class="mb-2">
                    <div class="d-flex gap-2 align-items-end flex-wrap">
                        <div style="min-width: 130px;">
                            <label class="form-label mb-1">확인 여부</label>
                            <select class="form-select" name="read">
                                <option value="">전체</option>
                                <option value="Y">확인됨</option>
                                <option value="N">미확인</option>
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label mb-1">검색어</label>
                            <input type="text" class="form-control" name="searchText"
                                   placeholder="회원 이름 / 요청 내용 검색"
                                   onkeydown="if(event.keyCode === 13){ event.preventDefault(); RequestsModal.search(); }">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary px-3" onclick="RequestsModal.search()">검색</button>
                            <button type="button" class="btn btn-outline-secondary px-3" onclick="RequestsModal.search()">
                                <i class="ti ti-refresh me-1"></i> 새로고침
                            </button>
                            <button type="button" class="btn btn-outline-success px-3" id="rm-readAll">모두 확인</button>
                        </div>
                    </div>
                </form>

                <div class="d-flex align-items-center mb-2">
                    <span id="rm-pageCount" class="badge bg-secondary-lt"></span>
                </div>

                <div id="rm-table"></div>
            </div>
        </div>
    </div>
</div>

<script>
    window.RequestsModal = (function(){
        var listUrl  = @json($reqListUrl);
        var readBase = @json($reqReadBase);
        var readAllUrl = @json($reqReadAllUrl);

        var table = null;
        var currentEventId = null;
        var modalEl = null;
        var bsModal = null;
        var isOpen = false;

        function genderBadge(g){
            if(g === 'F'){ return '<span class="badge bg-pink-lt">여</span>'; }
            if(g === 'M'){ return '<span class="badge bg-blue-lt">남</span>'; }
            return '';
        }

        function filters(){
            return {
                eventId: currentEventId || '',
                read: $('#rm-searchForm [name=read]').val(),
                searchText: $('#rm-searchForm [name=searchText]').val(),
            };
        }

        function reload(){ if(table){ table.setData(listUrl, filters()); } }

        function fn_read(id){
            $.ajax({
                url: readBase + '/' + id + '/read', type: 'PATCH',
                success: function(res){
                    if(res?.status === 200){
                        reload();
                        if(window.AdminRequestsWidget){ window.AdminRequestsWidget.reload(); }
                    }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        }

        function buildTable(){
            table = new Tabulator("#rm-table", {
                ajaxURL: listUrl,
                layout: "fitColumns",
                height: "55vh",
                initialSort: [{column: "id", dir: "desc"}],
                columns: [
                    { title: "No", field: "id", width: 70, hozAlign: "center" },
                    { title: "회원", field: "member", width: 150, headerSort: false,
                        formatter: function(cell){
                            var m = cell.getRow().getData().member;
                            var name = m ? $('<div>').text(m.name).html() : '알 수 없음';
                            return `<span class="fw-bold">${name}</span> ${m ? genderBadge(m.gender) : ''}`;
                        }
                    },
                    { title: "테이블", field: "table_no", width: 100, hozAlign: "center",
                        formatter: function(cell){
                            var v = cell.getValue();
                            return (v != null) ? `<span class="badge bg-blue-lt">${v}번</span>` : '<span class="text-muted">미배정</span>';
                        }
                    },
                    { title: "요청 내용", field: "body", minWidth: 240,
                        formatter: function(cell){
                            return '<div style="white-space:pre-wrap;word-break:break-word;line-height:1.6;padding:6px 0;">'
                                + $('<div>').text(cell.getValue() || '').html() + '</div>';
                        },
                        variableHeight: true
                    },
                    { title: "요청 시각", field: "created_at", width: 120, hozAlign: "center",
                        formatter: function(cell){
                            if(!cell.getValue()){ return '-'; }
                            var s = new Date(cell.getValue()).toLocaleString('ko-KR');
                            return s.replace(/\s(오전|오후)/, '<br>$1');
                        }
                    },
                    { title: "상태", field: "read_at", width: 100, hozAlign: "center", headerSort: false,
                        formatter: function(cell){
                            var data = cell.getRow().getData();
                            if(cell.getValue()){ return '<span class="badge bg-green-lt">확인됨</span>'; }
                            var $btn = $("<button>").addClass("btn btn-sm btn-outline-primary w-100").text("확인")
                                .on("click", function(){ fn_read(data.id); });
                            return $btn.get(0);
                        }
                    },
                ],
                ajaxResponse: function(url, params, response){
                    var total = response.total ?? (response.data ? response.data.length : 0);
                    $('#rm-pageCount').text('총 ' + total + '건');
                    return response;
                },
            });
        }

        $(document).on('click', '#rm-readAll', async function(){
            if(await salert({text: '미확인 요청을 모두 확인 처리하시겠습니까?'})){
                $.ajax({
                    url: readAllUrl, type: 'PATCH',
                    success: function(res){
                        if(res?.status === 200){
                            reload();
                            if(window.AdminRequestsWidget){ window.AdminRequestsWidget.reload(); }
                        }
                    },
                    error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
                });
            }
        });

        return {
            search: function(){ reload(); },
            open: function(eventId, eventLabel){
                if(!eventId){ return; }
                currentEventId = eventId;
                $('#rm-eventLabel').text(eventLabel || '');
                $('#rm-searchForm')[0].reset();

                if(!modalEl){
                    modalEl = document.getElementById('requestsModal');
                    var ModalClass =
                        (window.tabler && window.tabler.Modal) ||
                        (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) ||
                        (window.bootstrap && window.bootstrap.Modal) ||
                        window.Modal;
                    if(!ModalClass){ console.error('Modal 클래스를 찾을 수 없습니다.'); return; }
                    bsModal = new ModalClass(modalEl);
                    modalEl.addEventListener('shown.bs.modal', function(){ isOpen = true; });
                    modalEl.addEventListener('hidden.bs.modal', function(){ isOpen = false; });
                    modalEl.addEventListener('shown.coreui.modal', function(){ isOpen = true; });
                    modalEl.addEventListener('hidden.coreui.modal', function(){ isOpen = false; });
                }

                bsModal.show();
                isOpen = true;

                setTimeout(function(){
                    if(!table){ buildTable(); }
                    reload();
                    if(table){ table.redraw(true); }
                }, 200);
            },
            // 플로팅 위젯에서 읽음 처리 시, 모달이 열려 있으면 동기화.
            reloadIfOpen: function(){ if(isOpen){ reload(); } },
        };
    })();
</script>
