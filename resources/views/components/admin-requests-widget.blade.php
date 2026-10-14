{{--
    회원 → 관리자 "요청하기" 플로팅 위젯.
    - 모든 관리자 페이지(layouts/app.blade.php)에 공통 포함되어 어디서든 보인다.
    - 우측 하단 플로팅 버튼 + 미확인 뱃지. 클릭 시 요청 목록 패널이 열린다.
    - Reverb(Pusher 프로토콜)로 'admin-requests' 공개 채널을 구독해 실시간 수신한다.
      (pusher-js 는 layouts/app.blade.php 에서 공통 로드됨)
--}}
@auth('admin')
@php
    $reqReverb = [
        'key'    => config('broadcasting.connections.reverb.key'),
        'host'   => env('VITE_REVERB_HOST', env('REVERB_HOST')),
        'port'   => (int) env('VITE_REVERB_PORT', env('REVERB_PORT', 443)),
        'scheme' => env('VITE_REVERB_SCHEME', env('REVERB_SCHEME', 'https')),
    ];
@endphp

<style>
    .ar-fab {
        position: fixed; right: 24px; bottom: 24px; z-index: 1055;
        width: 60px; height: 60px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: #d9a406 !important; border-color: #d9a406 !important; color: #fff;
        box-shadow: 0 8px 24px rgba(217,164,6,.4);
    }
    .ar-fab:hover { background: #b98a04 !important; border-color: #b98a04 !important; color: #fff;}
    .ar-fab .ar-badge {
        position: absolute; top: -5px; right: -5px;
        min-width: 22px; height: 22px; padding: 0 6px;
        border-radius: 11px; font-size: 12px; font-weight: 800; line-height: 22px;
        text-align: center; color: #fff; background: #206bc4; box-shadow: 0 0 0 2px #fff;
    }
    .ar-panel {
        position: fixed; right: 24px; bottom: 100px; z-index: 1055;
        width: 560px; max-width: calc(100vw - 32px); max-height: 80vh;
        display: none; flex-direction: column;
        background: #fff; border-radius: 16px; overflow: hidden;
        box-shadow: 0 12px 40px rgba(0,0,0,.25);
    }
    .ar-panel.show { display: flex; }
    .ar-panel-head { padding: 16px 18px; border-bottom: 1px solid #e9ecef; }
    .ar-panel-body { overflow-y: auto; padding: 14px; flex: 1; }
    .ar-item { border: 1px solid #e9ecef; border-radius: 12px; padding: 12px 14px; margin-bottom: 10px; }
    .ar-item.ar-unread { border-color: #cfe0ff; background: #f3f8ff; }
    .ar-empty { text-align: center; color: #adb5bd; padding: 40px 0; font-size: 14px; }
    .ar-table-no {
        display: inline-flex; align-items: center; gap: 3px;
        background: #206bc4; color: #fff; border-radius: 8px;
        padding: 2px 8px; font-size: 12px; font-weight: 700;
    }
    .ar-event {
        display: inline-flex; align-items: center;
        background: #f1f3f5; color: #495057; border: 1px solid #e9ecef;
        border-radius: 8px; padding: 3px 10px; font-size: 13px; font-weight: 600;
    }
</style>

<button type="button" id="arFab" class="ar-fab btn" title="회원 요청">
    <i class="ti ti-hand-stop fs-2"></i>
    <span class="ar-badge" id="arBadge" style="display:none;">0</span>
</button>

<div class="ar-panel" id="arPanel">
    <div class="ar-panel-head d-flex align-items-center justify-content-between">
        <div>
            <h3 class="mb-0 fs-3 fw-bold">
                회원 요청
                <span id="arLiveBadge" class="badge bg-secondary-lt ms-1" title="실시간 연결 상태">
                    <i class="ti ti-circle-filled me-1 text-muted" style="font-size:0.6em;"></i>대기
                </span>
            </h3>
        </div>
        <div class="d-flex gap-1">
            <button type="button" class="btn btn-sm btn-outline-success" id="arReadAll" title="모두 확인">
                모두 확인
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="arClose" title="닫기">
                <i class="ti ti-x"></i>
            </button>
        </div>
    </div>
    <div class="ar-panel-body" id="arList">
        <div class="ar-empty">불러오는 중…</div>
    </div>
</div>

<script>
    window.AdminRequestsWidget = (function(){
        var cfg = @json($reqReverb);
        var urls = {
            index:   "{{ route('admin.requests.index') }}",
            read:    "{{ url('admin/requests') }}/__ID__/read",
            readAll: "{{ route('admin.requests.readAll') }}",
        };
        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        var items = [];          // 현재 로드된 요청들 (최신 10개)
        var serverUnread = 0;    // 서버 기준 전체 미확인 개수
        var opened = false;

        var $fab, $panel, $badge, $list, $live;

        function esc(s){ return $('<div>').text(s == null ? '' : s).html(); }

        function genderBadge(g){
            if(g === 'F'){ return '<span class="badge bg-pink-lt">여</span>'; }
            if(g === 'M'){ return '<span class="badge bg-blue-lt">남</span>'; }
            return '';
        }

        function fmt(iso){
            if(!iso){ return ''; }
            try { return new Date(iso).toLocaleString('ko-KR'); } catch(e){ return ''; }
        }

        function unreadCount(){ return serverUnread; }

        function renderBadge(){
            var n = unreadCount();
            if(n > 0){ $badge.text(n > 99 ? '99+' : n).show(); }
            else { $badge.hide(); }
        }

        function renderList(){
            if(!items.length){
                $list.html('<div class="ar-empty">받은 요청이 없습니다.</div>');
                return;
            }
            var html = items.map(function(it){
                var name = it.member ? esc(it.member.name) : '알 수 없음';
                var g = it.member ? genderBadge(it.member.gender) : '';
                var ev = it.event_title
                    ? '<span class="ar-event"><i class="ti ti-calendar-event me-1"></i>' + esc(it.event_title) + '</span>'
                    : '';
                var tbl = (it.table_no != null)
                    ? '<span class="ar-table-no"><i class="ti ti-armchair-2"></i>' + it.table_no + '번 테이블</span>'
                    : '<span class="text-muted" style="font-size:12px;">테이블 미배정</span>';
                return `
                    <div class="ar-item ${it.read_at ? '' : 'ar-unread'}" data-id="${it.id}">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="fw-bold fs-3">${name}</span> ${g} ${tbl}
                            </div>
                            <small class="text-muted">${fmt(it.created_at)}</small>
                        </div>
                        ${ev ? '<div class="mt-1">' + ev + '</div>' : ''}
                        <div class="my-3 py-1" style="white-space:pre-wrap;word-break:break-word;line-height:1.6;">${esc(it.body)}</div>
                        <div class="d-flex gap-1 mt-2">
                            ${it.read_at
                                ? '<span class="badge bg-green-lt">확인됨</span>'
                                : '<button type="button" class="btn btn-sm btn-outline-primary ar-read">확인</button>'}
                        </div>
                    </div>`;
            }).join('');
            $list.html(html);
        }

        function setLive(state){
            if(state === 'on'){ $live.removeClass('bg-secondary-lt bg-red-lt').addClass('bg-green-lt').html('<i class="ti ti-circle-filled me-1 text-green" style="font-size:0.6em;"></i>연결됨'); }
            else if(state === 'off'){ $live.removeClass('bg-secondary-lt bg-green-lt').addClass('bg-red-lt').html('<i class="ti ti-circle-filled me-1 text-red" style="font-size:0.6em;"></i>끊김'); }
            else { $live.removeClass('bg-green-lt bg-red-lt').addClass('bg-secondary-lt').html('<i class="ti ti-circle-filled me-1 text-muted" style="font-size:0.6em;"></i>대기'); }
        }

        // 전역 $.ajaxSetup 의 "처리 중..." 로더를 띄우지 않는 조용한 ajax 래퍼.
        // (beforeSend/complete 를 no-op 으로 덮어쓰고 global 이벤트도 끈다)
        function silentAjax(opts){
            return $.ajax($.extend({
                global: false,
                beforeSend: function(){},
                complete: function(){},
                headers: { 'X-CSRF-TOKEN': csrf },
            }, opts));
        }

        function load(){
            silentAjax({
                url: urls.index, type: 'GET', data: { limit: 10 },
                success: function(res){
                    items = (res?.requests || []).slice(0, 10);
                    serverUnread = res?.unreadCount ?? items.filter(function(it){ return !it.read_at; }).length;
                    renderBadge();
                    if(opened){ renderList(); }
                }
            });
        }

        function openPanel(){ opened = true; $panel.addClass('show'); renderList(); }
        function closePanel(){ opened = false; $panel.removeClass('show'); }

        function markRead(id){
            silentAjax({
                url: urls.read.replace('__ID__', id), type: 'PATCH',
                success: function(){
                    var it = items.find(function(x){ return x.id === id; });
                    if(it && !it.read_at){ it.read_at = new Date().toISOString(); serverUnread = Math.max(0, serverUnread - 1); }
                    renderBadge(); renderList();
                    if(window.RequestsModal){ window.RequestsModal.reloadIfOpen(); }
                }
            });
        }

        function markAllRead(){
            silentAjax({
                url: urls.readAll, type: 'PATCH',
                success: function(){
                    var now = new Date().toISOString();
                    items.forEach(function(it){ if(!it.read_at){ it.read_at = now; } });
                    serverUnread = 0;
                    renderBadge(); renderList();
                    if(window.RequestsModal){ window.RequestsModal.reloadIfOpen(); }
                }
            });
        }

        function handleIncoming(d){
            if(!d || !d.id){ return; }
            if(items.some(function(x){ return x.id === d.id; })){ return; }
            items.unshift(d);
            if(items.length > 10){ items = items.slice(0, 10); }
            if(!d.read_at){ serverUnread += 1; }
            renderBadge();
            if(opened){ renderList(); }
            // 패널이 닫혀 있으면 버튼을 잠깐 흔들어 주의를 끈다.
            if(!opened){
                $fab.addClass('animate-pulse');
                setTimeout(function(){ $fab.removeClass('animate-pulse'); }, 2000);
            }
        }

        function initRealtime(){
            if(typeof Pusher === 'undefined' || !cfg.key || !cfg.host){ setLive('off'); return; }
            try {
                var pusher = new Pusher(cfg.key, {
                    wsHost: cfg.host,
                    wsPort: cfg.port || 80,
                    wssPort: cfg.port || 443,
                    forceTLS: cfg.scheme === 'https',
                    enabledTransports: ['ws', 'wss'],
                    disableStats: true,
                    cluster: '',
                });
                pusher.connection.bind('connected', function(){ setLive('on'); });
                pusher.connection.bind('disconnected', function(){ setLive('off'); });
                pusher.connection.bind('unavailable', function(){ setLive('off'); });
                pusher.connection.bind('error', function(){ setLive('off'); });

                var channel = pusher.subscribe('admin-requests');
                channel.bind('request.sent', handleIncoming);
            } catch(e){
                console.error('요청 위젯 Reverb 연결 실패:', e);
                setLive('off');
            }
        }

        $(document).ready(function(){
            $fab   = $('#arFab');
            $panel = $('#arPanel');
            $badge = $('#arBadge');
            $list  = $('#arList');
            $live  = $('#arLiveBadge');

            $fab.on('click', function(){ opened ? closePanel() : openPanel(); });
            $('#arClose').on('click', closePanel);
            $('#arReadAll').on('click', markAllRead);

            // 목록 내 버튼 (위임)
            $list.on('click', '.ar-read', function(){ markRead(parseInt($(this).closest('.ar-item').data('id'), 10)); });

            load();
            initRealtime();
        });

        // 외부(요청 모달 등)에서 읽음 처리 후 플로팅 위젯을 서버 기준으로 갱신.
        return {
            reload: function(){ load(); }
        };
    })();
</script>
@endauth
