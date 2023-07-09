<?php

namespace App\Modules\Crawler\Repository;

use App\Facades\Factories\PipelineFactory;
use App\Facades\Services\DataMapper;
use App\Modules\Crawler\Http\Request\CrawlerUploadRequest;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Pipe\Upload\CreateQueue;
use App\Modules\Experiment\Model\DTO\QueueDto;
use Arimolzer\IPStackFinder\Facade\IPStackFinderFacade;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tests\Feature\Modules\Crawler\Repository\CrawlerRepositoryTest;

/**
 * @see CrawlerRepositoryTest
 */
class CrawlerRepository
{
    public function __construct(protected Crawler $crawler)
    {
    }

    public function getData(): QueryBuilder
    {
        return QueryBuilder::for($this->crawler)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
                AllowedFilter::scope('experiment'),
            ])
            ->where('testing', 0)
            ->where('active', 1)
            ->with(['experiment'])
            ->latest();
    }

    public function getById(Crawler $crawler): Crawler
    {
        $crawler->with(
            [
                'experiment',
                'queues',
                'agent.preferences',
                'configuration.preferences',
                'proxy',
                'user',
            ]
        );

        return $crawler;
    }

    public function inactive(): Collection
    {
        return $this->crawler->query()
            ->where('testing', 0)
            ->where('active', 0)
            ->with(
                [
                    'queues',
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )->latest()->get();
    }

    public function processed(): Collection
    {
        return $this->crawler->query()->where('testing', 0)
            ->where('active', 1)
            ->with(['queues' => function ($q): void {
                $q->where('queues.processed', 1);
            },
            ])
            ->with(
                [
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )
            ->whereHas('queues', function ($q): void {
                $q->where('processed', 1);
            })
            ->latest()
            ->get();
    }

    public function unprocessed(): Collection
    {
        return $this->crawler->query()->where('testing', 0)
            ->where('active', 1)
            ->whereHas('queues', function ($q): void {
                $q->where('processed', 0);
            })
            ->with(['queues' => function ($q): void {
                $q->where('queues.processed', 0);
            },
            ])
            ->with(
                [
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )
            ->latest()
            ->get();
    }

    public function reviewed(): Collection
    {
        return $this->crawler->query()->where('testing', 1)
            ->where('active', 1)
            ->with(['queues' => function ($q): void {
                $q->where('queues.reviewed', 1);
            },
            ])
            ->with(
                [
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )
            ->whereHas('queues', function ($q): void {
                $q->where('reviewed', 1);
            })
            ->latest()
            ->get();
    }

    public function testing(): LengthAwarePaginator
    {
        return $this->crawler->query()->where('testing', 1)
            ->where('active', 1)
            ->with(['queues', 'agent.preferences', 'proxy', 'configuration.preferences'])->latest()
            ->paginate(10);
    }

    public function save(array $data): Crawler|Model
    {
        return $this->crawler->query()->create($data);
    }

    public function update(array $data, Crawler $crawler): Crawler
    {
        $crawler->update($data);

        return $crawler->fresh();
    }

    public function disableProdCrawlers(): Collection
    {
        $this->crawler->query()->where('testing', 0)->update(['active' => 0]);

        return $this->crawler->query()->where('active', 0)->get();
    }

    public function forceDelete(Crawler $crawler): Collection
    {
        $crawler->proxy()->delete();
        $crawler->agent()->delete();
        $crawler->configuration()->delete();
        $crawler->queues()->delete();
        $crawler->delete();

        return $this->crawler->with([
            'experiment',
            'queues',
            'agent.preferences',
            'proxy',
            'configuration.preferences',
        ])->latest()->get();
    }

    public function delete(Crawler $crawler): Collection
    {
        $crawler->delete();

        return $this->crawler->with(
            [
                'experiment',
                'queues',
                'agent.preferences',
                'proxy',
                'configuration.preferences',
            ]
        )->latest()->get();
    }

    public function ip($ip)
    {
        $final = $ip ?: request()->ip();
        if ( ! $this->isLocalAddress($final)) {
            return response()->json([
                'result' => IPStackFinderFacade::get($final),
            ], ResponseAlias::HTTP_OK);
        }

        return $final;
    }

    public function isLocalAddress($ip): bool
    {
        return '::1' === $ip || '127.0.0.1' === $ip;
    }

    public function undo(): Collection
    {
        $this->crawler->query()->where('updated', 1)->delete();

        return $this->crawler->with(
            [
                'experiment',
                'queues',
                'agent.preferences',
                'proxy',
                'configuration.preferences',
                'user',
            ]
        )->latest()->get();
    }

    public function cleanup(): Collection
    {
        $this->crawler->query()->where('testing', 0)->delete();

        return $this->crawler->with(
            [
                'experiment',
                'queues',
                'agent.preferences',
                'proxy',
                'configuration.preferences',
                'user',
            ]
        )->latest()->get();
    }

    public function upload(CrawlerUploadRequest $request, Crawler $crawler)
    {
        return DataMapper::disableEnforceType()
                ->removeUndefinedAttributes()
                ->mapList($request->getJsonFileContent(), QueueDto::class)
                ->map(function (QueueDto $queueDto) use ($crawler) {
                    return PipelineFactory::create()
                            ->send($queueDto->setCrawler($crawler))
                            ->through([
                                CreateQueue::class
                            ])
                            ->then(function (): void {
                                Log::info('1682672742: Queue Upload done');
                            });
                });
    }
}
