<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Term\Http\Service\TermService;
use App\Modules\Term\Model\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class TermServiceTest extends TestCase
{
    protected $user;

    public function setUp():void 
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function testGetData()
    {
        $service = new TermService(new Term());
        $result = $service->getData();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach($result as $term){
            unset($term->created_at);
            unset($term->updated_at);
            $this->assertDatabaseHas("terms", $term->toArray());
            $this->assertInstanceOf(Term::class, $term);
        }
    }
}
