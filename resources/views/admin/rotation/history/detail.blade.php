@extends('layouts.app')

@section('title', '만남 이력 상세 조회')

@section('content')
<style>
    /* 프로필 비교 테이블: 가운데 라벨 기준으로 A/B 값을 좁게 모아 눈 이동 최소화 */
    .cmp-table { width: 100%; max-width: 720px; margin: 0 auto; border-collapse: separate; border-spacing: 0; }
    .cmp-table td, .cmp-table th { padding: 7px 12px; vertical-align: middle; }
    .cmp-table .cmp-label { width: 90px; text-align: center; color: #6b7280; font-size: 0.8rem; background: #f8fafc; font-weight: 600; }
    .cmp-table .cmp-a { text-align: right; background: #f2f7ff; }
    .cmp-table .cmp-b { text-align: left;  background: #fff2f7; }
    .cmp-table tr + tr td { border-top: 1px solid #eef1f5; }
    .cmp-head td { font-weight: 700; font-size: 1.05rem; border-bottom: 2px solid #e9edf2; }

    /* 메시지 채팅 박스: 폭을 제한해 가운데 모아 읽기 편하게 */
    .chat-wrap { max-width: 640px; margin: 0 auto; max-height: 360px; overflow-y: auto; padding: 4px; }
    .chat-row { display: flex; margin-bottom: 10px; }
    .chat-row.a { justify-content: flex-start; }
    .chat-row.b { justify-content: flex-end; }
    .chat-meta { font-size: 0.72rem; color: #9aa3af; margin-bottom: 2px; }
    .chat-bubble { max-width: 78%; padding: 8px 12px; border-radius: 14px; font-size: 0.9rem; line-height: 1.45; word-break: break-word; }
    .chat-row.a .chat-bubble { background: #e7f1ff; color: #10408a; border-top-left-radius: 4px; }
    .chat-row.b .chat-bubble { background: #ffe6f0; color: #9c1f54; border-top-right-radius: 4px; }
</style>

<div class="row row-cards mt-2">

    {{-- 검색 입력 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-2 align-items-end flex-wrap">
                    <div style="min-width: 200px;">
                        <label class="form-label mb-1"><span class="badge bg-blue-lt">A</span> 전화번호</label>
                        <input type="text" class="form-control" id="phoneA" value="{{ $initialPhoneA }}" placeholder="예: 01011112222"
                               onkeydown="if(event.keyCode===13){ event.preventDefault(); $('#btnLoad').click(); }">
                    </div>
                    <div class="pb-2"><i class="ti ti-arrows-left-right text-muted"></i></div>
                    <div style="min-width: 200px;">
                        <label class="form-label mb-1"><span class="badge bg-pink-lt">B</span> 전화번호</label>
                        <input type="text" class="form-control" id="phoneB" value="{{ $initialPhoneB }}" placeholder="예: 01033334444"
                               onkeydown="if(event.keyCode===13){ event.preventDefault(); $('#btnLoad').click(); }">
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary px-4" id="btnLoad">
                            <i class="ti ti-search me-1"></i> 이력 조회
                        </button>
                    </div>
                    <div class="ms-auto text-muted align-self-center" style="font-size:0.85em;">
                        같은 행사에 함께 참여한 이력을 보여줍니다.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 두 사람 요약 (가운데로 모음) --}}
    <div class="col-12 d-none" id="summaryCard">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center gap-4 flex-wrap">
                    <div class="text-end" id="summaryA" style="min-width:160px;"></div>
                    <div class="text-center px-3">
                        <div class="h1 mb-0 text-primary" id="meetingCount">0</div>
                        <div class="text-muted" style="font-size:0.8rem;">함께한 행사</div>
                        <div class="mt-1 d-flex justify-content-center gap-1 flex-wrap" id="meetingCountByType"></div>
                    </div>
                    <div class="text-start" id="summaryB" style="min-width:160px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- 만남 이력 카드들 --}}
    <div class="col-12" id="meetingList"></div>

    {{-- 빈 상태 --}}
    <div class="col-12 d-none" id="emptyCard">
        <div class="card">
            <div class="card-body text-center text-muted py-5" id="emptyText">두 사람의 전화번호를 입력하고 [이력 조회]를 눌러주세요.</div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        var dataUrl = "{{ route('admin.rotation.history.detail.data') }}";

        function esc(v){ return $('<div>').text(v == null ? '' : v).html(); }

        function genderBadge(g){
            if(g === 'F'){ return '<span class="badge bg-pink-lt">여</span>'; }
            if(g === 'M'){ return '<span class="badge bg-blue-lt">남</span>'; }
            return '';
        }

        function val(p, key){
            if(key === 'age'){ return p.age ? (p.age + '세') : (p.birth_year ? p.birth_year + '년생' : '-'); }
            if(key === 'height'){ return p.height ? p.height + 'cm' : '-'; }
            if(key === 'smoke'){
                if(p.is_smoker === true){ return '흡연'; }
                if(p.is_smoker === false){ return '비흡연'; }
                return '-';
            }
            return esc(p[key] || '-');
        }

        // A | 라벨 | B 비교 테이블 (가운데 정렬 → 눈 이동 최소화)
        function compareTable(a, b){
            var rows = [
                ['나이', 'age'], ['직업', 'job'], ['키', 'height'],
                ['MBTI', 'mbti'], ['지역', 'region'], ['흡연', 'smoke'],
            ];
            var body = rows.map(function(r){
                return `<tr>`
                     + `<td class="cmp-a">${val(a, r[1])}</td>`
                     + `<td class="cmp-label">${r[0]}</td>`
                     + `<td class="cmp-b">${val(b, r[1])}</td>`
                     + `</tr>`;
            }).join('');

            return `<table class="cmp-table">`
                 + `<tr class="cmp-head">`
                     + `<td class="cmp-a">${esc(a.name)||'A'} ${genderBadge(a.gender)}</td>`
                     + `<td class="cmp-label">상대</td>`
                     + `<td class="cmp-b">${genderBadge(b.gender)} ${esc(b.name)||'B'}</td>`
                 + `</tr>`
                 + body
                 + `</table>`;
        }

        function summaryPerson(p, align){
            if(!p || !p.name){ return '<span class="text-muted">정보 없음</span>'; }
            return `<div><span class="fw-bold fs-2">${esc(p.name)}</span> ${genderBadge(p.gender)}</div>`
                 + (p.phone ? `<div class="text-muted" style="font-size:0.85rem;">${esc(p.phone)}</div>` : '');
        }

        // 관계/투표를 한 줄로 간결하게
        function relationRow(m, a, b){
            var badge;
            if(m.is_mutual){ badge = '<span class="badge bg-pink text-white"><i class="ti ti-heart-filled me-1"></i>상호 매칭</span>'; }
            else if(m.a_voted_b || m.b_voted_a){ badge = '<span class="badge bg-yellow-lt">한쪽만 투표</span>'; }
            else { badge = '<span class="badge bg-secondary-lt">투표 없음</span>'; }

            function voted(v){
                return v
                    ? '<span class="badge bg-blue-lt">투표함</span>'
                    : '<span class="badge bg-secondary-lt">투표 안함</span>';
            }

            return `<div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">`
                 + `${badge}`
                 + `<span class="text-muted" style="font-size:0.9rem;">`
                     + `${esc(a.name)||'A'} → ${esc(b.name)||'B'} ${voted(m.a_voted_b)}`
                     + ` &nbsp;·&nbsp; `
                     + `${esc(b.name)||'B'} → ${esc(a.name)||'A'} ${voted(m.b_voted_a)}`
                 + `</span>`
                 + `</div>`;
        }

        function chatBox(m, a, b){
            var head = `<div class="text-center text-muted mb-2" style="font-size:0.85rem;">`
                     + `<i class="ti ti-messages me-1"></i>`
                     + `${esc(a.name)||'A'} → ${esc(b.name)||'B'} <b>${m.messages_a_to_b}</b>건`
                     + ` · ${esc(b.name)||'B'} → ${esc(a.name)||'A'} <b>${m.messages_b_to_a}</b>건`
                     + `</div>`;

            if(!m.messages || !m.messages.length){
                return head + '<div class="text-muted text-center py-3">주고받은 메시지가 없습니다.</div>';
            }

            var chat = m.messages.map(function(msg){
                var isA = msg.from === 'a';
                var who = isA ? (a.name || 'A') : (b.name || 'B');
                var t = msg.created_at ? new Date(msg.created_at).toLocaleString('ko-KR') : '';
                return `<div class="chat-row ${isA ? 'a' : 'b'}">`
                     + `<div>`
                         + `<div class="chat-meta ${isA ? 'text-start' : 'text-end'}">${esc(who)} · ${t}</div>`
                         + `<div class="chat-bubble">${esc(msg.body)}</div>`
                     + `</div>`
                     + `</div>`;
            }).join('');

            return head + `<div class="chat-wrap">${chat}</div>`;
        }

        function eventHeader(e){
            if(!e){ return ''; }
            var t = e.event_time ? new Date(e.event_time).toLocaleString('ko-KR') : '-';
            return `<div class="d-flex align-items-center flex-wrap gap-2">`
                 + `<i class="ti ti-calendar-event text-primary"></i>`
                 + `<span class="fw-bold fs-3">${esc(e.title) || '-'}</span>`
                 + `<span class="badge bg-azure-lt">${esc(e.type_label) || ''}</span>`
                 + `<span class="badge bg-secondary-lt">${esc(e.status_label) || ''}</span>`
                 + `<span class="text-muted ms-auto" style="font-size:0.9rem;">${t}</span>`
                 + `</div>`;
        }

        function matchButtons(m, a, b){
            var aName = esc(a.name || 'A'), bName = esc(b.name || 'B');
            return `<div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mt-2">`
                 + `<button type="button" class="btn btn-sm btn-outline-pink btn-voted" `
                     + `data-event-id="${m.event_id}" data-member-id="${m.a_member_id}" data-label="${aName}">`
                     + `<i class="ti ti-heart me-1"></i>${aName} 매칭 조회</button>`
                 + `<button type="button" class="btn btn-sm btn-outline-pink btn-voted" `
                     + `data-event-id="${m.event_id}" data-member-id="${m.b_member_id}" data-label="${bName}">`
                     + `<i class="ti ti-heart me-1"></i>${bName} 매칭 조회</button>`
                 + `</div>`;
        }

        // 함께한 행사들을 "행사 유형(와인파티/1:1)"별로 묶어서 섹션으로 렌더링.
        function meetingCard(m){
            var a = m.a || {}, b = m.b || {};
            return `<div class="card mb-3">`
                 + `<div class="card-header">${eventHeader(m.event)}</div>`
                 + `<div class="card-body">`
                     + compareTable(a, b)
                     + `<hr class="my-3">`
                     + relationRow(m, a, b)
                     + matchButtons(m, a, b)
                     + `<hr class="my-3">`
                     + chatBox(m, a, b)
                 + `</div></div>`;
        }

        function render(res){
            $('#summaryCard, #emptyCard').addClass('d-none');
            $('#meetingList').empty();

            if(!res){ return; }

            if(!res.found){
                var miss = [];
                if(!res.a){ miss.push('A'); }
                if(!res.b){ miss.push('B'); }
                $('#emptyText').text('전화번호 ' + miss.join(', ') + ' 로 참여한 이력이 없습니다.');
                $('#emptyCard').removeClass('d-none');
                return;
            }

            $('#summaryA').html(summaryPerson(res.a));
            $('#summaryB').html(summaryPerson(res.b));
            $('#meetingCount').text((res.meetings || []).length);

            // 함께한 행사를 유형별로 집계해 배지로 표시 (와인파티 → 1:1 → 기타 순).
            var byType = {};
            (res.meetings || []).forEach(function(m){
                var key = (m.event && m.event.type) ? m.event.type : 'etc';
                if(!byType[key]){
                    byType[key] = { label: (m.event && m.event.type_label) ? m.event.type_label : '기타', count: 0 };
                }
                byType[key].count++;
            });
            var order = ['wine', 'one'];
            var typeHtml = Object.keys(byType).sort(function(x, y){
                var ix = order.indexOf(x); if(ix < 0){ ix = 99; }
                var iy = order.indexOf(y); if(iy < 0){ iy = 99; }
                return ix - iy;
            }).map(function(k){
                return `<span class="badge bg-azure-lt">${esc(byType[k].label)} ${byType[k].count}</span>`;
            }).join('');
            $('#meetingCountByType').html(typeHtml);

            $('#summaryCard').removeClass('d-none');

            if(!res.meetings || !res.meetings.length){
                $('#emptyText').text('두 사람이 함께 참여한 행사가 없습니다.');
                $('#emptyCard').removeClass('d-none');
                return;
            }

            $('#meetingList').html(res.meetings.map(meetingCard).join(''));
        }

        $('#btnLoad').on('click', function(){
            var a = ($('#phoneA').val() || '').trim();
            var b = ($('#phoneB').val() || '').trim();
            if(!a || !b){ salert({text:'두 전화번호를 모두 입력해주세요.', cancel:false}); return; }
            if(a === b){ salert({text:'서로 다른 전화번호를 입력해주세요.', cancel:false}); return; }

            $('#meetingList').empty();
            $.get(dataUrl, { phoneA: a, phoneB: b }, render).fail(function(xhr){
                salert({text: xhr?.responseJSON?.error?.message ?? '조회 중 오류가 발생했습니다.', cancel:false});
            });
        });

        // 각 행사 카드의 "매칭 조회" 버튼 (A/B 각자가 그 행사에서 투표/매칭한 상대).
        $(document).on('click', '.btn-voted', function(){
            var eventId  = $(this).data('event-id');
            var memberId = $(this).data('member-id');
            var label    = $(this).data('label') || '';
            VotedModal.open(eventId, memberId, label + ' 님이 선택/매칭한 상대');
        });

        // 쿼리스트링으로 넘어온 초기값이 있으면 자동 조회.
        if(($('#phoneA').val() || '').trim() && ($('#phoneB').val() || '').trim()){
            $('#btnLoad').click();
        }
    });
</script>

@include('admin.rotation._votedModal')
@endsection
