<?php

namespace App\Modules\Outlet\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Outlet\Model\Outlet;
use App\Modules\Outlet\Repository\OutletRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OutletController extends Controller
{
    public function __construct(protected OutletRepository $repository)
    {
    }

    public function index(): ResourceCollection
    {
        $outlets = $this->repository->getData();

        return ResourceCollection::make($outlets);
    }

    public function store(Request $request): JsonResource
    {
        $outlet = $this->repository->save($request->all());

        return JsonResource::make($outlet);
    }

    public function show($id): JsonResource
    {
        $outlet = $this->repository->getById($id);

        return JsonResource::make($outlet);
    }

    public function edit(Outlet $outlet): JsonResource
    {
        return JsonResource::make($outlet);
    }

    public function update(Request $request, Outlet $outlet): JsonResource
    {
        return JsonResource::make($this->repository->update($request->all(), $outlet));
    }

    public function destroy(Outlet $outlet): ResourceCollection
    {
        return ResourceCollection::make($this->repository->delete($outlet));
    }
}
