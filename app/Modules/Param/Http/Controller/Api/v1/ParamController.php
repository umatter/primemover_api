<?php

namespace App\Modules\Param\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Param\Model\Param;
use App\Modules\Param\Repository\ParamRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ParamController extends Controller
{
    public function __construct(protected ParamRepository $repository)
    {
    }

    public function index(): ResourceCollection
    {
        $params = $this->repository->getData();

        return ResourceCollection::make($params);
    }

    public function store(Request $request): JsonResource
    {
        $param = $this->repository->save($request->all());

        return JsonResource::make($param);
    }

    public function show($id): JsonResource
    {
        $param = $this->repository->getById($id);

        return JsonResource::make($param);
    }

    public function edit(Param $param): JsonResource
    {
        return JsonResource::make($param);
    }

    public function update(Request $request, Param $param): JsonResource
    {
        return JsonResource::make($this->repository->update($request->all(), $param));
    }

    public function destroy(Param $param): ResourceCollection
    {
        return ResourceCollection::make($this->repository->delete($param));
    }
}
