<?php

namespace App\Modules\Queue\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Queue\Http\Request\StoreQueueRequest;
use App\Modules\Queue\Http\Request\UpdateQueueRequest;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Repository\QueueRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class QueueController extends Controller
{
    public function __construct(protected QueueRepository $repository)
    {
    }

    public function index(Request $request): ResourceCollection
    {
        $crawlerId = $request->input('crawler');
        $sortBy = $request->input('sortBy');
        $queues = $this->repository->getData($crawlerId, $sortBy);

        return ResourceCollection::make($queues);
    }

    public function store(StoreQueueRequest $request): JsonResource
    {
        $queue = $this->repository->save($request->validated());

        return JsonResource::make($queue);
    }

    public function show(Queue $queue): JsonResource
    {
        return JsonResource::make($queue->load(['jobs']));
    }

    public function job($id): JsonResource
    {
        $queue = $this->repository->job($id);

        return JsonResource::make($queue);
    }

    public function processed(): ResourceCollection
    {
        $queue = $this->repository->processed();

        return ResourceCollection::make($queue);
    }

    public function unprocessed(): ResourceCollection
    {
        $queue = $this->repository->unprocessed();

        return ResourceCollection::make($queue);
    }

    public function reviewed(): ResourceCollection
    {
        $queue = $this->repository->reviewed();

        return ResourceCollection::make($queue);
    }

    public function unreviewed(): ResourceCollection
    {
        $queue = $this->repository->unreviewed();

        return ResourceCollection::make($queue);
    }

    public function experimentUnreviewed(Experiment $experiment): ResourceCollection
    {
        $queue = $this->repository->experimentUnreviewed($experiment);

        return ResourceCollection::make($queue);
    }

    public function experimentReviewed(Experiment $experiment): ResourceCollection
    {
        $queue = $this->repository->experimentReviewed($experiment);

        return ResourceCollection::make($queue);
    }

    public function experimentUnprocessed(Experiment $experiment): ResourceCollection
    {
        $queue = $this->repository->experimentUnprocessed($experiment);

        return ResourceCollection::make($queue);
    }

    public function experimentProcessed(Experiment $experiment): ResourceCollection
    {
        $queue = $this->repository->experimentProcessed($experiment);

        return ResourceCollection::make($queue);
    }

    public function edit(Queue $queue): JsonResource
    {
        return JsonResource::make($queue);
    }

    public function update(UpdateQueueRequest $request, Queue $queue): JsonResource
    {
        return JsonResource::make($this->repository->update($request->validated(), $queue));
    }

    /**
     * @throws Exception
     */
    public function destroy(Queue $queue): ResourceCollection
    {
        return ResourceCollection::make($this->repository->delete($queue));
    }
}
