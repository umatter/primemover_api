<?php

namespace App\Modules\Experiment\Pipe\Download;

use App\Modules\Experiment\Model\DTO\DownloadData;
use App\Modules\S3\Facade\S3Service;
use Closure;
use Illuminate\Support\Facades\Storage;

class StoreCreatedZipArchive
{
    public function handle(DownloadData $passable, Closure $next): mixed
    {
        $zipArchive = Storage::path($passable->getFinalArchiveName());

        Storage::disk(S3Service::getTargetDisk())
            ->put($passable->getPathUploadS3(), file_get_contents($zipArchive));

        return $next($passable);
    }
}
