<?php

namespace App\Modules\Experiment\Pipe\Upload;

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

class CreateCrawlerQueues
{
    /**
     * @throws Exception
     */
    public function handle(CrawlerDto $crawlerDto, Closure $next): mixed
    {
        try {
            $queues = $crawlerDto
                ->setUserId(Auth::id())
                ->getQueues();

            $result = Collection::make($queues)->map(function (QueueDto $dto) use ($crawlerDto): void {
                $queue = $crawlerDto->getExperimentCrawler()
                    ->queues()
                    ->create([
                        'name' => $dto->getName(),
                        'description' => $dto->getDescription(),
                        'start_at' => $dto->getStartAt(),
                        'user_id' => $crawlerDto->getUserId(),
                    ]);
                $this->createJobs($queue, $dto, $crawlerDto);
                Log::info('1682672555: Crawler queue created', compact('queue'));
            });

            Log::info('Crawler queues created', compact('crawlerDto', 'result'));

        } catch (Throwable $e) {
            throw new Exception('Crawler queue could not be created', 422, $e);
        }

        return $next($crawlerDto);
    }

    /**
     * @throws Exception
     */
    private function createJobs(Queue|Model $queue, QueueDto $dto, CrawlerDto $crawlerDto): void
    {
        try {
            Collection::make($dto->getJobs())->map(function (JobDto $dto) use ($crawlerDto, $queue): void {
                $job = $queue->jobs()->create([
                    'name' => $dto->getName(),
                    'description' => $dto->getDescription(),
                    'type' => $dto->getType(),
                    'user_id' => $crawlerDto->getUserId(),
                ]);
                $this->createBehaviors($job, $dto, $crawlerDto);
                Log::info('1682672566: Crawler job created', compact('job'));

            });
        } catch (Throwable $e) {
            throw new Exception('Crawler job could not be created', 422, $e);
        }

    }

    /**
     * @throws Exception
     */
    private function createBehaviors(Job|Model $job, JobDto $dto, CrawlerDto $crawlerDto): void
    {
        try {
            Collection::make($dto->getBehaviors())->map(function (BehaviorDto $dto) use ($crawlerDto, $job): void {
                $behaviors = $job->behaviors()->create([
                    'name' => $dto->getName(),
                    'description' => $dto->getDescription(),
                    'value' => $dto->getValue(),
                    'user_id' => $crawlerDto->getUserId(),
                ]);
                Log::info('1682672577: Job behaviors created', compact('behaviors'));
            });
        } catch (Throwable $e) {
            throw new Exception('Job behavior could not be created', 422, $e);
        }
    }
}
