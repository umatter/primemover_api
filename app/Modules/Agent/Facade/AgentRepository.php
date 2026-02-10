<?php

namespace App\Modules\Agent\Facade;

use App\Modules\Agent\Model\Agent;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Facade;

/**
 * @method static LengthAwarePaginator all(?int $perPage = null)
 * @method static Agent                getById(int $id)
 * @method static Agent                save(array $data)
 * @method static Agent                update(array $data, Agent $agent)
 * @method static array                delete(Agent $agent)
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
