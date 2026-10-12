@extends('layouts.auth')

@section('content')
<div class="card card-md shadow-lg border-0">
    <div class="card-body p-5">
        <h2 class="h2 text-center mb-4 fw-bold">관리자 페이지에 오신 것을 환영합니다</h2>

        <p class="text-center text-muted mb-4">계정을 사용하여 로그인하세요.</p>

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <div class="d-flex">
                    <div>
                        <i class="ti ti-alert-triangle me-2"></i>
                    </div>
                    <div>
                        {{ $errors->first() }}
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('admin.login') }}" method="post" autocomplete="on">
            @csrf

            <input type="hidden" name="redirectTo" value="{{ $redirectTo ?? '' }}" class="form-control form-control-lg">

            <div class="mb-3">
                <label class="form-label fw-medium">아아디</label>
                <input type="text" name="username" value="{{ old('username', $username ?? '') }}" class="form-control form-control-lg" placeholder="아이디를 입력해주세요." required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium">비밀번호</label>
                <div class="input-group input-group-flat">
                    <input type="password" name="password" id="password" value="{{ old('password', $password ?? '') }}" class="form-control form-control-lg" placeholder="비밀번호를 입력해주세요." required>
                    <span class="input-group-text">
                        <a href="#" id="toggle_password" class="link-secondary" title="비밀번호 보기" data-bs-toggle="tooltip">
                            <svg id="eye_icon" xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path id="path_show" d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                <path id="path_show_outer" d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                <path id="path_hide" d="M3 3l18 18" style="display:none;" />
                            </svg>
                        </a>
                    </span>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-check">
                    <input type="checkbox" name="remember" class="form-check-input" {{ isset($isChecked) && $isChecked ? 'checked' : '' }} />
                    <span class="form-check-label text-muted">아이디, 비밀번호 자동저장</span>
                </label>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">로그인</button>
            </div>
        </form>
    </div>
</div>
<div class="text-center text-muted mt-3">
    계정이 없으신가요? <a href="#" tabindex="-1" class="btn-regist">회원가입 요청</a>
</div>

<script>
    $(document).ready(function() {
        $('#toggle_password').on('click', function(e) {
            e.preventDefault();

            const input = $('#password');
            const isPassword = input.attr('type') === 'password';

            // 타입 전환
            input.attr('type', isPassword ? 'text' : 'password');

            // 아이콘 색상 토글
            $(this).toggleClass('link-secondary', isPassword);
            $(this).toggleClass('text-primary', !isPassword);

            // 아이콘 모양 토글
            $('#path_show, #path_show_outer').toggle(isPassword);
            $('#path_hide').toggle(!isPassword);
        });

        $('.btn-regist').click(function(e) {
            e.preventDefault();

            salert({text: '준비중입니다.', cancel: false});
        })
    });
</script>


@endsection
