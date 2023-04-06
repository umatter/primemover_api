<?php

namespace App\Modules\S3\Service;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class S3Service
{
    public function downloadFile(string $filename): \Illuminate\Http\Response
    {
        if ( ! Storage::disk('s3')->exists($filename)) {
            return Response::noContent(404);
        }

        $file = Storage::disk('s3')->get($filename);

        return Response::make($file, 200, [
            'Content-Type' => Storage::disk('s3')->mimeType($filename),
            'Content-Disposition' => 'attachment; filename="'.basename($filename).'"',
        ]);
    }
}
