<?php

namespace App\Modules\Term\Http\Service;

use App\Modules\Term\Model\Term;
use Illuminate\Pagination\LengthAwarePaginator;

class TermService
{
    protected Term $term;

    public function __construct(Term $term)
    {
        $this->term = $term;
    }
    
    public function getData(): LengthAwarePaginator
    {
        $terms = Term::query()
        ->latest()
        ->paginate(10);

        return $terms;
    }
    
}