<?php

namespace App\Modules\Term\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Term\Model\Term;
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
