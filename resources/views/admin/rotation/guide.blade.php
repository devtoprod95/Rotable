@extends('layouts.app')

@section('title', '참가자 안내')

@section('content')
{{--
    참가자 안내 슬라이드 (관리자용).
    - 실제 슬라이드 덱은 공유 partial(_guideDeck)에 있다. (공개 페이지 public.guide 와 공용)
    - 외부 공개 주소: {{ route('public.guide') }}
--}}
<div class="row row-cards mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center flex-wrap gap-2">
                <h3 class="card-title mb-0"><i class="ti ti-presentation me-1"></i> 참가자 안내 슬라이드</h3>
                <a href="{{ route('public.guide') }}" target="_blank" rel="noopener"
                   class="btn btn-sm btn-outline-teal ms-2">
                    <i class="ti ti-external-link me-1"></i> 공개 페이지 열기
                </a>
            </div>

            <div class="card-body">
                @include('admin.rotation._guideDeck')
            </div>
        </div>
    </div>
</div>
@endsection
