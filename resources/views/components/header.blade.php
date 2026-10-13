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
</div>
