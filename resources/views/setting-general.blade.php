@extends('layouts.app')

@section('title', '종합 설정')

@section('content')
<div class="row row-cards justify-content-center mt-2">
    <div class="col-lg-12">
        <form action="#" method="POST" class="card">
            @csrf
            <div class="card-header"><h3 class="card-title">계정 및 시스템 종합 설정</h3></div>

            <div class="card-body">
                {{-- 1. 기본 텍스트 및 셀렉트 그룹 --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label required">이름</label>
                        <input type="text" class="form-control" placeholder="홍길동">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">이메일 주소</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-mail"></i></span>
                            <input type="email" class="form-control" placeholder="test@example.com">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">권한 등급</label>
                        <select class="form-select">
                            <option>일반 사용자</option>
                            <option>관리자</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4">

                {{-- 2. 체크박스, 라디오, 스위치 그룹 --}}
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">알림 수신 방식 (체크박스)</label>
                        <label class="form-check"><input type="checkbox" class="form-check-input" checked> 이메일</label>
                        <label class="form-check"><input type="checkbox" class="form-check-input"> SMS</label>
                        <label class="form-check"><input type="checkbox" class="form-check-input"> 푸시 알림</label>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">계정 상태 (라디오)</label>
                        <label class="form-check form-check-inline"><input type="radio" class="form-check-input" name="status"> 활성</label>
                        <label class="form-check form-check-inline"><input type="radio" class="form-check-input" name="status" checked> 정지</label>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">기능 활성화 (스위치)</label>
                        <label class="form-check form-switch"><input class="form-check-input" type="checkbox" checked> 2단계 인증 사용</label>
                        <label class="form-check form-switch"><input class="form-check-input" type="checkbox"> 다크 모드 강제</label>
                    </div>
                </div>

                <hr class="my-4">

                {{-- 3. 비밀번호 및 검증 영역 --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">비밀번호 변경</label>
                        <input type="password" class="form-control" placeholder="새 비밀번호">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">비밀번호 확인</label>
                        <input type="password" class="form-control" placeholder="비밀번호 재입력">
                        <small class="form-hint">비밀번호는 최소 8자 이상이어야 합니다.</small>
                    </div>
                </div>

                {{-- 4. 고급 필드 (날짜, 파일) --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">날짜 선택</label>
                        <input type="text" class="form-control datepicker" placeholder="날짜를 선택하세요">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">프로필 이미지 업로드</label>
                        <input type="file" class="form-control">
                    </div>
                </div>

                {{-- 5. 텍스트 영역 및 인라인 도움말 --}}
                <div class="mb-2">
                    <label class="form-label">추가 설명</label>
                    <textarea class="form-control" rows="3" placeholder="사용자에게 남길 관리자 메모..."></textarea>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end align-items-center">
                <div class="me-auto text-muted">
                    <i class="ti ti-alert-circle"></i> 모든 정보는 실시간으로 저장되지 않습니다.
                </div>
                <button type="reset" class="btn btn-ghost-secondary me-2">초기화</button>
                <button type="button" class="btn btn-primary px-4">변경 사항 저장</button>
            </div>
        </form>
    </div>
</div>
@endsection
