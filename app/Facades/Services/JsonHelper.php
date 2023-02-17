<?php

namespace App\Facades\Services;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array|object|null jsonToArray(?string $jsonString = '', bool $associative = true)
 * @method static string|null       encodeJson($data)
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
