<?php

namespace App\Facades\Services;

use App\Models\DTO\Preference as PreferenceDto;
use App\Models\Preference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void createManyForModel(Collection $data, Model $model)
 * @method static Preference create(PreferenceDto $preferenceData)
 */
class PreferenceService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\PreferenceService::class;
    }
}
