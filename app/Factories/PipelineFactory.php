<?php

namespace App\Factories;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\App;

class PipelineFactory
{
    public function create(): Pipeline
    {
        return App::make(Pipeline::class);
    }
}
