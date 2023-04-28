<?php

namespace Tests\Feature\Modules\Agent\Http\Controller\Api\v1;

use App\Modules\Agent\Http\Controller\Api\v1\AgentController;
use App\Modules\Agent\Http\Request\AgentStoreRequest;
use App\Modules\Agent\Http\Request\AgentUpdateRequest;
use App\Modules\Agent\Model\Agent;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\App;
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
     * @var AgentController
     */
    protected mixed $testObject;

    public function setUp(): void
    {
        parent::setUp();
        $this->testObject = App::make(AgentController::class);
    }

    /**
     *@covers ::index
     */
    public function testIndex(): void
    {
        $result = $this->testObject->index();

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
        $result = $this->testObject->create();

        $this->assertInstanceOf(ResourceCollection::class, $result);
    }

    /**
     *@covers ::show
     */
    public function testShow(): void
    {
        $result = $this->testObject->show($this->agent->id);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Agent::class, $result->resource);
        $this->assertEquals($this->agent->id, $result->resource->id);
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

        $result = $this->testObject->store($request);

        $this->assertDatabaseHas('agents', $data);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Agent::class, $result->resource);
    }

    /**
     *@covers ::edit
     */
    public function testEdit(): void
    {
        $result = $this->testObject->edit($this->agent->id);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Agent::class, $result->resource);
        $this->assertEquals($this->agent->id, $result->resource->id);
    }

    /**
     *@covers ::update
     */
    public function testUpdate(): void
    {
        $data = [
            'name' => 'agent updated',
            'description' => 'description',
            'identification' => 'identification',
            'multilogin_id' => 'multilogin_id',
            'multilogin_profile' => 'multilogin_profile',
            'location' => 'location',
            'active' => 0,
        ];

        $request = m::mock(AgentUpdateRequest::class);
        $request->expects('validated')
            ->twice()
            ->andReturns($data);

        $result = $this->testObject->update($request, $this->agent);

        $this->assertDatabaseHas('agents', $request->validated());
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Agent::class, $result->resource);
    }

    /**
     *@covers ::destroy
     */
    public function testDelete(): void
    {
        $result = $this->testObject->destroy($this->agent);

        $this->assertDatabaseMissing('agents', $this->agent->toArray());
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertEquals(true, $result->resource['status']);
    }
}
