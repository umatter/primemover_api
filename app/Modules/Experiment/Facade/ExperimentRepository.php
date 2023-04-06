<?php

namespace App\Modules\Experiment\Facade;

use App\Modules\Experiment\Http\Request\ExperimentUploadRequest;
use App\Modules\Experiment\Model\Experiment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Response download(Experiment $experiment)
 * @method static LengthAwarePaginator getData()
 * @method static Collection all()
 * @method static Experiment getById($experiment)
 * @method static Experiment save($data)
 * @method static Experiment update($data, Experiment $experiment)
 * @method static bool delete(Experiment $experiment)
 * @method static bool forceDelete(Experiment $experiment)
 * @method static Builder|Model upload(ExperimentUploadRequest $request, Experiment $experiment)
 *
 * @see \App\Modules\Experiment\Repository\ExperimentRepository
 */
class ExperimentRepository extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Modules\Experiment\Repository\ExperimentRepository::class;
    }
}
