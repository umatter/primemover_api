<?php

namespace App\Modules\Configuration\Factory;

use App\Modules\Configuration\Model\Configuration;
use Illuminate\Support\Facades\App;

class ConfigurationFactory
{
    public function create(array $attributes = []): Configuration
    {
        return App::make(Configuration::class, [
            'attributes' => $attributes,
        ]);
    }
}
