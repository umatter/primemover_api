<?php

namespace App\Modules\S3\Service;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class S3Service
{
    public const MOCK = 'local';

    public function downloadFile(string $filename): \Illuminate\Http\Response
    {
        $targetDisk = $this->getTargetDisk();

        if ( ! Storage::disk($targetDisk)->exists($filename)) {
            return Response::noContent(404);
        }

        $file = Storage::disk($targetDisk)->get($filename);

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
