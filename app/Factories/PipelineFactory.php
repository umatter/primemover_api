<?php

namespace App\Factories;

use Illuminate\Support\Facades\App;
use MichaelRubel\EnhancedPipeline\Pipeline;

class PipelineFactory
{
    public function create(): Pipeline
    {
        return App::make(Pipeline::class);
    }
}
