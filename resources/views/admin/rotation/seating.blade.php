@extends('layouts.app')

@section('title', '로테이션 진행')

@section('content')
<style>
    /* Choices 드롭다운이 턴 네비게이션 등 다른 요소 위로 오도록 z-index 최상단 처리.
       열린 셀렉트의 카드가 다른 카드/버튼보다 위에 쌓이도록 함께 올린다. */
    .choices { position: relative; }
    .choices.is-open { z-index: 1080; }
    .choices__list--dropdown,
    .choices__list[aria-expanded] {
        z-index: 1080 !important;
    }
    /* 열린 셀렉트를 품은 카드가 형제 카드 위로 오도록 */
    .card:has(.choices.is-open) { position: relative; z-index: 1080; }

    /* 칩 제거(x) 버튼: 클릭 영역을 넓히고 hover 시 배경 피드백 */
    .seat-chips .chip-remove { transition: background-color .12s ease; cursor: pointer; }
    .seat-chips .chip-remove:hover { background-color: rgba(214, 57, 57, 0.15); }

    /* 상단 결과조회 버튼: 데스크톱은 한 줄, 모바일은 2열 그리드로 가지런히 */
    .seating-result-grid { display: flex; gap: .5rem; flex-wrap: wrap; }
    @media (max-width: 767.98px){
        .card-header .seating-actions { width: 100%; }
        .seating-result-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; width: 100%; }
        .seating-result-grid .btn { width: 100%; }
    }
</style>

{{-- 행사 운영 공용 탭바: 행사 선택 + [행사 정보][체크인][로테이션 진행] --}}
<div class="mt-2">
    <x-event-tabs :event-id="$eventId" active="progress" />
</div>

<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h3 class="card-title mb-0">로테이션 진행</h3>
                    <span class="badge d-none" id="eventStatusBadge"></span>
                </div>
                {{-- 결과 조회(보조): 진행 중 참고용. 모바일에선 2열 그리드로 정렬 --}}
                <div class="d-flex align-items-center gap-2 seating-actions">
                    <span class="text-secondary small d-none d-lg-inline">결과 보기</span>
                    <div class="seating-result-grid">
                        <button type="button" id="goMatchesBtn" class="btn btn-sm btn-outline-pink">
                            <i class="ti ti-heart me-1"></i> 전체 매칭
                        </button>
                        <button type="button" id="goMatchedBtn" class="btn btn-sm btn-outline-pink">
                            <i class="ti ti-heart-filled me-1"></i> 매칭 결과<span id="matchedCount"></span>
                        </button>
                        <button type="button" id="goMessagesBtn" class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-messages me-1"></i> 메시지<span id="messagesCount"></span>
                        </button>
                        <button type="button" id="goRequestsBtn" class="btn btn-sm btn-outline-yellow">
                            <i class="ti ti-hand-stop me-1"></i> 요청<span id="requestsCount"></span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                {{-- 행사 선택은 상단 탭바가 맡는다. 이 화면의 스크립트가 값을 읽으므로 숨겨서 유지한다. --}}
                <select class="d-none" id="eventSelect" aria-hidden="true" tabindex="-1">
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}" {{ $eventId == $event->id ? 'selected' : '' }}>{{ $event->label }}</option>
                    @endforeach
                </select>

                <div class="row g-3 align-items-start">
                    <div class="col-md-4">
                        <label class="form-label">턴</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" id="roundPrev" title="이전 턴">
                                <i class="ti ti-chevron-left"></i>
                            </button>
                            <input type="number" min="0" class="form-control text-center" id="roundInput" value="{{ $roundNo }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="roundNext" title="다음 턴">
                                <i class="ti ti-chevron-right"></i>
                            </button>
                        </div>
                        <small class="form-hint text-muted" id="roundHint">턴을 이동하며 배치를 볼 수 있습니다.</small>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label d-none d-md-block" id="actionLabel">&nbsp;</label>
                        <div class="d-flex align-items-center" style="min-height: 38px;">
                            <button type="button" class="btn btn-primary w-100 d-none" id="rotateBtn">
                                <i class="ti ti-rotate-clockwise me-1"></i>로테이션 진행
                            </button>
                            <button type="button" class="btn btn-success w-100 d-none" id="finishBtn">
                                <i class="ti ti-checkbox me-1"></i>매칭 시작
                            </button>
                            <button type="button" class="btn btn-danger w-100 d-none" id="closeVotingBtn">
                                <i class="ti ti-flag-check me-1"></i>매칭 종료
                            </button>
                            <span class="btn btn-outline-secondary w-100 d-none disabled" id="closedBtn">
                                <i class="ti ti-lock me-1"></i>종료된 행사
                            </span>
                            <div class="d-none align-items-center justify-content-center text-danger fw-bold text-center w-100" id="pastNotice">
                                최신 턴에서만 로테이션을 진행할 수 있습니다.
                            </div>
                        </div>
                        {{-- 로테이션 되돌리기 (실수로 한 번 더 진행했을 때). 진행 중(S)·1턴 이상에서만 노출 --}}
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 mt-2 d-none" id="rotateBackBtn">
                            <i class="ti ti-arrow-back-up me-1"></i>로테이션 되돌리기 (이전 턴)
                        </button>
                        {{-- 종료 되돌리기 (실수로 매칭을 종료했을 때). 종료(X) 상태에서만 노출 --}}
                        <button type="button" class="btn btn-outline-dark btn-sm w-100 mt-2 d-none" id="reopenBtn">
                            <i class="ti ti-lock-open me-1"></i>종료 되돌리기 (이전 상태로)
                        </button>
                        {{-- 매칭 시작 취소 (실수로 매칭을 시작했을 때). 매칭중(V)·와인파티에서만 노출. 기존 투표는 유지 --}}
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 mt-2 d-none" id="cancelVotingBtn">
                            <i class="ti ti-arrow-back-up me-1"></i>매칭 시작 취소 (진행으로)
                        </button>
                        <small class="form-hint text-muted mt-2 d-block" id="actionHint"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div id="boardArea" class="row row-cards"></div>
    </div>
</div>

<script>
    $(document).ready(function(){
        const dataUrl    = "{{ route('admin.rotation.seating.data') }}";
        const storeUrl   = "{{ route('admin.rotation.seating.store') }}";
        const deleteUrl  = "{{ route('admin.rotation.seating.delete') }}";
        const rotateUrl  = "{{ route('admin.rotation.seating.rotate') }}";
        const rotateBackUrl = "{{ route('admin.rotation.seating.rotateBack') }}";
        const finishUrl  = "{{ route('admin.rotation.seating.finish') }}";
        const closeVotingUrl = "{{ route('admin.rotation.seating.closeVoting') }}";
        const cancelVotingUrl = "{{ route('admin.rotation.seating.cancelVoting') }}";
        const reopenUrl  = "{{ route('admin.rotation.seating.reopen') }}";
        const matchesUrlTemplate = "{{ route('admin.rotation.event.matches.list', ['id' => '__ID__']) }}";
        const messagesUrlTemplate = "{{ route('admin.rotation.event.messages.list', ['id' => '__ID__']) }}";
        const requestsListUrl = "{{ route('admin.requests.list') }}";

        // 서버 enum 기반 상태 라벨 (P/S/V/X). 뱃지 표기에 사용.
        const STATUS_LABEL = @json($statuses);
        // 상태별 Tabler 뱃지 색상 클래스. 종료(X)는 검은 뱃지(bg-dark + 흰 글씨).
        const STATUS_BADGE = { P: 'bg-yellow-lt', S: 'bg-green-lt', V: 'bg-pink-lt', X: 'bg-dark text-white' };
        // 상태 뱃지에 쓰일 수 있는 색상 클래스 전부 (갱신 시 먼저 제거할 목록)
        const STATUS_BADGE_CLASSES = 'bg-yellow-lt bg-green-lt bg-pink-lt bg-secondary-lt bg-secondary bg-dark text-white';

        // 현재 행사 상태 뱃지를 최신 상태로 갱신한다.
        function renderStatusBadge(status){
            let $badge = $('#eventStatusBadge');
            if(!status || !STATUS_LABEL[status]){
                $badge.addClass('d-none').text('');
            } else {
                let cls = STATUS_BADGE[status] || 'bg-secondary';
                $badge.removeClass(STATUS_BADGE_CLASSES).removeClass('d-none')
                      .addClass(cls)
                      .text('현재 상태: ' + STATUS_LABEL[status]);
            }
            // 상단 탭바(event-tabs)의 상태 표시들도 함께 갱신해, 로테이션 진행/매칭 시작·종료로
            // 상태가 바뀔 때 화면의 모든 상태 표기가 어긋나지 않게 한다.
            syncEventTabsStatus(status);
        }

        // 상단 탭바(event-tabs 컴포넌트)의 상태 표기를 현재 상태/턴에 맞춰 갱신한다.
        //  - 가운데 상태 뱃지(#eventTabsStatusBadge)의 색/라벨
        //  - '로테이션 진행' 탭의 턴 뱃지([data-event-tabs-round]) — 종료면 '종료', 아니면 'N턴'/'진행 전'
        //  - 숨은 행사 선택(#eventTabsSelect)의 선택된 옵션 라벨 접두 [상태]
        function syncEventTabsStatus(status){
            if(status && STATUS_LABEL[status]){
                let cls = STATUS_BADGE[status] || 'bg-secondary-lt';
                $('#eventTabsStatusBadge')
                    .removeClass(STATUS_BADGE_CLASSES)
                    .addClass(cls)
                    .text(STATUS_LABEL[status]);
            }

            // 턴 뱃지: 종료(X)면 '종료', 매칭중(V)면 '매칭중'(턴 수 대신 상태 표기),
            // 그 외엔 1턴 이상이면 'N턴', 아니면 '진행 전'
            $('[data-event-tabs-round]').text(
                status === 'X' ? '종료'
                    : (status === 'V' ? '매칭중'
                        : (currentRound > 0 ? (currentRound + '턴') : '진행 전'))
            );

            // 선택된 옵션 라벨의 [상태] 접두를 갱신한다. (형식: "[상태] 제목 · 일시")
            if(status && STATUS_LABEL[status]){
                let $opt = $('#eventTabsSelect option:selected');
                if($opt.length){
                    let text = $opt.text();
                    let replaced = text.replace(/^\s*\[[^\]]*\]/, '[' + STATUS_LABEL[status] + ']');
                    // 원래 접두가 없던 경우엔 앞에 붙인다.
                    $opt.text(replaced === text && !/^\s*\[/.test(text) ? ('[' + STATUS_LABEL[status] + '] ' + text) : replaced);
                }
            }
        }

        // 액션(로테이션 진행/매칭 시작·종료) 응답으로 상태·턴 표기를 즉시 갱신한다.
        // 응답 data 에 담긴 status/round_no 를 신뢰 소스로 사용한다. (뒤이은 loadData 전에 선반영)
        function applyStatusFromResponse(res){
            let d = res && res.data ? res.data : {};
            if(typeof d.round_no !== 'undefined'){ currentRound = d.round_no; }
            renderStatusBadge(d.status || null);
        }

        let members = [];
        let dataXhr = null;      // 진행 중인 요청 추적 (중복 호출 방지)
        let viewRound = null;    // 현재 보고 있는 턴 (null이면 최신 턴으로 자동 설정)
        let currentRound = 0;    // 좌석이 존재하는 최신 턴 (메타 기준)
        let isClosed = false;    // 행사가 종료(X)되었는지 — 종료 시 회원 추가 비활성
        let isOneToOne = false;  // 1:1 로테이션 행사 여부 — 마지막 턴 '매칭 종료' 버튼/문구 분기

        // 성별 색상 구분: 남성=파랑(blue), 여성=핑크(pink)
        function genderBadgeClass(g){ return g === 'F' ? 'bg-pink-lt' : 'bg-blue-lt'; }
        function genderLabel(g){ return g === 'F' ? '여' : '남'; }

        // assignedIds: 현재 턴에 이미 배정된 회원 id Set (중복 배정 방지용)
        function memberOptions(assignedIds){
            let html = '<option value="">+ 회원 추가</option>';
            members.forEach(function(m){
                if(assignedIds && assignedIds.has(m.id)) return; // 이미 배정된 회원은 제외
                // 동명이인 구분을 위해 직업/출생연도를 함께 표기한다.
                let info = [];
                if(m.birth_year){ info.push(String(m.birth_year).slice(-2) + '년생'); }
                if(m.job){ info.push(m.job); }
                let suffix = info.length ? ` · ${info.join(' · ')}` : '';
                html += `<option value="${m.id}">[${genderLabel(m.gender)}] ${m.label || (m.name + "호")}${suffix}</option>`;
            });
            return html;
        }

        function loadData(){
            // 이전 요청이 진행 중이면 취소하여 중복 렌더 방지
            if(dataXhr){ dataXhr.abort(); }
            // viewRound가 아직 없으면(최초) -1을 보내 서버가 최신 턴을 선택하게 한다.
            let reqRound = (viewRound === null) ? -1 : viewRound;
            dataXhr = $.get(dataUrl, { eventId: $('#eventSelect').val(), roundNo: reqRound }, function(res){
                members = res?.members || [];
                renderActions(res?.board?.meta || {});
                renderBoard(res?.board || {});
            }).always(function(){ dataXhr = null; });
            refreshCounts();
            return dataXhr;
        }

        // 상단 버튼들에 현재 행사의 매칭 쌍 수 / 메시지 수 / 요청 수를 표시한다.
        function refreshCounts(){
            var eventId = $('#eventSelect').val();
            var $matched = $('#matchedCount');
            var $messages = $('#messagesCount');
            var $requests = $('#requestsCount');

            if(!eventId){ $matched.text(''); $messages.text(''); $requests.text(''); return; }

            function setCount($el, cnt, unit){
                $el.text((cnt > 0) ? ' (' + cnt + unit + ')' : '');
            }

            // 매칭 쌍 수 (남자 기준 중복 제거된 쌍)
            $.get(matchesUrlTemplate.replace('__ID__', eventId) + '?matched=1', function(res){
                setCount($matched, res?.total ?? (res?.data ? res.data.length : 0), '쌍');
            }).fail(function(){ $matched.text(''); });

            // 전체 메시지 수
            $.get(messagesUrlTemplate.replace('__ID__', eventId), { size: 1 }, function(res){
                setCount($messages, res?.total ?? 0, '건');
            }).fail(function(){ $messages.text(''); });

            // 전체 요청 수
            $.get(requestsListUrl, { eventId: eventId, size: 1 }, function(res){
                setCount($requests, res?.total ?? 0, '건');
            }).fail(function(){ $requests.text(''); });
        }

        // 메타 기반 버튼/네비게이션 제어:
        //  - 턴 네비게이션(◀▶)으로 과거 턴 조회 가능
        //  - [로테이션 진행]/[종료]는 "최신 턴을 보고 있을 때만" 노출
        //  - 과거 턴을 볼 때는 진행/종료 버튼 숨김
        function renderActions(meta){
            let $rotate = $('#rotateBtn').addClass('d-none');
            let $rotateBack = $('#rotateBackBtn').addClass('d-none');
            let $finish = $('#finishBtn').addClass('d-none');
            let $closeVoting = $('#closeVotingBtn').addClass('d-none');
            let $closed = $('#closedBtn').addClass('d-none');
            let $reopen = $('#reopenBtn').addClass('d-none');
            let $cancelVoting = $('#cancelVotingBtn').addClass('d-none');
            let $hint   = $('#actionHint');
            let $roundHint = $('#roundHint');
            let $pastNotice = $('#pastNotice').removeClass('d-flex').addClass('d-none');
            let $actionLabel = $('#actionLabel').removeClass('d-none');

            currentRound = (typeof meta.current_round !== 'undefined') ? meta.current_round : 0;

            // 종료(X) 여부: 회원 추가 비활성화 판단에 사용.
            isClosed = !!meta.is_closed;
            // 1:1 로테이션 여부: 마지막 턴 '매칭 종료' 버튼/문구 분기에 사용.
            isOneToOne = !!meta.is_one_to_one;

            // 현재 행사 상태 뱃지 최신화 (버튼 클릭 → loadData 재호출 시마다 갱신됨)
            renderStatusBadge(meta.event_status);

            // 조회 가능한 최소 턴: 1턴까지만 (0턴은 세팅용이라 조회하지 않음).
            // 단, 아직 0턴만 있는 세팅 단계(current_round=0)에서는 0턴을 본다.
            let minRound = Math.min(1, currentRound);

            // 최초 로딩이면 보는 턴을 최신 턴으로 맞춘다.
            if(viewRound === null){ viewRound = currentRound; }
            // 보는 턴이 [minRound, currentRound] 범위를 벗어나지 않도록 보정
            if(viewRound > currentRound){ viewRound = currentRound; }
            if(viewRound < minRound){ viewRound = minRound; }

            $('#roundInput').val(viewRound);

            // 네비게이션 버튼 활성/비활성 (1턴 아래로는 못 내려감)
            $('#roundPrev').prop('disabled', viewRound <= minRound);
            $('#roundNext').prop('disabled', viewRound >= currentRound);

            let isLatest = (viewRound === currentRound);
            let isBaseView = (viewRound <= 1);
            if(isBaseView){
                $roundHint.html(currentRound === 0
                    ? '기준 자리 배치 화면입니다. (회원 추가/삭제/이동 가능)'
                    : '1턴(기준) 화면입니다. 진행 중에도 여기서 자리를 옮기면 <b>모든 턴에 바로 반영</b>돼요.');
            } else {
                $roundHint.html('조회 전용 화면입니다. 자리를 옮기려면 <b>1턴(기준)</b>으로 이동하세요.');
            }

            // 생성된 테이블이 없으면 액션 버튼을 노출하지 않는다.
            if(!meta.table_count){
                $hint.text('');
                return;
            }

            // 투표중/종료는 로테이션이 끝난 뒤의 "행사 전체 상태"이므로,
            // 어느 턴을 보고 있든 상태 기준으로 먼저 처리한다.
            if(meta.is_closed){
                $closed.removeClass('d-none');
                $reopen.removeClass('d-none');
                // 되돌리면 복귀할 상태를 타입별로 안내한다. (와인파티→매칭중 / 1:1→진행)
                var backLabel = meta.is_one_to_one ? '진행' : '매칭중';
                $hint.html('이미 종료된 행사입니다. (매칭 마감)<br>실수로 종료했다면 아래에서 <b>' + backLabel + '</b> 상태로 되돌릴 수 있어요.');
                return;
            }
            if(meta.is_voting){
                $closeVoting.removeClass('d-none');
                // 매칭중에서 진행(S)으로 되돌리기 — 와인파티 전용(1:1은 V 단계가 없음). 투표가 삭제됨을 안내.
                if(!meta.is_one_to_one){
                    $cancelVoting.removeClass('d-none');
                }
                $hint.html('매칭이 진행 중입니다. 회원들이 상대를 선택하고 있어요.<br>(행사 다음 날 오전 9시 자동 종료)'
                    + (!meta.is_one_to_one ? '<br>실수로 시작했다면 아래에서 <b>진행</b> 상태로 되돌릴 수 있어요. (기존 투표는 유지)' : ''));
                return;
            }

            // 과거 턴을 보고 있으면 진행/종료 버튼 대신 안내 문구를 표시한다. (label/힌트 라인은 왼쪽과 정렬 유지)
            if(!isLatest){
                $pastNotice.removeClass('d-none').addClass('d-flex');
                $hint.text('이전 턴은 로테이션을 진행할 수 없습니다.');
                return;
            }

            if(meta.is_last){
                if(meta.is_one_to_one){
                    // 1:1: 매칭 선택을 홈에서 받으므로 '매칭 시작(V)' 없이 바로 '매칭 종료(X)'.
                    $closeVoting.removeClass('d-none');
                    $hint.text('마지막 턴입니다. 매칭을 종료하면 결과가 공개됩니다.');
                } else {
                    $finish.removeClass('d-none');
                    $hint.text('마지막 턴입니다. 매칭을 시작할 수 있습니다.');
                }
            } else {
                $rotate.removeClass('d-none');
                // 0턴(아직 로테이션 시작 전)이면 '시작', 그 이후면 '진행'.
                if(viewRound === 0){
                    $rotate.html('<i class="ti ti-player-play me-1"></i>로테이션 시작');
                    $hint.text('로테이션을 시작합니다.');
                } else {
                    $rotate.html('<i class="ti ti-rotate-clockwise me-1"></i>로테이션 진행');
                    $hint.text('다음 턴을 자동 생성합니다. (여자 고정 / 남자 이동)');
                }
            }

            // 로테이션 되돌리기: 진행이 1턴 이상 됐고(= 되돌릴 게 있음) 최신 턴을 볼 때만 노출.
            // (투표중/종료는 위에서 이미 return 되므로 여기까지 오지 않는다)
            if(currentRound >= 1){
                $rotateBack.removeClass('d-none');
            }
        }

        function renderBoard(data){
            // 이전 Choices 인스턴스를 먼저 정리(원본 select 복원)한 뒤 보드를 비운다.
            destroySeatChoices();
            let $area = $('#boardArea').empty();
            if(!data.tables || data.tables.length === 0){
                $area.html('<div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">생성된 테이블이 없습니다. 행사 관리 &gt; 상세에서 테이블을 먼저 추가해주세요.</div></div></div>');
                return;
            }

            // 과거 턴에서도 인원 추가/삭제는 항상 가능하게 둔다. (로테이션 진행 버튼만 최신 턴 제한)
            // 현재 턴에 이미 배정된 회원 id 수집 (다른 테이블 셀렉트에서 제외)
            let assignedIds = new Set();
            data.tables.forEach(function(t){
                t.members.forEach(function(m){ assignedIds.add(m.member_id); });
            });

            // 좌석(기준자리) 편집은 "기준 뷰"(1턴 이하)에서만 허용한다.
            // 2턴 이상에서 보이는 자리는 공식으로 계산된 '이동 결과'이므로,
            // 그 자리에 추가하면 홈 테이블이 잘못 지정된다. → 편집 비활성.
            let isBaseView = !isClosed && (viewRound === null || viewRound <= 1);

            data.tables.forEach(function(t){
                // 성별로 분리
                var males   = t.members.filter(function(m){ return m.gender === 'M'; });
                var females = t.members.filter(function(m){ return m.gender === 'F'; });
                var maleN = males.length, femaleN = females.length;

                // 칩 1개 HTML
                function chip(m){
                    let info = [];
                    if(m.birth_year){ info.push(String(m.birth_year).slice(-2) + '년생'); }
                    if(m.job){ info.push(m.job); }
                    let infoText = info.length
                        ? ` <span style="font-weight:400;opacity:0.7;font-size:0.82em;">${info.join(' · ')}</span>`
                        : '';
                    let removeBtn = isBaseView
                        ? `<a href="#" class="chip-remove d-inline-flex align-items-center justify-content-center text-danger"
                                   data-seating-id="${m.seating_id}" title="제거"
                                   style="width:1.3rem;height:1.3rem;border-radius:50%;text-decoration:none;"><i class="ti ti-x" style="font-size:0.9rem;line-height:1;"></i></a>`
                        : '';
                    return `<span class="badge ${genderBadgeClass(m.gender)} mb-1 d-inline-flex align-items-center gap-1 py-1 ps-2 pe-1" style="max-width:100%;">
                                <span class="text-truncate">${m.label || (m.name + "호")}${infoText}</span>
                                ${removeBtn}
                            </span>`;
                }

                // 한 컬럼(남 또는 여) HTML — 헤더(성별·인원) + 칩 세로 나열
                function genderColumn(label, badgeCls, list){
                    var chipsHtml = list.length
                        ? list.map(chip).join('')
                        : '<span class="text-muted" style="font-size:0.85em;">없음</span>';
                    return `
                        <div class="col-6">
                            <div class="d-flex align-items-center justify-content-center gap-1 mb-2">
                                <span class="badge ${badgeCls}">${label} ${list.length}</span>
                            </div>
                            <div class="d-flex flex-column align-items-stretch gap-0 seat-chips">${chipsHtml}</div>
                        </div>`;
                }

                // 기준 뷰에서만 '회원 추가' 셀렉트 노출. 아니면 안내 문구.
                let adder = isBaseView
                    ? `<select class="form-select form-select-sm seat-add mt-2" data-table-id="${t.id}">${memberOptions(assignedIds)}</select>`
                    : `<div class="text-muted small mt-2"><i class="ti ti-info-circle me-1"></i>좌석 편집은 1턴(기준) 화면에서만 가능합니다.</div>`;

                // 성비 뱃지(헤더)
                let ratioBadges = `<span class="badge bg-blue-lt ms-1">남 ${maleN}</span>`
                                + `<span class="badge bg-pink-lt ms-1">여 ${femaleN}</span>`;

                let card = `
                    <div class="col-md-4 col-sm-6">
                        <div class="card h-100">
                            <div class="card-header py-2 d-flex align-items-center flex-wrap gap-1">
                                <strong>${t.table_no}번 테이블</strong>
                                <span class="text-muted ms-1">(${t.members.length}명)</span>
                                <span class="ms-auto">${ratioBadges}</span>
                            </div>
                            <div class="card-body py-2">
                                <div class="row g-2">
                                    ${genderColumn('남', 'bg-blue-lt', males)}
                                    ${genderColumn('여', 'bg-pink-lt', females)}
                                </div>
                                ${adder}
                            </div>
                        </div>
                    </div>`;
                $area.append(card);
            });

            // 각 테이블의 '회원 추가' 셀렉트를 Choices(검색형)로 변환.
            // 보드는 매 렌더마다 재생성되므로 여기서 매번 초기화한다.
            initSeatChoices();
        }

        // 회원 추가 셀렉트(.seat-add)에 Choices 적용. (단일 선택 + 검색)
        // 보드가 매 렌더마다 재생성되므로, 이전 인스턴스를 먼저 destroy 하여
        // option 이 중복 누적되는 것을 방지한다.
        let seatChoices = [];
        function destroySeatChoices(){
            seatChoices.forEach(function(c){
                try { c.destroy(); } catch (e) { /* noop */ }
            });
            seatChoices = [];
        }
        function initSeatChoices(){
            destroySeatChoices();
            document.querySelectorAll('#boardArea .seat-add').forEach(function(el){
                var c = new Choices(el, {
                    searchEnabled: true,
                    searchFields: ['label'],
                    searchFloor: 1,
                    searchResultLimit: -1,
                    shouldSort: false,
                    itemSelectText: '',
                    searchPlaceholderValue: '🔍 이름으로 검색',
                    noResultsText: '검색 결과가 없습니다.',
                    noChoicesText: '추가할 회원이 없습니다.',
                    fuseOptions: { threshold: 0.3, ignoreLocation: true },
                });
                // 종료된 행사면 선택 자체를 막는다.
                if(isClosed){ try { c.disable(); } catch(e){} }
                seatChoices.push(c);
            });
        }

        // 회원 추가 (넣을 때마다 즉시 저장)
        $('#boardArea').on('change', '.seat-add', function(){
            let memberId = $(this).val();
            let tableId  = $(this).data('table-id');
            if(!memberId) return;

            $.ajax({
                url: storeUrl,
                type: 'POST',
                data: {
                    event_id: $('#eventSelect').val(),
                    rotation_table_id: tableId,
                    member_id: memberId
                },
                success: function(res){
                    if(res?.status === 200){ loadData(); }
                    else { alert(res?.msg); }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 회원 제거 (즉시 삭제)
        $('#boardArea').on('click', '.chip-remove', function(e){
            e.preventDefault();
            let seatingId = $(this).data('seating-id');
            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: { id: seatingId },
                success: function(res){
                    if(res?.status === 200){ loadData(); }
                    else { alert(res?.msg); }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 로테이션 진행: 현재 턴 기준 다음 턴 자동 생성 (여자 고정 / 남자 이동)
        $('#rotateBtn').on('click', async function(){
            let msg = (viewRound === 0)
                ? '로테이션을 시작하시겠습니까?'
                : '다음 턴으로 로테이션을 진행하시겠습니까?';
            if(!(await salert({text: msg}))) return;

            $.ajax({
                url: rotateUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val() },
                success: function(res){
                    // 응답(권한 있는 최신 값)으로 상태/턴 표기를 먼저 즉시 갱신한다.
                    // (뒤이은 loadData 조회가 끝나기 전에도 상단 탭바·카드 뱃지가 어긋나지 않게)
                    applyStatusFromResponse(res);
                    // 진행 후 최신 턴을 보도록 viewRound를 리셋하고, 보드 갱신이 끝난 뒤 알림.
                    viewRound = null;
                    $.when(loadData()).always(function(){ alert(res?.msg); });
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 로테이션 되돌리기: 현재 턴을 한 단계 이전으로 복구 (실수로 한 번 더 진행했을 때)
        $('#rotateBackBtn').on('click', async function(){
            if(!(await salert({html: '로테이션을 <b>이전 턴으로 되돌리시겠습니까?</b><br>실수로 한 번 더 진행한 경우에만 사용하세요.'}))) return;

            $.ajax({
                url: rotateBackUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val() },
                success: function(res){
                    applyStatusFromResponse(res);
                    viewRound = null;
                    $.when(loadData()).always(function(){ alert(res?.msg); });
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 투표 시작: 행사 상태를 투표중(V)으로
        $('#finishBtn').on('click', async function(){
            if(!(await salert({html: '매칭을 시작하시겠습니까?<br>회원들이 서로에게 투표(선택)할 수 있게 됩니다.'}))) return;

            $.ajax({
                url: finishUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val() },
                success: function(res){
                    applyStatusFromResponse(res);
                    viewRound = null;
                    $.when(loadData()).always(function(){ alert(res?.msg); });
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 투표 마감(수동): 행사 상태를 종료(X)로
        $('#closeVotingBtn').on('click', async function(){
            // 1:1은 자동 마감이 없고 홈에서 선택을 받으므로 문구를 분기한다.
            let confirmHtml = isOneToOne
                ? '매칭을 종료하시겠습니까?<br>종료 시점의 선택으로 매칭 결과가 확정·공개되며,<br>회원은 더 이상 선택을 수정할 수 없습니다.'
                : '그대로 두면 <b>행사 다음 날 오전 9시</b>에 자동으로 마감돼요.<br>지금 바로 매칭을 종료하시겠습니까?<br>종료 후에는 회원이 선택을 수정할 수 없습니다.';
            if(!(await salert({html: confirmHtml}))) return;

            $.ajax({
                url: closeVotingUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val() },
                success: function(res){
                    applyStatusFromResponse(res);
                    viewRound = null;
                    $.when(loadData()).always(function(){ alert(res?.msg); });
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 종료 되돌리기(reopen): 종료(X)된 행사를 종료 직전 상태로 강제 복구.
        // 와인파티 → 매칭중(V), 1:1 → 진행(S). 참가자 화면도 실시간으로 재개된다.
        $('#reopenBtn').on('click', async function(){
            var backLabel = isOneToOne ? '진행(매칭 선택 재개)' : '매칭중(매칭 선택 재개)';
            if(!(await salert({html: '종료를 되돌려 <b>' + backLabel + '</b> 상태로 복구하시겠습니까?<br>참가자 화면에서 매칭 선택이 다시 열립니다.'}))) return;

            $.ajax({
                url: reopenUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val() },
                success: function(res){
                    applyStatusFromResponse(res);
                    viewRound = null;
                    $.when(loadData()).always(function(){ alert(res?.msg); });
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 매칭 시작 취소(cancelVoting): 매칭중(V)→진행(S). 기존 투표(매칭 데이터)는 그대로 유지한다.
        $('#cancelVotingBtn').on('click', async function(){
            if(!(await salert({html: '매칭 시작을 취소하고 <b>진행</b> 상태로 되돌리시겠습니까?<br>기존 투표(매칭 데이터)는 그대로 유지되며, 다시 매칭을 시작하면 이어서 선택할 수 있어요.'}))) return;

            $.ajax({
                url: cancelVotingUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val() },
                success: function(res){
                    applyStatusFromResponse(res);
                    viewRound = null;
                    $.when(loadData()).always(function(){ alert(res?.msg); });
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 선택된 행사 id를 URL 쿼리파라미터에 반영 (공유 가능 URL)
        function syncUrl(){
            let eventId = $('#eventSelect').val();
            let url = new URL(window.location.href);
            if(eventId){ url.searchParams.set('eventId', eventId); }
            else { url.searchParams.delete('eventId'); }
            window.history.replaceState({}, '', url);
        }

        // 선택된 행사의 메시지를 모달로 조회
        $('#goMessagesBtn').on('click', function(){
            var eventId = $('#eventSelect').val();
            if(!eventId){ return; }
            var label = $('#eventSelect option:selected').text();
            MessagesModal.open(eventId, label);
        });

        // 선택된 행사의 매칭 결과를 모달로 조회
        $('#goMatchesBtn').on('click', function(){
            var eventId = $('#eventSelect').val();
            if(!eventId){ return; }
            var label = $('#eventSelect option:selected').text();
            MatchesModal.open(eventId, label);
        });

        // 선택된 행사의 "매칭된 쌍"만 모달로 조회 (남자 기준)
        $('#goMatchedBtn').on('click', function(){
            var eventId = $('#eventSelect').val();
            if(!eventId){ return; }
            var label = $('#eventSelect option:selected').text();
            MatchedModal.open(eventId, label);
        });

        // 선택된 행사의 회원 요청을 모달로 조회
        $('#goRequestsBtn').on('click', function(){
            var eventId = $('#eventSelect').val();
            if(!eventId){ return; }
            var label = $('#eventSelect option:selected').text();
            RequestsModal.open(eventId, label);
        });

        // 턴 네비게이션: 이전/다음 턴 보기 (1턴 ~ 최신 턴 범위, 0턴은 세팅용이라 제외)
        $('#roundPrev').on('click', function(){
            if(viewRound === null){ viewRound = currentRound; }
            let minRound = Math.min(1, currentRound);
            if(viewRound > minRound){ viewRound -= 1; loadData(); }
        });
        $('#roundNext').on('click', function(){
            if(viewRound === null){ viewRound = currentRound; }
            if(viewRound < currentRound){ viewRound += 1; loadData(); }
        });

        // #eventSelect 는 화면에서는 숨겨져 있다. (행사 전환은 상단 탭바가 페이지 이동으로 처리)
        // 다만 값을 스크립트에서 바꿀 수 있는 경로를 남겨 두기 위해 change 핸들러는 유지한다.
        $('#eventSelect').change(function(){ viewRound = null; syncUrl(); loadData(); });

        // 최초 로딩: URL에 eventId가 있으면 그 행사를 선택
        (function(){
            let paramEventId = new URL(window.location.href).searchParams.get('eventId');
            if(paramEventId && $('#eventSelect option[value="' + paramEventId + '"]').length){
                $('#eventSelect').val(paramEventId);
            }
            syncUrl();
            loadData();
        })();
    });
</script>

@include('admin.rotation._messagesModal')
@include('admin.rotation._matchesModal')
@include('admin.rotation._matchedModal')
@include('admin.rotation._requestsModal')
@endsection
