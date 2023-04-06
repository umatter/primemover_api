<?php

namespace App\Modules\Experiment\Pipe\Download;

use App\Modules\Experiment\Model\DTO\DownloadData;
use Closure;

class CleanUpTempFilesAndFolders
{
    public function handle(DownloadData $passable, Closure $next): mixed
    {
        $folderPath = $passable->getFolderPath();

        array_map('unlink', glob("{$folderPath}/*.*"));
        rmdir($folderPath);

        return $next($passable);
    }
}
