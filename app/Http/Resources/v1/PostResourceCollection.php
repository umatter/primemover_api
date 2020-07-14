<?php

namespace App\Http\Resources\v1;
use Lang;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PostResourceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }


    public function with($request)
    {
        $columns = [
            'title',
            'body',
            'updated_at',
        ];

        return [
            'columns' => $columns,
            'version' => '1.0.0',
            'author' => url('https://primemover.example')
        ];
    }
}
