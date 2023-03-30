<?php

namespace App\Modules\Term\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Term\Service\TermService;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TermController extends Controller
{
    public function __construct(protected TermService $termService)
    {
    }

    public function index(): ResourceCollection
    {
        $terms = $this->termService->getData();

        return ResourceCollection::make($terms);
    }
}
