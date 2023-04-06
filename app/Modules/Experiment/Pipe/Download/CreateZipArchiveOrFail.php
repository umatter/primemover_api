<?php

namespace App\Modules\Experiment\Pipe\Download;

use App\Modules\Experiment\Model\DownloadData;
use Closure;
use Exception;

class CreateZipArchiveOrFail
{
    /**
     * @throws Exception
     */
    public function handle(DownloadData $passable, Closure $next): mixed
    {

        return $next($passable);
    }
}
