<?php

namespace Tests;

use App\Events\ModelUpdateEvent;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Outlet\Model\Outlet;
use App\Modules\Param\Model\Param;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Queue\Model\Queue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;
    use WithoutModelEvents;

    /**
     * @var Controller|MockInterface|Model
     */
    protected mixed $testObject;

    protected User $user;

    protected Proxy $proxy;

    protected Configuration $configuration;

    protected Agent $agent;

    protected Experiment $experiment;

    protected Crawler $crawler;

    protected Queue $queue;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake(ModelUpdateEvent::class);
        $this->withoutExceptionHandling();

        $this->user = User::factory()->create();

        $this->proxy = Proxy::factory()->create(['user_id' => $this->user->id]);
        $this->configuration = Configuration::factory()->create(['user_id' => $this->user->id]);
        $this->agent = Agent::factory()->create(['user_id' => $this->user->id]);
        $this->experiment = Experiment::factory()->create(['user_id' => $this->user->id]);

        $this->crawler = Crawler::factory()->create([
            'proxy_id' => $this->proxy->id,
            'agent_id' => $this->agent->id,
            'experiment_id' => $this->experiment->id,
            'configuration_id' => $this->configuration->id,
        ]);

        $this->queue = Queue::factory()->create(['crawler_id' => $this->crawler->id]);

        Queue::factory()->create(['crawler_id' => $this->crawler->id, 'reviewed' => 1]);
        Job::factory()->count(10)->create(['queue_id' => $this->queue->id]);
        Outlet::factory()->count(10)->create(['user_id' => $this->user->id]);
        Param::factory()->count(10)->create(['user_id' => $this->user->id, 'configuration_id' => $this->configuration->id]);
    }
}
