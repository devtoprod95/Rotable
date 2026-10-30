<div class="sticky-top">
    <header class="navbar navbar-expand-md sticky-top d-print-none">
        <div class="container-xl">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <a href="{{ route('admin.dashboard') }}" class="navbar-brand navbar-brand-autodark d-flex align-items-center gap-2">
                <img src="{{ asset('images/logo.png') }}" alt="logo" style="height: 42px; width: 42px; object-fit: contain;">
                <span class="fw-bold fs-3">{{ env('APP_NAME') }}</span>
            </a>

            <div class="navbar-nav flex-row order-md-last">

                @if( !empty(auth('admin')->user()) )
                        @php
                            $isSuperAdmin = auth('admin')->user()?->hasRole(\App\Enums\Role::SuperAdmin->value) ?? false;
                        @endphp
                        @if ($isSuperAdmin)
                            @php
                                $telegramEnabled = \App\Services\TelegramNotifier::isEnabled();
                            @endphp
                            <div class="nav-item d-flex align-items-center me-2">
                                <label class="form-check form-switch form-switch-lg m-0 d-flex flex-column align-items-center" title="텔레그램 알림 on/off" style="padding-left:0;">
                                    <input class="form-check-input m-0" type="checkbox" id="telegramToggle"
                                           data-url="{{ route('admin.settings.telegram.toggle') }}"
                                           {{ $telegramEnabled ? 'checked' : '' }}>
                                    <span class="form-check-label fw-bold mt-1" style="font-size:0.75rem;">
                                        <span id="telegramToggleText">텔레그램 알림 {{ $telegramEnabled ? 'ON' : 'OFF' }}</span>
                                    </span>
                                </label>
                            </div>
                            <div class="nav-item d-flex align-items-center me-2">
                                <span class="vr" style="width:2px; height:32px; background-color:#adb5bd; opacity:1;"></span>
                            </div>
                            <div class="nav-item dropdown d-flex align-items-center me-2">
                                <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-bold" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-device-gamepad-2 me-1"></i> 미니게임 · 안내
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="{{ url('/public/games') }}" target="_blank" rel="noopener" class="dropdown-item">
                                        <i class="ti ti-device-gamepad-2 me-2 fs-2"></i> 공개 게임 열기
                                    </a>
                                    <a href="{{ route('public.guide') }}" target="_blank" rel="noopener" class="dropdown-item">
                                        <i class="ti ti-slideshow me-2 fs-2"></i> 안내 슬라이드
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <button type="button" class="dropdown-item" id="adminGuideOpenBtn">
                                        <i class="ti ti-help-circle me-2 fs-2 text-danger"></i> 사용 설명서
                                    </button>
                                </div>
                            </div>
                        @endif
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                                <span class="avatar avatar-sm">{{ mb_substr(auth('admin')->user()->name, 0, 1) }}</span>
                                <div class="d-none d-xl-block ps-2">
                                    <div>{{ auth('admin')->user()->name }}</div>
                                    <div class="mt-1 small text-muted">
                                        @foreach(auth('admin')->user()->roles as $role)
                                            {{ $role->display_name }}
                                            @if(!$loop->last) |
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a href="/profile" class="dropdown-item">
                                    <i class="ti ti-user me-2 fs-2"></i>프로필
                                </a>
                                <button type="button" class="dropdown-item text-danger btn-logout">
                                    <i class="ti ti-logout me-2 fs-2"></i>로그아웃
                                </button>
                            </div>
                        </div>
                @else
                    <div class="nav-item">
                        <a href="{{ route('admin.login') }}" class="btn btn-primary">
                            로그인
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </header>

    <header class="navbar-expand-md">
        <div class="collapse navbar-collapse" id="navbar-menu">
            <div class="navbar navbar-light">
                <div class="container-xl">
                    <div class="row flex-column flex-md-row flex-fill align-items-center">
                        <div class="col">
                            <x-menu></x-menu>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    @if( !empty(auth('admin')->user()) )
    {{-- 관리자 사용 설명서 모달 --}}
    <div class="modal modal-blur fade" id="adminGuideModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-book me-2"></i>로테이블 관리자 사용 설명서</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- 전체 흐름 --}}
                    <div class="card card-sm mb-4">
                        <div class="card-body">
                            <div class="fw-bold text-blue mb-2"><i class="ti ti-route me-1"></i> 전체 운영 흐름</div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="badge bg-blue text-white">① 행사 생성</span>
                                <i class="ti ti-chevron-right text-muted"></i>
                                <span class="badge bg-blue text-white">② 테이블/좌석 배정</span>
                                <i class="ti ti-chevron-right text-muted"></i>
                                <span class="badge bg-blue text-white">③ 로테이션 진행</span>
                                <i class="ti ti-chevron-right text-muted"></i>
                                <span class="badge bg-blue text-white">④ 투표 시작</span>
                                <i class="ti ti-chevron-right text-muted"></i>
                                <span class="badge bg-blue text-white">⑤ 투표 마감</span>
                                <i class="ti ti-chevron-right text-muted"></i>
                                <span class="badge bg-pink text-white">⑥ 매칭/메시지 확인</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        {{-- 좌측: 기능별 버튼 --}}
                        <div class="col-md-4">
                            <div class="list-group" id="guideTabs">
                                <button type="button" class="list-group-item list-group-item-action active" data-guide="event">
                                    <i class="ti ti-calendar-event me-2"></i> 1. 행사 관리
                                </button>
                                <button type="button" class="list-group-item list-group-item-action" data-guide="seating">
                                    <i class="ti ti-armchair me-2"></i> 2. 좌석 배정
                                </button>
                                <button type="button" class="list-group-item list-group-item-action" data-guide="progress">
                                    <i class="ti ti-rotate me-2"></i> 3. 로테이션 진행 / 투표
                                </button>
                                <button type="button" class="list-group-item list-group-item-action" data-guide="match">
                                    <i class="ti ti-heart me-2"></i> 4. 매칭 / 메시지 / 요청
                                </button>
                                <button type="button" class="list-group-item list-group-item-action" data-guide="detail">
                                    <i class="ti ti-sparkles me-2"></i> 5. 상세페이지 제작
                                </button>
                            </div>
                        </div>

                        {{-- 우측: 내용 패널 --}}
                        <div class="col-md-8">
                            <div class="guide-panel" data-panel="event">
                                <h4 class="mb-1"><i class="ti ti-calendar-event me-1"></i> 1. 행사 관리</h4>
                                <p class="text-muted">소개팅 <b>회차(행사)</b>를 만들고, 참여 현황을 관리하는 출발점입니다.</p>
                                <div class="fw-bold text-blue mb-1"><i class="ti ti-user-cog me-1"></i> 관리자가 하는 일</div>
                                <ul class="lh-lg">
                                    <li><b>행사 생성</b>: 행사명 · <b>타입</b> · <b>일시</b>를 입력해 회차를 만듭니다. 생성 시 상태는 자동으로 <span class="badge bg-secondary text-white">대기</span> 입니다.</li>
                                    <li><b>타입 선택</b>: <span class="badge bg-azure-lt">와인파티</span> <span class="badge bg-azure-lt">1:1 로테이션</span> 등. <mark>타입에 따라 단체 대화(3:3·4:4·3:2)일 수도, 1:1일 수도</mark> 있습니다.</li>
                                    <li><b>테이블 수</b>를 입력하면 테이블과 0턴이 함께 생성되어 바로 좌석 배정을 시작할 수 있습니다.</li>
                                    <li><b>상세 화면</b>에서 참여자 현황(남/여/총원), 각 회원의 <b>받은 투표수 · 보낸 메시지 · 요청수</b>를 확인합니다.</li>
                                </ul>
                                <div class="fw-bold text-pink mb-1"><i class="ti ti-users me-1"></i> 그러면 참가자는</div>
                                <ul class="lh-lg">
                                    <li>입장 화면의 <b>회차 선택 목록</b>에 이 행사가 노출되어, <mark>닉네임·핸드폰·비밀번호(4자리)로 입장등록</mark> 할 수 있습니다.</li>
                                </ul>
                                <a href="{{ route('admin.rotation.event.index') }}" class="btn btn-primary btn-sm"><i class="ti ti-external-link me-1"></i> 행사 관리로 이동</a>
                            </div>

                            <div class="guide-panel d-none" data-panel="seating">
                                <h4 class="mb-1"><i class="ti ti-armchair me-1"></i> 2. 좌석 배정</h4>
                                <p class="text-muted">누가 어느 테이블에 앉을지 <b>턴(0턴)</b> 단위로 배치합니다.</p>
                                <div class="fw-bold text-blue mb-1"><i class="ti ti-user-cog me-1"></i> 관리자가 하는 일</div>
                                <ul class="lh-lg">
                                    <li><b>행사 · 턴</b>을 선택하고, 테이블 그리드에서 참가자를 좌석에 끌어다 배치합니다.</li>
                                    <li><span class="badge bg-green text-white">자동 저장</span> <mark>좌석을 넣을 때마다 즉시 저장</mark>되므로 별도 저장 버튼이 필요 없습니다.</li>
                                    <li>0턴(첫 배치)이 끝나면 다음 턴은 <b>로테이션 진행</b>으로 자동 생성됩니다.</li>
                                </ul>
                                <div class="alert alert-important alert-warning py-2 my-2">
                                    <i class="ti ti-alert-triangle me-1"></i> <b>0턴 배치가 전체 로테이션의 기준</b>이 됩니다. 남/여 좌석을 꼼꼼히 확인하세요.
                                </div>
                                <div class="fw-bold text-pink mb-1"><i class="ti ti-users me-1"></i> 그러면 참가자는</div>
                                <ul class="lh-lg">
                                    <li>자신에게 배정된 <b>테이블/자리</b>를 안내받아 해당 자리에 착석합니다.</li>
                                </ul>
                                <a href="{{ route('admin.rotation.seating.index') }}" class="btn btn-primary btn-sm"><i class="ti ti-external-link me-1"></i> 좌석 배정으로 이동</a>
                            </div>

                            <div class="guide-panel d-none" data-panel="progress">
                                <h4 class="mb-1"><i class="ti ti-rotate me-1"></i> 3. 로테이션 진행 / 투표</h4>
                                <p class="text-muted">좌석 화면의 버튼으로 <b>행사 상태를 전환</b>하며 진행을 통제합니다.</p>
                                <div class="fw-bold text-blue mb-1"><i class="ti ti-user-cog me-1"></i> 관리자가 하는 일</div>
                                <ul class="lh-lg">
                                    <li><b>로테이션 진행</b>: 현재 턴 기준 다음 턴을 자동 생성합니다. <mark>여자는 고정, 남자가 한 칸씩 이동</mark>해 새로운 상대와 만나게 됩니다. 상태는 <span class="badge bg-blue text-white">진행</span>.</li>
                                    <li><b>투표 시작</b>: 로테이션이 끝나면 눌러서 상태를 <span class="badge bg-purple text-white">투표중</span> 으로 전환합니다.</li>
                                    <li><b>투표 마감</b>: 수동으로 즉시 마감하거나, <mark>행사 다음 날 오전 9시 자동 마감</mark>됩니다. 상태는 <span class="badge bg-dark text-white">종료</span>.</li>
                                </ul>
                                <div class="fw-bold text-pink mb-1"><i class="ti ti-users me-1"></i> 그러면 참가자는</div>
                                <ul class="lh-lg">
                                    <li><b>진행</b> 중에는 턴이 바뀔 때마다 안내된 자리로 이동하며 대화하고, <b>중간에 메시지</b>를 보낼 수 있습니다.</li>
                                    <li><b>투표중</b>이 되면 마음에 든 상대를 <mark>최대 <b class="text-red">3명</b>까지 매칭 투표</mark>할 수 있습니다.</li>
                                    <li><span class="badge bg-azure-lt">와인파티</span> 투표 후 <b>2차 자유 대화 시간</b>에 원하는 상대와 자유롭게 앉아 대화하고, 매칭 결과는 <b>다음 날 오전</b>에 발표됩니다.</li>
                                    <li><span class="badge bg-pink-lt">1:1 소개팅</span> 한 명씩 1:1로 마주 앉아 대화하고(여자 고정/남자 이동), 로테이션이 끝나면 <mark>그 자리에서 바로 매칭 결과</mark>를 확인합니다.</li>
                                </ul>
                            </div>

                            <div class="guide-panel d-none" data-panel="match">
                                <h4 class="mb-1"><i class="ti ti-heart me-1"></i> 4. 매칭 / 메시지 / 요청</h4>
                                <p class="text-muted">행사 상세 상단 버튼으로 <b>결과를 조회</b>합니다.</p>
                                <div class="fw-bold text-blue mb-1"><i class="ti ti-user-cog me-1"></i> 관리자가 확인하는 것</div>
                                <ul class="lh-lg">
                                    <li><b>전체 매칭</b>: 누가 누구를 선택했는지 전체 투표 현황</li>
                                    <li><b>매칭 결과</b>: <mark>서로 선택한 상호 매칭 쌍</mark>만 (남자 기준으로 묶어 표시)</li>
                                    <li><b>전체 메시지</b>: 참가자끼리 오간 메시지 (보낸사람/받는사람/읽음 여부)</li>
                                    <li><b>전체 요청</b>: 회원이 관리자에게 보낸 요청 (플로팅 위젯 수신)</li>
                                </ul>
                                <div class="fw-bold text-pink mb-1"><i class="ti ti-users me-1"></i> 그러면 참가자는</div>
                                <ul class="lh-lg">
                                    <li><span class="badge bg-azure-lt">와인파티</span> <mark>매칭 결과는 <b class="text-red">다음 날 오전</b>에 발표</mark>됩니다.</li>
                                    <li><span class="badge bg-pink-lt">1:1 소개팅</span> <mark>로테이션 종료 직후 바로 매칭 결과</mark>를 확인하고, 나를 선택한 상대의 정보도 공유받아 이후 연락할 수 있습니다.</li>
                                    <li>두 유형 모두 <b>서로 선택한 경우에만</b> 매칭 성사로 안내됩니다.</li>
                                </ul>
                            </div>

                            <div class="guide-panel d-none" data-panel="detail">
                                <h4 class="mb-1"><i class="ti ti-sparkles me-1"></i> 5. 상세페이지 제작</h4>
                                <p class="text-muted">홍보용 상세페이지를 HTML로 꾸미고 <b>이미지로 저장</b>해 외부 홍보에 사용합니다.</p>
                                <div class="fw-bold text-blue mb-1"><i class="ti ti-user-cog me-1"></i> 관리자가 하는 일</div>
                                <ul class="lh-lg">
                                    <li><b>유형 선택</b>: 상단에서 <span class="badge bg-pink-lt">와인파티</span> <span class="badge bg-pink-lt">1:1 소개팅</span> 중 하나를 먼저 고릅니다. <mark>유형별로 상단 이미지 · 로테이블 포인트 · 이용안내 문구가 다릅니다.</mark></li>
                                    <li><b>1열/2열 샘플</b> 버튼을 누르면 <b>선택한 유형</b>의 기본 틀을 불러옵니다. <mark>모임 1개면 1열, 여러 개면 2열</mark>을 추천합니다.</li>
                                    <li>HTML 에디터로 문구·디자인을 수정합니다. (참여조건, 이용안내, FAQ, 로테이블 포인트 등 포함)</li>
                                    <li><span class="badge bg-primary text-white">저장</span> 은 <mark>유형별로 따로 저장</mark>됩니다. 와인파티에서 저장한 내용과 1:1 소개팅 내용은 서로 영향을 주지 않습니다. 유형을 전환하면 해당 유형의 저장본이 에디터에 나타납니다.</li>
                                    <li><b>미리보기</b>로 확인하고 <span class="badge bg-dark text-white">이미지 다운로드</span> 로 <mark>PNG 이미지</mark>를 저장합니다.</li>
                                </ul>
                                <div class="alert alert-important alert-info py-2 my-2">
                                    <i class="ti ti-info-circle me-1"></i> 유형별 전용 상단 이미지를 쓰려면 <code>public/images/detail_wine.png</code> · <code>detail_solo.png</code> 를 넣으세요. 없으면 기본 이미지로 표시됩니다.
                                </div>
                                <div class="fw-bold text-pink mb-1"><i class="ti ti-photo me-1"></i> 활용</div>
                                <ul class="lh-lg">
                                    <li>저장한 이미지를 <b>SNS·오픈채팅·홍보 채널</b>에 올려 참가자 모집에 사용합니다.</li>
                                </ul>
                                <a href="{{ route('admin.rotation.detail.index') }}" class="btn btn-primary btn-sm"><i class="ti ti-external-link me-1"></i> 상세페이지 제작으로 이동</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">닫기</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function(){
            // 설명서 모달 열기 (기존 모달과 동일하게 Modal 클래스 폴백 탐색)
            var guideModal = null;
            var openBtn = document.getElementById('adminGuideOpenBtn');
            var modalEl = document.getElementById('adminGuideModal');

            // sticky-top 안에 있으면 백드롭보다 아래로 깔려 먹통이 되므로 body 로 이동.
            if (modalEl && modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }

            if (openBtn && modalEl) {
                openBtn.addEventListener('click', function(){
                    if (!guideModal) {
                        var ModalClass =
                            (window.tabler && window.tabler.Modal) ||
                            (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) ||
                            (window.bootstrap && window.bootstrap.Modal) ||
                            (window.coreui && window.coreui.Modal) ||
                            window.Modal;
                        if (!ModalClass) { console.error('Modal 클래스를 찾을 수 없습니다.'); return; }
                        guideModal = new ModalClass(modalEl);
                    }
                    guideModal.show();
                });
            }

            // 기능별 탭 전환 (모달이 body 로 이동했어도 document 기준으로 찾으므로 동작)
            document.addEventListener('click', function(e){
                var btn = e.target.closest('#guideTabs [data-guide]');
                if (!btn) { return; }
                var key = btn.getAttribute('data-guide');
                document.querySelectorAll('#guideTabs [data-guide]').forEach(function(el){ el.classList.remove('active'); });
                btn.classList.add('active');
                document.querySelectorAll('.guide-panel').forEach(function(p){
                    p.classList.toggle('d-none', p.getAttribute('data-panel') !== key);
                });
            });

            // 텔레그램 메시지 전달 on/off 토글 (최고관리자 전용)
            var telegramToggle = document.getElementById('telegramToggle');
            if (telegramToggle) {
                telegramToggle.addEventListener('change', function () {
                    var enabled = telegramToggle.checked;
                    var url     = telegramToggle.getAttribute('data-url');
                    var label   = document.getElementById('telegramToggleText');
                    var csrf    = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

                    telegramToggle.disabled = true;
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ enabled: enabled }),
                    })
                    .then(function (res) { return res.ok ? res.json() : Promise.reject(res); })
                    .then(function (data) {
                        var on = !!data.enabled;
                        telegramToggle.checked = on;
                        if (label) { label.textContent = '텔레그램 알림 ' + (on ? 'ON' : 'OFF'); }
                    })
                    .catch(function () {
                        // 실패 시 토글 원상복구
                        telegramToggle.checked = !enabled;
                        if (label) { label.textContent = '텔레그램 알림 ' + (!enabled ? 'ON' : 'OFF'); }
                        alert('텔레그램 설정 변경에 실패했습니다.');
                    })
                    .finally(function () { telegramToggle.disabled = false; });
                });
            }
        });
    </script>
    @endif
</div>
