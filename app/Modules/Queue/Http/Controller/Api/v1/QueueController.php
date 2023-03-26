<?php

namespace App\Modules\Queue\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Queue\Http\Request\StoreQueueRequest;
use App\Modules\Queue\Http\Request\UpdateQueueRequest;
use App\Modules\Queue\Http\Service\QueueService;
use App\Modules\Queue\Model\Queue;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class QueueController extends Controller
{
    protected QueueService $queueService;
    
    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    public function index(Request $request): ResourceCollection
    {
        $crawlerId = $request->input('crawler');
        $sortBy = $request->input('sortBy');
        $queues = $this->queueService->getData($crawlerId, $sortBy);

        return ResourceCollection::make($queues);
    }

    public function store(StoreQueueRequest $request): JsonResource
    {
        $queue = $this->queueService->save($request->validated());

        return JsonResource::make($queue);
    }

    public function show(Queue $queue): JsonResource
    {
        return JsonResource::make($queue->load(['jobs']));
    }

    public function job($id): JsonResource
    {
        $queue = $this->queueService->job($id);

        return JsonResource::make($queue);
    }

    public function processed(): ResourceCollection
    {
        $queue = $this->queueService->processed();

        return ResourceCollection::make($queue);
    }

    public function unprocessed(): ResourceCollection
    {
        $queue = $this->queueService->unprocessed();

        return ResourceCollection::make($queue);
    }

    public function reviewed(): ResourceCollection
    {
        $queue = $this->queueService->reviewed();

        return ResourceCollection::make($queue);
    }

    public function unreviewed(): ResourceCollection
    {
        $queue = $this->queueService->unreviewed();

        return ResourceCollection::make($queue);
    }

    public function experimentUnreviewed(Experiment $experiment): ResourceCollection
    {
        $queue = $this->queueService->experimentUnreviewed($experiment);

        return ResourceCollection::make($queue);
    }

    public function experimentReviewed(Experiment $experiment): ResourceCollection
    {
        $queue = $this->queueService->experimentReviewed($experiment);

        return ResourceCollection::make($queue);
    }

    public function experimentUnprocessed(Experiment $experiment): ResourceCollection
    {
        $queue = $this->queueService->experimentUnprocessed($experiment);

        return ResourceCollection::make($queue);
    }

    public function experimentProcessed(Experiment $experiment): ResourceCollection
    {
        $queue = $this->queueService->experimentProcessed($experiment);

        return ResourceCollection::make($queue);
    }

    public function edit(Queue $queue): JsonResource
    {
        return JsonResource::make($queue);
    }

    public function update(UpdateQueueRequest $request, Queue $queue): JsonResource
    {
        return JsonResource::make($this->queueService->update($request->validated(), $queue));
    }

    /**
     * @throws Exception
     */
    public function destroy(Queue $queue): ResourceCollection
    {
        return ResourceCollection::make($this->queueService->delete($queue));
    }
}
