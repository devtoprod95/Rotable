<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoTest extends TestCase
{
    # php artisan test --filter testResizeVideo
    public function testResizeVideo(): void
    {
        $tmpDir    = storage_path('app/tmp');
        $inputPath = $tmpDir . '/test_FHD.mp4';
        $this->assertFileExists($inputPath, $inputPath . ' 파일이 없습니다.');

        $targetWidth    = 480;
        $targetHeight   = 853;
        $targetDuration = 10;

        $inputInfo  = pathinfo($inputPath);
        $baseName   = $inputInfo['filename'];
        $extension  = $inputInfo['extension'] ?? 'mp4';
        $outputName = sprintf('%dx%dx%s_resize.%s', $targetWidth, $targetHeight, $baseName, $extension);
        $outputPath = $tmpDir . '/' . $outputName;

        // -stream_loop -1 : 영상을 무한 반복 재생
        // -t {duration}   : 지정한 길이에서 잘라냄
        // → 둘을 같이 쓰면 원본보다 길이가 짧아도 반복해서 늘려주고,
        //   원본이 더 길면 그냥 잘라내는 효과를 한 번에 처리 (조건 분기 불필요)
        //
        // -vf scale=...:force_original_aspect_ratio=increase,crop=...
        //   먼저 목표 사이즈를 완전히 덮도록 확대(비율 유지) 후, 정확히 목표 크기로 크롭
        //   → 찌그러짐 없이 정확히 원하는 width x height로 출력됨
        $cmd = sprintf(
            'ffmpeg -stream_loop -1 -i %s -t %s -vf %s -y %s 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg((string) $targetDuration),
            escapeshellarg(sprintf(
                'scale=%d:%d:force_original_aspect_ratio=increase,crop=%d:%d',
                $targetWidth, $targetHeight, $targetWidth, $targetHeight
            )),
            escapeshellarg($outputPath)
        );

        exec($cmd, $output, $returnCode);
        $isSuccess = $returnCode === 0;

        // ffprobe로 결과물 실제 width/height/duration 검증
        $probeCmd = sprintf(
            'ffprobe -v error -select_streams v:0 -show_entries stream=width,height -show_entries format=duration -of json %s',
            escapeshellarg($outputPath)
        );
        exec($probeCmd, $probeOutput, $probeReturnCode);
        $info = json_decode(implode('', $probeOutput), true);

        dump([
            'ffmpeg_status' => $isSuccess ? '성공' : '실패',
            'return_code'   => $returnCode,
            'output_path'   => $outputPath,
            'width'         => $info['streams'][0]['width'] ?? null,
            'height'        => $info['streams'][0]['height'] ?? null,
            'duration'      => isset($info['format']['duration']) ? (float) $info['format']['duration'] : null,
        ]);
    }

    # php artisan test --filter testHasAudioStream
    public function testHasAudioStream(): void
    {
        $tmpDir    = storage_path('app/tmp');
        $videoPath = $tmpDir . '/FHD.mp4';
        $this->assertFileExists($videoPath, $videoPath . ' 파일이 없습니다.');

        // -select_streams a           : 오디오 스트림만 선택
        // -show_entries stream=index  : 오디오 스트림의 index만 출력 (있으면 0/1.., 없으면 빈 출력)
        // -of csv=p=0                 : 값만 간결하게 출력
        $cmd = sprintf(
            'ffprobe -v error -select_streams a -show_entries stream=index -of csv=p=0 %s 2>&1',
            escapeshellarg($videoPath)
        );

        $output   = trim((string) shell_exec($cmd));
        $hasAudio = $output !== '';

        dump([
            'video_path' => $videoPath,
            'has_audio'  => $hasAudio ? '오디오 트랙 있음' : '오디오 트랙 없음',
        ]);
    }
}
