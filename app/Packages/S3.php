<?php

namespace App\Packages;

use Illuminate\Support\Facades\Storage;
use Exception;

class S3
{
    protected string $disk = 's3';
    protected string $bucket;
    protected string $region;

    public function __construct()
    {
        $this->bucket = config('filesystems.disks.s3.bucket');
        $this->region = config('filesystems.disks.s3.region');
    }

    /**
     * S3에 파일 업로드
     *
     * @param string $filePath 로컬 파일 경로
     * @param string $s3Key S3 저장 경로
     * @param string $mimeType 파일 MIME 타입
     * @return array ['url' => $url, 's3_key' => $s3Key, 'bucket' => $bucket]
     */
    public function upload(string $filePath, string $s3Key, string $mimeType = 'video/mp4'): array
    {
        try {
            $fileContent = file_get_contents($filePath);

            if ($fileContent === false) {
                throw new Exception('로컬 파일을 읽을 수 없습니다. filepath: ' . $filePath);
            }

            Storage::disk($this->disk)->put($s3Key, $fileContent, [
                'ContentType' => $mimeType,
            ]);

            $url = $this->getUrl($s3Key);

            return [
                'success' => true,
                'url'     => $url,
                's3_key'  => $s3Key,
                'bucket'  => $this->bucket,
                's3Path'  => "s3://{$this->bucket}/{$s3Key}",
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * S3 파일 URL 조회
     */
    public function getUrl(string $s3Key): string
    {
        return "https://{$this->bucket}.s3.{$this->region}.amazonaws.com/{$s3Key}";
    }

    /**
     * S3 파일 다운로드
     */
    public function get(string $s3Key): string
    {
        return Storage::disk($this->disk)->get($s3Key);
    }

    /**
     * S3 파일 존재 여부 확인
     */
    public function exists(string $s3Key): bool
    {
        return Storage::disk($this->disk)->exists($s3Key);
    }

    /**
     * S3 파일 삭제
     */
    public function delete(string $s3Key): bool
    {
        return Storage::disk($this->disk)->delete($s3Key);
    }

    /**
     * S3 파일 크기 조회 (바이트)
     */
    public function size(string $s3Key): int
    {
        return Storage::disk($this->disk)->size($s3Key);
    }

    /**
     * S3 full URL을 key로 변환
     */
    public function urlToKey(string $url): ?string
    {
        $prefix = "s3://{$this->bucket}/";
        if (str_starts_with($url, $prefix)) {
            return substr($url, strlen($prefix));
        }
        return null;
    }

    /**
     * Bucket 명 반환
     */
    public function getBucket(): string
    {
        return $this->bucket;
    }
}
