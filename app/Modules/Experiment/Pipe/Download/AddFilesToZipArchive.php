<?php

namespace App\Modules\Experiment\Pipe\Download;

use App\Modules\Experiment\Model\DTO\DownloadData;
use Closure;
use Illuminate\Support\Facades\Log;

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
            Log::debug('1682678765: Add file to archive', ['file' => $file]);
        }

        $zipArchive->close();

        return $next($passable);
    }
}
