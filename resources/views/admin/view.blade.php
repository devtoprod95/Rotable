@php
    use App\Enums\Admin;
@endphp

@extends('layouts.app')

@section('title')
    관리자 {{ empty($adminObj) ? '등록' : '수정' }}
@endsection

@section('content')
    <div class="row row-cards mt-2">
        <div class="col-12">
            <form id="ajaxForm" >
                <input type="hidden" name="id" value="{{ $adminObj->id ?? '' }}">

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">관리자 정보 입력</h3>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">아이디</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-user"></i>
                                    </span>
                                    <input type="text" class="form-control" name="username" placeholder="아이디를 입력하세요" value="{{ $adminObj->username ?? '' }}" required>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label required">이름</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-id"></i>
                                    </span>
                                    <input type="text" class="form-control" name="name" placeholder="이름을 입력하세요" value="{{ $adminObj->name ?? '' }}" required>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label {{ empty($adminObj) ? 'required' : '' }}">{{ empty($adminObj) ? '비밀번호' : '새 비밀번호' }}</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" name="password" placeholder="{{ empty($adminObj) ? '비밀번호를 입력하세요' : '새 비밀번호를 입력하세요' }}">
                                </div>
                                <small class="form-hint text-muted">
                                    {{ empty($adminObj) ? '※ 영문, 숫자, 특수문자 조합으로 8자 이상 입력해주세요.' : '※ 비밀번호 변경 시에만 입력하세요. (영문, 숫자, 특수문자 조합 8자 이상)' }}
                                </small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label {{ empty($adminObj) ? 'required' : '' }}">비밀번호 확인</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-lock-check"></i>
                                    </span>
                                    <input type="password" class="form-control" name="password_confirmation" placeholder="비밀번호를 다시 입력하세요">
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label required">활성여부</label>
                                <div class="form-selectgroup">
                                    @foreach (Admin::isActives() as $value => $label)
                                        <label class="form-selectgroup-item">
                                            <input type="radio"
                                                name="is_active"
                                                value="{{ $value }}"
                                                class="form-selectgroup-input"
                                                {{ (isset($adminObj) && $adminObj->is_active == $value) || (!isset($adminObj) && $value == \App\Enums\Admin::ACTIVE->value) ? 'checked' : '' }}>

                                            <span class="form-selectgroup-label">
                                                <i class="ti {{ $value == \App\Enums\Admin::ACTIVE->value ? 'ti-check' : 'ti-x' }} me-1"></i>
                                                {{ $label }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label required">권한</label>
                                <div class="form-selectgroup form-selectgroup-boxes">
                                    @foreach ($roles as $role)
                                        <label class="form-selectgroup-item">
                                            <input type="checkbox"
                                                name="roles[]"
                                                value="{{ $role->id }}"
                                                class="form-selectgroup-input"
                                                {{ !empty($adminObj) && in_array($role->id, $adminObj->roles->pluck('id')->toArray()) ? 'checked' : '' }}>

                                            <span class="form-selectgroup-label d-flex align-items-center p-2">
                                                {{ $role->display_name }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-secondary" onclick="history.back()">뒤로가기</button>
                        <button type="button" class="btn btn-primary btn-save">{{ empty($adminObj) ? '등록' : '수정' }}하기</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function(){
            $('.btn-save').on('click', async function(e) {
                var formData             = new FormData($('#ajaxForm')[0]);
                let username             = $('input[name=username]').val().trim();
                let name                 = $('input[name=name]').val().trim();
                let password             = $('input[name=password]').val();
                let passwordConfirmation = $('input[name=password_confirmation]').val();
                let id                   = $('input[name=id]').val();
                let roles                = $('input[name="roles[]"]:checked').map(function() {
                    return $(this).val();
                }).get();
                let is_active            = $('input[name=is_active]:checked').val();

                // 영문(대/소문자) + 숫자 + 특수문자 조합
                const passwordRegex = /^(?=.*[a-zA-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]).+$/;

                if( !username ){
                    await salert({text: '아이디를 입력해주세요.', cancel: false});
                    return false;
                }
                if( !name ){
                    await salert({text: '이름을 입력해주세요.', cancel: false});
                    return false;
                }

                if( !id ){
                    // 등록 시
                    if( !password ){
                        await salert({text: '비밀번호를 입력해주세요.', cancel: false});
                        return false;
                    }
                    if( password.length < 8 || !passwordRegex.test(password) ){
                        await salert({text: '비밀번호는 영문, 숫자, 특수문자 조합으로 8자 이상 입력해야 합니다.', cancel: false});
                        return false;
                    }
                    if( password !== passwordConfirmation ){
                        await salert({text: '비밀번호 확인이 일치하지 않습니다.', cancel: false});
                        return false;
                    }
                } else {
                    // 수정 시: 비밀번호 입력 시에만 검증
                    if( password ){
                        if( password.length < 8 || !passwordRegex.test(password) ){
                            await salert({text: '새 비밀번호는 영문, 숫자, 특수문자 조합으로 8자 이상 입력해야 합니다.', cancel: false});
                            return false;
                        }
                        if( password !== passwordConfirmation ){
                            await salert({text: '비밀번호 확인이 일치하지 않습니다.', cancel: false});
                            return false;
                        }
                    }
                }

                if( roles.length < 1 ){
                    await salert({text: '권한을 최소 1개 선택해주세요.', cancel: false});
                    return false;
                }

                let confirmText = id ? '관리자를 수정하시겠습니까?' : '관리자를 등록하시겠습니까?';
                if( await salert({text: confirmText}) ){
                    $.ajax({
                        url: "{{ route('admin.store') }}",
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(res) {
                            alert(res?.msg);
                            if( res?.status === 200 ){
                                location.href = "{{ route('admin.index') }}";
                            }
                        },
                        error: function(xhr) {
                            var res = xhr?.responseJSON;
                            alert(res?.error?.message ?? '오류가 발생했습니다.');
                        }
                    });
                }
            });
        });
    </script>
@endsection
