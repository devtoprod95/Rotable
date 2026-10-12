@extends('layouts.erd')

@section('content')
<pre class="mermaid">
erDiagram

%% ==========================================
%% 소재(Sojae) 관련 관계
%% ==========================================
sojae_categories ||--o{ sojae_datas : "카테고리 분류 (cate_id 1:N)"
sojae_categories ||--o{ encode_datas : "인코딩 규격 정의 (cate_id 1:N)"
admins ||--o{ sojae_datas : "소재 등록자 (admin_id 1:N)"
sojae_datas ||--o{ sojae_detail_datas : "해상도별 인코딩 상세정보 (sojae_id 1:N)"
sojae_datas ||--o{ campaign_product_sojae_datas : "지면상품 연계 매핑 (sojae_id 1:N)"

%% ==========================================
%% 테이블 정의
%% ==========================================

sojae_categories["sojae_categories (소재 카테고리)"] {
    bigint id PK "카테고리 고유 ID"
    varchar name "카테고리명 (대분류)"
    varchar detail_name "상세 카테고리명 / 상품명"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
}

sojae_datas["sojae_datas (소재 데이터)"] {
    bigint id PK "소재 고유 ID"
    bigint cate_id FK "sojae_categories.id"
    bigint admin_id FK "admins.id (등록자)"
    varchar sojae_type "소재 유형 (in_contents, video)"
    varchar encode_id "통합인코딩 ID"
    varchar title "소재명"
    varchar s3_key "S3 업로드 키"
    varchar file_url "인코딩 원본 영상 URL"
    varchar thumbnail "썸네일 URL"
    smallint ad_time "광고 시간 (초)"
    int origin_width "원본 가로 해상도"
    int origin_height "원본 세로 해상도"
    decimal origin_size "원본 파일 용량 (MB)"
    varchar status "승인상태 (P:대기, Y:승인, F:반려)"
    varchar is_use "사용여부 (Y/N)"
    text reject "반려사유"
    varchar encode_status "인코딩 상태 (P:대기, Y:성공, N:실패)"
    text encode_msg "인코딩 결과 메시지"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
    timestamp deleted_at "삭제일 (SoftDelete)"
}

sojae_detail_datas["sojae_detail_datas (소재 상세 데이터)"] {
    bigint id PK "소재 상세 고유 ID"
    bigint sojae_id FK "sojae_datas.id"
    char encode_status "진행상태 (P:대기, Y:완료, N:실패)"
    varchar media_name "미디어명 (D1920X270, SHD 등)"
    varchar media_hash "미디어 해시 키"
    int width "가로 해상도"
    int height "세로 해상도"
    decimal duration "영상 재생시간 (초)"
    varchar ratio "디스플레이 비율 (16:9 등)"
    varchar format "파일 확장자/포맷"
    decimal size "파일 용량 (MB)"
    decimal frame "프레임 속도 (FPS)"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
    timestamp deleted_at "삭제일 (SoftDelete)"
}

encode_datas["encode_datas (카테고리 별 인코딩 항목 관리)"] {
    bigint id PK "인코딩 항목 ID"
    bigint cate_id FK "sojae_categories.id"
    varchar rate "화면 비율"
    varchar type "규격 타입"
    int width "가로 해상도"
    int height "세로 해상도"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
}

admins["admins (관리자 데이터)"] {
    bigint id PK "관리자 ID"
    varchar username "로그인 아이디"
    varchar name "관리자명"
}

campaign_product_sojae_datas["campaign_product_sojae_datas (지면상품별 광고 소재 설정)"] {
    bigint id PK "매핑 ID"
    bigint campaign_product_id FK "campaign_product_datas.id"
    bigint sojae_id FK "sojae_datas.id"
    decimal rate "노출비중 (%)"
    smallint rank "순위"
}

</pre>
@endsection
