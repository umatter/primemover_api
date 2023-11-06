<?php

namespace App\Modules\S3\Service;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Exception\FileNotFoundException;

class S3Service
{
    public const MOCK = 'local';

    /**
     * @throws FileNotFoundException
     */
    public function downloadFile(string $filename): \Illuminate\Http\Response
    {
        $targetDisk = $this->getTargetDisk();
        $file = $this->getFileOrFail($targetDisk, $filename);

        Log::info('1682672850: File download processing', [
            'filename' => $filename,
            'file' => $file,
            'disk' => $targetDisk,
        ]);

        return Response::make($file, 200, [
            'Content-Type' => Storage::disk($targetDisk)->mimeType($filename),
            'Content-Disposition' => 'attachment; filename="'.basename($filename).'"',
        ]);
    }

    /**
     * @throws FileNotFoundException
     */
    public function getFileOrFail(string $disk, string $filename): string
    {
        if ( ! $file = Storage::disk($disk)->get($filename)) {
            throw new FileNotFoundException('File not found. See log for more details.');
        }

        return $file;
    }

    public function getTargetDisk(): string
    {
        $useMock = Config::get('filesystems.disks.s3.mock', false);

        return $useMock ? self::MOCK : 's3';
    }
}
