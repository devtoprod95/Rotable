{{--
    재사용 "매칭 결과" 모달. (상호 매칭된 쌍만, 남자 기준)
    - 페이지네이션이 없으므로 테이블 라이브러리 없이 마크업으로 직접 렌더한다.
    - 사용법(JS): MatchedModal.open(eventId, eventLabel)
    - matches.list 라우트에 ?matched=1 을 붙여 매칭된 쌍만 받는다.
--}}
@php
    $matchedUrlTemplate = route('admin.rotation.event.matches.list', ['id' => '__ID__']);
@endphp

<div class="modal modal-blur fade" id="matchedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3 px-4 align-items-start">
                <div>
                    <h5 class="modal-title mb-0">매칭 결과</h5>
                    <small class="text-muted" id="matched-eventLabel"></small>
                </div>
                <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex align-items-center mb-2">
                    <span id="matched-pageCount" class="badge bg-secondary-lt"></span>
                    <small class="text-muted ms-2">서로 선택해 <span class="badge bg-pink-lt">매칭</span>된 쌍만 남자 기준으로 표시합니다.</small>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" onclick="MatchedModal.refresh()">
                        <i class="ti ti-refresh me-1"></i> 새로고침
                    </button>
                </div>

                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="text" id="matched-search" class="form-control" placeholder="이름 / 전화번호 / 직업으로 검색" autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" id="matched-search-clear" title="검색 초기화">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:200px;">남자</th>
                                <th class="text-center" style="width:44px;"></th>
                                <th>매칭된 상대</th>
                                <th class="text-center" style="width:130px;">투표 시각</th>
                            </tr>
                        </thead>
                        <tbody id="matched-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.MatchedModal = (function(){
        var urlTemplate = @json($matchedUrlTemplate);

        var currentEventId = null;
        var rows = [];
        var modalEl = null;
        var bsModal = null;

        function listUrl(eventId){ return urlTemplate.replace('__ID__', eventId) + '?matched=1'; }

        function esc(v){ return $('<div>').text(v == null ? '' : v).html(); }

        function genderBadge(gender){
            if(gender === 'F'){ return '<span class="badge bg-pink-lt">여</span>'; }
            if(gender === 'M'){ return '<span class="badge bg-blue-lt">남</span>'; }
            return '';
        }

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

        function targetsCell(targets){
            if(!targets || !targets.length){ return '-'; }
            var multiple = targets.length > 1;
            return targets.map(function(t, i){
                var specs = specsText(t);
                var specLine = specs ? `<div class="text-start" style="font-size:0.8em;color:#666;">${specs}</div>` : '';
                var sepCls = (multiple && i < targets.length - 1) ? 'border-bottom pb-1 mb-1' : '';
                return `<div class="py-1 text-start ${sepCls}">`
                         + `<div class="d-flex align-items-center gap-1"><span class="fw-bold">${esc(t.name) || '-'}</span> ${genderBadge(t.gender)} <span class="badge bg-pink-lt"><i class="ti ti-heart-filled text-pink"></i> 매칭</span></div>`
                         + specLine
                     + `</div>`;
            }).join('');
        }

        function votedAtCell(v){
            if(!v){ return '-'; }
            var s = new Date(v).toLocaleString('ko-KR');
            return s.replace(/\s(오전|오후)/, '<br>$1');
        }

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
            var kw = ($('#matched-search').val() || '').trim();
            var list = filterRows(kw);
            var $tb = $('#matched-tbody').empty();

            if(!list.length){
                $tb.append('<tr><td colspan="4" class="text-center text-muted py-4">매칭된 쌍이 없습니다.</td></tr>');
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
            // 쌍 수 = 각 남자(행)가 매칭된 상대(targets) 수의 합.
            var pairCount = list.reduce(function(sum, r){ return sum + ((r.targets || []).length); }, 0);
            $('#matched-pageCount').text('총 ' + pairCount + '쌍');
        }

        function load(){
            if(!currentEventId){ return; }
            $('#matched-tbody').html('<tr><td colspan="4" class="text-center text-muted py-4">불러오는 중...</td></tr>');
            $.get(listUrl(currentEventId), function(res){
                rows = (res && res.data) ? res.data : [];
                render();
            }).fail(function(){
                rows = [];
                $('#matched-tbody').html('<tr><td colspan="4" class="text-center text-danger py-4">불러오지 못했습니다.</td></tr>');
                $('#matched-pageCount').text('');
            });
        }

        $(function(){
            var t = null;
            $('#matched-search').on('input', function(){ clearTimeout(t); t = setTimeout(render, 150); });
            $('#matched-search-clear').on('click', function(){ $('#matched-search').val(''); render(); });
        });

        return {
            refresh: function(){ load(); },
            open: function(eventId, eventLabel){
                if(!eventId){ return; }
                currentEventId = eventId;
                $('#matched-eventLabel').text(eventLabel || '');
                $('#matched-search').val('');
                $('#matched-tbody').empty();
                $('#matched-pageCount').text('');

                if(!modalEl){
                    modalEl = document.getElementById('matchedModal');
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
