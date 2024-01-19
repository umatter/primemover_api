<?php

namespace App\Modules\Proxy\Repository;

use App\Modules\Proxy\Model\Proxy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProxyRepository
{
    public function __construct(protected Proxy $proxy)
    {
    }

    public function all($data): ResourceCollection
    {
        $proxies = QueryBuilder::for(Proxy::class)
            ->allowedSorts(['id', 'name', 'created_at', 'updated_at'])
            ->defaultSort('-id')
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
                AllowedFilter::scope('crawler'),
            ]);

        return ResourceCollection::make($proxies->paginate())
            ->additional([
                'values' => [
                    'name' => $proxies->pluck('name')->unique(),
                    'hostname' => $proxies->pluck('hostname')->unique(),
                    'username' => $proxies->pluck('username')->unique(),
                    'port' => $proxies->pluck('port')->unique(),
                ],
                'count' => $proxies->count(),
            ]);
    }

    public function save(array $data): Proxy|Model
    {
        $data['user_id'] = Auth::id() ?? 1;

        return $this->proxy->query()->create($data);
    }

    public function update(array $data, Proxy $proxy): Proxy
    {
        $proxy->update($data);

        return $proxy->fresh();
    }

    public function delete(Proxy $proxy): Collection
    {
        $proxy->delete();

        return Proxy::with(['user', 'crawler'])
            ->get();
    }
}
