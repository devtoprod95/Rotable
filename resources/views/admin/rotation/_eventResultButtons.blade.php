{{--
    행사 운영 화면 공통 "결과 보기" 버튼: [전체 매칭] [매칭 결과] [메시지] [요청]
    (행사 상세 · 체크인 · 로테이션 진행 · 페이백 · 매출에서 같은 모양/동작으로 쓴다.)

    - 버튼(공통 .eventform-btn-grid: 모바일 2열) + 4개 조회 모달(_messagesModal/_matchesModal/_matchedModal/_requestsModal) + 건수 표시 스크립트를 한 번에 가져온다.
      모달은 이 파일에서만 include 하므로 쓰는 화면에서 따로 include 하지 않는다.
    - 반드시 `.row` 안에서 `@include` 한다. (col-12 한 칸을 그린다)

    파라미터:
      $eventId      (int)          대상 행사 id
      $eventLabel   (string)       모달 제목에 쓸 행사 라벨
      $showList     (bool=false)   true 면 [목록] 버튼을 함께 그린다. (행사 상세 전용)
      $eventSelect  (string|null)  행사가 화면 안에서 바뀌는 경우 행사 id 를 읽을 셀렉트 선택자 (예: '#eventSelect').
                                   없으면 $eventId 고정.

    JS: window.EventResultButtons.refresh() — 건수(매칭 쌍/메시지/요청)를 다시 불러온다.
--}}
@php
    $showList    = $showList ?? false;
    $eventSelect = $eventSelect ?? null;
@endphp

<div class="col-12">
    <div class="eventform-btn-grid">
        <button type="button" id="goMatchesBtn" class="btn btn-outline-pink">
            <i class="ti ti-heart me-1"></i><span>전체 매칭</span>
        </button>
        <button type="button" id="goMatchedBtn" class="btn btn-outline-pink">
            <i class="ti ti-heart-filled me-1"></i><span>매칭 결과<span id="matchedCount"></span></span>
        </button>
        <button type="button" id="goMessagesBtn" class="btn btn-outline-primary">
            <i class="ti ti-messages me-1"></i><span>메시지<span id="messagesCount"></span></span>
        </button>
        <button type="button" id="goRequestsBtn" class="btn btn-outline-yellow">
            <i class="ti ti-hand-stop me-1"></i><span>요청<span id="requestsCount"></span></span>
        </button>
        @if ($showList)
            <a href="{{ route('admin.rotation.event.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-list me-1"></i><span>목록</span>
            </a>
        @endif
    </div>

    {{-- 조회 모달 (이 화면 안에서 열린다) --}}
    @include('admin.rotation._messagesModal')
    @include('admin.rotation._matchesModal')
    @include('admin.rotation._matchedModal')
    @include('admin.rotation._requestsModal')

    <script>
        window.EventResultButtons = (function(){
            var fixedEventId   = {{ (int) $eventId }};
            var fixedLabel     = @json($eventLabel);
            var eventSelectSel = @json($eventSelect);
            var matchesUrlTpl  = @json(route('admin.rotation.event.matches.list', ['id' => '__ID__']));
            var messagesUrlTpl = @json(route('admin.rotation.event.messages.list', ['id' => '__ID__']));
            var requestsUrl    = @json(route('admin.requests.list'));

            // 현재 행사 id / 라벨. 셀렉트가 있으면 선택값을, 없으면 고정값을 쓴다.
            function current(){
                if(eventSelectSel){
                    var $sel = $(eventSelectSel);
                    return { id: $sel.val(), label: $sel.find('option:selected').text() };
                }
                return { id: fixedEventId, label: fixedLabel };
            }

            function setCount(sel, cnt, unit){
                $(sel).text((cnt > 0) ? ' (' + cnt + unit + ')' : '');
            }

            // 목록 조회는 화면에 "처리 중..." 전역 로딩을 띄우지 않는다.
            function quietGet(url, data, done, fail){
                $.ajax({ url: url, type: 'GET', data: data, beforeSend: $.noop, complete: $.noop }).done(done).fail(fail || $.noop);
            }

            function refresh(){
                var id = current().id;
                if(!id){ $('#matchedCount, #messagesCount, #requestsCount').text(''); return; }

                // 매칭 쌍 수 (남자 기준 중복 제거된 쌍)
                quietGet(matchesUrlTpl.replace('__ID__', id), { matched: 1 }, function(res){
                    setCount('#matchedCount', res?.total ?? (res?.data ? res.data.length : 0), '쌍');
                }, function(){ $('#matchedCount').text(''); });

                // 전체 메시지 수
                quietGet(messagesUrlTpl.replace('__ID__', id), { size: 1 }, function(res){
                    setCount('#messagesCount', res?.total ?? 0, '건');
                }, function(){ $('#messagesCount').text(''); });

                // 전체 요청 수
                quietGet(requestsUrl, { eventId: id, size: 1 }, function(res){
                    setCount('#requestsCount', res?.total ?? 0, '건');
                }, function(){ $('#requestsCount').text(''); });
            }

            function bind(sel, modal){
                $(sel).on('click', function(){
                    var c = current();
                    if(!c.id){ return; }
                    window[modal].open(c.id, c.label);
                });
            }

            $(function(){
                bind('#goMatchesBtn',  'MatchesModal');
                bind('#goMatchedBtn',  'MatchedModal');
                bind('#goMessagesBtn', 'MessagesModal');
                bind('#goRequestsBtn', 'RequestsModal');
                refresh();
            });

            return { refresh: refresh };
        })();
    </script>
</div>
