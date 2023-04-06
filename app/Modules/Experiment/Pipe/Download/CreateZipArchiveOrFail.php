<?php

namespace App\Modules\Experiment\Pipe\Download;

use App\Facades\Factories\ZipFactory;
use App\Modules\Experiment\Model\DTO\DownloadData;
use Closure;
use Exception;
use ZipArchive;

class CreateZipArchiveOrFail
{
    /**
     * @throws Exception
     */
    public function handle(DownloadData $passable, Closure $next): mixed
    {
        $zipArchive = ZipFactory::create();

        if (false === $zipArchive->open($passable->getZipFilePath(), ZipArchive::CREATE)) {
            throw new Exception('Unable to create ZIP archive');
        }

        return $next($passable->setZipArchive($zipArchive));
    }
}
