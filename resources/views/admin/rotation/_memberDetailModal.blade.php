{{--
    회원 상세정보 모달.
    - 참여자 목록의 "상세보기" 버튼으로 호출한다.
    - 추가된 회원 정보(프로필 전체)를 보기 좋은 UI로 보여준다.
    - 사용법(JS): MemberDetailModal.open(memberData)
      memberData 는 참여자 행의 data-member(@json) 객체.
    - [수정] 으로 같은 모달에서 회원 정보를 고칠 수 있다. (저장 시 PUT members.update)
      번호표·성별은 체크인 명단과 함께 바뀐다. (서버가 중복·기록 여부를 검증) 테이블·플랫폼은 여기서 바꾸지 않는다.
    - 비밀번호는 최고관리자(isSuperAdmin)에게만 입력칸이 보인다.
    포함 시 변수: $event(EventRound), $isSuperAdmin(bool)
--}}
@php
    $lifestyleOptions = [
        'drinkCapacity'       => \App\Models\Member::drinkCapacityOptions(),
        'drinkFrequency'      => \App\Models\Member::drinkFrequencyOptions(),
        'friendMeetFrequency' => \App\Models\Member::friendMeetFrequencyOptions(),
        'religion'            => \App\Models\Member::religionOptions(),
        'outerPreference'     => \App\Models\Member::outerPreferenceOptions(),
        'innerPreference'     => \App\Models\Member::innerPreferenceOptions(),
    ];
    $memberEditDrinks = ($event ?? null) && $event->isOneToOne()
        ? $event->drinks->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])->values()
        : collect();
    $memberUpdateUrlTemplate = route('admin.rotation.event.members.update', ['id' => $event->id, 'memberId' => '__MID__']);
    $memberImpersonateUrlTemplate = route('admin.rotation.event.members.impersonate', ['id' => $event->id, 'memberId' => '__MID__']);
@endphp
<style>
    /* 수정 폼 안내문구: 한 줄씩, 작은 화면에서도 읽히는 크기/색 */
    #memberDetailModal .member-edit-notes > div,
    #memberDetailModal .member-edit-hint > div,
    #memberDetailModal .member-edit-hint{ display:block; width:100%; white-space:normal; word-break:keep-all; overflow-wrap:break-word; }
    /* Tabler 의 .alert 는 display:flex 라 자식 div 가 가로(열)로 늘어선다 → block 으로 바꿔 한 행에 한 문장 */
    #memberDetailModal .member-edit-notes{ display:block !important; font-size:.92rem; line-height:1.7; }
    #memberDetailModal .member-edit-hint{ font-size:.88rem; line-height:1.65; color:#495057; }
    /* 모바일: 하단 버튼은 홈 인디케이터(safe area)만큼 띄운다. (요청 플로팅 버튼은 모바일에서 전역으로 숨김) */
    @media (max-width: 767.98px){
        /* 작은 화면: 주소창 등으로 100vh 가 실제 보이는 높이보다 커서 모달이 넘치므로, 보이는 높이(dvh)에 고정하고 본문만 스크롤한다. */
        #memberDetailModal .modal-dialog-scrollable{ height:calc(100dvh - 1rem); max-height:calc(100dvh - 1rem); }
        #memberDetailModal .modal-footer{ padding-bottom:calc(.75rem + env(safe-area-inset-bottom)); }
    }
</style>
<div class="modal fade" id="memberDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">회원 상세정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-2">
              <div id="memberDetail-view">
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

              {{-- 수정 폼 --}}
              <form id="memberDetail-edit" class="d-none" autocomplete="off" onsubmit="return false;">
                <div class="alert alert-info py-2 mb-3 member-edit-notes">
                    <div><i class="ti ti-info-circle me-1"></i>번호표·성별을 바꾸면 체크인 명단에도 함께 바뀌어요.</div>
                    <div><i class="ti ti-info-circle me-1"></i>이미 다른 사람이 쓰는 번호는 쓸 수 없어요.</div>
                    <div><i class="ti ti-info-circle me-1"></i>투표·메시지 기록이 있으면 성별을 바꿀 수 없어요.</div>
                    <div><i class="ti ti-info-circle me-1"></i>테이블은 체크인 화면의 <b>번호·테이블 변경</b>에서 바꿔요.</div>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label mb-1">번호표 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" name="name" inputmode="numeric" maxlength="3" placeholder="예: 3">
                    </div>
                    <div class="col-6">
                        <label class="form-label mb-1 d-block">성별 <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group" aria-label="성별">
                            <input type="radio" class="btn-check" name="gender" id="memberEditGenderM" value="M" autocomplete="off">
                            <label class="btn btn-outline-blue btn-lg" for="memberEditGenderM">남</label>
                            <input type="radio" class="btn-check" name="gender" id="memberEditGenderF" value="F" autocomplete="off">
                            <label class="btn btn-outline-pink btn-lg" for="memberEditGenderF">여</label>
                        </div>
                    </div>
                    <div class="col-12 d-none" id="memberDetail-nameHint">
                        <div class="member-edit-hint">숫자 번호표가 아니라 번호표는 바꿀 수 없어요.</div>
                    </div>
                    @if (($event->current_round ?? 0) > 0)
                        <div class="col-12">
                            <div class="member-edit-hint">
                                <div><i class="ti ti-alert-triangle me-1 text-warning"></i>로테이션이 시작된 뒤 성별을 바꾸면</div>
                                <div class="ps-4">자리 이동 방식(여자 고정 / 남자 이동)이 바뀌어</div>
                                <div class="ps-4">현재 자리가 달라져요.</div>
                            </div>
                        </div>
                    @endif
                    <div class="col-12">
                        <label class="form-label mb-1">핸드폰 <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" name="phone" inputmode="numeric" maxlength="13" placeholder="01012345678">
                        <div class="member-edit-hint mt-1">
                            <div>로그인 아이디예요.</div>
                            <div>바꾸면 참가자도 새 번호로 입장해야 해요.</div>
                            <div>만남 이력(번호 기준) 연결도 달라져요.</div>
                        </div>
                    </div>
                    @if (! empty($isSuperAdmin))
                        <div class="col-12">
                            <label class="form-label mb-1">비밀번호 <span class="text-muted fw-normal">(4자리)</span></label>
                            <input type="text" class="form-control font-monospace" name="password" maxlength="4" placeholder="변경할 때만 입력">
                            <div class="member-edit-hint mt-1"><div>비워두면 그대로 둬요.</div></div>
                        </div>
                    @endif
                    <div class="col-6 col-md-4">
                        <label class="form-label mb-1">출생연도</label>
                        <input type="text" class="form-control" name="birth_year" inputmode="numeric" maxlength="4" placeholder="1997">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label mb-1">키 (cm)</label>
                        <input type="text" class="form-control" name="height" inputmode="numeric" maxlength="3" placeholder="175">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label mb-1">직업</label>
                        <input type="text" class="form-control" name="job" maxlength="50">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label mb-1">MBTI</label>
                        <input type="text" class="form-control text-uppercase" name="mbti" maxlength="4" placeholder="ENFP">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label mb-1">지역/거주지</label>
                        <input type="text" class="form-control" name="region" maxlength="50">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label mb-1">흡연 여부</label>
                        <select class="form-select" name="is_smoker">
                            <option value="">미입력</option><option value="1">흡연</option><option value="0">비흡연</option>
                        </select>
                    </div>
                    <div class="col-6"><label class="form-label mb-1">주량</label><select class="form-select" name="drink_capacity"></select></div>
                    <div class="col-6"><label class="form-label mb-1">평균 음주 횟수</label><select class="form-select" name="drink_frequency"></select></div>
                    <div class="col-6"><label class="form-label mb-1">친구 약속 빈도</label><select class="form-select" name="friend_meet_frequency"></select></div>
                    <div class="col-6"><label class="form-label mb-1">종교</label><select class="form-select" name="religion"></select></div>
                    <div class="col-12"><label class="form-label mb-1">외적 조건 <span class="text-muted small">(여러 개 선택 가능)</span></label><div class="d-flex flex-wrap gap-3" id="md-outer-pref"></div></div>
                    <div class="col-12"><label class="form-label mb-1">내적 조건 <span class="text-muted small">(여러 개 선택 가능)</span></label><div class="d-flex flex-wrap gap-3" id="md-inner-pref"></div></div>
                    <div class="col-6">
                        <label class="form-label mb-1">애완동물</label>
                        <select class="form-select" name="has_pet">
                            <option value="">미입력</option><option value="1">있음</option><option value="0">없음</option>
                        </select>
                    </div>
                    @if ($memberEditDrinks->isNotEmpty())
                        <div class="col-12">
                            <label class="form-label mb-1">음료 <span class="text-muted fw-normal">(1:1)</span></label>
                            <select class="form-select" name="event_drink_id"></select>
                        </div>
                    @endif
                    <div class="col-12 col-md-6"><label class="form-label mb-1">이상형</label><input type="text" class="form-control" name="ideal_type" maxlength="100"></div>
                    <div class="col-12 col-md-6"><label class="form-label mb-1">나의 장점</label><input type="text" class="form-control" name="merit" maxlength="100"></div>
                    <div class="col-12 col-md-6"><label class="form-label mb-1">취미 / 요즘 빠진 것</label><input type="text" class="form-control" name="hobby" maxlength="100"></div>
                    <div class="col-12 col-md-6"><label class="form-label mb-1">TMI</label><input type="text" class="form-control" name="tmi" maxlength="100"></div>
                </div>
                <div class="member-edit-hint mt-3">
                    <div>키·출생연도·직업은 체크인 명단에도 같은 값으로 반영돼요.</div>
                    <div>비워두면 명단 값은 그대로 두고 회원 값만 비워요.</div>
                </div>
              </form>
            </div>

            <div class="modal-footer border-0 pt-0 flex-nowrap gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill flex-md-grow-0" id="memberDetail-closeBtn" data-bs-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary flex-fill flex-md-grow-0" id="memberDetail-editBtn"><i class="ti ti-edit me-1"></i>수정</button>
                <button type="button" class="btn btn-outline-secondary flex-fill flex-md-grow-0 d-none" id="memberDetail-cancelBtn">취소</button>
                <button type="button" class="btn btn-primary flex-fill flex-md-grow-0 d-none" id="memberDetail-saveBtn"><i class="ti ti-device-floppy me-1"></i>저장</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.MemberDetailModal = (function(){
        var modalEl = null;
        var bsModal = null;
        var current = null;   // 지금 보고 있는 회원 데이터
        var UPDATE_URL_TPL = {!! json_encode($memberUpdateUrlTemplate, JSON_UNESCAPED_SLASHES) !!};
        var IMPERSONATE_URL_TPL = {!! json_encode($memberImpersonateUrlTemplate, JSON_UNESCAPED_SLASHES) !!};
        var OPTIONS = {!! json_encode($lifestyleOptions, JSON_UNESCAPED_UNICODE) !!};
        var DRINKS  = {!! json_encode($memberEditDrinks, JSON_UNESCAPED_UNICODE) !!};

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

        function platformBadge(v){
            if(v === 'frip'){ return '<span class="badge bg-cyan-lt">프립</span>'; }
            if(v === 'munto'){ return '<span class="badge bg-orange-lt">문토</span>'; }
            return v ? esc(v) : '-';
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

            $('#memberDetail-name').text(d.label || (d.name ? d.name + '호' : '-'));
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
            basic += gridItem('주량', esc(d.drink_capacity) || '-');
            basic += gridItem('평균 음주 횟수', esc(d.drink_frequency) || '-');
            basic += gridItem('애완동물', (d.has_pet === true || d.has_pet === 1) ? '있음' : ((d.has_pet === false || d.has_pet === 0) ? '없음' : '-'));
            basic += gridItem('친구 약속 빈도', esc(d.friend_meet_frequency) || '-');
            basic += gridItem('종교', esc(d.religion) || '-');
            basic += gridItem('외적 조건', esc(listText(d.outer_preference)) || '-');
            basic += gridItem('내적 조건', esc(listText(d.inner_preference)) || '-');
            basic += gridItem('흡연 여부', smokerBadge(d.is_smoker));
            basic += gridItem('신청 플랫폼', platformBadge(d.platform));
            basic += gridItem('마지막 로그인', d.last_login_at ? esc(d.last_login_at) : '기록 없음');
            $('#memberDetail-basic').html(basic);

            // 자기소개
            var intro = '';
            intro += introBlock('이상형', d.ideal_type);
            intro += introBlock('나의 장점', d.merit);
            intro += introBlock('취미 / 요즘 빠진 것', d.hobby);
            intro += introBlock('TMI', d.tmi);
            $('#memberDetail-intro').html(intro);
        }

        // ----- 수정 모드 -----
        function fillSelect($sel, values, current){
            $sel.empty().append($('<option>').val('').text('미입력'));
            (values || []).forEach(function(v){ $sel.append($('<option>').val(v).text(v)); });
            $sel.val(current == null ? '' : current);
        }
        // 다중 선택 값(배열)을 "A, B" 문구로
        function listText(v){ return Array.isArray(v) ? v.join(', ') : ''; }
        // 다중 선택 체크박스 채우기. (name 을 주지 않아 form 직렬화와 섞이지 않게 하고, 저장 때 따로 모은다)
        // '상관없음'은 다른 항목과 함께 고를 수 없다.
        function fillChecks($box, values, current){
            var cur = Array.isArray(current) ? current : [];
            $box.empty();
            (values || []).forEach(function(v){
                var $l = $('<label class="form-check mb-0"></label>');
                var $i = $('<input type="checkbox" class="form-check-input md-pref-check">').val(v).prop('checked', cur.indexOf(v) !== -1);
                $l.append($i).append($('<span class="form-check-label"></span>').text(v));
                $box.append($l);
            });
        }
        function readChecks($box){
            return $box.find('.md-pref-check:checked').map(function(){ return this.value; }).get();
        }
        function boolVal(v){
            if(v === true || v === 1 || v === '1'){ return '1'; }
            if(v === false || v === 0 || v === '0'){ return '0'; }
            return '';
        }
        function setMode(editing){
            $('#memberDetail-view').toggleClass('d-none', editing);
            $('#memberDetail-edit').toggleClass('d-none', !editing);
            $('#memberDetail-closeBtn, #memberDetail-editBtn').toggleClass('d-none', editing);
            $('#memberDetail-cancelBtn, #memberDetail-saveBtn').toggleClass('d-none', !editing);
        }
        function startEdit(){
            var d = current, $f = $('#memberDetail-edit');
            if(!d){ return; }
            $f[0].reset();
            // 번호표·성별. 숫자 번호표가 아닌 예외 회원(이름 등)은 번호표를 바꿀 수 없어 입력을 막는다.
            var numeric = /^\d+$/.test(String(d.name == null ? '' : d.name));
            $f.find('[name=name]').val(numeric ? d.name : '').prop('disabled', !numeric);
            $('#memberDetail-nameHint').toggleClass('d-none', numeric);
            $f.find('[name=gender]').prop('checked', false).filter('[value=' + (d.gender === 'F' ? 'F' : 'M') + ']').prop('checked', true);
            $f.find('[name=phone]').val(d.phone || '');
            $f.find('[name=password]').val('');
            $f.find('[name=birth_year]').val(d.birth_year || '');
            $f.find('[name=height]').val(d.height || '');
            $f.find('[name=job]').val(d.job || '');
            $f.find('[name=mbti]').val(d.mbti || '');
            $f.find('[name=region]').val(d.region || '');
            $f.find('[name=is_smoker]').val(boolVal(d.is_smoker));
            $f.find('[name=has_pet]').val(boolVal(d.has_pet));
            fillSelect($f.find('[name=drink_capacity]'), OPTIONS.drinkCapacity, d.drink_capacity);
            fillSelect($f.find('[name=drink_frequency]'), OPTIONS.drinkFrequency, d.drink_frequency);
            fillSelect($f.find('[name=friend_meet_frequency]'), OPTIONS.friendMeetFrequency, d.friend_meet_frequency);
            fillSelect($f.find('[name=religion]'), OPTIONS.religion, d.religion);
            fillChecks($('#md-outer-pref'), OPTIONS.outerPreference, d.outer_preference);
            fillChecks($('#md-inner-pref'), OPTIONS.innerPreference, d.inner_preference);
            var $drink = $f.find('[name=event_drink_id]');
            if($drink.length){
                $drink.empty().append($('<option>').val('').text('미선택'));
                DRINKS.forEach(function(x){ $drink.append($('<option>').val(x.id).text(x.name)); });
                $drink.val(d.event_drink_id == null ? '' : String(d.event_drink_id));
            }
            ['ideal_type', 'merit', 'hobby', 'tmi'].forEach(function(k){ $f.find('[name=' + k + ']').val(d[k] || ''); });
            setMode(true);
        }
        function save(){
            var d = current;
            if(!d || !d.id){ return; }
            var $f = $('#memberDetail-edit');
            var phone = String($f.find('[name=phone]').val() || '').replace(/\D+/g, '');
            if(!/^010\d{8}$/.test(phone)){ alert('핸드폰 번호는 010으로 시작하는 11자리 숫자여야 합니다.'); return; }

            var $name = $f.find('[name=name]');
            if(!$name.prop('disabled')){
                var t = parseInt($name.val(), 10);
                if(!/^\d+$/.test(String($name.val() || '').trim()) || t < 1 || t > 999){ alert('번호표는 1~999 사이 숫자로 입력해 주세요.'); return; }
            }

            var data = {};
            $f.serializeArray().forEach(function(x){ data[x.name] = x.value; });
            data.phone = phone;
            // 다중 선택: 비우면 빈 값으로 보내 지운다. (배열 키는 jQuery 가 outer_preference[] 로 직렬화)
            var outer = readChecks($('#md-outer-pref')), inner = readChecks($('#md-inner-pref'));
            var ANY = '상관없음';
            if((outer.length > 1 && outer.indexOf(ANY) !== -1) || (inner.length > 1 && inner.indexOf(ANY) !== -1)){
                alert('상관없음은 다른 항목과 함께 고를 수 없습니다.'); return;
            }
            data.outer_preference = outer.length ? outer : '';
            data.inner_preference = inner.length ? inner : '';

            var $btn = $('#memberDetail-saveBtn').prop('disabled', true);
            $.ajax({
                url: UPDATE_URL_TPL.replace('__MID__', d.id),
                type: 'PUT',
                data: data,
                success: function(res){
                    alert(res?.msg);
                    // 표/뱃지/음료 현황이 모두 서버 값으로 다시 그려지도록 새로고침한다.
                    if(res?.status === 200){ location.reload(); }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); },
            }).always(function(){ $btn.prop('disabled', false); });
        }
        $(function(){
            // 관리자가 해당 회원으로 로그인: POST 폼을 새 탭으로 제출한다. (회원 로그인은 덮어쓰고, 관리자 로그인은 유지)
            $(document).on('click', '.btn-member-login', function(e){
                e.stopPropagation();
                var memberId = $(this).data('memberId');
                if(!memberId){ return; }
                var $form = $('<form method="POST" target="_blank" class="d-none"></form>')
                    .attr('action', IMPERSONATE_URL_TPL.replace('__MID__', memberId))
                    .append($('<input type="hidden" name="_token">').val($('meta[name="csrf-token"]').attr('content')));
                $('body').append($form);
                $form.trigger('submit');
                $form.remove();
            });
            // '상관없음'을 고르면 같은 묶음의 나머지를 해제하고, 다른 항목을 고르면 '상관없음'을 해제한다.
            $(document).on('change', '.md-pref-check', function(){
                var $box = $(this).closest('.d-flex');
                if(!this.checked){ return; }
                if(this.value === '상관없음'){ $box.find('.md-pref-check').not(this).prop('checked', false); }
                else { $box.find('.md-pref-check[value="상관없음"]').prop('checked', false); }
            });
            $('#memberDetail-editBtn').on('click', startEdit);
            $('#memberDetail-cancelBtn').on('click', function(){ setMode(false); });
            $('#memberDetail-saveBtn').on('click', save);
        });

        return {
            open: function(memberData){
                if(!memberData){ return; }
                current = memberData;
                setMode(false);
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
