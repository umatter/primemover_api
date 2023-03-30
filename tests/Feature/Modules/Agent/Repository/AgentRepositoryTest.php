<?php

namespace Tests\Feature\Modules\Agent\Repository;

use App\Modules\Agent\Model\Agent;
use App\Modules\Agent\Repository\AgentRepository;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Agent\Repository\AgentRepository
 */
class AgentRepositoryTest extends TestCase
{
    /**
     * @covers ::getData
     */
    public function testGetData(): void
    {
        $service = new AgentRepository(new Agent());
        $result = $service->getData();

        $this->assertInstanceOf(Collection::class, $result->get());

        foreach ($result->get() as $agent) {
            unset($agent->preferences);
            $this->assertDatabaseHas('agents', $agent->toArray());
            $this->assertInstanceOf(Agent::class, $agent);
        }
    }

    /**
     * @covers ::additional
     */
    public function testAdditional(): void
    {
        $service = new AgentRepository(new Agent());
        $agentBuilder = $service->getData();
        $result = $service->additional($agentBuilder);

        $this->assertIsArray($result);
    }

    /**
     * @covers ::getById
     */
    public function testGetById(): void
    {
        $agent = Agent::factory()->create();

        $service = new AgentRepository(new Agent());
        $result = $service->getById($agent->id);

        $this->assertInstanceOf(Agent::class, $result);
        $this->assertEquals($agent->id, $result->id);
    }

    /**
     * @covers ::save
     */
    public function testSave(): void
    {
        $data = [
            'name' => 'age',
            'description' => 'description',
            'identification' => 'identification',
            'multilogin_id' => 'multilogin_id',
            'multilogin_profile' => 'multilogin_profile',
            'location' => 'location',
            'active' => 1,
            'user_id' => $this->user->id,
        ];

        $service = new AgentRepository(new Agent());
        $result = $service->save($data);

        $this->assertInstanceOf(Agent::class, $result);
        $this->assertDatabaseHas('agents', $data);
    }

    /**
     * @covers ::update
     */
    public function testUpdate(): void
    {
        $agent = Agent::factory()->create();

        $data = [
            'name' => 'updated name',
            'description' => 'description',
            'identification' => 'identification',
            'multilogin_id' => 'multilogin_id',
            'multilogin_profile' => 'multilogin_profile',
            'location' => 'location',
        ];

        $service = new AgentRepository(new Agent());
        $result = $service->update($data, $agent);

        $this->assertInstanceOf(Agent::class, $result);
        $this->assertDatabaseHas('agents', $data);
    }

    /**
     * @covers ::delete
     */
    public function testDelete(): void
    {
        $agent = Agent::factory()->create();

        $service = new AgentRepository(new Agent());
        $result = $service->delete($agent);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
    }
}
