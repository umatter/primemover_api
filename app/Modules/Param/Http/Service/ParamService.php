<?php

namespace App\Modules\Param\Http\Service;

use App\Modules\Param\Model\Param;
use Illuminate\Database\Eloquent\Collection;

class ParamService
{
    protected Param $param;

    public function __construct(Param $param)
    {
        $this->param = $param;
    }
    
    public function getData(): Collection
    {
        $params = Param::with(
            [
                'user',
                'configuration',
            ]
        )->latest()->get();

        return $params;
    }
    
    public function getById($id): Param
    {
        $param = $this->param->with(['user'])->findOrFail($id);
        
        return $param;
    }

    public function save(array $data): Param
    {
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;
        
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