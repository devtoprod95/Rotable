<?php

namespace App\Http\Controllers;

use App\Dtos\AdminListRequestDto;
use App\Dtos\AdminRoleListRequestDto;
use App\Dtos\AdminRoleRouteListRequestDto;
use App\Dtos\AdminRoleRouteStoreRequestDto;
use App\Dtos\AdminRoleRouteUpdateRequestDto;
use App\Dtos\AdminRoleStoreRequestDto;
use App\Dtos\AdminStoreRequestDto;
use App\Enums\Admin;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRouteStoreRequest;
use App\Http\Requests\Admin\RoleRouteUpdateRequest;
use App\Http\Requests\Admin\RoleStoreRequest;
use App\Http\Requests\Admin\StoreRequest;
use App\Models\Admin as ModelsAdmin;
use App\Models\DeniedRoute;
use App\Models\Role as ModelsRole;
use App\Services\AdminService;
use App\Services\TelegramNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdminController extends Controller
{
    protected Request $request;
    protected AdminService $adminService;

    public function __construct(
        Request $request,
        AdminService $adminService
    )
    {
        $this->request      = $request;
        $this->adminService = $adminService;
    }

    public function index(): View
    {
        $role         = $this->request->input('role') ?: '';
        $isActive     = $this->request->input('isActive');
        $deninedRoute = $this->request->input('deninedRoute') ?: [];
        $startDate    = $this->request->input('startDate') ?: '';
        $endDate      = $this->request->input('endDate') ?: '';
        $searchType   = $this->request->input('searchType') ?: Admin::ListSearchUsername->value;
        $searchText   = $this->request->input('searchText') ?: '';
        $page         = $this->request->input('page') ?: 1;
        $size         = $this->request->input('size') ?: 30;
        $sort         = $this->request->input('sort')[0] ?? ['field' => 'id', 'dir' => 'desc'];
        $params       = [
            'role'              => $role,
            'isActive'          => $isActive,
            'deninedRoute'      => $deninedRoute,
            'startDate'         => $startDate,
            'endDate'           => $endDate,
            'searchType'        => $searchType,
            'searchText'        => $searchText,
            'page'              => $page,
            'size'              => $size,
            'sort'              => $sort,
            'roles'             => Role::cases(),
            'actives'           => Admin::isActives(),
            'searchTypes'       => Admin::listSearchTypes(),
            'deninedRoutesObjs' => DeniedRoute::get()
        ];

        return view('admin.list', $params);
    }

    public function list(): JsonResponse
    {
        $dto = new AdminListRequestDto();
        $dto->bind([
            'role'         => $this->request->input('role', ''),
            'isActive'     => $this->request->input('isActive'),
            'deninedRoute' => $this->request->input('deninedRoute', []),
            'startDate'    => $this->request->input('startDate', ''),
            'endDate'      => $this->request->input('endDate', ''),
            'searchType'   => $this->request->input('searchType') ?: Admin::ListSearchUsername->value,
            'searchText'   => $this->request->input('searchText', ''),
            'page'         => $this->request->input('page', 1),
            'size'         => $this->request->input('size', 30),
            'sort'         => $this->request->input('sort.0', ['field' => 'id', 'dir' => 'desc']),
        ]);
        $result = $this->adminService->list($dto);

        return response()->json($result);
    }

    public function view(int|null $id = null): View
    {
        $adminObj = null;
        if( $id ){
            $adminObj = ModelsAdmin::with('roles')->find($id);
        }

        $params = [
            'adminObj'          => $adminObj,
            'roles'             => ModelsRole::get(),
            'actives'           => Admin::isActives(),
            'deninedRoutesObjs' => DeniedRoute::get()
        ];

        return view('admin.view', $params);
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $validated = (object) $request->validated();
        $dto       = new AdminStoreRequestDto($validated);
        $result    = $this->adminService->store($dto);

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    public function delete(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'ids'   => 'required|array',
            'ids.*' => 'integer',
        ], [
            'ids.required'  => '항목을 전달해주세요.',
            'ids.array'     => '잘못된 요청 형식입니다.',
            'ids.*.integer' => '유효하지 않은 ID 형식입니다.',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $ids    = $this->request->input('ids');
        $result = $this->adminService->delete($ids);

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    public function updateActive(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'ids'       => 'required|array',
            'ids.*'     => 'integer',
            'is_active' => 'required|string',
        ], [
            'ids.required'       => '항목을 전달해주세요.',
            'ids.array'          => '잘못된 요청 형식입니다.',
            'ids.*.integer'      => '유효하지 않은 ID 형식입니다.',
            'is_active.required' => '활성 여부를 전달해주세요.',
            'is_active.string'   => '잘못된 요청 형식입니다.',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $ids      = $this->request->input('ids');
        $isActive = $this->request->input('is_active');
        $result   = $this->adminService->updateActive($ids, $isActive);

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    public function roleIndex(): View
    {
        $deninedRoute = $this->request->input('deninedRoute') ?: [];
        $searchType   = $this->request->input('searchType') ?: Admin::RoleListSearchName->value;
        $searchText   = $this->request->input('searchText') ?: '';
        $page         = $this->request->input('page') ?: 1;
        $size         = $this->request->input('size') ?: 30;
        $sort         = $this->request->input('sort')[0] ?? ['field' => 'id', 'dir' => 'desc'];
        $params       = [
            'deninedRoute'      => $deninedRoute,
            'searchType'        => $searchType,
            'searchText'        => $searchText,
            'page'              => $page,
            'size'              => $size,
            'sort'              => $sort,
            'searchTypes'       => Admin::roleListSearchTypes(),
            'deninedRoutesObjs' => DeniedRoute::get()
        ];

        return view('admin.roleList', $params);
    }

    public function roleList(): JsonResponse
    {
        $deninedRoute = $this->request->input('deninedRoute') ?: [];
        $searchType   = $this->request->input('searchType') ?: Admin::ListSearchUsername->value;
        $searchText   = $this->request->input('searchText') ?: '';
        $page         = $this->request->input('page') ?: 1;
        $size         = $this->request->input('size') ?: 30;
        $sort         = $this->request->input('sort')[0] ?? ['field' => 'id', 'dir' => 'desc'];
        $dtoBind      = [
            'deninedRoute' => $deninedRoute,
            'searchType'   => $searchType,
            'searchText'   => $searchText,
            'page'         => $page,
            'size'         => $size,
            'sort'         => $sort,
        ];
        $dto = new AdminRoleListRequestDto();
        $dto->bind($dtoBind);
        $result = $this->adminService->roleList($dto);

        return response()->json($result);
    }

    public function roleInfo(int $id): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'id' => 'integer',
        ], [
            'id.required' => '항목을 전달해주세요.',
            'id.integer'  => '유효하지 않은 ID 형식입니다.',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $result = $this->adminService->roleInfo($id);

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    public function roleDelete(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'ids'   => 'required|array',
            'ids.*' => 'integer',
        ], [
            'ids.required'  => '항목을 전달해주세요.',
            'ids.array'     => '잘못된 요청 형식입니다.',
            'ids.*.integer' => '유효하지 않은 ID 형식입니다.',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $ids    = $this->request->input('ids');
        $result = $this->adminService->roleDelete($ids);

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    public function roleStore(RoleStoreRequest $request): JsonResponse
    {
        $validated = (object) $request->validated();
        $dto       = new AdminRoleStoreRequestDto($validated);
        $result    = $this->adminService->roleStore($dto);

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    public function roleRouteIndex(): View
    {
        $searchType   = $this->request->input('searchType') ?: Admin::RoleRouteListSearchName->value;
        $searchText   = $this->request->input('searchText') ?: '';
        $page         = $this->request->input('page') ?: 1;
        $size         = $this->request->input('size') ?: 30;
        $sort         = $this->request->input('sort')[0] ?? ['field' => 'id', 'dir' => 'desc'];
        $params       = [
            'searchType'  => $searchType,
            'searchText'  => $searchText,
            'page'        => $page,
            'size'        => $size,
            'sort'        => $sort,
            'searchTypes' => Admin::roleRouteListSearchTypes(),
            'routeList'   => routeList(),
            'roles'       => ModelsRole::get(),
        ];
        return view('admin.roleRouteList', $params);
    }

    public function roleRouteList(): JsonResponse
    {
        $searchType = $this->request->input('searchType') ?: Admin::RoleRouteListSearchName->value;
        $searchText = $this->request->input('searchText') ?: '';
        $page       = $this->request->input('page') ?: 1;
        $size       = $this->request->input('size') ?: 30;
        $sort       = $this->request->input('sort')[0] ?? ['field' => 'id', 'dir' => 'desc'];
        $dtoBind    = [
            'searchType' => $searchType,
            'searchText' => $searchText,
            'page'       => $page,
            'size'       => $size,
            'sort'       => $sort,
        ];
        $dto = new AdminRoleRouteListRequestDto();
        $dto->bind($dtoBind);
        $result = $this->adminService->roleRouteList($dto);

        return response()->json($result);
    }

    public function roleRouteInfo(int $id): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'id' => 'integer',
        ], [
            'id.required' => '항목을 전달해주세요.',
            'id.integer'  => '유효하지 않은 ID 형식입니다.',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $result = $this->adminService->roleRouteInfo($id);

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    public function roleRouteDelete(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'ids'   => 'required|array',
            'ids.*' => 'integer',
        ], [
            'ids.required'  => '항목을 전달해주세요.',
            'ids.array'     => '잘못된 요청 형식입니다.',
            'ids.*.integer' => '유효하지 않은 ID 형식입니다.',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $ids    = $this->request->input('ids');
        $result = $this->adminService->roleRouteDelete($ids);

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    public function roleRouteStore(RoleRouteStoreRequest $request): JsonResponse
    {
        $validated = (object) $request->validated();
        $dto       = new AdminRoleRouteStoreRequestDto($validated);
        $result    = $this->adminService->roleRouteStore($dto);

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    public function roleRouteUpdate(RoleRouteUpdateRequest $request): JsonResponse
    {
        $validated = (object) $request->validated();
        $dto       = new AdminRoleRouteUpdateRequestDto($validated);
        $result    = $this->adminService->roleRouteUpdate($dto);

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    public function impersonate(ModelsAdmin $admin): JsonResponse
    {
        /** @var ModelsAdmin|null $currentAdmin */
        $currentAdmin = auth('admin')->user();

        if (! $currentAdmin || ! $currentAdmin->hasRole(Role::SuperAdmin->value)) {
            return apiRes(Response::HTTP_FORBIDDEN, helpersFailMessage('최고 관리자만 로그인 대행 기능을 사용할 수 있습니다.'));
        }

        if ($currentAdmin->id === $admin->id) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage('현재 로그인 중인 계정입니다.'));
        }

        if ($admin->is_active == Admin::INACTIVE->value) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage('정지된 계정으로는 로그인할 수 없습니다.'));
        }

        performAdminImpersonation($admin, $currentAdmin);

        return apiRes(Response::HTTP_OK, helpersSuccessMessage("{$admin->name}({$admin->username}) 계정으로 로그인되었습니다."));
    }

    public function leaveImpersonation(): JsonResponse
    {
        $impersonatorId = session('impersonator_id');

        if (! $impersonatorId) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage('기존 관리자 정보가 존재하지 않습니다.'));
        }

        $originalAdmin = ModelsAdmin::find($impersonatorId);

        if (! $originalAdmin) {
            return apiRes(Response::HTTP_NOT_FOUND, helpersFailMessage('기존 관리자 계정을 찾을 수 없습니다.'));
        }

        leaveAdminImpersonation($originalAdmin);

        return apiRes(Response::HTTP_OK, helpersSuccessMessage("기존 관리자({$originalAdmin->name}) 계정으로 복귀했습니다."));
    }

    /**
     * 텔레그램 메시지 전달 on/off 토글. (최고관리자 전용 · superAdmin 미들웨어로 보호)
     * 요청 body 의 enabled(bool)로 상태를 설정하고, 미지정 시 현재 상태를 반전시킨다.
     */
    public function toggleTelegram(Request $request): JsonResponse
    {
        $enabled = $request->has('enabled')
            ? $request->boolean('enabled')
            : ! TelegramNotifier::isEnabled();

        TelegramNotifier::setEnabled($enabled);

        return apiRes(Response::HTTP_OK, helpersCustomArrayMessage(true, [
            'msg'     => $enabled ? '텔레그램 메시지 전달을 켰습니다.' : '텔레그램 메시지 전달을 껐습니다.',
            'enabled' => $enabled,
        ]));
    }
}
