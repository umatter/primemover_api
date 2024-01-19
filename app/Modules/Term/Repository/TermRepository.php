<?php

namespace App\Modules\Term\Repository;

use App\Modules\Term\Model\Term;
use Illuminate\Pagination\LengthAwarePaginator;

class TermRepository
{
    public function __construct(protected Term $term)
    {
    }

    public function getData(): LengthAwarePaginator
    {
        return Term::query()
            ->paginate(10);
    }
}
