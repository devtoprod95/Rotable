@extends('layouts.error')

@section('content')
<div class="page page-center">
    <div class="container py-4">
        <div class="empty">
            <div class="empty-header">403</div>
            <p class="empty-title">접근 권한이 없습니다.</p>

            <p class="empty-subtitle text-muted">
                {{ $exception->getMessage() ?: '죄송합니다. 현재 페이지에 접근할 수 있는 권한이 없습니다.' }}
            </p>

            <div class="empty-action">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i class="ti ti-home"></i>
                    <span>홈으로 돌아가기</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
