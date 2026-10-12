@extends('layouts.erd')

@section('content')
<pre class="mermaid">
erDiagram

%% ==========================================
%% 관리자 및 RBAC 관계
%% ==========================================
admins ||--o{ admin_has_roles : "관리자 매핑 (admin_id 1:N)"
roles ||--o{ admin_has_roles : "역할 매핑 (role_id 1:N)"
roles ||--o{ role_has_denied_routes : "역할 매핑 (role_id 1:N)"
denied_routes ||--o{ role_has_denied_routes : "제한 라우트 매핑 (route_id 1:N)"

admins ||--o{ sojae_datas : "소재 등록자 (admin_id 1:N)"
admins ||--o{ ad_request_datas : "신청서 등록자/광고주 (admin_id/adver_id 1:N)"
admins ||--o{ campaign_datas : "캠페인 등록자/광고주 (admin_id/adver_id 1:N)"

%% ==========================================
%% 테이블 정의
%% ==========================================

admins["admins (관리자 데이터)"] {
    bigint id PK "관리자 고유 ID"
    varchar username UK "관리자 로그인 아이디"
    varchar password "비밀번호 (해시)"
    varchar name "관리자명"
    tinyint is_active "계정 활성화 여부 (1:활성, 0:비활성)"
    timestamp last_login_at "마지막 로그인 시간"
    varchar last_login_ip "마지막 로그인 IP"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
    timestamp deleted_at "삭제일 (SoftDelete)"
}

roles["roles (역할)"] {
    bigint id PK "역할 고유 ID"
    varchar name UK "역할 시스템 코드 (SuperAdmin, Advertiser 등)"
    varchar display_name "화면 표시용 이름"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
}

denied_routes["denied_routes (접근 불가 라우트)"] {
    bigint id PK "제한 라우트 ID"
    varchar route UK "라우트명 (admin.users.delete 등)"
    varchar route_name "라우트 한글명"
    timestamp created_at "등록일"
    timestamp updated_at "수정일"
}

admin_has_roles["admin_has_roles (관리자-역할 관계)"] {
    bigint admin_id PK,FK "admins.id"
    bigint role_id PK,FK "roles.id"
}

role_has_denied_routes["role_has_denied_routes (역할-접근불가 관계)"] {
    bigint role_id PK,FK "roles.id"
    bigint route_id PK,FK "denied_routes.id"
}

sojae_datas["sojae_datas (소재 데이터)"] {
    bigint id PK "소재 ID"
    bigint admin_id FK "admins.id"
    varchar title "소재명"
}

ad_request_datas["ad_request_datas (게재신청서 데이터)"] {
    bigint id PK "게재신청서 ID"
    bigint admin_id FK "admins.id (등록자)"
    bigint adver_id FK "admins.id (광고주)"
    varchar campaign_title "캠페인명"
}

campaign_datas["campaign_datas (캠페인 기본 정보)"] {
    bigint id PK "캠페인 ID"
    bigint admin_id FK "admins.id (등록자)"
    bigint adver_id FK "admins.id (광고주)"
    varchar title "캠페인명"
}

</pre>
@endsection
