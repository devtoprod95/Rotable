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
    $guideType = ($initialType ?? 'solo') === 'wine' ? 'wine' : 'solo';
@endphp
<div class="row row-cards mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center flex-wrap gap-2">
                <h3 class="card-title mb-0"><i class="ti ti-presentation me-1"></i> 안내 슬라이드</h3>

                {{-- 타입 전환 (와인파티 / 1:1 소개팅) — URL 로 구분 --}}
                <div class="btn-group btn-group-sm ms-2" role="group">
                    <a href="{{ route('admin.rotation.guide.index', ['type' => 'solo', 'eventId' => $eventId]) }}"
                       class="btn {{ $guideType === 'solo' ? 'btn-pink active' : 'btn-outline-pink' }}">
                        <i class="ti ti-users-group me-1"></i> 1:1 소개팅
                    </a>
                    <a href="{{ route('admin.rotation.guide.index', ['type' => 'wine', 'eventId' => $eventId]) }}"
                       class="btn {{ $guideType === 'wine' ? 'btn-pink active' : 'btn-outline-pink' }}">
                        <i class="ti ti-glass-cocktail me-1"></i> 와인파티
                    </a>
                </div>

                {{-- 슬라이드의 입장등록 QR 이 연결될 행사 --}}
                <div class="d-flex align-items-center gap-1">
                    <label class="form-label mb-0 text-secondary small" for="guideEventSelect"><i class="ti ti-qrcode me-1"></i>입장 QR 행사</label>
                    <select class="form-select form-select-sm" id="guideEventSelect" style="width:auto; max-width:260px;">
                        @foreach ($events as $ev)
                            <option value="{{ $ev->id }}" {{ (int) $eventId === (int) $ev->id ? 'selected' : '' }}>{{ $ev->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="btnFullscreen">
                        <i class="ti ti-maximize me-1"></i> 전체화면 발표
                    </button>
                    <a href="{{ route('public.guide', ['type' => $guideType, 'eventId' => $eventId]) }}" target="_blank" rel="noopener"
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
                @include('admin.rotation._guideDeck', ['initialType' => $guideType, 'showToolbar' => false, 'registerUrl' => $registerUrl ?? null])
            </div>
        </div>
    </div>
</div>
<script>
    // 행사를 바꾸면 같은 타입의 슬라이드를 그 행사 QR 로 다시 연다.
    $('#guideEventSelect').on('change', function(){
        var url = new URL(window.location.href);
        url.searchParams.set('eventId', this.value);
        window.location.href = url.toString();
    });
</script>
@endsection
