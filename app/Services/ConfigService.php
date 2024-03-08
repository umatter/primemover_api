<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ConfigService
{
    /**
     * @throws Exception
     */
    public function getConfigOrFail(string $key, bool $canBeEmpty = false)
    {
        if ( ! $key) {
            Log::error('1624255131: Config key must not be empty');

            throw new Exception('Config key must not be empty');
        }

        $configValue = Config::get($key);

        if ($canBeEmpty) {
            return $configValue;
        }

        if ( ! $configValue) {
            Log::alert('1624255138: Config is not set or empty', ['key' => $key]);

            throw new Exception('Config is not set or empty');
        }

        return $configValue;
    }
}
