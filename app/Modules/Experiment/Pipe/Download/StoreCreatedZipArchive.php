<?php

namespace App\Modules\Experiment\Pipe\Download;

use App\Modules\Experiment\Model\DTO\DownloadData;
use App\Modules\S3\Facade\S3Service;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
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
            $disk = S3Service::getTargetDisk();

            $putFile = Storage::disk($disk)
                ->put($passable->getPathUploadS3(), file_get_contents($zipArchive));

            Log::debug('1682678476: File info', [
                'success' => $putFile,
            ]);

            /*
             * put() returns FALSE on a disk it cannot write to; it does not
             * throw. This result was logged and then ignored, so a failed write
             * carried on through the rest of the pipeline and surfaced two steps
             * later as "File not found" from the read-back in
             * ExperimentService::download() -- an error about the wrong half of
             * the operation, naming neither the disk nor the write.
             *
             * That is what GET /experiments/{id}/download has been answering.
             * Failing here instead says which disk, and that the write is the
             * problem.
             */
            if (false === $putFile) {
                throw new RuntimeException(sprintf(
                    'Could not write the export archive to the "%s" disk (path: %s). '
                    .'The download reads this file back immediately, so it cannot continue. '
                    .'Check the disk has an endpoint and a real bucket name -- on this '
                    .'deployment AWS_BUCKET holds an ARN and there is no AWS account behind it.',
                    $disk,
                    $passable->getPathUploadS3()
                ));
            }
        } catch (Throwable $e) {
            Log::error('1682678937: Failed to store archive', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $next($passable);
    }
}
