{{--
    매칭 조회 / 매칭 결과 모달 공용 — 선택한 사람 1명 = 카드 1장.
    - 보여주는 정보: 번호표·닉네임 라벨, 년생 · 직업 · 키 · 사는 곳, 핸드폰, (상대별) 투표 시각
    - 보여주지 않는 정보: MBTI · 흡연 · 생활습관 (회원 상세 모달에서 확인)
    - 사용법(JS): MatchCard.html(row, { countLabel: '선택' | '매칭' })   row = { voter, targets[], has_mutual }
                 MatchCard.filter(rows, keyword)                         번호표·닉네임·직업·핸드폰·지역 검색
    - 두 모달이 같은 화면에 함께 포함되므로 @once 로 한 번만 넣는다.
--}}
@once
<style>
    .mc-list{ display:grid; grid-template-columns:1fr; gap:.6rem; }
    @media (min-width: 992px){ .mc-list{ grid-template-columns:1fr 1fr; } }
    .mc-card{ border:1px solid #e9ecf1; border-radius:14px; background:#fff; padding:.75rem .85rem; box-shadow:0 1px 3px rgba(20,30,60,.05); }
    .mc-card.has-mutual{ border-color:#f5b5c8; }
    .mc-head{ display:flex; align-items:flex-start; gap:.6rem; }
    .mc-ticket{ flex:0 0 auto; width:48px; height:48px; border-radius:12px; color:#fff; display:flex; flex-direction:column;
        align-items:center; justify-content:center; line-height:1.05; }
    .mc-ticket small{ font-size:.68rem; font-weight:700; opacity:.85; }
    .mc-ticket b{ font-size:1.15rem; font-weight:800; }
    .mc-ticket.is-m{ background:#206bc4; } .mc-ticket.is-f{ background:#d6336c; }
    .mc-who{ flex:1 1 auto; min-width:0; }
    .mc-name{ font-weight:800; overflow-wrap:anywhere; line-height:1.3; }
    .mc-spec{ font-size:.82rem; color:#495057; line-height:1.5; overflow-wrap:anywhere; }
    .mc-phone{ font-size:.82rem; color:#495057; font-variant-numeric:tabular-nums; }
    .mc-phone a{ color:inherit; text-decoration:none; }
    .mc-count{ flex:0 0 auto; text-align:right; font-size:.78rem; color:#495057; white-space:nowrap; }
    .mc-targets{ margin-top:.6rem; border-top:1px dashed #e9ecf1; padding-top:.4rem; display:flex; flex-direction:column; gap:.3rem; }
    .mc-target{ display:flex; gap:.5rem; padding:.45rem .55rem; border-radius:10px; background:#f8f9fa; }
    .mc-target.is-mutual{ background:#fff0f5; }
    .mc-target > .ti{ margin-top:.15rem; flex:0 0 auto; }
    .mc-target-body{ flex:1 1 auto; min-width:0; }
    .mc-target-top{ display:flex; align-items:center; gap:.4rem; }
    .mc-target-name{ font-weight:700; min-width:0; overflow-wrap:anywhere; }
    .mc-time{ margin-left:auto; font-size:.75rem; color:#868e96; white-space:nowrap; }
    .mc-empty{ grid-column:1 / -1; text-align:center; color:#868e96; padding:2.5rem 0; }
</style>
<script>
    window.MatchCard = (function(){
        function esc(v){ return $('<div>').text(v == null ? '' : v).html(); }
        function labelOf(m){ return esc(m.label || (m.name ? m.name + '호' : '-')); }
        // 97년생 · 개발자 · 175cm · 강남구
        function spec(m){
            var p = [];
            if(m.birth_year){ p.push(String(m.birth_year).slice(-2) + '년생'); }
            if(m.job){ p.push(esc(m.job)); }
            if(m.height){ p.push(m.height + 'cm'); }
            if(m.region){ p.push(esc(m.region)); }
            return p.join(' · ');
        }
        // 01012345678 → 010-1234-5678 (누르면 전화)
        function phone(m){
            var raw = String(m.phone || '').replace(/\D+/g, '');
            if(!raw){ return ''; }
            var shown = raw.length === 11 ? raw.replace(/(\d{3})(\d{4})(\d{4})/, '$1-$2-$3') : raw;
            return '<div class="mc-phone"><i class="ti ti-phone me-1"></i><a href="tel:' + raw + '">' + esc(shown) + '</a></div>';
        }
        function time(v){
            if(!v){ return ''; }
            var d = new Date(v);
            if(isNaN(d.getTime())){ return ''; }
            var p = function(n){ return (n < 10 ? '0' : '') + n; };
            return (d.getMonth() + 1) + '/' + d.getDate() + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
        }

        function html(r, opts){
            opts = opts || {};
            var v = r.voter || {};
            var targets = r.targets || [];
            var mutualCnt = targets.filter(function(t){ return t.is_mutual; }).length;
            var vs = spec(v);
            var count = opts.countLabel === '매칭'
                ? '<span class="badge bg-pink-lt"><i class="ti ti-heart-filled text-pink me-1"></i>매칭 ' + targets.length + '</span>'
                : targets.length + '명 선택'
                  + (mutualCnt ? '<br><span class="badge bg-pink-lt mt-1"><i class="ti ti-heart-filled text-pink me-1"></i>매칭 ' + mutualCnt + '</span>' : '');
            var head = '<div class="mc-head">'
                + '<div class="mc-ticket ' + (v.gender === 'F' ? 'is-f' : 'is-m') + '"><small>' + (v.gender === 'F' ? '여' : '남') + '</small><b>' + esc(v.name || '-') + '</b></div>'
                + '<div class="mc-who"><div class="mc-name">' + labelOf(v) + '</div>'
                +   (vs ? '<div class="mc-spec">' + vs + '</div>' : '') + phone(v) + '</div>'
                + '<div class="mc-count">' + count + '</div></div>';
            var list = targets.map(function(t){
                var ts = spec(t);
                return '<div class="mc-target' + (t.is_mutual ? ' is-mutual' : '') + '">'
                     +   '<i class="ti ' + (t.is_mutual ? 'ti-heart-filled text-pink' : 'ti-arrow-right text-muted') + '"></i>'
                     +   '<div class="mc-target-body">'
                     +     '<div class="mc-target-top"><span class="mc-target-name">' + labelOf(t) + '</span>'
                     +       '<span class="mc-time">' + time(t.voted_at) + '</span></div>'
                     +     (ts ? '<div class="mc-spec">' + ts + '</div>' : '') + phone(t)
                     +   '</div>'
                     + '</div>';
            }).join('');
            return '<div class="mc-card' + (mutualCnt ? ' has-mutual' : '') + '">' + head + '<div class="mc-targets">' + list + '</div></div>';
        }

        function filter(rows, kw){
            if(!kw){ return rows; }
            var k = kw.toLowerCase();
            var kDigits = kw.replace(/\D+/g, '');
            function hit(m){
                if(!m){ return false; }
                if(kDigits.length >= 3 && String(m.phone || '').replace(/\D+/g, '').indexOf(kDigits) !== -1){ return true; }
                return [m.name, m.label, m.job, m.region].some(function(x){ return x && String(x).toLowerCase().indexOf(k) !== -1; });
            }
            return rows.filter(function(r){ return hit(r.voter) || (r.targets || []).some(hit); });
        }

        return { html: html, filter: filter };
    })();
</script>
@endonce
