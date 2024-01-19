<?php

namespace App\Modules\Term\Repository;

use App\Modules\Term\Model\Term;
use Illuminate\Pagination\LengthAwarePaginator;

class TermRepository
{
    public function all(): LengthAwarePaginator
    {
        return Term::queryBuilder()->paginate(10);
    }
}
