<?php

namespace App\Modules\Term\Service;

use App\Modules\Term\Model\Term;
use Illuminate\Pagination\LengthAwarePaginator;

class TermService
{
    public function __construct(protected Term $term)
    {
    }

    public function getData(): LengthAwarePaginator
    {
        return Term::query()
            ->latest()
            ->paginate(10);
    }
}
