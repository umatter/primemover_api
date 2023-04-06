<?php

namespace App\Modules\Experiment\Pipe\Download;

use App\Facades\Factories\ZipFactory;
use App\Modules\Experiment\Model\DownloadData;
use App\Modules\S3\Facade\S3Service;
use Closure;
use Exception;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class CreateZipArchive
{
    /**
     * @throws Exception
     */
    public function handle(DownloadData $passable, Closure $next): mixed
    {
        $experiment = $passable->getExperiment();
        $folderPath = $passable->getFolderPath();
        $zipArchive = ZipFactory::create();

        if (false === $zipArchive->open($passable->getZipFilePath(), ZipArchive::CREATE)) {
            throw new Exception('Unable to create ZIP archive');
        }

        $this->addFolderToZip($folderPath, $zipArchive, $experiment->id);
        $this->cleanUpTemp($folderPath);
        $this->storeFile($passable);

        $passable->setResponse(
            S3Service::downloadFile($passable->getPathUploadS3())
        );

        return $next($passable);
    }

    private function storeFile(DownloadData $passable): void
    {
        $zipArchive = Storage::path($passable->getFinalArchiveName());
        Storage::disk('s3')->put($passable->getPathUploadS3(), file_get_contents($zipArchive));
    }

    private function cleanUpTemp($folderPath): void
    {
        array_map('unlink', glob("{$folderPath}/*.*"));
        rmdir($folderPath);
    }

    private function addFolderToZip($folderPath, $zip, $experimentId): void
    {
        $folderPath = rtrim($folderPath, '/').'/';
        $files = glob($folderPath.'*');

        foreach ($files as $file) {
            $filename = basename($file);
            $s = explode('_', $filename);
            if (is_file($file) && intval($s[0]) === $experimentId) {
                $zip->addFile($file, $filename);
            }
        }
        $zip->close();
    }
}
