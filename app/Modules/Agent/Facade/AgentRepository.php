<?php

namespace App\Modules\Agent\Facade;

use App\Modules\Agent\Model\Agent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @method static QueryBuilder getData()
 * @method static array        additional(Builder|Collection $agents)
 * @method static Agent        getById(int $id)
 * @method static Agent        save(array $data)
 * @method static Agent        update(array $data, Agent $agent)
 * @method static array        delete(Agent $agent)
 *
 * @see \App\Modules\Agent\Repository\AgentRepository
 */
class AgentRepository extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Modules\Agent\Repository\AgentRepository::class;
    }
}
