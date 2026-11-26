<?php

namespace Tests\Feature;

use App\Modules\Configuration\Model\Configuration;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * POST configurations creates the configuration's params and preferences.
 *
 * Before this, only the upload pipeline created params, so a configuration made
 * any other way had no treatment at all -- the runner had nothing to select with.
 * Every configuration now starts with exactly one params row, in the control arm
 * (pi = psi = kappa = tau = 0) unless the request says otherwise.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Configuration\Http\Controller\Api\v1\ConfigurationController
 */
class ConfigurationStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // These assert the HTTP answer (403, 422), so let the handler render it.
        $this->withExceptionHandling();
        Passport::actingAs($this->user);
    }

    /**
     * @covers ::store
     */
    public function test_without_params_it_starts_in_the_control_arm(): void
    {
        $response = $this->postJson('/api/v1/configurations', $this->payload())->assertCreated();

        $configuration = Configuration::findOrFail($response->json('data.id'));
        $param = $configuration->params()->sole();

        foreach (['pi', 'psi', 'kappa', 'tau', 'alpha', 'beta'] as $weight) {
            $this->assertEquals(0, $param->{$weight}, "{$weight} must default to 0");
        }
        $this->assertSame($this->user->id, (int) $param->user_id);

        $listed = $response->json('data.params.0');
        $this->assertSame('[]', $listed['search_terms']);
        $this->assertSame('[]', $listed['media_outlet_urls']);
    }

    /**
     * @covers ::store
     */
    public function test_given_params_are_stored(): void
    {
        $terms = [['term' => 'climate', 'pi' => -0.25]];

        $response = $this->postJson('/api/v1/configurations', $this->payload([
            'params' => ['pi' => 0.5, 'tau' => 1, 'kappa' => 2, 'search_terms' => $terms],
        ]))->assertCreated();

        $param = Configuration::findOrFail($response->json('data.id'))->params()->sole();
        $this->assertEquals(0.5, $param->pi);
        $this->assertEquals(1, $param->tau);
        $this->assertEquals(2, $param->kappa);
        $this->assertEquals(0, $param->psi, 'unsent weights fall back to the control value');
        $this->assertSame($terms, json_decode($response->json('data.params.0.search_terms'), true));
    }

    /**
     * @covers ::store
     */
    public function test_invalid_params_reject_the_whole_request(): void
    {
        $count = Configuration::count();

        $this->postJson('/api/v1/configurations', $this->payload(['params' => ['pi' => 4]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('params.pi');

        $this->assertSame($count, Configuration::count());
    }

    /**
     * @covers ::store
     */
    public function test_given_preferences_are_created(): void
    {
        $response = $this->postJson('/api/v1/configurations', $this->payload([
            'preferences' => [
                ['name' => 'location', 'value' => 'US-IL-CHICAGO'],
                ['name' => 'cookie_pref', 'value' => '{"accept_all": true}', 'description' => 'consent'],
            ],
        ]))->assertCreated();

        $configuration = Configuration::findOrFail($response->json('data.id'));
        $this->assertDatabaseHas('preferences', [
            'preferencable_type' => Configuration::class,
            'preferencable_id' => $configuration->id,
            'name' => 'cookie_pref',
            'value' => '{"accept_all": true}',
            'description' => 'consent',
            'active' => 1,
        ]);
        $this->assertSame(2, $configuration->preferences()->count());
        $this->assertCount(2, $response->json('data.preferences'));
    }

    /** @return array<string, array{0: mixed}> */
    public static function emptyValues(): array
    {
        return [
            'empty string' => [''],
            'whitespace' => ['  '],
            'null' => [null],
        ];
    }

    /**
     * "" and whitespace reach validation as null (TrimStrings, ConvertEmptyStringsToNull)
     * and preferences.value is NOT NULL: with only `present` this was a 500.
     *
     * @covers ::store
     *
     * @dataProvider emptyValues
     */
    public function test_an_empty_preference_value_is_a_422_and_creates_nothing(mixed $value): void
    {
        $before = Configuration::count();

        $this->postJson('/api/v1/configurations', $this->payload([
            'preferences' => [
                ['name' => 'location', 'value' => 'US-IL-CHICAGO'],
                ['name' => 'usage_type', 'value' => $value],
            ],
        ]))->assertUnprocessable()->assertJsonValidationErrors('preferences.1.value');

        $this->assertSame($before, Configuration::count());
    }

    /**
     * Values are strings on the wire; a number is refused rather than converted.
     *
     * @covers ::store
     */
    public function test_a_non_string_preference_value_is_a_422(): void
    {
        $this->postJson('/api/v1/configurations', $this->payload([
            'preferences' => [['name' => 'aversion_left', 'value' => 0.3]],
        ]))->assertUnprocessable()->assertJsonValidationErrors('preferences.0.value');
    }

    /**
     * @param  array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'treatment arm A',
            'description' => 'left-leaning',
            'active' => 1,
        ], $overrides);
    }
}
