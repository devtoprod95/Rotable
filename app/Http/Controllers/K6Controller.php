<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class K6Controller extends Controller
{
    /**
     * K6 부하 테스트 메인 페이지
     */
    public function index(): View
    {
        $k6Binary    = $this->getK6BinaryPath();
        $isInstalled = $k6Binary !== null;
        $appUrl      = url('/');
        $isDocker    = file_exists('/.dockerenv');

        return view('setting-k6', compact('isInstalled', 'appUrl', 'isDocker'));
    }

    /**
     * k6 바이너리 자동 설치
     */
    public function installK6(): JsonResponse
    {
        try {
            $dir = storage_path('app/k6');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $tarPath = $dir . '/k6.tar.gz';

            // k6 v0.51.0 Linux AMD64 다운로드
            $url = 'https://github.com/grafana/k6/releases/download/v0.51.0/k6-v0.51.0-linux-amd64.tar.gz';
            $ch  = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $data = curl_exec($ch);
            curl_close($ch);

            if (!$data) {
                return response()->json(['status' => 500, 'msg' => 'k6 다운로드에 실패했습니다. (GitHub 접근 확인 필요)']);
            }

            file_put_contents($tarPath, $data);

            // 리눅스 tar 명령어로 압축 해제
            $output     = [];
            $resultCode = 0;
            exec("tar -xzf " . escapeshellarg($tarPath) . " -C " . escapeshellarg($dir), $output, $resultCode);

            if ($resultCode !== 0) {
                @unlink($tarPath);
                return response()->json(['status' => 500, 'msg' => '압축 해제에 실패했습니다. (tar 명령어 실행 실패)']);
            }

            // 바이너리 파일 이동 및 권한 부여
            $extractedK6 = $dir . '/k6-v0.51.0-linux-amd64/k6';
            if (file_exists($extractedK6)) {
                rename($extractedK6, $dir . '/k6');
                chmod($dir . '/k6', 0755);
            } else {
                @unlink($tarPath);
                return response()->json(['status' => 500, 'msg' => '추출된 k6 바이너리를 찾을 수 없습니다.']);
            }

            // 임시 파일 및 폴더 삭제
            @unlink($tarPath);
            if (is_dir($dir . '/k6-v0.51.0-linux-amd64')) {
                @unlink($dir . '/k6-v0.51.0-linux-amd64/LICENSE.md');
                @unlink($dir . '/k6-v0.51.0-linux-amd64/README.md');
                @rmdir($dir . '/k6-v0.51.0-linux-amd64');
            }

            return response()->json(['status' => 200, 'msg' => 'k6 바이너리가 성공적으로 설치되었습니다.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'msg' => '설치 오류 발생: ' . $e->getMessage()]);
        }
    }

    /**
     * k6 스크립트 실행
     */
    public function run(Request $request): JsonResponse
    {
        $k6Binary = $this->getK6BinaryPath();
        if (!$k6Binary) {
            return response()->json([
                'status' => 400,
                'msg'    => 'k6 바이너리가 설치되어 있지 않습니다. 설치 버튼을 눌러 먼저 설치해 주세요.'
            ]);
        }

        $script = $request->input('script');
        if (empty($script)) {
            return response()->json([
                'status' => 400,
                'msg'    => '스크립트 내용이 비어 있습니다.'
            ]);
        }

        $dir = storage_path('app/k6');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tempScriptFile = $dir . '/k6_run_' . uniqid() . '.js';
        file_put_contents($tempScriptFile, $script);

        // k6 run 실행 (보안상 외부 인자를 그대로 shell 인자로 넘기지 않고 JS 파일 경로만 바인딩함)
        $command = escapeshellcmd($k6Binary) . ' run ' . escapeshellarg($tempScriptFile);

        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"]  // stderr
        ];

        $process = proc_open($command, $descriptorspec, $pipes);

        $stdout = '';
        $stderr = '';

        if (is_resource($process)) {
            fclose($pipes[0]);

            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);

            $timeout = 45;      // 최대 45초 실행 제한
            $start   = time();

            while (true) {
                $stdout .= stream_get_contents($pipes[1]);
                $stderr .= stream_get_contents($pipes[2]);

                $status = proc_get_status($process);
                if (!$status['running']) {
                    break;
                }

                if ((time() - $start) > $timeout) {
                    proc_terminate($process);
                    $stderr .= "\n[오류] 테스트가 제한 시간(45초)을 초과하여 강제 종료되었습니다.";
                    break;
                }

                usleep(100000); // 100ms 대기
            }

            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);

            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        } else {
            @unlink($tempScriptFile);
            return response()->json([
                'status' => 500,
                'msg'    => 'k6 프로세스를 실행할 수 없습니다.'
            ]);
        }

        @unlink($tempScriptFile);

        return response()->json([
            'status' => 200,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ]);
    }

    /**
     * 시스템 상의 k6 바이너리 경로 탐색
     */
    private function getK6BinaryPath(): ?string
    {
        // 1. 시스템 PATH 환경변수에 있는 글로벌 k6 확인
        $which = shell_exec('which k6 2>/dev/null');
        if (!empty($which)) {
            return trim($which);
        }

        // 2. 프로젝트 내 로컬 k6 확인
        $localPath = storage_path('app/k6/k6');
        if (file_exists($localPath)) {
            return $localPath;
        }

        return null;
    }
}
