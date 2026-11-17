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

<style>
    /* 회원 요청 — 표(라이브러리) 대신 카드 목록. 모바일 1열 / 992px↑ 2열.
       카드: ① 회원·테이블·상태(확인 버튼)  ② 요청 내용(말풍선)  ③ 관리자 답장 + 답장 버튼  ④ 요청 시각 */
    #requestsModal .rq-list{ display:grid; grid-template-columns:1fr; gap:.6rem; }
    @media (min-width: 992px){ #requestsModal .rq-list{ grid-template-columns:1fr 1fr; } }
    #requestsModal .rq-card{ border:1px solid #e9ecf1; border-radius:14px; background:#fff; padding:.75rem .85rem; box-shadow:0 1px 3px rgba(20,30,60,.05); }
    #requestsModal .rq-card.is-unread{ border-color:#f4c94a; box-shadow:0 0 0 2px rgba(244,201,74,.25); }
    #requestsModal .rq-head{ display:flex; align-items:center; flex-wrap:wrap; gap:.35rem; }
    #requestsModal .rq-name{ font-weight:800; overflow-wrap:anywhere; }
    #requestsModal .rq-status{ margin-left:auto; }
    #requestsModal .rq-body{ margin-top:.5rem; padding:.6rem .75rem; border-radius:12px; background:#fff8e1; border-left:4px solid #f4b400;
        white-space:pre-wrap; word-break:break-word; line-height:1.6; font-size:.95rem; color:#212529; }
    #requestsModal .rq-reply{ margin-top:.5rem; padding:.55rem .75rem; border-radius:12px; background:#f1f5f9; border-left:4px solid #94a3b8; }
    #requestsModal .rq-reply-meta{ font-size:.76rem; color:#64748b; font-weight:700; margin-bottom:.15rem; }
    #requestsModal .rq-reply-text{ white-space:pre-wrap; word-break:break-word; line-height:1.6; font-size:.92rem; }
    #requestsModal .rq-foot{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.4rem; margin-top:.55rem; }
    #requestsModal .rq-time{ font-size:.78rem; color:#868e96; }
    #requestsModal .rq-foot .btn{ min-height:36px; }
    #requestsModal .rq-empty{ grid-column:1 / -1; text-align:center; color:#868e96; padding:2.5rem 0; }
    @media (max-width: 575.98px){ #requestsModal .rq-foot .btn{ flex:1 1 auto; } }
</style>

<div class="modal fade" id="requestsModal" tabindex="-1" aria-hidden="true">
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
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-auto">
                            <label class="form-label mb-1">확인 여부</label>
                            <select class="form-select" name="read">
                                <option value="">전체</option>
                                <option value="Y">확인됨</option>
                                <option value="N">미확인</option>
                            </select>
                        </div>
                        <div class="col-12 col-md">
                            <label class="form-label mb-1">검색어</label>
                            <input type="text" class="form-control" name="searchText"
                                   placeholder="회원 이름 / 요청 내용 검색"
                                   onkeydown="if(event.keyCode === 13){ event.preventDefault(); RequestsModal.search(); }">
                        </div>
                        <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-primary flex-fill px-3" onclick="RequestsModal.search()">
                                <i class="ti ti-search me-1"></i> 검색
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill px-3" onclick="RequestsModal.search()">
                                <i class="ti ti-refresh me-1"></i> 새로고침
                            </button>
                            <button type="button" class="btn btn-outline-success flex-fill px-3" id="rm-readAll">모두 확인</button>
                        </div>
                    </div>
                </form>

                <div class="d-flex align-items-center gap-2 mb-2">
                    <span id="rm-pageCount" class="badge bg-secondary-lt"></span>
                    <span class="text-muted small">최신순 · 미확인은 노란 테두리</span>
                </div>

                <div id="rm-list" class="rq-list" aria-live="polite"></div>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-outline-secondary d-none" id="rm-moreBtn" onclick="RequestsModal.more()">
                        <i class="ti ti-chevron-down me-1"></i>더 보기
                    </button>
                </div>
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

        function reload(){ loadList(true); }

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

        // ===== 목록 (표 라이브러리 없이 카드로 직접 렌더) =====
        var PAGE_SIZE = 50;
        var items = [];
        var total = 0, page = 0, lastPage = 0, loadingList = false;

        function esc(v){ return $('<div>').text(v == null ? '' : v).html(); }
        function fmt(v){
            if(!v){ return ''; }
            var d = new Date(v);
            if(isNaN(d.getTime())){ return ''; }
            var p = function(n){ return (n < 10 ? '0' : '') + n; };
            return (d.getMonth() + 1) + '/' + d.getDate() + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
        }
        function card(d){
            var m = d.member;
            var name = m ? esc(m.label || (m.name + '호')) : '알 수 없음';
            var table = (d.table_no != null)
                ? '<span class="badge bg-azure-lt"><i class="ti ti-armchair me-1"></i>' + esc(d.table_no) + '번 테이블</span>'
                : '<span class="badge bg-secondary-lt">테이블 미배정</span>';
            var status = d.read_at
                ? '<span class="badge bg-green-lt rq-status"><i class="ti ti-check me-1"></i>확인됨</span>'
                : '<span class="badge bg-yellow-lt rq-status"><i class="ti ti-bell me-1"></i>미확인</span>';
            var reply = '';
            if(d.reply_body){
                var when = d.reply_edited_at ? fmt(d.reply_edited_at) + ' (수정됨)' : fmt(d.replied_at);
                var meta = ['관리자 답장', d.replied_by || '', when].filter(Boolean).join(' · ');
                reply = '<div class="rq-reply"><div class="rq-reply-meta"><i class="ti ti-message-reply me-1"></i>' + esc(meta) + '</div>'
                      + '<div class="rq-reply-text">' + esc(d.reply_body) + '</div></div>';
            }
            var readBtn = d.read_at ? ''
                : '<button type="button" class="btn btn-sm btn-primary rq-read-btn" data-id="' + d.id + '"><i class="ti ti-check me-1"></i>확인</button>';
            var replyBtn = '<button type="button" class="btn btn-sm btn-outline-warning rq-reply-btn" data-id="' + d.id + '">'
                         + '<i class="ti ti-message-reply me-1"></i>' + (d.reply_body ? '답장 수정' : '답장') + '</button>';
            return '<div class="rq-card' + (d.read_at ? '' : ' is-unread') + '" data-id="' + d.id + '">'
                 +   '<div class="rq-head"><span class="rq-name">' + name + '</span>' + (m ? genderBadge(m.gender) : '') + table + status + '</div>'
                 +   '<div class="rq-body">' + esc(d.body || '') + '</div>'
                 +   reply
                 +   '<div class="rq-foot"><span class="rq-time"><i class="ti ti-clock me-1"></i>' + esc(fmt(d.created_at)) + ' · #' + d.id + '</span>'
                 +     '<span class="d-flex gap-2 flex-grow-1 flex-sm-grow-0 justify-content-end">' + replyBtn + readBtn + '</span></div>'
                 + '</div>';
        }
        function renderList(){
            var $l = $('#rm-list');
            $l.html(items.length ? items.map(card).join('') : '<div class="rq-empty">요청이 없어요.</div>');
            $('#rm-pageCount').text('총 ' + total + '건');
            $('#rm-moreBtn').toggleClass('d-none', page >= lastPage);
        }
        function loadList(reset){
            if(!currentEventId || loadingList){ return; }
            loadingList = true;
            var nextPage = reset ? 1 : page + 1;
            if(reset && !items.length){ $('#rm-list').html('<div class="rq-empty">불러오는 중…</div>'); }
            // 화면 안에 "불러오는 중"을 보여주므로 전역 로딩 팝업(ajaxSetup beforeSend/complete)은 끈다.
            $.ajax({
                url: listUrl, type: 'GET', data: $.extend({ page: nextPage, size: PAGE_SIZE }, filters()),
                beforeSend: $.noop, complete: $.noop,
            }).done(function(res){
                var rows = (res && res.data) ? res.data : [];
                items = reset ? rows : items.concat(rows.filter(function(r){ return !items.some(function(x){ return x.id === r.id; }); }));
                total = res?.total ?? items.length;
                page = res?.current_page ?? nextPage;
                lastPage = res?.last_page ?? page;
                renderList();
            }).fail(function(){
                if(reset){ $('#rm-list').html('<div class="rq-empty text-danger">불러오지 못했어요.</div>'); }
            }).always(function(){ loadingList = false; });
        }

        // 카드 버튼 (목록이 다시 그려지므로 위임)
        $(document).on('click', '#requestsModal .rq-read-btn', function(){ fn_read($(this).data('id')); });
        $(document).on('click', '#requestsModal .rq-reply-btn', function(){
            var id = $(this).data('id');
            var data = items.find(function(x){ return String(x.id) === String(id); });
            if(data && window.AdminRequestsWidget){ window.AdminRequestsWidget.openReply(data, function(){ reload(); }); }
        });

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
            more: function(){ loadList(false); },
            open: function(eventId, eventLabel, options){
                if(!eventId){ return; }
                var opts = options || {};
                currentEventId = eventId;
                currentMemberId = opts.memberId || null;
                $('#rm-eventLabel').text(eventLabel || '');
                $('#rm-titleText').text(currentMemberId ? '보낸 요청' : '회원 요청');
                $('#rm-searchForm')[0].reset();
                items = []; total = 0; page = 0; lastPage = 0;
                $('#rm-list').empty(); $('#rm-pageCount').text(''); $('#rm-moreBtn').addClass('d-none');

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
                        reload();
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
                var elNow = document.getElementById('rm-list');
                if(elNow && elNow.offsetParent !== null){ reload(); }
            },
            // 플로팅 위젯에서 읽음 처리 시, 모달이 열려 있으면 동기화.
            reloadIfOpen: function(){ if(isOpen){ reload(); } },
        };
    })();
</script>
