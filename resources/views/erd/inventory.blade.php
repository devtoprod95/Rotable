@extends('layouts.erd')

@section('content')
<pre class="mermaid">
erDiagram

%% ==========================================
%% 지면(Inventory) 관련 관계
%% ==========================================
admins ||--o{ ad_inventory_datas : "지면 등록자 (admin_id 1:N)"

%% ==========================================
%% 테이블 정의
%% ==========================================

admins["admins (관리자 데이터)"] {
    bigint id PK "관리자 ID"
    varchar username "로그인 아이디"
    varchar name "관리자명"
}

ad_config_datas["ad_config_datas (광고 설정 정보)"] {
    bigint id PK "광고설정 고유 ID"
    varchar monetization "수익화 유형"
    varchar supply_partner "공급 파트너"
    varchar channel_name "채널명"
    varchar channel_genre "채널 장르"
    varchar category_1 "카테고리 대분류"
    varchar category_2 "카테고리 중분류"
    varchar category_3 "카테고리 소분류"
    varchar app_site_name "앱/사이트명"
    varchar operating_system "운영체제 (OS)"
    varchar device_type "기기 유형"
    varchar bundle_id_domain "Bundle ID / Domain"
    varchar app_url "앱 URL"
    varchar app_ads_txt "ads.txt 정보"
    varchar key "데이터 식별용 키"
    varchar hash_key UK "행 데이터 MD5 고유 해시"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
    timestamp deleted_at "삭제일 (SoftDelete)"
}

ad_inventory_datas["ad_inventory_datas (지면 마스터 데이터)"] {
    bigint id PK "지면 고유 ID"
    bigint admin_id FK "admins.id (등록자)"
    varchar media_company "매체사 (AMAGI, K20 등)"
    varchar partner "파트너사 (amagi, wurl)"
    varchar title "지면명"
    text url "url"
    varchar status "상태 (Y:활성, N:정지)"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
    timestamp deleted_at "삭제일 (SoftDelete)"
}

</pre>
@endsection
