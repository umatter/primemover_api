<?php

namespace App\Facades\Factories;

use Illuminate\Support\Facades\Facade;
use MichaelRubel\EnhancedPipeline\Pipeline;

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
