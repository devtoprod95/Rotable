<?php

namespace App\Services;

use App\Dtos\AdminListRequestDto;
use App\Dtos\AdminRoleListRequestDto;
use App\Dtos\AdminRoleRouteListRequestDto;
use App\Dtos\AdminRoleRouteStoreRequestDto;
use App\Dtos\AdminRoleRouteUpdateRequestDto;
use App\Dtos\AdminRoleStoreRequestDto;
use App\Dtos\AdminStoreRequestDto;
use App\Models\Admin;
use App\Models\DeniedRoute;
use App\Models\Role;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

class AdminService
{
    protected array $returnMsg = [];

    public function __construct()
    {
        $this->returnMsg = helpersDefaultMessage();
    }

    public function list(AdminListRequestDto $dto): LengthAwarePaginator
    {
        $query = Admin::query()->with('roles');

        // 역할 필터
        if (!empty($dto->role)) {
            $query->whereHas('roles', function($q) use ($dto) {
                $q->where('name', $dto->role);
            });
        }

        // 활성여부 필터
        if ($dto->isActive !== '' && $dto->isActive !== null) {
            $query->where('is_active', $dto->isActive);
        }

        // 차단 라우트 필터
        if (!empty($dto->deninedRoute)) {
            $query->whereHas('roles', function($q) use ($dto) {
                $q->whereHas('deniedRoutes', function($q2) use ($dto) {
                    $q2->whereIn('denied_routes.id', $dto->deninedRoute);
                });
            });
        }

        // 가입일 범위
        if (!empty($dto->startDate)) {
            $query->whereDate('created_at', '>=', $dto->startDate);
        }
        if (!empty($dto->endDate)) {
            $query->whereDate('created_at', '<=', $dto->endDate);
        }

        // 검색
        if (!empty($dto->searchText)) {
            $query->where($dto->searchType, 'like', '%' . $dto->searchText . '%');
        }

        // 정렬
        if (!empty($dto->sort)) {
            $query->orderBy($dto->sort['field'], $dto->sort['dir']);
        }

        return $query->paginate($dto->size, ['*'], 'page', $dto->page);
    }

    public function store(AdminStoreRequestDto $dto): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $admin = Admin::find($dto->id) ?? new Admin();
            if ($admin->exists) {
                // 수정: 비밀번호가 입력된 경우에만 검증 및 변경
                if (!empty($dto->password)) {
                    if (mb_strlen($dto->password) < 8) {
                        throw new Exception('새 비밀번호는 최소 8자 이상이어야 합니다.');
                    }
                    if (!preg_match('/[a-zA-Z]/', $dto->password) || !preg_match('/[0-9]/', $dto->password) || !preg_match('/[^a-zA-Z0-9]/', $dto->password)) {
                        throw new Exception('새 비밀번호는 영문, 숫자, 특수문자 조합이어야 합니다.');
                    }
                    $admin->password = Hash::make($dto->password);
                }
            } else {
                // 신규 등록
                if (empty($dto->password)) {
                    throw new Exception('비밀번호를 입력해주세요.');
                }
                if (mb_strlen($dto->password) < 8) {
                    throw new Exception('비밀번호는 최소 8자 이상이어야 합니다.');
                }
                if (!preg_match('/[a-zA-Z]/', $dto->password) || !preg_match('/[0-9]/', $dto->password) || !preg_match('/[^a-zA-Z0-9]/', $dto->password)) {
                    throw new Exception('비밀번호는 영문, 숫자, 특수문자 조합이어야 합니다.');
                }
                $existing = Admin::where('username', $dto->username)->first();
                if ($existing !== null) {
                    throw new Exception('이미 사용 중인 아이디입니다.');
                }
                $admin->password = Hash::make($dto->password);
            }

            $admin->username  = $dto->username;
            $admin->name      = $dto->name;

            // 활성여부·권한(역할) 변경은 최고관리자만 가능하다.
            // 비최고관리자는 신규 등록 불가, 수정 시에도 is_active/roles 는 기존 값을 유지한다.
            $currentAdmin = auth('admin')->user();
            $isSuperAdmin = $currentAdmin !== null
                && $currentAdmin->hasRole(\App\Enums\Role::SuperAdmin->value);

            if ($isSuperAdmin) {
                $admin->is_active = $dto->isActive;
                $admin->save();
                $admin->roles()->sync($dto->roles);
            } else {
                if (! $admin->exists) {
                    throw new Exception('관리자 신규 등록은 최고관리자만 가능합니다.');
                }
                // is_active/roles 는 건드리지 않고 나머지(아이디/이름/비밀번호)만 저장.
                $admin->save();
            }

            $returnMsg = helpersSuccessMessage();
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    public function delete(array $ids): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $admins = Admin::whereIn('id', $ids)->get();
            if ($admins->isEmpty()) {
                throw new Exception('관리자를 찾을 수 없습니다.');
            }

            foreach ($admins as $admin) {
                $admin->delete();
            }

            $returnMsg = helpersSuccessMessage();
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    public function updateActive(array $ids, string $isActive): array
    {
        $returnMsg = $this->returnMsg;

        try {
            // 활성여부 변경은 최고관리자만 가능하다.
            $currentAdmin = auth('admin')->user();
            if ($currentAdmin === null || ! $currentAdmin->hasRole(\App\Enums\Role::SuperAdmin->value)) {
                throw new Exception('활성여부 변경은 최고관리자만 가능합니다.');
            }

            Admin::whereIn('id', $ids)->update(['is_active' => $isActive]);

            $returnMsg = helpersSuccessMessage();
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    public function roleList(AdminRoleListRequestDto $dto): LengthAwarePaginator
    {
        $query = Role::query()->withCount('deniedRoutes');

        // 차단 라우트 필터
        if (!empty($dto->deninedRoute)) {
            $query->whereHas('deniedRoutes', function($q2) use ($dto) {
                $q2->whereIn('denied_routes.id', $dto->deninedRoute);
            });
        }

        // 검색
        if (!empty($dto->searchText)) {
            $query->where($dto->searchType, 'like', '%' . $dto->searchText . '%');
        }

        // 정렬
        if (!empty($dto->sort)) {
            $query->orderBy($dto->sort['field'], $dto->sort['dir']);
        }

        return $query->paginate($dto->size, ['*'], 'page', $dto->page);
    }

    public function roleInfo(int $id): array
    {
        $returnMsg = $this->returnMsg;
        $obj       = Role::with('deniedRoutes')->find($id);
        $returnMsg = helpersCustomArrayMessage(true, ['data' => $obj ?? []]);

        return $returnMsg;
    }

    public function roleDelete(array $ids): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $objs = Role::whereIn('id', $ids)->get();
            if ($objs->isEmpty()) {
                throw new Exception('권한을 찾을 수 없습니다.');
            }

            foreach ($objs as $obj) {
                $obj->delete();
            }

            $returnMsg = helpersSuccessMessage();
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    public function roleStore(AdminRoleStoreRequestDto $dto): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $exists = Role::where('display_name', $dto->displayName)->exists();
            if( $exists ){
                throw new Exception('이미 사용중인 권한명이 있습니다.');
            }

            $exists = Role::where('name', $dto->name)->exists();
            if( $exists ){
                throw new Exception('이미 사용중인 권한값이 있습니다.');
            }

            $obj               = new Role();
            $obj->display_name = $dto->displayName;
            $obj->name         = $dto->name;
            $obj->save();

            $returnMsg = helpersSuccessMessage();
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    public function roleRouteList(AdminRoleRouteListRequestDto $dto): LengthAwarePaginator
    {
        $query = DeniedRoute::query()->withCount(['roles' => function ($query) {
            $query->select(DB::raw('count(distinct roles.id)'));
        }]);

        // 검색
        if (!empty($dto->searchText)) {
            $query->where($dto->searchType, 'like', '%' . $dto->searchText . '%');
        }

        // 정렬
        if (!empty($dto->sort)) {
            $query->orderBy($dto->sort['field'], $dto->sort['dir']);
        }

        return $query->paginate($dto->size, ['*'], 'page', $dto->page);
    }

    public function roleRouteInfo(int $id): array
    {
        $returnMsg = $this->returnMsg;
        $obj       = DeniedRoute::with(['roles' => function ($query) {
            $query->select('roles.*')->distinct();
        }])->find($id);

        $returnMsg = helpersCustomArrayMessage(true, ['data' => $obj ?? []]);

        return $returnMsg;
    }

    public function roleRouteDelete(array $ids): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $objs = DeniedRoute::whereIn('id', $ids)->get();
            if ($objs->isEmpty()) {
                throw new Exception('페이지를 찾을 수 없습니다.');
            }

            foreach ($objs as $obj) {
                $obj->delete();
            }

            $returnMsg = helpersSuccessMessage();
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    public function roleRouteStore(AdminRoleRouteStoreRequestDto $dto): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $exists = DeniedRoute::where('route', $dto->route)->exists();
            if( $exists ){
                throw new Exception('이미 등록된 페이지입니다.');
            }
            if (!Route::has($dto->route)) {
                throw new Exception('존재하지 않는 페이지입니다: ' . $dto->route);
            }

            $deniedRoute = DeniedRoute::create([
                'route'      => $dto->route,
                'route_name' => $dto->routeName
            ]);
            $deniedRoute->roles()->attach($dto->roles);

            $returnMsg = helpersSuccessMessage();
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    public function roleRouteUpdate(AdminRoleRouteUpdateRequestDto $dto): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $obj = DeniedRoute::where('id', $dto->id)->first();
            if( $obj === null ){
                throw new Exception('empty DeniedRoute');
            }

            $exists = DeniedRoute::where('route', $obj->route)->where('id', '!=', $dto->id)->exists();
            if( $exists ){
                throw new Exception('이미 등록된 페이지입니다.');
            }
            if (!Route::has($obj->route)) {
                throw new Exception('존재하지 않는 페이지입니다: ' . $dto->route);
            }

            $obj->route_name = $dto->routeName;
            $obj->save();
            $obj->roles()->sync($dto->roles);

            $returnMsg = helpersSuccessMessage();
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }
}
