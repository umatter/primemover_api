<?php

namespace Tests\Feature\Modules\Agent\Http\Controller\Api\v1;

use App\Modules\Agent\Http\Controller\Api\v1\AgentController;
use App\Modules\Agent\Http\Request\AgentStoreRequest;
use App\Modules\Agent\Model\Agent;
use App\Modules\Agent\Repository\AgentRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Mockery as m;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Agent\Http\Controller\Api\v1\AgentController
 */
class AgentControllerTest extends TestCase
{
    /**
     *@covers ::index
     */
    public function testIndex(): void
    {
        $controller = new AgentController(new AgentRepository(new Agent()));
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

    /**
     *@covers ::create
     */
    public function testCreate(): void
    {
        $controller = new AgentController(new AgentRepository(new Agent()));
        $result = $controller->create();

        $this->assertInstanceOf(ResourceCollection::class, $result);
    }

    /**
     *@covers ::show
     */
    public function testShow(): void
    {
        $agent = Agent::factory()->create();

        $controller = new AgentController(new AgentRepository(new Agent()));
        $result = $controller->show($agent->id);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Agent::class, $result->resource);
        $this->assertEquals($agent->id, $result->resource->id);
    }

    /**
     *@covers ::store
     */
    public function testStore(): void
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
        $request->allows('validated')->andReturns($data);

        $controller = new AgentController(new AgentRepository(new Agent()));
        $result = $controller->store($request);

        $this->assertDatabaseHas('agents', $data);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Agent::class, $result->resource);
    }

    /**
     *@covers ::edit
     */
    public function testEdit(): void
    {
        $agent = Agent::factory()->create();

        $controller = new AgentController(new AgentRepository(new Agent()));
        $result = $controller->edit($agent);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Agent::class, $result->resource);
        $this->assertEquals($agent->id, $result->resource->id);
    }

    /**
     *@covers ::update
     */
    public function testUpdate(): void
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

        $controller = new AgentController(new AgentRepository(new Agent()));
        $result = $controller->update($data, $agent);

        $this->assertDatabaseHas('agents', $data->all());
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Agent::class, $result->resource);
    }

    /**
     *@covers ::destroy
     */
    public function testDelete(): void
    {
        $agent = Agent::factory()->create();

        $controller = new AgentController(new AgentRepository(new Agent()));
        $result = $controller->destroy($agent);

        $this->assertDatabaseMissing('agents', $agent->toArray());
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertEquals(true, $result->resource['status']);
    }
}
