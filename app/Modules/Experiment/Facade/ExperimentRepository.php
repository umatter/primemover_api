<?php

namespace App\Modules\Experiment\Facade;

use App\Modules\Experiment\Http\Request\ExperimentUploadRequest;
use App\Modules\Experiment\Model\Experiment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Facade;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @method static Response download(Experiment $experiment)
 * @method static void upload(ExperimentUploadRequest $request, Experiment $experiment)
 * @method static QueryBuilder getData()
 * @method static Collection all()
 * @method static Experiment getById($experiment)
 * @method static Experiment save($data)
 * @method static Experiment update($data, Experiment $experiment)
 * @method static bool delete(Experiment $experiment)
 * @method static bool forceDelete(Experiment $experiment)
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
