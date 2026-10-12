@extends('layouts.erd')

@section('content')
<pre class="mermaid">
erDiagram

%% ==========================================
%% 캠페인 및 지면상품 관계
%% ==========================================
admins ||--o{ campaign_datas : "캠페인 등록자 (admin_id 1:N)"
admins ||--o{ campaign_datas : "캠페인 광고주 (adver_id 1:N)"
corporation ||--o{ campaign_datas : "영업사/대행사 (cor_cid 1:N)"
kssc_datas ||--o{ campaign_datas : "캠페인 업종 (kssc_id 1:N)"

campaign_datas ||--|{ campaign_product_datas : "지면상품 목록 (campaign_id 1:N)"

%% 지면상품 다대다(N:M) 매핑
campaign_product_datas ||--o{ campaign_product_config_datas : "지면상품 매핑 (1:N)"
ad_config_datas ||--o{ campaign_product_config_datas : "매체설정 매핑 (1:N)"

campaign_product_datas ||--o{ campaign_product_country_datas : "지면상품 매핑 (1:N)"
country_datas ||--o{ campaign_product_country_datas : "타겟국가 매핑 (1:N)"

campaign_product_datas ||--o{ campaign_product_sojae_datas : "지면상품 매핑 (1:N)"
sojae_datas ||--o{ campaign_product_sojae_datas : "광고소재 매핑 (1:N)"

%% ==========================================
%% 테이블 정의
%% ==========================================

campaign_datas["campaign_datas (캠페인 기본 정보)"] {
    bigint id PK "캠페인 마스터 ID"
    varchar status "상태 (P:대기, Y:집행, S:중지, F:종료)"
    bigint admin_id FK "admins.id (등록자)"
    bigint adver_id FK "admins.id (광고주)"
    varchar title "캠페인명"
    date started_at "캠페인 시작일"
    date ended_at "캠페인 종료일"
    char cor_cid FK "corporation.cid (영업사)"
    bigint kssc_id FK "kssc_datas.id (업종)"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
    timestamp deleted_at "삭제일 (SoftDelete)"
}

campaign_product_datas["campaign_product_datas (캠페인별 지면상품 데이터)"] {
    bigint id PK "지면상품 고유 ID"
    bigint campaign_id FK "campaign_datas.id"
    smallint sort_order "표시 순서 (1, 2, 3...)"
    bigint ad_budget "광고 예산 (원)"
    int unit_price "CPM 기준단가 (원)"
    varchar frequency "프리퀀시 여부 (Y: ON, N: OFF)"
    varchar media_company "매체사 (AMAGI, K20 등)"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
    timestamp deleted_at "삭제일 (SoftDelete)"
}

campaign_product_config_datas["campaign_product_config_datas (지면상품별 매체/채널 장르 설정)"] {
    bigint id PK "매핑 고유 ID"
    bigint campaign_product_id FK "campaign_product_datas.id"
    bigint ad_config_id FK "ad_config_datas.id"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
}

campaign_product_country_datas["campaign_product_country_datas (지면상품별 타겟팅 국가)"] {
    bigint id PK "매핑 고유 ID"
    bigint campaign_product_id FK "campaign_product_datas.id"
    bigint country_id FK "country_datas.id"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
}

campaign_product_sojae_datas["campaign_product_sojae_datas (지면상품별 광고 소재 설정)"] {
    bigint id PK "매핑 고유 ID"
    bigint campaign_product_id FK "campaign_product_datas.id"
    bigint sojae_id FK "sojae_datas.id"
    decimal rate "노출 비중 (%)"
    smallint rank "노출 우선순위"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
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

country_datas["country_datas (국가 정보 데이터)"] {
    bigint id PK "국가 고유 ID"
    varchar name "국가명"
    varchar code "국가 코드 (ISO)"
}

sojae_datas["sojae_datas (소재 데이터)"] {
    bigint id PK "소재 고유 ID"
    varchar title "소재명"
    varchar status "승인상태"
    varchar file_url "영상 URL"
    varchar thumbnail "썸네일 URL"
}

corporation["corporation (업체정보)"] {
    char cid PK "업체 식별 ID"
    varchar corp_name "사업자명"
}

kssc_datas["kssc_datas (한국표준산업분류 KSIC)"] {
    bigint id PK "업종 ID"
    varchar code UK "업종코드"
    varchar depth1 "업종명"
}

admins["admins (관리자 데이터)"] {
    bigint id PK "관리자 ID"
    varchar name "관리자명"
}

</pre>
@endsection
