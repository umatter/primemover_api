<?php

namespace App\Modules\Param\Service;

use App\Modules\Param\Model\Param;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ParamService
{
    public function __construct(protected Param $param)
    {
    }

    public function getData(): Collection
    {
        return Param::with(
            [
                'user',
                'configuration',
            ]
        )->latest()->get();
    }

    public function getById($id): Param|Model
    {
        return $this->param->with(['user'])->findOrFail($id);
    }

    public function save(array $data): Param|Model
    {
        $data['user_id'] = Auth::id();

        return $this->param->query()->create($data);
    }

    public function update(array $data, Param $param): Param
    {
        $param->update($data);

        return $param;
    }

    public function delete(Param $param): Collection
    {
        $param->delete();

        return Param::with(
            ['user', 'configuration']
        )->latest()->get();
    }
}
