<?php

namespace Tests\Feature\Modules\Term\Service;

use App\Modules\Term\Model\Term;
use App\Modules\Term\Service\TermService;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class TermServiceTest extends TestCase
{
    /** @test */
    public function testGetData(): void
    {
        $service = new TermService(new Term());
        $result = $service->getData();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $term) {
            unset($term->created_at, $term->updated_at);

            $this->assertDatabaseHas('terms', $term->toArray());
            $this->assertInstanceOf(Term::class, $term);
        }
    }
}
