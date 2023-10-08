<?php

namespace App\Modules\S3\Service;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class S3Service
{
    public const MOCK = 'local';

    public function downloadFile(string $filename): \Illuminate\Http\Response
    {
        $targetDisk = $this->getTargetDisk();

        if ( ! Storage::disk($targetDisk)->exists($filename)) {
            Log::error('1682672839: File not found', [
                'filename' => $filename,
                'disk' => $targetDisk,
            ]);

            return Response::noContent(404);
        }

        $file = Storage::disk($targetDisk)->get($filename);

        Log::info('1682672850: File download processing', [
            'filename' => $filename,
            'disk' => $targetDisk,
        ]);

        return Response::make($file, 200, [
            'Content-Type' => Storage::disk($targetDisk)->mimeType($filename),
            'Content-Disposition' => 'attachment; filename="'.basename($filename).'"',
        ]);
    }

    public function getTargetDisk(): string
    {
        $useMock = Config::get('filesystems.disks.s3.mock', false);

        return $useMock ? self::MOCK : 's3';
    }
}
