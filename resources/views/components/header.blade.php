<div class="sticky-top">
    <header class="navbar navbar-expand-md sticky-top d-print-none">
        <div class="container-xl">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <a href="{{ route('admin.dashboard') }}" class="navbar-brand navbar-brand-autodark d-flex align-items-center gap-2 text-truncate" style="max-width: 60vw;">
                <img src="{{ asset('images/logo.png') }}" alt="logo" style="height: 42px; width: 42px; object-fit: contain; flex: 0 0 auto;">
                <span class="fw-bold fs-3 text-truncate">{{ env('APP_NAME') }}</span>
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
                            <div class="nav-item d-none d-md-flex align-items-center me-2">
                                <label class="form-check form-switch form-switch-lg m-0 d-flex flex-column align-items-center" title="텔레그램 알림 on/off" style="padding-left:0;">
                                    <input class="form-check-input m-0" type="checkbox" id="telegramToggle"
                                           data-url="{{ route('admin.settings.telegram.toggle') }}"
                                           {{ $telegramEnabled ? 'checked' : '' }}>
                                    <span class="form-check-label fw-bold mt-1" style="font-size:0.75rem;">
                                        <span id="telegramToggleText">텔레그램 알림 {{ $telegramEnabled ? 'ON' : 'OFF' }}</span>
                                    </span>
                                </label>
                            </div>
                            <div class="nav-item d-none d-md-flex align-items-center me-2">
                                <span class="vr" style="width:2px; height:32px; background-color:#adb5bd; opacity:1;"></span>
                            </div>
                            <div class="nav-item dropdown d-none d-md-flex align-items-center me-2">
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
                                    <a href="{{ route('admin.rotation.guide.notice') }}" class="dropdown-item">
                                        <i class="ti ti-message-2 me-2 fs-2"></i> 채팅방 공지
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a href="{{ route('admin.manual.summary') }}" class="dropdown-item">
                                        <i class="ti ti-route-2 me-2 fs-2 text-blue"></i> 핵심 요약 (순서도)
                                    </a>
                                    <a href="{{ route('admin.manual') }}" class="dropdown-item">
                                        <i class="ti ti-help-circle me-2 fs-2 text-danger"></i> 전체 사용 설명서
                                    </a>
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
                                <a href="{{ route('admin.view', ['id' => auth('admin')->id()]) }}" class="dropdown-item">
                                    <i class="ti ti-user me-2 fs-2"></i>프로필
                                </a>
                                @if (config('services.telegram.invite_link'))
                                    <a href="{{ config('services.telegram.invite_link') }}" target="_blank" rel="noopener" class="dropdown-item">
                                        <i class="ti ti-brand-telegram me-2 fs-2 text-azure"></i>텔레그램 알림방 입장
                                    </a>
                                @endif
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
    <script>
        document.addEventListener('DOMContentLoaded', function(){
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
