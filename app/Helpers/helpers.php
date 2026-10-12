<?php
 
 use App\Enums\CacheKey;
use App\Models\Admin;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

if (!function_exists("apiRes")) {
    function apiRes(int $status, array $params = [], string $message = ""): JsonResponse
    {
        $result = [
            "status" => $status,
            "meta"   => [
                "timestamp" => Carbon::now()->format('Y-m-d H:i:s'),
                "api_type"  => 'apiRes',
            ]
        ];
        if( $status == Response::HTTP_OK ){
            unset($params["isSuccess"]);
            $result = array_merge($result, $params);
            if( trim($message) != "" ){
                $result["message"] = $message;
            }
        }else{
            if( !empty($params['msg']) ){
                $message = $params['msg'];
            }
            $message = empty($message) ? '잘못 된 접근입니다.' : $message;
            $error   = [
                "error" => [
                    "code"    => $status,
                    "message" => trim($message),
                ]
            ];
            if( isset($params["error_code"]) && !empty($params["error_code"]) ){
                $error["error"]["code"] = $params["error_code"];
            }
            if( isset($params["data"]) && !empty($params["data"]) ){
                $error["data"] = $params["data"];
            }
            $result = array_merge($result, $error);
        }

        return response()->json($result, $status);
    }
}

if (!function_exists("helpersDefaultMessage")) {
    function helpersDefaultMessage( $message = "잘못 된 접근입니다.")
    {
        return [
            "isSuccess" => false,
            "msg"       => $message,
        ];
    }
}

if (!function_exists("helpersFailMessage")) {
    function helpersFailMessage($message = "변경 사항이 없거나 처리가 실패하였습니다. 관리자에 문의 바랍니다.")
    {
        return [
            "isSuccess" => false,
            "msg"       => $message,
        ];
    }
}

if (!function_exists("helpersSuccessMessage")) {
    function helpersSuccessMessage($message = "정상 처리 되었습니다.")
    {
        return [
            "isSuccess" => true,
            "msg"       => $message,
        ];
    }
}

if (!function_exists("helpersCustomArrayMessage")) {
    function helpersCustomArrayMessage(bool $isSuccess, array $body)
    {
        $result["isSuccess"] = $isSuccess;
		return array_merge($result, $body);
    }
}

if (!function_exists("routeList")) {
    function routeList(): array
    {
        $routes            = Route::getRoutes();
        $permittedPrefixes = ['admin.', 'settings.', 'dashboard'];
        $filteredRoutes    = [];

        foreach ($routes as $route) {
            $name = $route->getName();
            if ($name && in_array('GET', $route->methods()) && Str::startsWith($name, $permittedPrefixes)) {
                $filteredRoutes[] = [
                    'name' => $name,
                    'uri'  => '/' . $route->uri(),
                ];
            }
        }

        return $filteredRoutes;
    }
}

if (!function_exists("channelLog")) {
    /**
     * 커스텀 채널 로거
     *
     * 지정된 채널과 파일명으로 로그를 기록하며, 실행 시간과 호출 위치 정보를 자동으로 추가합니다.
     * Laravel의 daily 드라이버를 사용하여 날짜별로 로그 파일을 생성합니다.
     *
     * @param Throwable|string|array $message 로그 인스턴스
     * @param string $channel 로그 채널명 (폴더명으로도 사용됨)
     * @param string|null $filename 로그 파일명 (null일 경우 채널명 사용, 실제 파일은 filename-yyyy-mm-dd.log 형태로 저장)
     * @param string $level 로그 레벨 (emergency, alert, critical, error, warning, notice, info, debug)
     * @param int $days 로그 파일 보관 일수 (기본: 30일)
     *
     * @example channelLog('시작', 'apple') → logs/apple/apple-2025-06-02.log
     * @example channelLog('에러', 'apple', 'error_log', 'error') → logs/apple/error_log-2025-06-02.log
     * @example channelLog('디버그', 'apple', null, 'debug') → logs/apple/apple-2025-06-02.log
     *
     * @return void
     */
    function channelLog(Throwable|string|array $message, string $channel, ?string $filename = null, string $level = 'info', int $days = 30)
    {
        // 파일명이 없으면 오늘 날짜를 파일명으로 사용
        $driver = 'daily';
        if (!$filename) {
            $logFilename = 'app';
        } else {
            $logFilename = $filename;
        }

        // 채널명을 파일명에 따라 고유하게 생성
        $uniqueChannelName = $channel . '_' . $logFilename;

        // 채널 구성이 없으면 생성
        if (!config()->has('logging.channels.'.$uniqueChannelName)) {
            config(['logging.channels.'.$uniqueChannelName => [
                'driver'     => $driver,
                'path'       => storage_path("logs/{$channel}/{$logFilename}.log"),
                'level'      => env('LOG_LEVEL', 'debug'),
                'days'       => $days,
                'permission' => 0777,
            ]]);
        }

        // Exception 객체인지 확인해서 위치 정보 추출
        if ($message instanceof Throwable) {
            // Exception 객체인 경우 실제 에러 발생 위치 사용
            $runFile     = basename($message->getFile());
            $location    = "{$runFile}:{$message->getLine()}";
            $messageText = $message->getMessage();
        } else {
            // 일반 문자열 또는 배열인 경우 호출한 위치 사용
            $backtrace   = debug_backtrace();
            $caller      = $backtrace[0];
            $runFile     = basename($caller['file']);
            $location    = "{$runFile}:{$caller['line']}";

            // 배열인 경우 JSON으로 인코딩
            if (is_array($message)) {
                $messageText = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                $messageText = $message;
            }
        }

        // 메시지에 추가 정보 붙이기
        $prefixedMessage = sprintf(
            "[location: %s] %s",
            $location,
            $messageText
        );

        // 고유한 채널명으로 로거 가져오기
        $logger = \Illuminate\Support\Facades\Log::channel($uniqueChannelName);

        // 유효한 로그 레벨인지 확인
        $validLevels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
        if (!in_array(strtolower($level), $validLevels)) {
            $level = 'info'; // 기본값으로 설정
        }

        // 로깅 실행
        $logger->{$level}($prefixedMessage);
    }
}

if (!function_exists('helperCurl')) {
    /**
     * HTTP 요청 처리
     *
     * @param string $method (GET, POST, PUT, DELETE, PATCH)
     * @param string $url
     * @param array|string $header 요청 헤더
     * @param mixed $data 요청 바디 데이터 (배열 또는 JSON 문자열)
     * @param bool $status HTTP 상태코드 포함 여부
     * @param bool $includeHeader 응답 헤더 포함 여부
     * @return mixed
     */
    function helperCurl(
        string $method,
        string $url,
        array $header        = [],
        mixed $data          = '',
        bool  $status        = false,
        bool  $includeHeader = false
    ) {
        $client = new Client([
            'timeout'         => 30,
            'connect_timeout' => 10,
            'http_errors'     => false,
            'verify'          => env('APP_ENV') === 'production',   // 개발환경에서는 SSL 검증 스킵
        ]);

        $options = [
            'headers' => $header,
        ];

        if (!empty($data)) {
            if (is_array($data)) {
                $options['json'] = $data;
            } else {
                // JSON 문자열인 경우
                $options['body'] = $data;
            }
        }

        // HTTP 요청 실행
        $response        = $client->request(strtoupper($method), $url, $options);
        $statusCode      = $response->getStatusCode();
        $rawBody         = $response->getBody()->getContents();
        $responseHeaders = $response->getHeaders();
        $contentType     = strtolower($response->getHeaderLine('Content-Type'));

        // JSON 응답은 디코딩, XML/기타 포맷은 원문 그대로 반환
        $body = $rawBody;
        if (str_contains($contentType, 'application/json') || str_contains($contentType, '+json')) {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $body = $decoded;
            }
        } elseif (str_contains($contentType, 'application/xml') || str_contains($contentType, 'text/xml')) {
            $body = $rawBody;
        }

        // 반환값 구성
        $result = [];

        if ($includeHeader) {
            $result['headers'] = $responseHeaders;
        }

        $result['body'] = $body;

        if ($status) {
            $result['status'] = $statusCode;
        }

        // 단순 바디만 반환하는 경우
        if (!$status && !$includeHeader) {
            return $body;
        }

        return $result;
    }
}

if (!function_exists('onlyNumber')) {
    function onlyNumber(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            $cleaned = preg_replace('/[^0-9]/', '', $value);
            return $cleaned !== '' ? (int) $cleaned : 0;
        }

        return 0;
    }
};

if (!function_exists('saveTempFile')) {
        /**
     * 임시 파일 저장
     */
    function saveTempFile(UploadedFile $file, ?string $subName = null): string
    {
        $tmpDir = storage_path('app/tmp');

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        // 1. 원본 파일명에서 한글 및 특수문자 제거 (알파벳, 숫자, 대시, 언더바만 허용)
        $pureFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $pureFileName = preg_replace('/[^A-Za-z0-9_\-]/', '', $pureFileName);

        // 만약 파일명이 순수 한글로만 되어 있어 비어버린 경우를 대비한 기본값 지정
        if (empty($pureFileName)) {
            $pureFileName = 'file';
        }

        $extension  = $file->getClientOriginalExtension();
        $datePart   = date('Ymd_His');
        $uniquePart = bin2hex(random_bytes(4));

        // 2. subname 존재 여부에 따른 파일명 분기 처리
        if (!empty($subName)) {
            // 서브네임에서도 한글/특수문자 제거
            $cleanSubName = preg_replace('/[^A-Za-z0-9_\-]/', '', $subName);
            $tmpFileName  = sprintf('%s_%s_%s_%s.%s', $pureFileName, $datePart, $uniquePart, $cleanSubName, $extension);
        } else {
            $tmpFileName = sprintf('%s_%s_%s.%s', $pureFileName, $datePart, $uniquePart, $extension);
        }

        $tmpPath = $tmpDir . '/' . $tmpFileName;

        if (!$file->move($tmpDir, $tmpFileName)) {
            throw new Exception('임시 저장 실패');
        }

        return $tmpPath;
    }
}

if (!function_exists('clearAdminPermissionCache')) {
    /**
     * 특정 관리자의 권한 라우트 캐시 삭제
     */
    function clearAdminPermissionCache(?Admin $admin = null): void
    {
        if (!$admin) {
            /** @var Admin|null $admin */
            $admin = Auth::guard('admin')->user();
        }

        if ($admin) {
            $roleIds = $admin->roles->pluck('id')->sort()->implode('_');
            if ($roleIds !== '') {
                Cache::forget(CacheKey::AdminDeniedRoutesRoles->forRoleIds($roleIds));
            }
        }
    }
}

if (!function_exists('performAdminImpersonation')) {
    /**
     * 관리자 대행 로그인 처리
     */
    function performAdminImpersonation(Admin $targetAdmin, ?Admin $impersonator = null): void
    {
        $currentAdmin = $impersonator ?: Auth::guard('admin')->user();

        // 1. 기존 및 대상 관리자 권한 캐시 클리어
        clearAdminPermissionCache($currentAdmin);
        clearAdminPermissionCache($targetAdmin);

        // 2. 최초 대행자 ID 세션 보존 (중첩 대행 방지)
        if (!session()->has('impersonator_id') && $currentAdmin) {
            session()->put('impersonator_id', $currentAdmin->id);
        }

        // 3. 대상 관리자로 로그인 및 세션 갱신
        Auth::guard('admin')->login($targetAdmin);
        request()->session()->regenerate();
    }
}

if (!function_exists('leaveAdminImpersonation')) {
    /**
     * 기존 관리자로 복귀 처리
     */
    function leaveAdminImpersonation(Admin $originalAdmin): void
    {
        /** @var Admin|null $currentImpersonatedAdmin */
        $currentImpersonatedAdmin = Auth::guard('admin')->user();

        // 1. 대행 중이던 계정 및 복귀할 계정 권한 캐시 클리어
        clearAdminPermissionCache($currentImpersonatedAdmin);
        clearAdminPermissionCache($originalAdmin);

        // 2. 대행 세션 제거
        session()->forget('impersonator_id');

        // 3. 원래 관리자로 로그인 및 세션 갱신
        Auth::guard('admin')->login($originalAdmin);
        request()->session()->regenerate();
    }
}
