<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Term\Http\Controller\Api\v1\TermController;
use App\Modules\Term\Model\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Tests\TestCase;

class TermControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    
    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function testIndex()
    {
        Term::create([
            "bigram" => "testing",
            "user_id" => $this->user->id
        ]);

        $controller = new TermController();
        $result = $controller->index();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach($result->resource as $term)
            $this->assertInstanceOf(Term::class, $term);
    }
}
