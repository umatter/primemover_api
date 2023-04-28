<?php

namespace App\Facades\Factories;

use Illuminate\Support\Facades\Facade;
use ZipArchive;

/**
 * @method static ZipArchive create()
 *
 * @see \App\Factories\ZipFactory
 */
class ZipFactory extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Factories\ZipFactory::class;
    }
}
