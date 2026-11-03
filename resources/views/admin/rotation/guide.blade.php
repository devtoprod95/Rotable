@extends('layouts.app')

@section('title', '안내 슬라이드')

@section('content')
{{--
    참가자 안내 슬라이드 (관리자용).
    - 실제 슬라이드 덱은 공유 partial(_guideDeck)에 있다. (공개 페이지 public.guide 와 공용)
    - URL 로 타입(wine|solo)을 구분한다: admin.rotation.guide.index({type})
    - 외부 공개 주소: {{ route('public.guide') }}
--}}
@php
    $guideType = ($initialType ?? 'wine') === 'solo' ? 'solo' : 'wine';
@endphp
<div class="row row-cards mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center flex-wrap gap-2">
                <h3 class="card-title mb-0"><i class="ti ti-presentation me-1"></i> 안내 슬라이드</h3>

                {{-- 타입 전환 (와인파티 / 1:1 소개팅) — URL 로 구분 --}}
                <div class="btn-group btn-group-sm ms-2" role="group">
                    <a href="{{ route('admin.rotation.guide.index', ['type' => 'wine']) }}"
                       class="btn {{ $guideType === 'wine' ? 'btn-pink active' : 'btn-outline-pink' }}">
                        <i class="ti ti-glass-cocktail me-1"></i> 와인파티
                    </a>
                    <a href="{{ route('admin.rotation.guide.index', ['type' => 'solo']) }}"
                       class="btn {{ $guideType === 'solo' ? 'btn-pink active' : 'btn-outline-pink' }}">
                        <i class="ti ti-users-group me-1"></i> 1:1 소개팅
                    </a>
                </div>

                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btnFullscreen">
                        <i class="ti ti-maximize me-1"></i> 전체화면 발표
                    </button>
                    <a href="{{ route('public.guide', ['type' => $guideType]) }}" target="_blank" rel="noopener"
                       class="btn btn-sm btn-outline-teal">
                        <i class="ti ti-external-link me-1"></i> 공개 페이지 열기
                    </a>
                    <a href="{{ route('public.script', ['type' => $guideType]) }}" target="_blank" rel="noopener"
                       class="btn btn-sm btn-outline-pink">
                        <i class="ti ti-microphone me-1"></i> 발표 멘트 열기
                    </a>
                </div>
            </div>

            <div class="card-body">
                @include('admin.rotation._guideDeck', ['initialType' => $guideType, 'showToolbar' => false])
            </div>
        </div>
    </div>
</div>
@endsection
