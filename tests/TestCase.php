<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    use CreatesApplication;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();

        /** @var User $user */
        $user = User::factory()->create();
        $proxy = $user->proxies()->getModel()->factory()->create();
        $configuration = $user->configurations()->getModel()->factory()->create();
        $agent = $user->agents()->getModel()->factory()->create();
        $experiment = $user->experiments()->getModel()->factory()->create();
        $crawler = $experiment->crawlers()->getModel()->factory()->create([
            'proxy_id' => $proxy->id,
            'agent_id' => $agent->id,
            'configuration_id' => $configuration->id,
        ]);
        $queue = $crawler->queues()->getModel()->factory()->create();
        $queue->jobs()->getModel()->factory()->count(10)->create();
        $queue = $crawler->queues()->getModel()->factory()->create(['reviewed' => 1]);
        $queue->jobs()->getModel()->factory()->count(10)->create();
        $user->outlets()->getModel()->factory()->count(10)->create();
    }
}
