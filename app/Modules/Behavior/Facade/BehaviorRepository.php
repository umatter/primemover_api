<?php

namespace App\Modules\Behavior\Facade;

use App\Modules\Behavior\Model\Behavior;
use Illuminate\Support\Facades\Facade;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @method static QueryBuilder getData()
 * @method static Behavior     getById(int $id)
 * @method static Behavior     save(array $data)
 * @method static Behavior     update(array $data, Behavior $agent)
 * @method static array        delete(Behavior $agent)
 *
 * @see \App\Modules\Behavior\Repository\BehaviorRepository
 */
class BehaviorRepository extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Modules\Behavior\Repository\BehaviorRepository::class;
    }
}
