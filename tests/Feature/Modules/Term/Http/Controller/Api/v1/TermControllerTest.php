<?php

namespace Tests\Feature\Modules\Term\Http\Controller\Api\v1;

use App\Modules\Term\Http\Controller\Api\v1\TermController;
use App\Modules\Term\Model\Term;
use App\Modules\Term\Repository\TermRepository;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Term\Http\Controller\Api\v1\TermController
 */
class TermControllerTest extends TestCase
{
    /**
     * @covers ::index
     */
    public function test_index(): void
    {
        Term::query()->create([
            'bigram' => 'testing',
            'user_id' => $this->user->id,
        ]);

        $controller = new TermController(new TermRepository(new Term()));
        $result = $controller->index();

        self::assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $term) {
            self::assertInstanceOf(Term::class, $term);
        }
    }
}
