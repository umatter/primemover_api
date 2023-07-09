<?php

namespace App\Modules\Crawler\Pipe\Upload;

use App\Modules\Experiment\Model\DTO\BehaviorDto;
use App\Modules\Experiment\Model\DTO\CrawlerDto;
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
            $queue = Queue::create([
                'name' => $queueDto->getName(),
                "description" => $queueDto->getDescription(),
                "start_at" => $queueDto->getStartAt(),
                "active" => $queueDto->getActive(),
                "crawler_id" => $queueDto->getCrawler()->id,
                "user_id" => Auth::id()
            ]);

            $queueDto
                ->setUserId(Auth::id())
                ->setCrawlerQueue($queue)
                ->getJobs();

            $result = Collection::make($queueDto->getJobs())->map(function (JobDto $dto) use ($queueDto): void {
                $job = $queueDto->getCrawlerQueue()
                    ->jobs()
                    ->create([
                    'name' => $dto->getName(),
                    'description' => $dto->getDescription(),
                    'type' => $dto->getType(),
                    'user_id' => $queueDto->getUserId(),
                ]);
                $this->createBehaviors($job, $dto, $queueDto);
                Log::info('1682672566: Queue job created', compact('job'));
            });

            Log::info('Queue jobs created', compact('queueDto', 'result'));

        } catch (Throwable $e) {
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
                $behaviors = $job->behaviors()->create([
                    'name' => $dto->getName(),
                    'description' => $dto->getDescription(),
                    'value' => $dto->getValue(),
                    'user_id' => $queueDto->getUserId(),
                ]);
                Log::info('1682672577: Job behaviors created', compact('behaviors'));
            });
        } catch (Throwable $e) {
            throw new Exception('Job behavior could not be created', 422, $e);
        }
    }
}
