<?php

namespace App\Factories;

use App\Models\Preference;
use Illuminate\Support\Facades\App;

class PreferenceFactory
{
    public function create(array $attributes = []): Preference
    {
        return App::make(Preference::class, [
            'attributes' => $attributes
        ]);
    }
}
