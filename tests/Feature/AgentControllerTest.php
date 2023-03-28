<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agent\Http\Controller\Api\v1\AgentController;
use App\Modules\Agent\Http\Request\AgentStoreRequest;
use App\Modules\Agent\Http\Service\AgentService;
use App\Modules\Agent\Model\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Mockery as m;
use Tests\TestCase;

/**
 * @internal
 */
class AgentControllerTest extends TestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function testIndex()
    {
        $controller = new AgentController(new AgentService(new Agent()));
        $result = $controller->index();

        $this->actingAs($this->user)
            ->getJson('/api/v1/agents')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'values', 'count']);

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $agent) {
            $this->assertInstanceOf(Agent::class, $agent);
        }
    }

    /** @test */
    public function testCreate()
    {
        $controller = new AgentController(new AgentService(new Agent()));
        $result = $controller->create();

        $this->assertInstanceOf(ResourceCollection::class, $result);
    }

    /** @test */
    public function testShow()
    {
        $agent = Agent::factory()->create();

        $controller = new AgentController(new AgentService(new Agent()));
        $result = $controller->show($agent->id);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Agent::class, $result->resource);
        $this->assertEquals($agent->id, $result->resource->id);
    }

    /** @test */
    public function testStore()
    {
        $data = [
            'name' => 'name',
            'description' => 'description',
            'identification' => 'identification',
            'multilogin_id' => 'multilogin_id',
            'multilogin_profile' => 'multilogin_profile',
            'location' => 'location',
            'active' => 0,
            'user_id' => $this->user->id,
        ];

        $request = m::mock(AgentStoreRequest::class)->makePartial();
        $request->shouldReceive('validated')->andReturn($data);

        $controller = new AgentController(new AgentService(new Agent()));
        $result = $controller->store($request);

        $this->assertDatabaseHas('agents', $data);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Agent::class, $result->resource);
    }

    /** @test */
    public function testEdit()
    {
        $agent = Agent::factory()->create();

        $controller = new AgentController(new AgentService(new Agent()));
        $result = $controller->edit($agent);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Agent::class, $result->resource);
        $this->assertEquals($agent->id, $result->resource->id);
    }

    /** @test */
    public function testUpdate()
    {
        $agent = Agent::factory()->create();

        $data = new Request([
            'name' => 'agent updated',
            'description' => 'description',
            'identification' => 'identification',
            'multilogin_id' => 'multilogin_id',
            'multilogin_profile' => 'multilogin_profile',
            'location' => 'location',
            'active' => 0,
        ]);

        $controller = new AgentController(new AgentService(new Agent()));
        $result = $controller->update($data, $agent);

        $this->assertDatabaseHas('agents', $data->all());
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Agent::class, $result->resource);
    }

    /** @test */
    public function testDelete()
    {
        $agent = Agent::factory()->create();

        $controller = new AgentController(new AgentService(new Agent()));
        $result = $controller->destroy($agent);

        $this->assertDatabaseMissing('agents', $agent->toArray());
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertEquals(true, $result->resource['status']);
    }
}
