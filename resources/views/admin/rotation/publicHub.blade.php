@extends('layouts.app')

@section('title', '공개 페이지')

@section('content')
<style>
    /* 공개 페이지 허브: 큰 터치 타깃의 카드형 링크 (모바일 현장 사용 고려) */
    .pub-hub .pub-card{
        display:flex; flex-direction:column; height:100%;
        border:1px solid #e6e8eb; border-radius:14px; background:#fff;
        padding:18px 16px; text-decoration:none; color:inherit;
        transition:box-shadow .12s ease, transform .12s ease, border-color .12s ease;
    }
    .pub-hub .pub-card:hover{ box-shadow:0 6px 18px -8px rgba(0,0,0,.25); transform:translateY(-2px); border-color:#cbd5e1; }
    .pub-hub .pub-card__ico{ font-size:1.9rem; line-height:1; }
    .pub-hub .pub-card__title{ font-weight:800; font-size:1.05rem; margin-top:10px; color:#1f2937; }
    .pub-hub .pub-card__desc{ font-size:.86rem; color:#6b7280; margin-top:6px; line-height:1.5; flex:1 1 auto; }
    .pub-hub .pub-card__go{ margin-top:12px; font-weight:700; font-size:.86rem; display:inline-flex; align-items:center; gap:4px; }
    .pub-hub .pub-variants{ display:flex; gap:.4rem; flex-wrap:wrap; margin-top:12px; }
    .pub-hub .pub-variants .btn{ flex:1 1 auto; }
    .pub-hub .pub-sec-title{ font-weight:800; color:#374151; margin:6px 0 2px; display:flex; align-items:center; gap:6px; }
</style>

@php
    // 공개 안내 슬라이드/호스트 멘트는 라우트가 type=wine|solo 로 제약돼 있다. (1:1 은 'solo')
    $publicTypes = [
        'wine' => '와인파티',
        'solo' => '1:1 소개팅',
    ];
@endphp

<div class="row row-cards mt-2 pub-hub">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h3 class="card-title mb-0">
                    <i class="ti ti-external-link text-primary me-1"></i> 공개 페이지 모음
                </h3>
                <span class="badge bg-azure-lt">
                    <i class="ti ti-info-circle me-1"></i> 링크는 새 탭으로 열립니다
                </span>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    참가자에게 공유하거나 현장에서 바로 띄우는 <b>공개(비인증) 페이지</b>와 <b>운영 안내 페이지</b>를 한 곳에 모았어요.
                    대부분 로그인 없이 접속할 수 있어, 링크를 복사해 오픈채팅·SNS 등에 공유하면 됩니다.
                </p>

                {{-- ── 참가자용 공개 페이지 (비인증, 공유 가능) ── --}}
                <div class="pub-sec-title"><i class="ti ti-users text-pink"></i> 참가자용 공개 페이지</div>
                <div class="text-muted mb-3" style="font-size:.85rem;">로그인 없이 접속 가능 — 링크를 그대로 공유하세요.</div>

                <div class="row row-cards g-3 mb-4">
                    {{-- 공개 미니게임 --}}
                    <div class="col-12 col-sm-6 col-lg-4">
                        <a href="{{ route('public.games') }}" target="_blank" rel="noopener" class="pub-card">
                            <div class="pub-card__ico">🎮</div>
                            <div class="pub-card__title">공개 미니게임</div>
                            <div class="pub-card__desc">
                                로그인·테이블 없이 지금 접속한 사람들끼리 즐기는 미니게임 모음(Yes/No, 밸런스 게임 등).
                            </div>
                            <span class="pub-card__go text-primary">열기 <i class="ti ti-external-link"></i></span>
                        </a>
                    </div>

                    {{-- 안내 슬라이드 (공개) --}}
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="pub-card">
                            <div class="pub-card__ico">🖼️</div>
                            <div class="pub-card__title">안내 슬라이드</div>
                            <div class="pub-card__desc">
                                행사 이용안내·룰·대화 주제를 담은 공개 슬라이드. 유형별로 열 수 있어요.
                            </div>
                            <div class="pub-variants">
                                @foreach ($publicTypes as $typeKey => $typeLabel)
                                    <a href="{{ route('public.guide', ['type' => $typeKey]) }}" target="_blank" rel="noopener"
                                       class="btn btn-sm btn-outline-pink">{{ $typeLabel }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- 호스트 발표 멘트 (공개) --}}
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="pub-card">
                            <div class="pub-card__ico">🎤</div>
                            <div class="pub-card__title">호스트 발표 멘트</div>
                            <div class="pub-card__desc">
                                안내 슬라이드를 보며 읽는 진행 멘트(대본). 핸드폰으로 보기 좋은 단일 페이지예요.
                            </div>
                            <div class="pub-variants">
                                @foreach ($publicTypes as $typeKey => $typeLabel)
                                    <a href="{{ route('public.script', ['type' => $typeKey]) }}" target="_blank" rel="noopener"
                                       class="btn btn-sm btn-outline-azure">{{ $typeLabel }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── 운영 안내 페이지 (관리자) ── --}}
                <div class="pub-sec-title"><i class="ti ti-settings text-teal"></i> 운영 안내 페이지</div>
                <div class="text-muted mb-3" style="font-size:.85rem;">관리자용 — 현장 운영에 참고하는 페이지입니다.</div>

                <div class="row row-cards g-3">
                    {{-- 채팅방 공지 --}}
                    <div class="col-12 col-sm-6 col-lg-4">
                        <a href="{{ route('admin.rotation.guide.notice') }}" target="_blank" rel="noopener" class="pub-card">
                            <div class="pub-card__ico">📢</div>
                            <div class="pub-card__title">채팅방 공지</div>
                            <div class="pub-card__desc">
                                행사 시간·유형에 맞춰 자동 생성되는 공지 문구. 복사해서 오픈채팅에 붙여넣으세요.
                            </div>
                            <span class="pub-card__go text-teal">열기 <i class="ti ti-external-link"></i></span>
                        </a>
                    </div>

                    {{-- 안내 슬라이드 (관리자 프레젠테이션) --}}
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="pub-card">
                            <div class="pub-card__ico">📽️</div>
                            <div class="pub-card__title">안내 슬라이드 (발표용)</div>
                            <div class="pub-card__desc">
                                행사장에서 PPT처럼 띄우는 관리자용 안내 슬라이드. 유형별로 열 수 있어요.
                            </div>
                            <div class="pub-variants">
                                @foreach ($publicTypes as $typeKey => $typeLabel)
                                    <a href="{{ route('admin.rotation.guide.index', ['type' => $typeKey]) }}" target="_blank" rel="noopener"
                                       class="btn btn-sm btn-outline-purple">{{ $typeLabel }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- 예외 상황 대처 --}}
                    <div class="col-12 col-sm-6 col-lg-4">
                        <a href="{{ route('admin.rotation.guide.exception') }}" target="_blank" rel="noopener" class="pub-card">
                            <div class="pub-card__ico">🧯</div>
                            <div class="pub-card__title">예외 상황 대처</div>
                            <div class="pub-card__desc">
                                현장에서 생기는 돌발 상황별 처리 방법 설명서.
                            </div>
                            <span class="pub-card__go text-teal">열기 <i class="ti ti-external-link"></i></span>
                        </a>
                    </div>

                    {{-- QR 코드 설정 --}}
                    <div class="col-12 col-sm-6 col-lg-4">
                        <a href="{{ route('admin.rotation.qr.index') }}" target="_blank" rel="noopener" class="pub-card">
                            <div class="pub-card__ico">🔳</div>
                            <div class="pub-card__title">QR 코드 설정</div>
                            <div class="pub-card__desc">
                                대상 URL을 등록하면 공개 주소(/qr/코드)가 발급돼요. 포스터·안내물에 활용하세요.
                            </div>
                            <span class="pub-card__go text-teal">열기 <i class="ti ti-external-link"></i></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
