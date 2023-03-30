<?php

namespace Tests\Feature\Modules\Term\Http\Api\v1;

use App\Modules\Term\Http\Controller\Api\v1\TermController;
use App\Modules\Term\Model\Term;
use App\Modules\Term\Service\TermService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class TermControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function testIndex(): void
    {
        Term::create([
            'bigram' => 'testing',
            'user_id' => $this->user->id,
        ]);

        $controller = new TermController(new TermService(new Term()));
        $result = $controller->index();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $term) {
            $this->assertInstanceOf(Term::class, $term);
        }
    }
}
