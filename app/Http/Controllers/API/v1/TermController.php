<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Term;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TermController extends Controller
{
    public function index(): ResourceCollection
    {
        $terms = Term::query()
            ->latest()
            ->paginate(10);

        return ResourceCollection::make($terms);
    }
}
