<?php

namespace App\Modules\Experiment\Pipe\Download;

use App\Modules\Experiment\Model\DTO\DownloadData;
use App\Modules\S3\Facade\S3Service;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class StoreCreatedZipArchive
{
    /**
     * @throws Throwable
     */
    public function handle(DownloadData $passable, Closure $next): mixed
    {
        $zipArchive = Storage::path($passable->getFinalArchiveName());

        Log::debug('1682678478: Storage info', [
            'disk' => S3Service::getTargetDisk(),
            'path' => $passable->getPathUploadS3(),
            'archive' => $zipArchive,
        ]);

        try {
            $putFile = Storage::disk(S3Service::getTargetDisk())
                ->put($passable->getPathUploadS3(), file_get_contents($zipArchive));

            Log::debug('1682678476: File info', [
                'success' => $putFile,
                'check' => Storage::disk(S3Service::getTargetDisk())->exists($passable->getPathUploadS3()),
            ]);
        } catch (Throwable $e) {
            Log::error('1682678937: Failed to store archive', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $next($passable);
    }
}
