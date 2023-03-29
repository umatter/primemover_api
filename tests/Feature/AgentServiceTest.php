<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agent\Http\Service\AgentService;
use App\Modules\Agent\Model\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Mockery as m;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

use function PHPUnit\Framework\assertInstanceOf;

class AgentServiceTest extends TestCase
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
        $service = new AgentService(new Agent());
        $result = $service->getData();

        $this->assertInstanceOf(Collection::class, $result->get());
        
        foreach ($result->get() as $agent){
            unset($agent->preferences);
            $this->assertDatabaseHas("agents", $agent->toArray());
            $this->assertInstanceOf(Agent::class, $agent);
        }
    }

    /** @test */
    public function testAdditional()
    {
        $service = new AgentService(new Agent());
        $agentBuilder = $service->getData();
        $result = $service->additional($agentBuilder);

        $this->assertIsArray($result);
    }

    /** @test */
    public function testGetById()
    {
        $agent = Agent::factory()->create();

        $service = new AgentService(new Agent());
        $result = $service->getById($agent->id);

        $this->assertInstanceOf(Agent::class, $result);
        $this->assertEquals($agent->id, $result->id);
    }

    /** @test */
    public function testSave()
    {
        $data = [
            "name" => "age",
            "description" => "description",
            "identification" => "identification",
            "multilogin_id" => "multilogin_id",
            "multilogin_profile" => "multilogin_profile",
            "location" => "location",
            "active" => 1,
            "user_id" => $this->user->id
        ];

        $service = new AgentService(new Agent());
        $result = $service->save($data);

        $this->assertInstanceOf(Agent::class, $result);
        $this->assertDatabaseHas("agents", $data);
    }

    /** @test */
    public function testUpdate()
    {
        $agent = Agent::factory()->create();

        $data = [
            "name" => "updated name",
            "description" => "description",
            "identification" => "identification",
            "multilogin_id" => "multilogin_id",
            "multilogin_profile" => "multilogin_profile",
            "location" => "location",
        ];

        $service = new AgentService(new Agent());
        $result = $service->update($data, $agent);

        $this->assertInstanceOf(Agent::class, $result);
        $this->assertDatabaseHas("agents", $data);
    }

    /** @test */
    public function testDelete()
    {
        $agent = Agent::factory()->create();
        
        $service = new AgentService(new Agent());
        $result = $service->delete($agent);

        $this->assertIsArray($result);
        $this->assertTrue($result["status"]);
    }
}
