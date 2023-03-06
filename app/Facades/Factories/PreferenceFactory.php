<?php

namespace App\Facades\Factories;

use App\Modules\Preference\Model\Preference;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Preference create(array $attributes = [])
 */
class PreferenceFactory extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Factories\PreferenceFactory::class;
    }
}
