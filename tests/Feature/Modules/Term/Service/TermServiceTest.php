<?php

namespace Tests\Feature\Modules\Term\Service;

use App\Modules\Term\Model\Term;
use App\Modules\Term\Service\TermService;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Term\Service\TermService
 */
class TermServiceTest extends TestCase
{
    /**
     * @covers ::getData
     */
    public function testGetData(): void
    {
        $service = new TermService(new Term());
        $result = $service->getData();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $term) {
            unset($term->created_at, $term->updated_at);

            self::assertDatabaseHas('terms', $term->toArray());
            self::assertInstanceOf(Term::class, $term);
        }
    }
}
