<?php

namespace Tests\Feature;

use App\Modules\Configuration\Model\Configuration;
use App\Modules\Param\Model\Param;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Writes to params/{id} are validated, and the two list fields keep the wire
 * format the runner depends on.
 *
 * search_terms and media_outlet_urls travel as JSON-encoded STRINGS. The
 * runner's SearchTermDeserializer / MediaOutletsDeserializer call getText() on
 * the field and parse it, so a real JSON array deserialises to an empty list --
 * no error, the bot just loses its treatment. Whatever a client sends, a GET
 * must therefore hand back a string.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Param\Http\Controller\Api\v1\ParamController
 */
class ParamWriteTest extends TestCase
{
    private Param $param;

    protected function setUp(): void
    {
        parent::setUp();
        // These assert the HTTP answer (403, 422), so let the handler render it.
        $this->withExceptionHandling();

        $this->param = $this->configuration->params()->first();
        Passport::actingAs($this->user);
    }

    /**
     * @covers ::update
     */
    public function test_a_valid_update_is_stored(): void
    {
        $this->putJson($this->url(), $this->payload(['pi' => -0.5, 'kappa' => 2]))->assertOk();

        $this->param->refresh();
        $this->assertEquals(-0.5, $this->param->pi);
        $this->assertEquals(2, $this->param->kappa);
    }

    /**
     * @covers ::update
     *
     * @dataProvider invalidPayloads
     *
     * @param array<string, mixed> $overrides
     */
    public function test_an_invalid_update_is_rejected(array $overrides, string $field): void
    {
        $this->putJson($this->url(), $this->payload($overrides))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function invalidPayloads(): array
    {
        return [
            'pi above 1' => [['pi' => 2], 'pi'],
            'pi below -1' => [['pi' => -1.01], 'pi'],
            'psi not numeric' => [['psi' => 'lots'], 'psi'],
            'kappa fractional' => [['kappa' => 1.5], 'kappa'],
            'kappa negative' => [['kappa' => -1], 'kappa'],
            'search term without term' => [['search_terms' => [['pi' => 0.1]]], 'search_terms.0.term'],
            'search term pi out of range' => [['search_terms' => [['term' => 'x', 'pi' => 3]]], 'search_terms.0.pi'],
            'search terms not json' => [['search_terms' => 'not json'], 'search_terms'],
            'search terms a json object' => [['search_terms' => '{"term": "x"}'], 'search_terms'],
            'outlet without domain' => [['media_outlet_urls' => [['url' => 'https://x.com', 'pi' => 0]]], 'media_outlet_urls.0.domain'],
            'outlet pi out of range' => [['media_outlet_urls' => [['domain' => 'x.com', 'pi' => -2]]], 'media_outlet_urls.0.pi'],
        ];
    }

    /**
     * @covers ::update
     */
    public function test_lists_sent_as_arrays_come_back_as_json_strings(): void
    {
        $terms = [['term' => 'climate', 'pi' => -0.25]];
        $outlets = [['domain' => 'cnbc.com', 'url' => 'https://www.cnbc.com/', 'pi' => -0.02]];

        $this->putJson($this->url(), $this->payload([
            'search_terms' => $terms,
            'media_outlet_urls' => $outlets,
        ]))->assertOk();

        $listed = $this->listedParam();
        $this->assertIsString($listed['search_terms']);
        $this->assertIsString($listed['media_outlet_urls']);
        $this->assertSame($terms, json_decode($listed['search_terms'], true));
        $this->assertSame($outlets, json_decode($listed['media_outlet_urls'], true));
    }

    /**
     * @covers ::update
     */
    public function test_an_array_and_the_equivalent_json_string_are_stored_identically(): void
    {
        $terms = [['term' => 'climate', 'pi' => -0.25]];

        $this->putJson($this->url(), $this->payload(['search_terms' => $terms]))->assertOk();
        $fromArray = $this->param->fresh()->getRawOriginal('search_terms');

        $this->putJson($this->url(), $this->payload(['search_terms' => json_encode($terms)]))->assertOk();
        $fromString = $this->param->fresh()->getRawOriginal('search_terms');

        $this->assertSame($fromString, $fromArray);
    }

    /**
     * @covers ::update
     */
    public function test_writing_back_what_a_get_returned_changes_nothing(): void
    {
        $this->putJson($this->url(), $this->payload([
            'search_terms' => [['term' => 'climate', 'pi' => -0.25]],
            'media_outlet_urls' => [['domain' => 'cnbc.com', 'url' => null, 'pi' => 0.1]],
        ]))->assertOk();
        $before = $this->listedParam();

        $this->putJson($this->url(), $this->payload([
            'search_terms' => $before['search_terms'],
            'media_outlet_urls' => $before['media_outlet_urls'],
        ]))->assertOk();

        $after = $this->listedParam();
        $this->assertSame($before['search_terms'], $after['search_terms']);
        $this->assertSame($before['media_outlet_urls'], $after['media_outlet_urls']);
    }

    /**
     * @covers ::update
     */
    public function test_ownership_columns_cannot_be_rewritten(): void
    {
        $other = Configuration::factory()->create(['user_id' => $this->user->id]);

        $this->putJson($this->url(), $this->payload([
            'configuration_id' => $other->id,
            'user_id' => $this->user->id + 999,
        ]))->assertOk();

        $this->param->refresh();
        $this->assertSame($this->configuration->id, (int) $this->param->configuration_id);
        $this->assertSame($this->user->id, (int) $this->param->user_id);
    }

    /**
     * @covers ::store
     */
    public function test_store_requires_an_existing_configuration(): void
    {
        $this->postJson('/api/v1/params', $this->payload(['configuration_id' => 999999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('configuration_id');
    }

    /**
     * @covers ::store
     */
    public function test_store_validates_and_stamps_the_caller(): void
    {
        $configuration = Configuration::factory()->create(['user_id' => $this->user->id]);

        $this->postJson('/api/v1/params', $this->payload([
            'configuration_id' => $configuration->id,
            'search_terms' => [['term' => 'climate', 'pi' => 0.3]],
        ]))->assertCreated();

        $param = $configuration->params()->sole();
        $this->assertSame($this->user->id, (int) $param->user_id);
        $this->assertSame([['term' => 'climate', 'pi' => 0.3]], json_decode($param->search_terms, true));
    }

    private function url(): string
    {
        return '/api/v1/params/'.$this->param->id;
    }

    /**
     * The param as the runner and the GUI read it: through the configuration.
     *
     * @return array<string, mixed>
     */
    private function listedParam(): array
    {
        $params = $this->getJson('/api/v1/configurations/'.$this->configuration->id)
            ->assertOk()
            ->json('data.params');

        return collect($params)->firstWhere('id', $this->param->id);
    }

    /**
     * @param  array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'pi' => 0,
            'psi' => 0,
            'kappa' => 0,
            'tau' => 0,
            'alpha' => 0,
            'beta' => 0,
        ], $overrides);
    }
}
