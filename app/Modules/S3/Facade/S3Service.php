<?php

namespace App\Modules\S3\Facade;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Response downloadFile(string $filename)
 * @method static string   getTargetDisk()
 *
 * @see \App\Modules\S3\Service\S3Service
 */
class S3Service extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Modules\S3\Service\S3Service::class;
    }
}
