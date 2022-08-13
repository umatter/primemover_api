<?php

namespace App\Facades\Services;

use Illuminate\Support\Facades\Facade;

/**
 * @method static null|array|object jsonToArray(?string $jsonString = '', bool $associative = true)
 * @method static null|string encodeJson($data)
 *
 * @see \App\Services\JsonHelper
 */
class JsonHelper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\JsonHelper::class;
    }
}
