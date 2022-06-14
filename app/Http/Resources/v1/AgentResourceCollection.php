<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AgentResourceCollection extends ResourceCollection
{
    public function with($request): array
    {
        $columns = [
            'name',
            'description',
            'identification',
            'updated_at',
        ];

        return [
            'columns' => $columns,
        ];
    }
}
