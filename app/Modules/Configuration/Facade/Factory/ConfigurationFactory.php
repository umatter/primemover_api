<?php

namespace App\Modules\Configuration\Facade\Factory;

use App\Modules\Configuration\Model\Configuration;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Configuration create(array $attributes = [])
 */
class ConfigurationFactory extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Modules\Configuration\Factory\ConfigurationFactory::class;
    }
}
