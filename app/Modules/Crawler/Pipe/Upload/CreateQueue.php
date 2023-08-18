<?php

namespace App\Modules\Crawler\Pipe\Upload;

use App\Modules\Experiment\Model\DTO\BehaviorDto;
use App\Modules\Experiment\Model\DTO\JobDto;
use App\Modules\Experiment\Model\DTO\QueueDto;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateQueue
{
    /**
     * @throws Exception
     */
    public function handle(QueueDto $queueDto, Closure $next): mixed
    {
        try {

            $queue = Queue::query()->forceCreateQuietly([
                'name' => $queueDto->getName(),
                'description' => $queueDto->getDescription(),
                'start_at' => $queueDto->getStartAt(),
                'active' => $queueDto->getActive(),
                'crawler_id' => $queueDto->getCrawler()->id,
                'user_id' => Auth::id(),
            ]);

            $queueDto
                ->setUserId(Auth::id())
                ->setCrawlerQueue($queue)
                ->getJobs();

            $result = Collection::make($queueDto->getJobs())->map(function (JobDto $dto) use ($queueDto): void {
                $job = $queueDto->getCrawlerQueue()
                    ->jobs()
                    ->forceCreateQuietly([
                        'name' => $dto->getName(),
                        'description' => $dto->getDescription(),
                        'type' => $dto->getType(),
                        'user_id' => $queueDto->getUserId(),
                    ]);
                $this->createBehaviors($job, $dto, $queueDto);
            });

            Log::info('1692374261: Queue jobs created', ['count' => $result->count()]);

        } catch (Throwable $e) {
            Log::error('1692374264: Queue job could not be created', ['exception' => $e->getMessage()]);

            throw new Exception('Queue job could not be created', 422, $e);
        }

        return $next($queueDto);
    }

    /**
     * @throws Exception
     */
    private function createBehaviors(Job|Model $job, JobDto $dto, QueueDto $queueDto): void
    {
        try {
            Collection::make($dto->getBehaviors())->map(function (BehaviorDto $dto) use ($queueDto, $job): void {
                $job->behaviors()->forceCreateQuietly([
                    'name' => $dto->getName(),
                    'description' => $dto->getDescription(),
                    'value' => $dto->getValue(),
                    'user_id' => $queueDto->getUserId(),
                ]);
            });
        } catch (Throwable $e) {
            Log::error('1692374266: Job behavior could not be created', ['exception' => $e->getMessage()]);

            throw new Exception('Job behavior could not be created', 422, $e);
        }
    }
}
