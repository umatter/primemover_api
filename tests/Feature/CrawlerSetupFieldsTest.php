<?php

namespace Tests\Feature;

use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Proxy\Model\Proxy;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * A crawler's setup can be changed after it is created.
 *
 * UpdateCrawlerRequest validated only name and experiment_id, and the controller
 * saves validated(), so agent, proxy, configuration, description and testing
 * were dropped without an error -- a 200 that changed nothing.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Crawler\Http\Controller\Api\v1\CrawlerController
 */
class CrawlerSetupFieldsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Asserts 422s, so let the handler render them.
        $this->withExceptionHandling();
        Passport::actingAs($this->user);
    }

    /**
     * @covers ::update
     */
    public function test_update_stores_the_setup_fields(): void
    {
        $agent = Agent::factory()->create(['user_id' => $this->user->id]);
        $proxy = Proxy::factory()->create(['user_id' => $this->user->id]);
        $configuration = Configuration::factory()->create(['user_id' => $this->user->id]);

        $this->putJson('/api/v1/crawlers/'.$this->crawler->id, [
            'name' => 'renamed',
            'experiment_id' => $this->experiment->id,
            'agent_id' => $agent->id,
            'proxy_id' => $proxy->id,
            'configuration_id' => $configuration->id,
            'description' => 'left-leaning arm',
            'testing' => true,
        ])->assertOk();

        $crawler = $this->crawler->fresh();
        $this->assertSame($agent->id, (int) $crawler->agent_id);
        $this->assertSame($proxy->id, (int) $crawler->proxy_id);
        $this->assertSame($configuration->id, (int) $crawler->configuration_id);
        $this->assertSame('left-leaning arm', $crawler->description);
        $this->assertSame(1, (int) $crawler->testing);
    }

    /**
     * @covers ::update
     */
    public function test_update_leaves_unsent_fields_alone(): void
    {
        $this->crawler->update(['testing' => 1, 'description' => 'keep me']);
        $before = $this->crawler->fresh();

        $this->putJson('/api/v1/crawlers/'.$this->crawler->id, [
            'name' => 'renamed',
            'experiment_id' => $this->experiment->id,
        ])->assertOk();

        $after = $this->crawler->fresh();
        $this->assertSame('renamed', $after->name);
        $this->assertSame((int) $before->agent_id, (int) $after->agent_id);
        $this->assertSame((int) $before->configuration_id, (int) $after->configuration_id);
        $this->assertSame('keep me', $after->description);
        $this->assertSame(1, (int) $after->testing);
    }

    /**
     * @covers ::update
     */
    public function test_update_rejects_a_setup_id_that_does_not_exist(): void
    {
        $this->putJson('/api/v1/crawlers/'.$this->crawler->id, [
            'name' => 'renamed',
            'experiment_id' => $this->experiment->id,
            'configuration_id' => 999999,
        ])->assertUnprocessable()->assertJsonValidationErrors('configuration_id');
    }

    /**
     * @covers ::store
     */
    public function test_store_takes_testing_and_defaults_it_to_off(): void
    {
        $base = [
            'experiment_id' => $this->experiment->id,
            'agent_id' => $this->agent->id,
            'proxy_id' => $this->proxy->id,
            'configuration_id' => $this->configuration->id,
        ];

        $test = $this->postJson('/api/v1/crawlers', [...$base, 'name' => 'test bot', 'testing' => true])->assertCreated();
        $prod = $this->postJson('/api/v1/crawlers', [...$base, 'name' => 'prod bot'])->assertCreated();

        $this->assertSame(1, (int) Crawler::findOrFail($test->json('data.id'))->testing);
        $this->assertSame(0, (int) Crawler::findOrFail($prod->json('data.id'))->testing);
    }
}
