@extends('layouts.erd')

@section('content')
<pre class="mermaid">
erDiagram

%% ==========================================
%% 게재신청서 관련 관계
%% ==========================================
admins ||--o{ ad_request_datas : "신청서 등록자 (admin_id 1:N)"
admins ||--o{ ad_request_datas : "광고주 (adver_id 1:N)"
kssc_datas ||--o{ ad_request_datas : "갑 업종 (gab_kssc_id 1:N)"
kssc_datas ||--o{ ad_request_datas : "을 업종 (eul_kssc_id 1:N)"

%% ==========================================
%% 테이블 정의
%% ==========================================

admins["admins (관리자 데이터)"] {
    bigint id PK "관리자 고유 ID"
    varchar username UK "로그인 아이디"
    varchar name "관리자명"
    tinyint is_active "활성화 여부"
    timestamp last_login_at "마지막 로그인"
}

kssc_datas["kssc_datas (한국표준산업분류 KSIC)"] {
    bigint id PK "업종 ID"
    varchar code UK "업종코드 (KSIC)"
    varchar depth1 "업종명"
}

ad_request_datas["ad_request_datas (게재신청서 데이터)"] {
    bigint id PK "게재신청서 고유 ID"
    bigint admin_id FK "admins.id (작성자)"
    varchar status "상태 (P:검수요청, Y:승인, F:반려)"
    text reject "반려 사유"
    bigint adver_id FK "admins.id (광고주)"
    varchar campaign_title "캠페인명 (최대 50자)"
    varchar gab_name "갑 상호/신청인명"
    varchar gab_phone "갑 전화번호"
    varchar gab_ceo "갑 대표자명"
    varchar gab_manager "갑 담당자명"
    varchar gab_fax "갑 팩스번호"
    varchar gab_biz_num "갑 사업자번호"
    varchar gab_email "갑 이메일"
    varchar gab_address "갑 주소"
    varchar gab_address_detail "갑 상세주소"
    bigint gab_kssc_id FK "kssc_datas.id (갑 업종)"
    date tax_bill_at "갑 세금계산서 발행일"
    bigint contract_amount "계약금액 (원)"
    int agency_fee "대행사 수수료 (%)"
    date started_at "계약 시작일"
    date ended_at "계약 종료일"
    varchar bill_type "결제조건 (cash, card)"
    date payment_date "대금지급일"
    text etc "기타 요청사항"
    varchar eul_name "을 상호명"
    varchar eul_address "을 주소"
    varchar eul_address_detail "을 상세주소"
    varchar eul_ceo "을 대표자명"
    varchar eul_biz_num "을 사업자번호"
    bigint eul_kssc_id FK "kssc_datas.id (을 업종)"
    varchar eul_manager "을 담당자명"
    varchar eul_phone "을 전화번호"
    varchar eul_fax "을 팩스번호"
    varchar eul_email "을 이메일"
    varchar bank_name "입금 은행명"
    varchar account_number "입금 계좌번호"
    text memo "특이사항/메모"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
    timestamp deleted_at "삭제일"
}

</pre>
@endsection
