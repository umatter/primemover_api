<?php

namespace App\Modules\Queue\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Queue\Http\Request\RunnerQueueUpdateRequest;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Repository\QueueRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class RunnerQueueController extends Controller
{
    public function __construct(protected QueueRepository $repository)
    {
    }

    public function update(RunnerQueueUpdateRequest $request, Queue $queue): JsonResource
    {
        return JsonResource::make($this->repository->update($request->validated(), $queue));
    }
}
