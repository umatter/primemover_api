<?php

namespace App\Factories;

use Illuminate\Support\Facades\App;
use ZipArchive;

class ZipFactory
{
    public function create(): ZipArchive
    {
        return App::make(ZipArchive::class);
    }
}
