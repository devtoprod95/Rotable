<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. 역할(Role) 테이블
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50)->unique()->comment('역할명 (예: admin, editor)');
                $table->string('display_name', 50)->nullable(false)->comment('역할 화면 표시용 이름');
                $table->timestamps();
                $table->comment('역할 테이블');
            });
        }

        // 2. 접근 불가 라우트(denied_routes) 테이블
        if (! Schema::hasTable('denied_routes')) {
            Schema::create('denied_routes', function (Blueprint $table) {
                $table->id();
                $table->string('route', 100)->unique()->comment('접근 불가 라우트명 (예: admin.users.delete)');
                $table->string('route_name', 100)->nullable(false)->comment('접근 불가 라우트 한글명 (예: 설정>일반)');
                $table->timestamps();
                $table->comment('접근 불가 라우트 테이블');
            });
        }

        // 3. 관리자-역할 관계 테이블
        if (! Schema::hasTable('admin_has_roles')) {
            Schema::create('admin_has_roles', function (Blueprint $table) {
                $table->foreignId('admin_id')->constrained('admins')->onDelete('cascade');
                $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
                $table->primary(['admin_id', 'role_id']);
                $table->comment('관리자-역할 관계 테이블');
            });
        }

        // 4. 역할-접근불가 관계 테이블 (Role이 어떤 라우트를 막을지 정의)
        if (! Schema::hasTable('role_has_denied_routes')) {
            Schema::create('role_has_denied_routes', function (Blueprint $table) {
                $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
                $table->foreignId('route_id')->constrained('denied_routes')->onDelete('cascade');
                $table->primary(['role_id', 'route_id'], 'role_denied_routes_pk');
                $table->comment('역할-접근불가 관계 테이블');
            });
        }
    }

    public function down(): void
    {
        // 생성한 테이블명과 정확히 매칭되도록 수정
        Schema::dropIfExists('role_has_denied_routes');
        Schema::dropIfExists('admin_has_roles');
        Schema::dropIfExists('denied_routes');
        Schema::dropIfExists('roles');
    }
};
