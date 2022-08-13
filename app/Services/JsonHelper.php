<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class JsonHelper
{

    public function jsonToArray(?string $jsonString = '', bool $associative = true): object|array|null
    {
        try {
            return json_decode($jsonString, $associative, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            Log::alert('1649254283: Failed to decode json', [
                'jsonString' => $jsonString,
                'exception' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function encodeJson(mixed $data): ?string
    {
        if (!$data) {
            return null;
        }

        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            Log::alert('1649254291: Failed to encode json', [
                'data' => $data,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
