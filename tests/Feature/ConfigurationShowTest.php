<?php

namespace Tests\Feature;

use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * GET configurations/{id} says how many crawlers use the configuration.
 *
 * crawler -> configuration is not unique, so editing "this bot's" treatment can
 * change other bots' too. A client has to be able to say so before it saves.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Configuration\Repository\ConfigurationRepository
 */
class ConfigurationShowTest extends TestCase
{
    /**
     * @covers ::getById
     */
    public function test_it_counts_the_crawlers_using_the_configuration(): void
    {
        $shared = Configuration::factory()->create(['user_id' => $this->user->id]);
        Crawler::factory()->count(2)->create([
            'experiment_id' => $this->experiment->id,
            'proxy_id' => $this->proxy->id,
            'agent_id' => $this->agent->id,
            'configuration_id' => $shared->id,
        ]);
        $unused = Configuration::factory()->create(['user_id' => $this->user->id]);

        Passport::actingAs($this->user);

        $this->getJson('/api/v1/configurations/'.$shared->id)->assertOk()->assertJsonPath('data.crawlers_count', 2);
        $this->getJson('/api/v1/configurations/'.$unused->id)->assertOk()->assertJsonPath('data.crawlers_count', 0);
    }
}
