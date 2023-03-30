<?php

namespace App\Modules\Behavior\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Behavior\Model\Behavior;
use App\Modules\Behavior\Service\BehaviorService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BehaviorController extends Controller
{
    public function __construct(protected BehaviorService $behaviorService)
    {
    }

    public function index(): ResourceCollection
    {
        $behaviors = $this->behaviorService->getData();

        return ResourceCollection::make($behaviors);
    }

    public function store(Request $request): JsonResource
    {
        $data = $request->all();

        $behavior = $this->behaviorService->save($data);

        return JsonResource::make($behavior);
    }

    public function show($id): JsonResource
    {
        $behavior = $this->behaviorService->getById($id);

        return JsonResource::make($behavior);
    }

    public function edit(Behavior $behavior): JsonResource
    {
        return JsonResource::make($behavior);
    }

    public function update(Request $request, Behavior $behavior): JsonResource
    {
        $behavior = $this->behaviorService->update($request->all(), $behavior);

        return JsonResource::make($behavior);
    }

    /**
     * @throws Exception
     */
    public function destroy(Behavior $behavior): ResourceCollection
    {
        $this->behaviorService->delete($behavior);

        return ResourceCollection::make(Behavior::all());
    }
}
