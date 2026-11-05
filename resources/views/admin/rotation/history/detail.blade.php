@extends('layouts.app')

@section('title', '만남 이력 상세 조회')

@section('content')
<style>
    /* 프로필 비교 테이블: 가운데 라벨 기준으로 A/B 값을 좁게 모아 눈 이동 최소화 */
    .cmp-table { width: 100%; max-width: 720px; margin: 0 auto; border-collapse: separate; border-spacing: 0; }
    .cmp-table td, .cmp-table th { padding: 7px 12px; vertical-align: middle; }
    /* 항목을 '왼쪽'에 두고 A | B 값을 나란히 (좌→우로 자연스럽게 읽히게) */
    .cmp-table .cmp-label { width: 110px; white-space: nowrap; text-align: left; color: #6b7280; font-size: 0.82rem; background: #f8fafc; font-weight: 700; }
    .cmp-table .cmp-a { text-align: left; background: #f2f7ff; }
    .cmp-table .cmp-b { text-align: left; background: #fff2f7; }
    .cmp-table tr + tr td { border-top: 1px solid #eef1f5; }
    .cmp-head td { font-weight: 700; font-size: 1.0rem; border-bottom: 2px solid #e9edf2; }

    /* 메시지 채팅 박스: 폭을 제한해 가운데 모아 읽기 편하게 */
    .chat-wrap { max-width: 640px; margin: 0 auto; max-height: 360px; overflow-y: auto; padding: 4px; }
    .chat-row { display: flex; margin-bottom: 10px; }
    .chat-row.a { justify-content: flex-start; }
    .chat-row.b { justify-content: flex-end; }
    .chat-meta { font-size: 0.72rem; color: #9aa3af; margin-bottom: 2px; }
    .chat-bubble { max-width: 78%; padding: 8px 12px; border-radius: 14px; font-size: 0.9rem; line-height: 1.45; word-break: break-word; }
    .chat-row.a .chat-bubble { background: #e7f1ff; color: #10408a; border-top-left-radius: 4px; }
    .chat-row.b .chat-bubble { background: #ffe6f0; color: #9c1f54; border-top-right-radius: 4px; }

    /* 유형 배지: 와인파티/1:1 을 색으로 구분 */
    .badge-type-wine { background: #fdeee3; color: #c2410c; }
    .badge-type-one  { background: #e9e4fb; color: #5b21b6; }
    .badge-type-etc  { background: #eef1f5; color: #4b5563; }

    /* 매칭/메시지 영역: 뱃지 나열 대신 카드형 칩으로 한눈에 보이게 */
    .meet-section { margin-top: 18px; }
    /* 섹션 제목: 왼쪽 강조 바 + 또렷한 글씨로 섹션 구분을 명확히 한다. */
    .meet-section__title { display:flex; align-items:center; gap:8px; font-weight:800; font-size:0.98rem; color:#1f2937; margin-bottom:12px; padding-left:10px; border-left:4px solid #6c8cff; }
    .meet-section__title .ti { font-size:1.15rem; color:#6c8cff; }
    .meet-vote-row { display:flex; align-items:center; justify-content:center; gap:14px; flex-wrap:wrap; }
    .meet-vote-chip { display:flex; align-items:center; gap:8px; padding:8px 14px; border-radius:12px; background:#f8fafc; border:1px solid #eef1f5; font-size:0.85rem; }
    .meet-vote-chip .ti { font-size:1.1rem; }
    .meet-vote-chip.is-yes { background:#eef9f1; border-color:#d7f0de; color:#1f7a3d; }
    .meet-vote-chip.is-no { color:#9aa3af; }
    .meet-mutual-banner { text-align:center; padding:10px; border-radius:12px; margin-bottom:12px; font-weight:700; }
    .meet-mutual-banner.is-mutual { background:#ffeef4; color:#c0266e; }
    .meet-mutual-banner.is-partial { background:#fff9e8; color:#92740a; }
    .meet-mutual-banner.is-none { background:#f3f4f6; color:#6b7280; }
    .meet-msg-count { text-align:center; font-size:0.82rem; color:#8a92a0; margin-bottom:8px; }

    /* 행사별 '당시 신원' 헤더: 번호 기준 조회라 행사마다 닉네임/번호표/플랫폼이 다를 수 있어
       각 행사 카드 상단에 "그 행사에서의 신원"을 또렷한 카드로 보여준다. */
    .meet-identity { display:flex; align-items:stretch; justify-content:center; gap:10px; flex-wrap:wrap; margin-bottom:4px; }
    .meet-id-card { flex:1 1 0; min-width:220px; max-width:320px; border:1px solid #eef1f5; border-radius:14px; padding:12px 14px; background:#fff; }
    .meet-id-card.is-a { border-color:#dbe7ff; background:linear-gradient(180deg,#f5f9ff,#ffffff); }
    .meet-id-card.is-b { border-color:#ffdcec; background:linear-gradient(180deg,#fff5fa,#ffffff); }
    .meet-id-top { display:flex; align-items:center; gap:6px; margin-bottom:6px; }
    .meet-id-tag { font-weight:800; font-size:0.72rem; padding:2px 8px; border-radius:999px; }
    .meet-id-tag.is-a { background:#dbe7ff; color:#1d4ed8; }
    .meet-id-tag.is-b { background:#ffdcec; color:#be185d; }
    .meet-id-nick { font-weight:800; font-size:1.05rem; color:#1f2937; line-height:1.2; word-break:break-all; }
    .meet-id-ticket { font-weight:800; font-size:0.9rem; color:#374151; }
    /* "항목: 값" 한 줄 */
    .meet-id-line { display:flex; gap:6px; align-items:baseline; margin-top:5px; font-size:0.9rem; }
    .meet-id-k { flex:0 0 58px; color:#8a92a0; font-weight:700; }
    .meet-id-v { color:#1f2937; font-weight:800; word-break:break-all; }
    .meet-id-sub { display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-top:6px; }
    .meet-id-connector { display:flex; align-items:center; color:#c4ccd6; font-size:1.3rem; padding:0 2px; }
    @media (max-width: 560px){ .meet-id-connector{ display:none; } }

    /* 관계 종합 통계 카드 (함께한 행사 / 상호매칭 / 한쪽선택 / 메시지) */
    .meet-stats { display:flex; gap:10px; flex-wrap:wrap; justify-content:center; }
    .meet-stat { flex:1 1 140px; max-width:200px; text-align:center; border:1px solid #eef1f5; border-radius:14px; padding:14px 10px; background:#fff; }
    .meet-stat__ico { font-size:1.4rem; line-height:1; margin-bottom:4px; }
    .meet-stat__num { font-size:1.8rem; font-weight:800; line-height:1.1; color:#1f2937; }
    .meet-stat__lbl { font-size:0.8rem; color:#8a92a0; font-weight:700; margin-top:2px; }
    .meet-stat.is-event  { background:#eef4ff; border-color:#dbe7ff; } .meet-stat.is-event  .meet-stat__ico, .meet-stat.is-event  .meet-stat__num { color:#2563eb; }
    .meet-stat.is-mutual { background:#ffeef4; border-color:#ffdcec; } .meet-stat.is-mutual .meet-stat__ico, .meet-stat.is-mutual .meet-stat__num { color:#c0266e; }
    .meet-stat.is-oneway { background:#fff9e8; border-color:#fdeec0; } .meet-stat.is-oneway .meet-stat__ico, .meet-stat.is-oneway .meet-stat__num { color:#92740a; }
    .meet-stat.is-msg    { background:#eef9f1; border-color:#d7f0de; } .meet-stat.is-msg    .meet-stat__ico, .meet-stat.is-msg    .meet-stat__num { color:#1f7a3d; }

    /* 여태 사용한 닉네임 — 닉네임별로 '어느 행사에서 썼는지' 함께 표기 */
    .meet-nick-item { margin:4px 0; line-height:1.6; }
    .meet-nick-badge { display:inline-block; background:#f1f3f8; border:1px solid #e3e7ef; border-radius:999px; padding:2px 10px; margin-right:4px; font-size:0.82rem; font-weight:700; color:#374151; }
    .meet-nick-pf { color:#8a92a0; font-weight:600; font-size:0.75rem; }
    .meet-nick-at { color:#8a92a0; font-size:0.75rem; margin-right:4px; }
    .meet-nick-ev { display:inline-block; background:#eef4ff; border:1px solid #dbe7ff; border-radius:8px; padding:1px 8px; margin:2px 2px; font-size:0.78rem; font-weight:700; color:#2563eb; }
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
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="ti ti-users me-1"></i> 두 사람 요약</h3>
            </div>
            <div class="card-body">
                {{-- 유형별 함께한 행사 뱃지 (와인파티/1:1 ...) --}}
                <div class="text-center mb-3">
                    <div class="d-flex justify-content-center gap-1 flex-wrap" id="meetingCountByType"></div>
                </div>
                {{-- 관계 종합(통계 카드) + 고정 프로필 비교 표 --}}
                <div id="summaryCompare"></div>
                <div id="genderNote" class="d-none mt-3 text-center text-warning" style="font-size:0.85rem;"></div>
                <div class="mt-3 text-center text-muted" style="font-size:0.8rem;">
                    <i class="ti ti-info-circle me-1"></i>
                    전화번호로 묶은 결과예요. <b>닉네임·번호표·플랫폼·직업</b> 등은 <b>행사마다 다를 수 있어</b>,
                    각 행사 카드의 <b>프로필 비교</b>에서 그 행사 기준으로 보여드려요.
                </div>
            </div>
        </div>
    </div>

    {{-- 만남 이력 카드들 --}}
    <div class="col-12 d-none" id="meetingListHeading">
        <h3 class="mb-0 mt-2"><i class="ti ti-calendar-heart me-1 text-primary"></i> 함께 참여한 행사</h3>
        <div class="text-muted" style="font-size:0.82rem;">각 행사별로 그때의 신원·프로필·매칭·메시지를 보여줍니다.</div>
    </div>
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
        // 관리자 표기 라벨(`닉네임(플랫폼)_N호`). 서버 label 이 없으면 `N호`, 이름도 없으면 fb.
        function nm(p, fb){ return (p && (p.label || (p.name ? p.name + '호' : ''))) || fb; }

        function genderBadge(g){
            if(g === 'F'){ return '<span class="badge bg-pink-lt">여</span>'; }
            if(g === 'M'){ return '<span class="badge bg-blue-lt">남</span>'; }
            return '';
        }

        function platformBadge(v){
            if(v === 'frip'){ return '<span class="badge bg-cyan-lt">프립</span>'; }
            if(v === 'munto'){ return '<span class="badge bg-orange-lt">문토</span>'; }
            return '<span class="text-muted">-</span>';
        }

        // 행사 유형 배지: 와인파티(주황)와 1:1(보라)을 색으로 구분한다. (type: wine | one)
        function typeBadge(type, label, extra){
            var cls = type === 'wine' ? 'badge-type-wine' : (type === 'one' ? 'badge-type-one' : 'badge-type-etc');
            return `<span class="badge ${cls}">${esc(label || '')}${extra ? ' ' + extra : ''}</span>`;
        }

        function val(p, key){
            // 나이: 그 행사가 열린 해 기준 (서버가 행사 연도로 계산해 내려준다). 출생연도를 함께 보여준다.
            if(key === 'age'){
                if(p.birth_year && p.age){ return p.birth_year + '년생 (' + p.age + '세)'; }
                if(p.birth_year){ return p.birth_year + '년생'; }
                return p.age ? (p.age + '세') : '-';
            }
            if(key === 'platform'){ return p.platform_label ? esc(p.platform_label) : '-'; }
            if(key === 'nickname'){ return p.nickname ? esc(p.nickname) : '-'; }
            if(key === 'ticket'){
                if(p.ticket != null && p.ticket !== ''){ return esc(p.ticket) + '호'; }
                var mTicket = /_([0-9]+호)\s*$/.exec(p.label || '');
                return mTicket ? mTicket[1] : '-';
            }
            if(key === 'gender'){ return p.gender === 'F' ? '여' : (p.gender === 'M' ? '남' : '-'); }
            if(key === 'phone'){ return p.phone ? esc(p.phone) : '-'; }
            if(key === 'height'){ return p.height ? p.height + 'cm' : '-'; }
            if(key === 'smoke'){
                if(p.is_smoker === true){ return '흡연'; }
                if(p.is_smoker === false){ return '비흡연'; }
                return '-';
            }
            return esc(p[key] || '-');
        }

        // 항목 | A | B 비교 테이블 (항목을 왼쪽에 두고 A·B 를 나란히 → 좌→우로 읽기 편하게)
        // a, b 는 "그 행사에 입장등록한 당시"의 정보다. (플랫폼·닉네임·번호표·직업·키 등은 행사마다 다를 수 있다.)
        function compareTable(a, b){
            var rows = [
                ['플랫폼', 'platform'], ['닉네임', 'nickname'], ['번호표', 'ticket'], ['성별', 'gender'],
                ['나이', 'age'], ['직업', 'job'], ['키', 'height'],
                ['MBTI', 'mbti'], ['지역', 'region'], ['흡연', 'smoke'],
            ];
            var body = rows.map(function(r){
                return `<tr>`
                     + `<td class="cmp-label">${r[0]}</td>`
                     + `<td class="cmp-a">${val(a, r[1])}</td>`
                     + `<td class="cmp-b">${val(b, r[1])}</td>`
                     + `</tr>`;
            }).join('');

            return `<table class="cmp-table">`
                 + `<tr class="cmp-head">`
                     + `<td class="cmp-label">항목</td>`
                     + `<td class="cmp-a"><span class="badge bg-blue-lt">A</span> 프로필</td>`
                     + `<td class="cmp-b"><span class="badge bg-pink-lt">B</span> 프로필</td>`
                 + `</tr>`
                 + body
                 + `</table>`;
        }

        // 상단 요약: 두 사람의 "고정 프로필 비교" + "관계 종합"을 한눈에.
        //  - 고정 정보(전화번호·성별·출생연도)만 비교표로. (닉네임·플랫폼·번호표 등 행사마다 바뀌는 건 각 행사 카드에서)
        //  - 성별/출생연도는 가장 최근 함께한 행사 기준 대표값. (행사마다 다르면 아래 경고로 보완)
        //  - meetings 를 종합해 상호매칭/한쪽선택/메시지 수를 집계해 관계를 요약한다.
        function summarySection(a, b, meetings){
            a = a || {}; b = b || {};
            meetings = meetings || [];

            // 대표 성별/출생연도: 가장 최근(목록 첫 번째) 함께한 행사의 값. 없으면 identity 폴백.
            var latest = meetings[0] || {};
            var la = latest.a || {}, lb = latest.b || {};
            function gender(p){ return p.gender === 'F' ? '여' : (p.gender === 'M' ? '남' : '-'); }
            function birth(p){
                if(p.birth_year && p.age){ return p.birth_year + '년생 (' + p.age + '세)'; }
                if(p.birth_year){ return p.birth_year + '년생'; }
                return p.age ? (p.age + '세') : '-';
            }
            function phone(p){ return p.phone ? esc(p.phone) : '<span class="text-muted">-</span>'; }

            // 여태 사용한 '플랫폼 닉네임' 목록 — 각 닉네임을 "어느 행사에서 썼는지" 함께 보여준다.
            //  - (닉네임+플랫폼) 조합별로 묶고, 그 조합을 쓴 행사명들을 함께 나열한다.
            function usedNicks(side){
                var map = {}, order = [];
                meetings.forEach(function(m){
                    var p = m[side] || {};
                    if(!p.nickname){ return; }
                    var pf  = p.platform_label ? p.platform_label : '';
                    var key = p.nickname + '|' + pf;
                    if(!map[key]){
                        map[key] = { nick: p.nickname, pf: pf, events: [] };
                        order.push(key);
                    }
                    var evName = (m.event && m.event.title) ? m.event.title : '행사';
                    if(map[key].events.indexOf(evName) === -1){ map[key].events.push(evName); }
                });
                if(!order.length){ return '<span class="text-muted">-</span>'; }

                return order.map(function(key){
                    var it = map[key];
                    var evs = it.events.map(function(e){
                        return '<span class="meet-nick-ev">' + esc(e) + '</span>';
                    }).join('');
                    return '<div class="meet-nick-item">'
                         + '<span class="meet-nick-badge">' + esc(it.nick)
                         + (it.pf ? ' <span class="meet-nick-pf">' + esc(it.pf) + '</span>' : '') + '</span>'
                         + '<span class="meet-nick-at">사용 행사:</span> ' + evs
                         + '</div>';
                }).join('');
            }

            var rows = [
                ['전화번호',      phone(a),         phone(b)],
                ['성별',          gender(la),       gender(lb)],
                ['출생연도',      birth(la),        birth(lb)],
                ['사용한 닉네임', usedNicks('a'),   usedNicks('b')],
            ];
            var body = rows.map(function(r){
                return `<tr><td class="cmp-label">${r[0]}</td><td class="cmp-a">${r[1]}</td><td class="cmp-b">${r[2]}</td></tr>`;
            }).join('');

            var compare = `<table class="cmp-table">`
                 + `<tr class="cmp-head">`
                     + `<td class="cmp-label">항목</td>`
                     + `<td class="cmp-a"><span class="badge bg-blue-lt">A</span></td>`
                     + `<td class="cmp-b"><span class="badge bg-pink-lt">B</span></td>`
                 + `</tr>` + body + `</table>`;

            // 관계 종합: 여러 행사 통틀어 상호매칭/한쪽선택/메시지 집계.
            var mutual = 0, oneWay = 0, msgTotal = 0;
            meetings.forEach(function(m){
                if(m.is_mutual){ mutual++; }
                else if(m.a_voted_b || m.b_voted_a){ oneWay++; }
                msgTotal += (m.messages_a_to_b || 0) + (m.messages_b_to_a || 0);
            });
            function statCard(cls, icon, value, label){
                return `<div class="meet-stat ${cls}"><div class="meet-stat__ico"><i class="ti ${icon}"></i></div>`
                     + `<div class="meet-stat__num">${value}</div><div class="meet-stat__lbl">${label}</div></div>`;
            }
            var stats = `<div class="meet-stats">`
                 + statCard('is-event', 'ti-calendar-heart', meetings.length, '함께한 행사')
                 + statCard('is-mutual', 'ti-heart-filled', mutual, '상호 매칭')
                 + statCard('is-oneway', 'ti-heart', oneWay, '한쪽만 선택')
                 + statCard('is-msg', 'ti-messages', msgTotal, '주고받은 메시지')
                 + `</div>`;

            return stats + `<div class="mt-3">` + compare + `</div>`;
        }

        // 매칭 결과: 상호 매칭이면 눈에 띄는 배너로, 투표 방향은 카드형 칩 2개로 한눈에 보이게.
        function relationRow(m, a, b){
            var bannerCls = m.is_mutual ? 'is-mutual' : ((m.a_voted_b || m.b_voted_a) ? 'is-partial' : 'is-none');
            var bannerText = m.is_mutual
                ? '💘 서로 선택했어요 (상호 매칭)'
                : ((m.a_voted_b || m.b_voted_a) ? '한쪽만 선택했어요' : '서로 선택하지 않았어요');
            var banner = `<div class="meet-mutual-banner ${bannerCls}">${bannerText}</div>`;

            function chip(fromLabel, toLabel, voted){
                var cls = voted ? 'is-yes' : 'is-no';
                var icon = voted ? 'ti-heart-filled' : 'ti-heart';
                return `<div class="meet-vote-chip ${cls}">`
                     + `<i class="ti ${icon}"></i>`
                     + `<span><b>${esc(fromLabel)}</b> → ${esc(toLabel)}</span>`
                     + `<span class="fw-bold">${voted ? '선택함' : '선택 안 함'}</span>`
                     + `</div>`;
            }

            var row = `<div class="meet-vote-row">`
                     + chip(nm(a,'A'), nm(b,'B'), m.a_voted_b)
                     + chip(nm(b,'B'), nm(a,'A'), m.b_voted_a)
                     + `</div>`;

            return `<div class="meet-section">`
                 + `<div class="meet-section__title"><i class="ti ti-heart"></i> 매칭 결과</div>`
                 + banner + row
                 + `</div>`;
        }

        // 메시지: 방향별 건수를 한 줄로 요약하고, 아래에 대화를 말풍선으로 보여준다.
        function chatBox(m, a, b){
            var an = esc(nm(a,'A')), bn = esc(nm(b,'B'));
            var total = (m.messages_a_to_b || 0) + (m.messages_b_to_a || 0);
            var title = `<div class="meet-section__title"><i class="ti ti-messages"></i> 메시지 `
                      + `<span class="badge bg-secondary-lt">총 ${total}건</span></div>`;

            if(!m.messages || !m.messages.length){
                return `<div class="meet-section">${title}`
                     + `<div class="text-muted text-center py-3">주고받은 메시지가 없습니다.</div></div>`;
            }

            var count = `<div class="meet-msg-count">`
                      + `${an} → ${bn} <b>${m.messages_a_to_b}</b>건 · ${bn} → ${an} <b>${m.messages_b_to_a}</b>건`
                      + `</div>`;

            var chat = m.messages.map(function(msg){
                var isA = msg.from === 'a';
                var who = isA ? nm(a,'A') : nm(b,'B');
                var t = msg.created_at ? new Date(msg.created_at).toLocaleString('ko-KR') : '';
                return `<div class="chat-row ${isA ? 'a' : 'b'}">`
                     + `<div>`
                         + `<div class="chat-meta ${isA ? 'text-start' : 'text-end'}">${esc(who)} · ${t}</div>`
                         + `<div class="chat-bubble">${esc(msg.body)}</div>`
                     + `</div>`
                     + `</div>`;
            }).join('');

            return `<div class="meet-section">${title}${count}<div class="chat-wrap">${chat}</div></div>`;
        }

        function eventHeader(e){
            if(!e){ return ''; }
            var t = e.event_time ? new Date(e.event_time).toLocaleString('ko-KR') : '-';
            return `<div class="d-flex align-items-center flex-wrap gap-2">`
                 + `<i class="ti ti-calendar-event text-primary"></i>`
                 + `<span class="fw-bold fs-3">${esc(e.title) || '-'}</span>`
                 + typeBadge(e.type, e.type_label)
                 + `<span class="badge bg-secondary-lt">${esc(e.status_label) || ''}</span>`
                 + `<span class="text-muted ms-auto" style="font-size:0.9rem;">${t}</span>`
                 + `</div>`;
        }

        // 각자가 그 행사에서 투표/매칭한 상대 목록을 여는 버튼 (매칭 결과 섹션 바로 아래)
        function matchButtons(m, a, b){
            var aName = esc(nm(a,'A')), bName = esc(nm(b,'B'));
            return `<div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mt-3">`
                 + `<button type="button" class="btn btn-sm btn-outline-pink btn-voted" `
                     + `data-event-id="${m.event_id}" data-member-id="${m.a_member_id}" data-label="${aName}">`
                     + `<i class="ti ti-list-details me-1"></i>${aName} 이(가) 고른 상대 보기</button>`
                 + `<button type="button" class="btn btn-sm btn-outline-pink btn-voted" `
                     + `data-event-id="${m.event_id}" data-member-id="${m.b_member_id}" data-label="${bName}">`
                     + `<i class="ti ti-list-details me-1"></i>${bName} 이(가) 고른 상대 보기</button>`
                 + `</div>`;
        }

        function meetingCard(m){
            var a = m.a || {}, b = m.b || {};
            return `<div class="card mb-3">`
                 + `<div class="card-header">${eventHeader(m.event)}</div>`
                 + `<div class="card-body">`
                     // ① 프로필 비교 (신원 정보 — 플랫폼·닉네임·번호표·성별 포함)
                     + `<div class="meet-section"><div class="meet-section__title"><i class="ti ti-user-circle"></i> 프로필 비교</div>`
                     + compareTable(a, b)
                     + `</div>`
                     // ② 매칭 결과 (relationRow 가 자체 제목 '매칭 결과' 포함)
                     + relationRow(m, a, b)
                     + matchButtons(m, a, b)
                     + `<hr class="my-3">`
                     // ③ 메시지 (chatBox 가 자체 제목 '메시지' 포함)
                     + chatBox(m, a, b)
                 + `</div></div>`;
        }

        function render(res){
            $('#summaryCard, #emptyCard, #meetingListHeading').addClass('d-none');
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

            $('#summaryCompare').html(summarySection(res.a, res.b, res.meetings));

            // 성별이 행사마다 다른지 확인해 안내한다. (예: 번호를 다른 사람에게 넘겨받아 다른 성별로 참여한 경우)
            var gendersA = new Set((res.meetings || []).map(function(m){ return m.a && m.a.gender; }).filter(Boolean));
            var gendersB = new Set((res.meetings || []).map(function(m){ return m.b && m.b.gender; }).filter(Boolean));
            var genderNote = '';
            if(gendersA.size > 1){ genderNote += '<div>⚠️ A 는 행사마다 성별이 다르게 기록돼 있어요. 행사별 카드를 확인하세요.</div>'; }
            if(gendersB.size > 1){ genderNote += '<div>⚠️ B 는 행사마다 성별이 다르게 기록돼 있어요. 행사별 카드를 확인하세요.</div>'; }
            $('#genderNote').html(genderNote).toggleClass('d-none', !genderNote);

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
                return typeBadge(k, byType[k].label, byType[k].count);
            }).join(' ');
            $('#meetingCountByType').html(typeHtml);

            $('#summaryCard').removeClass('d-none');

            if(!res.meetings || !res.meetings.length){
                $('#emptyText').text('두 사람이 함께 참여한 행사가 없습니다.');
                $('#emptyCard').removeClass('d-none');
                return;
            }

            $('#meetingListHeading').removeClass('d-none');
            $('#meetingList').html(res.meetings.map(meetingCard).join(''));
        }

        // 두 번호로 조회한다.
        //  - pushUrl=true  : 버튼으로 조회한 경우. 주소창에 ?phoneA=…&phoneB=… 를 남겨 새로고침·공유·뒤로가기를 지원한다.
        //  - pushUrl=false : 주소에서 이미 읽어 온 값으로 조회하는 경우(최초 진입/뒤로·앞으로 가기).
        var pageUrl = window.location.pathname;
        function runSearch(a, b, pushUrl){
            if(!a || !b){ salert({text:'두 전화번호를 모두 입력해주세요.', cancel:false}); return; }
            if(a === b){ salert({text:'서로 다른 전화번호를 입력해주세요.', cancel:false}); return; }

            if(pushUrl){
                var qs = '?phoneA=' + encodeURIComponent(a) + '&phoneB=' + encodeURIComponent(b);
                // 같은 조회를 반복해도 히스토리가 쌓이지 않게, 주소가 같으면 그대로 둔다.
                if(window.location.search !== qs){
                    history.pushState({ phoneA: a, phoneB: b }, '', pageUrl + qs);
                }
            }

            $('#meetingList').empty();
            $.get(dataUrl, { phoneA: a, phoneB: b }, render).fail(function(xhr){
                salert({text: xhr?.responseJSON?.error?.message ?? '조회 중 오류가 발생했습니다.', cancel:false});
            });
        }

        $('#btnLoad').on('click', function(){
            runSearch(($('#phoneA').val() || '').trim(), ($('#phoneB').val() || '').trim(), true);
        });

        // 뒤로/앞으로 가기: 주소의 phoneA/phoneB 로 입력창을 다시 채우고 조회한다.
        window.addEventListener('popstate', function(){
            var q = new URLSearchParams(window.location.search);
            var a = (q.get('phoneA') || '').trim();
            var b = (q.get('phoneB') || '').trim();
            $('#phoneA').val(a);
            $('#phoneB').val(b);
            if(a && b){
                runSearch(a, b, false);
            } else {
                // 조회 이전 상태로 되돌아온 경우: 결과를 비우고 안내 문구를 처음 상태로.
                render(null);
                $('#emptyText').text('두 사람의 전화번호를 입력하고 [이력 조회]를 눌러주세요.');
                $('#emptyCard').removeClass('d-none');
            }
        });

        // 각 행사 카드의 "매칭 조회" 버튼 (A/B 각자가 그 행사에서 투표/매칭한 상대).
        $(document).on('click', '.btn-voted', function(){
            var eventId  = $(this).data('event-id');
            var memberId = $(this).data('member-id');
            var label    = $(this).data('label') || '';
            var who = label || '회원';
            VotedModal.open(eventId, memberId, who + ' 님이 선택/매칭한 상대');
        });

        // 쿼리파라미터(?phoneA=…&phoneB=…)로 들어왔으면 자동 조회. (주소는 이미 맞으므로 pushUrl=false)
        (function(){
            var a = ($('#phoneA').val() || '').trim();
            var b = ($('#phoneB').val() || '').trim();
            if(a && b){ runSearch(a, b, false); }
        })();
    });
</script>

@include('admin.rotation._votedModal')
@endsection
