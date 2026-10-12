@extends('layouts.error')

@section('content')
<div class="page page-center">
    <div class="container py-4">
        <div class="empty">
            <div class="empty-header">404</div>
            <p class="empty-title">페이지를 찾을 수 없습니다.</p>
            <p class="empty-subtitle text-muted">
                {{ $exception->getMessage() ?: '죄송합니다. 찾으시는 페이지가 삭제되었거나 주소가 변경되었습니다.' }}
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
