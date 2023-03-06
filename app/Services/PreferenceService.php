<?php

namespace App\Services;

use App\Facades\Factories\PreferenceFactory;
use App\Models\DTO\Preference as PreferenceDto;
use App\Modules\Preference\Model\Preference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PreferenceService
{
    public function create(PreferenceDto $preferenceData): Preference
    {
        return PreferenceFactory::create([
            'user_id' => Auth::id(),
            'name' => $preferenceData->getName(),
            'description' => $preferenceData->getDescription(),
            'value' => $preferenceData->getValue(),
        ]);
    }

    public function createManyForModel(Collection $data, Model $model): void
    {
        $data->map(function (PreferenceDto $preferenceData) use ($model) {
            $this->create($preferenceData)
                ->preferencable()
                ->associate($model)
                ->save();
        });
    }
}
