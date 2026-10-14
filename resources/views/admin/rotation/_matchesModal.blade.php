{{--
    재사용 "매칭 조회" 모달. (전체 투표: 누가 누구를 선택했는지 전부)
    - 페이지네이션이 없으므로 테이블 라이브러리 없이 마크업으로 직접 렌더한다.
    - 상호 매칭(상대도 나를 선택)인 경우 뱃지로 표시한다.
    - 사용법(JS): MatchesModal.open(eventId, eventLabel)
    - matches.list 라우트는 {id} 를 치환해서 사용하므로 플레이스홀더(__ID__)를 둔다.
--}}
@php
    $matchesUrlTemplate = route('admin.rotation.event.matches.list', ['id' => '__ID__']);
@endphp

<div class="modal modal-blur fade" id="matchesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3 px-4 align-items-start">
                <div>
                    <h5 class="modal-title mb-0">매칭 조회</h5>
                    <small class="text-muted" id="match-eventLabel"></small>
                </div>
                <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex align-items-center mb-2">
                    <span id="match-pageCount" class="badge bg-secondary-lt"></span>
                    <small class="text-muted ms-2">같은 사람이 선택한 상대를 한 줄에 모아 표시합니다. (서로 선택 시 <span class="badge bg-pink-lt">매칭</span>)</small>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" onclick="MatchesModal.refresh()">
                        <i class="ti ti-refresh me-1"></i> 새로고침
                    </button>
                </div>

                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="text" id="match-search" class="form-control" placeholder="이름 / 전화번호 / 직업으로 검색" autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" id="match-search-clear" title="검색 초기화">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:200px;">선택한 사람</th>
                                <th class="text-center" style="width:44px;"></th>
                                <th>선택한 상대</th>
                                <th class="text-center" style="width:130px;">투표 시각</th>
                            </tr>
                        </thead>
                        <tbody id="match-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.MatchesModal = (function(){
        var urlTemplate = @json($matchesUrlTemplate);

        var currentEventId = null;
        var rows = [];           // 서버에서 받은 전체 행
        var modalEl = null;
        var bsModal = null;

        function listUrl(eventId){ return urlTemplate.replace('__ID__', eventId); }

        function esc(v){ return $('<div>').text(v == null ? '' : v).html(); }

        function genderBadge(gender){
            if(gender === 'F'){ return '<span class="badge bg-pink-lt">여</span>'; }
            if(gender === 'M'){ return '<span class="badge bg-blue-lt">남</span>'; }
            return '';
        }

        // 회원 상세 스펙(년생 · 직업 · 키 · MBTI · 지역 · 흡연)을 ' · ' 로 이어 반환.
        function specsText(m){
            var specs = [];
            if(m.birth_year){ specs.push(String(m.birth_year).slice(-2) + '년생'); }
            if(m.job){ specs.push(esc(m.job)); }
            if(m.height){ specs.push(m.height + 'cm'); }
            if(m.mbti){ specs.push(esc(m.mbti)); }
            if(m.region){ specs.push(esc(m.region)); }
            if(m.is_smoker === true){ specs.push('흡연'); }
            else if(m.is_smoker === false){ specs.push('비흡연'); }
            return specs.join(' · ');
        }

        // 선택한 사람(voter) 셀: 가운데 정렬 + 정보 줄바꿈.
        function voterCell(m){
            if(!m){ return '-'; }
            var line1 = [];
            if(m.birth_year){ line1.push(String(m.birth_year).slice(-2) + '년생'); }
            if(m.job){ line1.push(esc(m.job)); }
            var line2 = [];
            if(m.height){ line2.push(m.height + 'cm'); }
            if(m.mbti){ line2.push(esc(m.mbti)); }
            if(m.region){ line2.push(esc(m.region)); }
            if(m.is_smoker === true){ line2.push('흡연'); }
            else if(m.is_smoker === false){ line2.push('비흡연'); }

            var html = `<div class="text-center">`
                     + `<div><span class="fw-bold">${esc(m.name) || '-'}</span> ${genderBadge(m.gender)}</div>`;
            if(line1.length){ html += `<div style="font-size:0.8em;color:#666;">${line1.join(' · ')}</div>`; }
            if(line2.length){ html += `<div style="font-size:0.8em;color:#666;">${line2.join(' · ')}</div>`; }
            if(m.phone){ html += `<div style="font-size:0.8em;color:#888;">${esc(m.phone)}</div>`; }
            html += `</div>`;
            return html;
        }

        // 선택한 상대(targets) 셀: 여러 명을 세로로 나열. 상호 매칭은 뱃지.
        function targetsCell(targets){
            if(!targets || !targets.length){ return '-'; }
            var multiple = targets.length > 1;
            return targets.map(function(t, i){
                var mutual = t.is_mutual
                    ? ' <span class="badge bg-pink-lt"><i class="ti ti-heart-filled text-pink"></i> 매칭</span>'
                    : '';
                var specs = specsText(t);
                var specLine = specs ? `<div class="text-start" style="font-size:0.8em;color:#666;">${specs}</div>` : '';
                var sepCls = (multiple && i < targets.length - 1) ? 'border-bottom pb-1 mb-1' : '';
                return `<div class="py-1 text-start ${sepCls}">`
                         + `<div class="d-flex align-items-center gap-1"><span class="fw-bold">${esc(t.name) || '-'}</span> ${genderBadge(t.gender)}${mutual}</div>`
                         + specLine
                     + `</div>`;
            }).join('');
        }

        function votedAtCell(v){
            if(!v){ return '-'; }
            var s = new Date(v).toLocaleString('ko-KR');
            return s.replace(/\s(오전|오후)/, '<br>$1');
        }

        // 검색어로 필터링 (voter/target 의 이름·전화·직업).
        function filterRows(kw){
            if(!kw){ return rows; }
            var k = kw.toLowerCase();
            function hit(m){
                if(!m){ return false; }
                return [m.name, m.phone, m.job].some(function(x){ return x && String(x).toLowerCase().indexOf(k) !== -1; });
            }
            return rows.filter(function(r){
                return hit(r.voter) || (r.targets || []).some(hit);
            });
        }

        function render(){
            var kw = ($('#match-search').val() || '').trim();
            var list = filterRows(kw);
            var $tb = $('#match-tbody').empty();

            if(!list.length){
                $tb.append('<tr><td colspan="4" class="text-center text-muted py-4">표시할 투표 내역이 없습니다.</td></tr>');
            } else {
                var html = list.map(function(r){
                    return `<tr>`
                             + `<td class="text-center align-middle">${voterCell(r.voter)}</td>`
                             + `<td class="text-center align-middle"><i class="ti ti-arrow-right text-muted"></i></td>`
                             + `<td class="align-middle">${targetsCell(r.targets)}</td>`
                             + `<td class="text-center align-middle">${votedAtCell(r.voted_at)}</td>`
                         + `</tr>`;
                }).join('');
                $tb.html(html);
            }
            $('#match-pageCount').text('총 ' + list.length + '명');
        }

        function load(){
            if(!currentEventId){ return; }
            $('#match-tbody').html('<tr><td colspan="4" class="text-center text-muted py-4">불러오는 중...</td></tr>');
            $.get(listUrl(currentEventId), function(res){
                rows = (res && res.data) ? res.data : [];
                render();
            }).fail(function(){
                rows = [];
                $('#match-tbody').html('<tr><td colspan="4" class="text-center text-danger py-4">불러오지 못했습니다.</td></tr>');
                $('#match-pageCount').text('');
            });
        }

        // 검색 입력 바인딩 (1회)
        $(function(){
            var t = null;
            $('#match-search').on('input', function(){ clearTimeout(t); t = setTimeout(render, 150); });
            $('#match-search-clear').on('click', function(){ $('#match-search').val(''); render(); });
        });

        return {
            refresh: function(){ load(); },
            open: function(eventId, eventLabel){
                if(!eventId){ return; }
                currentEventId = eventId;
                $('#match-eventLabel').text(eventLabel || '');
                $('#match-search').val('');
                $('#match-tbody').empty();
                $('#match-pageCount').text('');

                if(!modalEl){
                    modalEl = document.getElementById('matchesModal');
                    var ModalClass =
                        (window.tabler && window.tabler.Modal) ||
                        (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) ||
                        (window.bootstrap && window.bootstrap.Modal) ||
                        (window.coreui && window.coreui.Modal) ||
                        window.Modal;
                    if(!ModalClass){ console.error('Modal 클래스를 찾을 수 없습니다.'); return; }
                    bsModal = new ModalClass(modalEl);
                }

                bsModal.show();
                load();
            },
        };
    })();
</script>
