{{--
    회원 상세정보 모달.
    - 참여자 목록의 "상세보기" 버튼으로 호출한다.
    - 추가된 회원 정보(프로필 전체)를 보기 좋은 UI로 보여준다.
    - 사용법(JS): MemberDetailModal.open(memberData)
      memberData 는 참여자 행의 data-member(@json) 객체.
--}}
<div class="modal modal-blur fade" id="memberDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">회원 상세정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-2">
                {{-- 프로필 헤더 --}}
                <div class="card card-sm mb-3">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar avatar-lg" id="memberDetail-avatar"></span>
                        <div class="flex-fill">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="h2 mb-0" id="memberDetail-name"></span>
                                <span id="memberDetail-gender"></span>
                            </div>
                            <div class="text-muted mt-1" id="memberDetail-sub"></div>
                        </div>
                    </div>
                </div>

                {{-- 기본 정보 --}}
                <div class="mb-1 text-uppercase text-muted fw-bold small">기본 정보</div>
                <div class="row g-2 mb-3" id="memberDetail-basic"></div>

                {{-- 자기소개 --}}
                <div class="mb-1 text-uppercase text-muted fw-bold small">자기소개</div>
                <div class="row g-2" id="memberDetail-intro"></div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.MemberDetailModal = (function(){
        var modalEl = null;
        var bsModal = null;

        function esc(v){
            if(v === null || v === undefined || v === '') { return '-'; }
            return $('<div>').text(String(v)).html();
        }

        function hasVal(v){
            return !(v === null || v === undefined || v === '');
        }

        function genderBadge(g){
            if(g === 'M'){ return '<span class="badge bg-blue-lt">남</span>'; }
            if(g === 'F'){ return '<span class="badge bg-pink-lt">여</span>'; }
            return '';
        }

        function isSmoker(v){
            return (v === true || v === 1 || v === '1');
        }

        function smokerBadge(v){
            if(v === null || v === undefined){ return '-'; }
            return isSmoker(v)
                ? '<span class="badge bg-red-lt">흡연</span>'
                : '<span class="badge bg-green-lt">비흡연</span>';
        }

        function subText(d){
            var parts = [];
            if(d.birth_year){
                parts.push(d.birth_year + '년생' + (d.age ? ' · ' + d.age + '세' : ''));
            } else if(d.age){
                parts.push(d.age + '세');
            }
            if(hasVal(d.region)){ parts.push($('<div>').text(d.region).html()); }
            if(hasVal(d.job)){ parts.push($('<div>').text(d.job).html()); }
            return parts.length ? parts.join('&nbsp;&nbsp;·&nbsp;&nbsp;') : '-';
        }

        function gridItem(label, valueHtml){
            return ''
                + '<div class="col-6 mb-2">'
                +   '<div class="datagrid-item">'
                +     '<div class="datagrid-title">' + label + '</div>'
                +     '<div class="datagrid-content">' + valueHtml + '</div>'
                +   '</div>'
                + '</div>';
        }

        function introBlock(label, value){
            return ''
                + '<div class="col-6 mb-2">'
                +   '<div class="small text-muted mb-1">' + label + '</div>'
                +   '<div class="p-2 rounded border bg-white text-body" style="min-height:42px;">' + esc(value) + '</div>'
                + '</div>';
        }

        function render(d){
            // 아바타 (성별 색상)
            var initial = d.name ? $('<div>').text(d.name.charAt(0)).html() : '?';
            var avatarCls = (d.gender === 'F') ? 'bg-pink-lt' : 'bg-blue-lt';
            $('#memberDetail-avatar').attr('class', 'avatar avatar-lg ' + avatarCls).text(d.name ? d.name.charAt(0) : '?');

            $('#memberDetail-name').text(d.name || '-');
            $('#memberDetail-gender').html(genderBadge(d.gender));
            $('#memberDetail-sub').html(subText(d));

            // 기본 정보 그리드
            var basic = '';
            basic += gridItem('핸드폰', esc(d.phone));
            if('password' in d){
                basic += gridItem('비밀번호', '<span class="text-red fw-bold font-monospace" style="user-select:all;cursor:text;">' + esc(d.password) + '</span>');
            }
            basic += gridItem('키', d.height ? esc(d.height) + 'cm' : '-');
            basic += gridItem('MBTI', esc(d.mbti));
            basic += gridItem('직업', esc(d.job));
            basic += gridItem('지역/거주지', esc(d.region));
            basic += gridItem('흡연 여부', smokerBadge(d.is_smoker));
            $('#memberDetail-basic').html(basic);

            // 자기소개
            var intro = '';
            intro += introBlock('이상형', d.ideal_type);
            intro += introBlock('나의 장점', d.merit);
            intro += introBlock('취미 / 요즘 빠진 것', d.hobby);
            intro += introBlock('TMI', d.tmi);
            $('#memberDetail-intro').html(intro);
        }

        return {
            open: function(memberData){
                if(!memberData){ return; }
                render(memberData);

                if(!modalEl){
                    modalEl = document.getElementById('memberDetailModal');
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
            },
        };
    })();
</script>
