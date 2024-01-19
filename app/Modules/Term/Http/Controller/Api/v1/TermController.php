<?php

namespace App\Modules\Term\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Term\Repository\TermRepository;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TermController extends Controller
{
    public function __construct(protected TermRepository $repository)
    {
    }

    public function index(): ResourceCollection
    {
        $terms = $this->repository->all();

        return ResourceCollection::make($terms);
    }
}
