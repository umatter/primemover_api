<?php

namespace App\Modules\Experiment\Pipe\Download;

use App\Modules\Experiment\Model\DTO\DownloadData;
use App\Modules\S3\Facade\S3Service;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StoreCreatedZipArchive
{
    public function handle(DownloadData $passable, Closure $next): mixed
    {
        $zipArchive = Storage::path($passable->getFinalArchiveName());

        $putFile = Storage::disk(S3Service::getTargetDisk())
            ->put($passable->getPathUploadS3(), file_get_contents($zipArchive));

        Log::debug('1682678476: File info', [
            'success' => $putFile,
            'disk' => S3Service::getTargetDisk(),
            'check' => Storage::disk(S3Service::getTargetDisk())->exists($passable->getPathUploadS3()),
        ]);

        return $next($passable);
    }
}
