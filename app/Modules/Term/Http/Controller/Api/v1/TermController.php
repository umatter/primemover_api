<?php

namespace App\Modules\Term\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Term\Service\TermService;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TermController extends Controller
{
    protected $termService;

    public function __construct(TermService $termService)
    {
        $this->termService = $termService;
    }

    public function index(): ResourceCollection
    {
        $terms = $this->termService->getData();

        return ResourceCollection::make($terms);
    }
}
