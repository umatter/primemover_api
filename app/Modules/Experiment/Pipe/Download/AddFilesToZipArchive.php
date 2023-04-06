<?php

namespace App\Modules\Experiment\Pipe\Download;

use App\Modules\Experiment\Model\DTO\DownloadData;
use Closure;

class AddFilesToZipArchive
{
    public function handle(DownloadData $passable, Closure $next): mixed
    {

        $experiment = $passable->getExperiment();
        $folderPath = $passable->getFolderPath();
        $zipArchive = $passable->getZipArchive();

        $folderPath = rtrim($folderPath, '/').'/';
        $files = glob($folderPath.'*');

        foreach ($files as $file) {
            $filename = basename($file);
            $s = explode('_', $filename);
            if (is_file($file) && intval($s[0]) === $experiment->id) {
                $zipArchive->addFile($file, $filename);
            }
        }

        $zipArchive->close();

        return $next($passable);
    }
}
