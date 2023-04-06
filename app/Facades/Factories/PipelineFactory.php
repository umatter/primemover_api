<?php

namespace App\Facades\Factories;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Pipeline create()
 *
 * @see \App\Factories\PipelineFactory
 */
class PipelineFactory extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Factories\PipelineFactory::class;
    }
}
