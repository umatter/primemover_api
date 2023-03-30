<?php

namespace App\Modules\Proxy\Service;

use App\Modules\Proxy\Model\Proxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProxyService
{
    public function __construct(protected Proxy $proxy)
    {
    }

    public function getData($data): ResourceCollection
    {
        $proxies = QueryBuilder::for(Proxy::class)
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->latest();

        $response = $proxies
            ->when($data->getPaged(), fn (Builder $builder) => $builder->paginate($data->getPaged()))
            ->when( ! $data->getPaged(), fn (Builder $builder) => $builder->get());

        return ResourceCollection::make($response)
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

        return $proxy;
    }

    public function delete(Proxy $proxy): Collection
    {
        $proxy->delete();

        return Proxy::with(['user', 'crawler'])
            ->latest()
            ->get();
    }
}
