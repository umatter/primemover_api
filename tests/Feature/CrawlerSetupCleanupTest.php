<?php

namespace Tests\Feature;

use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Proxy\Model\Proxy;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Destroying a bot or an experiment keeps the Library items other bots still use.
 *
 * crawler -> agent/proxy/configuration is not unique, and the destroy routes used
 * to delete each crawler's items outright, so one study's cleanup could delete
 * another study's browser profile, exit IP or treatment.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Crawler\SetupCleanup
 */
class CrawlerSetupCleanupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user);
    }

    public function test_destroying_a_bot_keeps_items_another_bot_uses(): void
    {
        $bot = $this->botWithOwnSetup($this->experiment);
        $other = $this->botWithOwnSetup(Experiment::factory()->create(['user_id' => $this->user->id]));
        // The agent is shared with a bot in another experiment; proxy and configuration are not.
        $bot->update(['agent_id' => $other->agent_id]);

        $this->deleteJson("/api/v1/crawlers/{$bot->id}/destroy")->assertOk();

        $this->assertModelMissing($bot);
        $this->assertModelExists(Agent::findOrFail($other->agent_id));
        $this->assertDatabaseMissing('proxies', ['id' => $bot->proxy_id]);
        $this->assertDatabaseMissing('configurations', ['id' => $bot->configuration_id]);
    }

    public function test_destroying_an_experiment_keeps_items_another_experiment_uses(): void
    {
        $experiment = Experiment::factory()->create(['user_id' => $this->user->id]);
        $first = $this->botWithOwnSetup($experiment);
        // Shared only inside the experiment being destroyed: still deleted.
        $second = $this->botWithOwnSetup($experiment, ['proxy_id' => $first->proxy_id]);
        $elsewhere = $this->botWithOwnSetup(Experiment::factory()->create(['user_id' => $this->user->id]));
        // Shared with another experiment: kept.
        $second->update(['configuration_id' => $elsewhere->configuration_id]);

        $this->deleteJson("/api/v1/experiments/{$experiment->id}/destroy")->assertOk();

        $this->assertModelMissing($experiment);
        $this->assertDatabaseMissing('crawlers', ['experiment_id' => $experiment->id]);
        $this->assertDatabaseMissing('proxies', ['id' => $first->proxy_id]);
        $this->assertDatabaseMissing('agents', ['id' => $first->agent_id]);
        $this->assertDatabaseMissing('agents', ['id' => $second->agent_id]);
        $this->assertDatabaseMissing('configurations', ['id' => $first->configuration_id]);
        $this->assertModelExists(Configuration::findOrFail($elsewhere->configuration_id));
        $this->assertModelExists($elsewhere->fresh());
    }

    /**
     * Items are active: the crawler's setup relations (and so the cleanup) only reach
     * active ones, and inactive items were never deleted.
     *
     * @param array<string, mixed> $overrides
     */
    private function botWithOwnSetup(Experiment $experiment, array $overrides = []): Crawler
    {
        return Crawler::factory()->create([
            'user_id' => $this->user->id,
            'experiment_id' => $experiment->id,
            'agent_id' => Agent::factory()->create(['user_id' => $this->user->id, 'active' => 1])->id,
            'proxy_id' => Proxy::factory()->create(['user_id' => $this->user->id, 'active' => 1])->id,
            'configuration_id' => Configuration::factory()->create(['user_id' => $this->user->id, 'active' => 1])->id,
            ...$overrides,
        ]);
    }
}
