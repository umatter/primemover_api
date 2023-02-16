<?php

namespace App\Facades\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static object                   map(array $source, string $destinationClass)
 * @method static Collection               mapList(array $sourceList, string $destinationClass)
 * @method static \App\Services\DataMapper disableEnforceType()
 * @method static \App\Services\DataMapper removeUndefinedAttributes()
 */
class DataMapper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\DataMapper::class;
    }
}
