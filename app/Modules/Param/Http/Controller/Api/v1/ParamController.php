<?php

namespace App\Modules\Param\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Param\Http\Service\ParamService;
use App\Modules\Param\Model\Param;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ParamController extends Controller
{
    protected $paramService;

    public function __construct(ParamService $paramService)
    {
        $this->paramService = $paramService;
    }

    public function index(): ResourceCollection
    {
        $params = $this->paramService->getData();

        return ResourceCollection::make($params);
    }

    public function store(Request $request): JsonResource
    {
        $param = $this->paramService->save($request->all());

        return JsonResource::make($param);
    }

    public function show($id): JsonResource
    {
        $param = $this->paramService->getById($id);

        return JsonResource::make($param);
    }

    public function edit(Param $param): JsonResource
    {
        return JsonResource::make($param);
    }

    public function update(Request $request, Param $param): JsonResource
    {
        return JsonResource::make($this->paramService->update($request->all(), $param));
    }

    public function destroy(Param $param): ResourceCollection
    {
        return ResourceCollection::make($this->paramService->delete($param));
    }
}
