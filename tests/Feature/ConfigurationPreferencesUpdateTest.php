<?php

namespace Tests\Feature;

use App\Modules\Configuration\Model\Configuration;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * PUT configurations/{id} can update the configuration's preferences by
 * name. pmspec (phase 6, task 0) uses this to change a bot's preferences
 * between longitudinal runs, and detects whether a given API build supports
 * it by sending `preferences: []` and checking whether the response echoes a
 * `preferences` list.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Configuration\Repository\ConfigurationRepository
 */
class ConfigurationPreferencesUpdateTest extends TestCase
{
    /**
     * @covers ::update
     */
    public function test_updates_the_value_of_an_existing_preference(): void
    {
        $preference = $this->configuration->preferences()->create([
            'name' => 'ideology',
            'description' => 'the ideology dial',
            'value' => '0.0',
            'active' => 1,
            'user_id' => $this->user->id,
        ]);

        Passport::actingAs($this->user);

        $this->putJson('/api/v1/configurations/'.$this->configuration->id, $this->payload([
            'preferences' => [
                ['name' => 'ideology', 'value' => '0.31'],
            ],
        ]))->assertOk();

        $this->assertDatabaseHas('preferences', [
            'id' => $preference->id,
            'name' => 'ideology',
            'value' => '0.31',
            // Description was not sent for this entry, so it must survive.
            'description' => 'the ideology dial',
        ]);
        $this->assertSame(1, $this->configuration->preferences()->count());
    }

    /**
     * @covers ::update
     */
    public function test_creates_a_preference_that_does_not_exist_yet(): void
    {
        Passport::actingAs($this->user);

        $this->putJson('/api/v1/configurations/'.$this->configuration->id, $this->payload([
            'preferences' => [
                ['name' => 'ideology', 'value' => '0.31', 'description' => 'the ideology dial'],
            ],
        ]))->assertOk();

        $this->assertDatabaseHas('preferences', [
            'preferencable_type' => Configuration::class,
            'preferencable_id' => $this->configuration->id,
            'name' => 'ideology',
            'value' => '0.31',
            'description' => 'the ideology dial',
            'user_id' => $this->user->id,
            'active' => 1,
        ]);
    }

    /**
     * @covers ::update
     */
    public function test_an_unnamed_sibling_preference_is_never_touched(): void
    {
        $sibling = $this->configuration->preferences()->create([
            'name' => 'tone',
            'description' => 'the tone dial',
            'value' => 'neutral',
            'active' => 1,
            'user_id' => $this->user->id,
        ]);

        Passport::actingAs($this->user);

        $this->putJson('/api/v1/configurations/'.$this->configuration->id, $this->payload([
            'preferences' => [
                ['name' => 'ideology', 'value' => '0.31'],
            ],
        ]))->assertOk();

        $this->assertDatabaseHas('preferences', [
            'id' => $sibling->id,
            'name' => 'tone',
            'value' => 'neutral',
        ]);
        $this->assertSame(2, $this->configuration->preferences()->count());
    }

    /**
     * pmspec's support probe sends exactly this and checks whether `data`
     * carries a `preferences` list in the response.
     *
     * @covers ::update
     */
    public function test_an_empty_preferences_list_is_a_noop_that_echoes_the_relation(): void
    {
        $preference = $this->configuration->preferences()->create([
            'name' => 'ideology',
            'description' => 'the ideology dial',
            'value' => '0.0',
            'active' => 1,
            'user_id' => $this->user->id,
        ]);

        Passport::actingAs($this->user);

        $response = $this->putJson('/api/v1/configurations/'.$this->configuration->id, $this->payload([
            'preferences' => [],
        ]))->assertOk();

        $response->assertJsonPath('data.preferences.0.name', 'ideology');

        $this->assertDatabaseHas('preferences', [
            'id' => $preference->id,
            'value' => '0.0',
        ]);
        $this->assertSame(1, $this->configuration->preferences()->count());
    }

    /**
     * active is not readable from any response before this change, so a client
     * that had to send it could only guess -- and guessing true re-activated an
     * inactive configuration.
     *
     * @covers ::update
     */
    public function test_an_update_without_active_keeps_the_configurations_active(): void
    {
        $this->configuration->update(['active' => 0]);

        Passport::actingAs($this->user);

        $response = $this->putJson('/api/v1/configurations/'.$this->configuration->id, [
            'name' => 'renamed',
            'preferences' => [],
        ])->assertOk();

        $this->assertSame(0, (int) $this->configuration->fresh()->active);
        $this->assertSame('renamed', $this->configuration->fresh()->name);
        $this->assertSame(0, (int) $response->json('data.active'));
    }

    /**
     * @covers ::update
     */
    public function test_a_preference_can_be_deactivated(): void
    {
        $preference = $this->configuration->preferences()->create([
            'name' => 'location',
            'value' => 'US-IL-CHICAGO',
            'active' => 1,
            'user_id' => $this->user->id,
        ]);

        Passport::actingAs($this->user);

        $this->putJson('/api/v1/configurations/'.$this->configuration->id, $this->payload([
            'preferences' => [
                ['name' => 'location', 'value' => 'US-IL-CHICAGO', 'active' => false],
            ],
        ]))->assertOk();

        $this->assertSame(0, (int) $preference->fresh()->active);
    }

    /**
     * @covers ::update
     */
    public function test_a_value_change_does_not_reactivate_an_inactive_preference(): void
    {
        $preference = $this->configuration->preferences()->create([
            'name' => 'location',
            'value' => 'US-IL-CHICAGO',
            'active' => 0,
            'user_id' => $this->user->id,
        ]);

        Passport::actingAs($this->user);

        $this->putJson('/api/v1/configurations/'.$this->configuration->id, $this->payload([
            'preferences' => [
                ['name' => 'location', 'value' => 'CH-BE-BERN'],
            ],
        ]))->assertOk();

        $preference->refresh();
        $this->assertSame('CH-BE-BERN', $preference->value);
        $this->assertSame(0, (int) $preference->active);
    }

    /**
     * @covers ::update
     */
    public function test_a_new_preference_sent_without_active_is_active(): void
    {
        Passport::actingAs($this->user);

        $this->putJson('/api/v1/configurations/'.$this->configuration->id, $this->payload([
            'preferences' => [
                ['name' => 'usage_type', 'value' => 'only_searches'],
            ],
        ]))->assertOk();

        $this->assertDatabaseHas('preferences', [
            'preferencable_id' => $this->configuration->id,
            'name' => 'usage_type',
            'active' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => $this->configuration->name,
            'description' => $this->configuration->description,
            'active' => 1,
        ], $overrides);
    }
}
