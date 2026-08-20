<?php

namespace App\Modules\Crawler\Repository;

use App\Facades\Factories\PipelineFactory;
use App\Facades\Services\DataMapper;
use App\Modules\Crawler\Http\Request\CrawlerUploadRequest;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Pipe\Upload\CreateQueue;
use App\Modules\Experiment\Model\DTO\QueueDto;
use Arimolzer\IPStackFinder\Facade\IPStackFinderFacade;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tests\Feature\Modules\Crawler\Repository\CrawlerRepositoryTest;

/**
 * @see CrawlerRepositoryTest
 */
class CrawlerRepository
{
    public function __construct(protected Crawler $crawler) {}

    public function all(?int $perPage = null): LengthAwarePaginator
    {
        return Crawler::queryBuilder()
            ->visibleTo()
            ->where('testing', 0)
            ->where('active', 1)
            ->with(['experiment'])
            ->fastPaginate($perPage ?? request()->input('per_page', 15));
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
        return $this->crawler->query()->visibleTo()
            ->where('testing', 0)
            ->where('active', 0)
            ->with(
                [
                    'queues',
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )->get();
    }

    public function processed(): Collection
    {
        return $this->crawler->query()->visibleTo()->where('testing', 0)
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
            ->get();
    }

    public function unprocessed(): Collection
    {
        return $this->crawler->query()->visibleTo()->where('testing', 0)
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
            ->get();
    }

    public function reviewed(): Collection
    {
        return $this->crawler->query()->visibleTo()->where('testing', 1)
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
            ->get();
    }

    public function testing(): LengthAwarePaginator
    {
        return $this->crawler->query()->visibleTo()->where('testing', 1)
            ->where('active', 1)
            ->with(['queues', 'agent.preferences', 'proxy', 'configuration.preferences'])
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

        return $this->crawler->query()->visibleTo()->where('active', 0)->get();
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
        ])->get();
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
        )->get();
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
        )->get();
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
        )->get();
    }

    /**
     * @throws Exception
     */
    public function upload(CrawlerUploadRequest $request, Crawler $crawler): \Illuminate\Support\Collection
    {
        DB::beginTransaction();
        try {
            $response = DataMapper::disableEnforceType()
                ->removeUndefinedAttributes()
                ->mapList($request->getJsonFileContent(), QueueDto::class)
                ->map(function (QueueDto $queueDto) use ($crawler) {
                    return PipelineFactory::create()
                        ->send($queueDto->setCrawler($crawler))
                        ->through([
                            CreateQueue::class,
                        ])
                        ->thenReturn();
                });

            DB::commit();

            return $response;
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('1725638225: Crawler upload failed', ['message' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);

            throw ValidationException::withMessages([
                'upload' => $exception->getMessage(),
            ]);
        }
    }
}
