<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Admin as EnumsAdmin;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function show()
    {
        $redirectTo         = $this->request->input('redirectTo');
        $rememberedUsername = Cookie::get('remember_username');
        $rememberedPassword = Cookie::get('remember_password');

        $params = [
            'redirectTo' => $redirectTo,
            'username'   => $rememberedUsername,
            'password'   => $rememberedPassword,
            'isChecked'  => !empty($rememberedUsername)
        ];
        return view('auth.login', $params);
    }

    // 로그인 로직 처리
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => '아이디를 입력해주세요.',
            'password.required' => '비밀번호를 입력해주세요.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $admin = Admin::where('username', $request->username)->first();
        if (!$admin) {
            return back()->withErrors([
                'username' => '존재하지 않는 아이디입니다.',
            ])->withInput();
        }

        if ($admin->is_active == EnumsAdmin::INACTIVE->value) {
            return back()->withErrors([
                'username' => '정지 된 계정입니다.',
            ])->withInput();
        }

        // 2. 비밀번호 검증 (Hash::check 사용)
        if (!Hash::check($request->password, $admin->password)) {
            return back()->withErrors([
                'password' => '비밀번호가 일치하지 않습니다.',
            ])->withInput();
        }

        if ($request->has('remember')) {
            // 체크함: 쿠키 생성 (10년)
            Cookie::queue('remember_username', $request->username, 5256000);
            Cookie::queue('remember_password', $request->password, 5256000);
        } else {
            // 체크 안 함: 쿠키 삭제
            Cookie::queue(Cookie::forget('remember_username'));
            Cookie::queue(Cookie::forget('remember_password'));
        }

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        $admin->update([
            'last_login_at' => now(),            // 현재 시간
            'last_login_ip' => $request->ip(),   // 요청한 IP 주소
        ]);

        $redirectTo = $request->input('redirectTo') ?: '/';
        return redirect($redirectTo);
    }

    // 토큰 기반 브라우저 자동 로그인 (MCP 연동)
    public function autoLogin(Request $request)
    {
        $token = $request->query('token');

        if (! $token) {
            return redirect('/admin/login')->withErrors(['username' => '유효하지 않은 토큰입니다.']);
        }

        $adminId = Cache::get('auto_login_token_' . $token);

        if (! $adminId) {
            return redirect('/admin/login')->withErrors(['username' => '만료되거나 유효하지 않은 자동 로그인 링크입니다.']);
        }

        $admin = Admin::find($adminId);

        if (! $admin) {
            return redirect('/admin/login')->withErrors(['username' => '존재하지 않는 계정입니다.']);
        }

        if ($admin->is_active == EnumsAdmin::INACTIVE->value) {
            return redirect('/admin/login')->withErrors(['username' => '정지 된 계정입니다.']);
        }

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        $admin->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return redirect('/');
    }

    // 로그아웃
    public function logout(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\Admin|null $admin */
            $admin = Auth::guard('admin')->user();
            clearAdminPermissionCache($admin);

            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return apiRes(Response::HTTP_OK, helpersSuccessMessage());
        } catch (\Throwable $th) {
            return apiRes(Response::HTTP_BAD_GATEWAY, helpersFailMessage($th->getMessage()));
        }
    }
}
