<?php

namespace App\Facades\Services;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed getConfigOrFail(string $key, bool $canBeEmpty = false)
 */
class ConfigService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\ConfigService::class;
    }
}
