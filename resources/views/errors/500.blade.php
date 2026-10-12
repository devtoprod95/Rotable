@extends('layouts.error')

@section('content')
<div class="page page-center">
    <div class="container py-4">
        <div class="empty">
            <div class="empty-header">500</div>
            <p class="empty-title">서버 오류가 발생했습니다.</p>
            <p class="empty-subtitle text-muted">
                {{ $exception->getMessage() ?: '죄송합니다. 서버에서 오류가 발생했습니다. 잠시 후 다시 시도해주세요.' }}
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
