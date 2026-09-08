<?php

namespace App\Modules\Experiment\Repository;

use App\Facades\Services\DataMapper;
use App\Facades\Services\PreferenceService;
use App\Modules\Experiment\Facade\ExperimentService;
use App\Modules\Experiment\Http\Request\ExperimentUploadRequest;
use App\Modules\Experiment\Model\DTO\CrawlerDto;
use App\Modules\Experiment\Model\DTO\DownloadData;
use App\Modules\Experiment\Model\Experiment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class ExperimentRepository
{
    public function __construct(protected Experiment $experiment) {}

    public function getData(): QueryBuilder
    {
        return Experiment::queryBuilder()->visibleTo();
    }

    public function all(): Collection
    {
        return $this->experiment->query()
            ->visibleTo()
            ->select(['id', 'name'])
            ->get();
    }

    public function getById($experiment): Experiment
    {
        // loadRunsBadCount() rather than an accessor: route-model binding has
        // already resolved the row, so the rollup the list gets as a subselect
        // has to be fetched here -- one extra query, not one per crawler.
        return $experiment
            ->loadRunsBadCount()
            ->load([
                'crawlers.agent',
                'crawlers.proxy',
                'crawlers.configuration',
                'crawlers.configuration.preferences',
                'preferences',
            ]);
    }

    public function save($data): Experiment|Model
    {
        $experiment = $this->experiment->query()->create($data->validated());

        if ($data->getPreferences()) {
            PreferenceService::createManyForModel($data->getPreferences(), $experiment);
        }

        return $experiment;
    }

    public function update($data, Experiment $experiment): Experiment
    {
        $experiment->update($data->validated());

        if ($data->getPreferences()) {
            PreferenceService::createManyForModel($data->getPreferences(), $experiment);
        }

        return $experiment->fresh();
    }

    public function delete(Experiment $experiment): bool
    {
        return $experiment->delete();
    }

    public function forceDelete(Experiment $experiment): bool
    {
        $experiment->crawlers()->each(function ($crawler): void {
            $crawler->proxy()->delete();
            $crawler->agent()->delete();
            $crawler->configuration()->delete();
            $crawler->queues()->delete();
        });
        $experiment->crawlers()->delete();

        return $experiment->delete();
    }

    /**
     * @throws Throwable
     * @throws ValidationException
     */
    public function upload(ExperimentUploadRequest $request, Experiment $experiment): \Illuminate\Support\Collection
    {
        DB::beginTransaction();

        try {
            $result = DataMapper::disableEnforceType()
                ->removeUndefinedAttributes()
                ->mapList($request->getJsonContent(), CrawlerDto::class)
                ->map(fn (CrawlerDto $crawlerDto) => ExperimentService::upload($experiment, $crawlerDto));
            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();

            throw ValidationException::withMessages([
                'upload' => $exception->getMessage(),
            ]);
        }

        return $result;
    }

    public function download(Experiment $experiment): Response
    {
        $passable = DownloadData::from(compact('experiment'));

        return ExperimentService::download($passable);
    }
}
