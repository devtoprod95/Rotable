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
    $rmReverb = [
        'key'    => config('broadcasting.connections.reverb.key'),
        'host'   => env('VITE_REVERB_HOST', env('REVERB_HOST')),
        'port'   => (int) env('VITE_REVERB_PORT', env('REVERB_PORT', 443)),
        'scheme' => env('VITE_REVERB_SCHEME', env('REVERB_SCHEME', 'https')),
    ];
@endphp

<div class="modal modal-blur fade" id="requestsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3 px-4 align-items-start">
                <div>
                    <h5 class="modal-title mb-0">
                        <span id="rm-titleText">회원 요청</span>
                        <span id="rm-liveBadge" class="badge bg-secondary-lt ms-1" title="실시간 연결 상태">
                            <i class="ti ti-circle-filled me-1 text-muted" style="font-size:0.6em;"></i>대기
                        </span>
                    </h5>
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
        var reverbCfg = @json($rmReverb);

        var table = null;
        var currentEventId = null;
        var currentMemberId = null;
        var modalEl = null;
        var bsModal = null;
        var isOpen = false;
        var realtimeStarted = false;   // Reverb 연결은 최초 1회만 수행
        var $live = null;

        function genderBadge(g){
            if(g === 'F'){ return '<span class="badge bg-pink-lt">여</span>'; }
            if(g === 'M'){ return '<span class="badge bg-blue-lt">남</span>'; }
            return '';
        }

        function filters(){
            return {
                eventId: currentEventId || '',
                memberId: currentMemberId || '',
                read: $('#rm-searchForm [name=read]').val(),
                searchText: $('#rm-searchForm [name=searchText]').val(),
            };
        }

        function reload(){ if(table){ table.setData(listUrl, filters()); } }

        // 실시간 연결 상태 뱃지 토글 (위젯과 동일 패턴).
        function setLive(state){
            if(!$live){ $live = $('#rm-liveBadge'); }
            if(state === 'on'){ $live.removeClass('bg-secondary-lt bg-red-lt').addClass('bg-green-lt').html('<i class="ti ti-circle-filled me-1 text-green" style="font-size:0.6em;"></i>실시간 연결됨'); }
            else if(state === 'off'){ $live.removeClass('bg-secondary-lt bg-green-lt').addClass('bg-red-lt').html('<i class="ti ti-circle-filled me-1 text-red" style="font-size:0.6em;"></i>끊김'); }
            else { $live.removeClass('bg-green-lt bg-red-lt').addClass('bg-secondary-lt').html('<i class="ti ti-circle-filled me-1 text-muted" style="font-size:0.6em;"></i>대기'); }
        }

        // Reverb(Pusher 프로토콜) 'admin-requests' 공개 채널 구독.
        // 연결 상태를 뱃지에 반영하고, 현재 열람 중인 행사의 새 요청이 오면 테이블을 갱신한다.
        function initRealtime(){
            if(realtimeStarted){ return; }
            realtimeStarted = true;
            if(typeof Pusher === 'undefined' || !reverbCfg.key || !reverbCfg.host){ setLive('off'); return; }
            try {
                var pusher = new Pusher(reverbCfg.key, {
                    wsHost: reverbCfg.host,
                    wsPort: reverbCfg.port || 80,
                    wssPort: reverbCfg.port || 443,
                    forceTLS: reverbCfg.scheme === 'https',
                    enabledTransports: ['ws', 'wss'],
                    disableStats: true,
                    cluster: '',
                });
                pusher.connection.bind('connected', function(){ setLive('on'); });
                pusher.connection.bind('disconnected', function(){ setLive('off'); });
                pusher.connection.bind('unavailable', function(){ setLive('off'); });
                pusher.connection.bind('error', function(){ setLive('off'); });

                var channel = pusher.subscribe('admin-requests');
                channel.bind('request.sent', function(d){
                    // 모달이 열려 있고, 수신 요청이 현재 열람 중인 행사와 같을 때만 갱신.
                    if(isOpen && d && (!currentEventId || String(d.event_id) === String(currentEventId))){
                        reload();
                    }
                });
            } catch(e){
                console.error('요청 모달 Reverb 연결 실패:', e);
                setLive('off');
            }
        }

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
                ajaxParams: filters,
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
            open: function(eventId, eventLabel, options){
                if(!eventId){ return; }
                var opts = options || {};
                currentEventId = eventId;
                currentMemberId = opts.memberId || null;
                $('#rm-eventLabel').text(eventLabel || '');
                $('#rm-titleText').text(currentMemberId ? '보낸 요청' : '회원 요청');
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
                    // 모달이 완전히 표시된 뒤에 테이블을 생성/갱신한다. (숨김 상태 생성 시 레이아웃 null 에러 방지)
                    var onShown = function(){
                        isOpen = true;
                        if(!table){
                            buildTable(); // 생성 시 ajaxParams(filters)로 자동 로드되므로 reload 불필요
                        } else {
                            reload();     // 재오픈: 현재 필터로 다시 로드
                        }
                        var el = document.getElementById('rm-table');
                        if(table && el && el.offsetParent !== null){ try { table.redraw(true); } catch(e){} }
                    };
                    modalEl.addEventListener('shown.bs.modal', onShown);
                    modalEl.addEventListener('shown.coreui.modal', onShown);
                    modalEl.addEventListener('hidden.bs.modal', function(){ isOpen = false; });
                    modalEl.addEventListener('hidden.coreui.modal', function(){ isOpen = false; });
                }

                bsModal.show();
                isOpen = true;

                initRealtime();   // 최초 1회 Reverb 연결

                // 이미 열려 있으면 shown 이 재발생하지 않으므로 보이는 경우 즉시 갱신.
                var elNow = document.getElementById('rm-table');
                if(elNow && elNow.offsetParent !== null){
                    if(!table){ buildTable(); } // 생성 시 자동 로드
                    else { reload(); }
                    try { table.redraw(true); } catch(e){}
                }
            },
            // 플로팅 위젯에서 읽음 처리 시, 모달이 열려 있으면 동기화.
            reloadIfOpen: function(){ if(isOpen){ reload(); } },
        };
    })();
</script>
