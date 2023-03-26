<?php

namespace App\Modules\Outlet\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Outlet\Http\Service\OutletService;
use App\Modules\Outlet\Model\Outlet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OutletController extends Controller
{
    protected $outletService;

    public function __construct(OutletService $outletService)
    {
        $this->outletService = $outletService;
    }

    public function index(): ResourceCollection
    {
        $outlets = $this->outletService->getData();

        return ResourceCollection::make($outlets);
    }

    public function store(Request $request): JsonResource
    {
        $outlet = $this->outletService->save($request->all());

        return JsonResource::make($outlet);
    }

    public function show($id): JsonResource
    {
        $outlet = $this->outletService->getById($id);

        return JsonResource::make($outlet);
    }

    public function edit(Outlet $outlet): JsonResource
    {
        return JsonResource::make($outlet);
    }

    public function update(Request $request, Outlet $outlet): JsonResource
    {
        return JsonResource::make($this->outletService->update($request->all(), $outlet));
    }

    public function destroy(Outlet $outlet): ResourceCollection
    {
        return ResourceCollection::make($this->outletService->delete($outlet));
    }
}
