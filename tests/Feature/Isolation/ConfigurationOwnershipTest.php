<?php

namespace Tests\Feature\Isolation;

use App\Models\User;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Param\Model\Param;
use Laravel\Passport\Passport;

/**
 * Who may write a configuration and its params.
 *
 * The params ARE the treatment, so rewriting another researcher's is rewriting
 * their experiment. Configurations are not owned through a single chain: nothing
 * stops two crawlers -- in two researchers' experiments -- sharing one (the base
 * fixtures do exactly that). A researcher therefore owns a configuration only
 * when no crawler outside their experiments uses it, and either one of theirs
 * does or, unattached, they created it.
 *
 * @internal
 *
 * @coversDefaultClass \App\Policies\ConfigurationPolicy
 */
class ConfigurationOwnershipTest extends IsolationTestCase
{
    /** @var array<string, Configuration> */
    private array $configs = [];

    protected function setUp(): void
    {
        parent::setUp();
        // These assert the HTTP answer (403, 422), so let the handler render it.
        $this->withExceptionHandling();

        $this->configs = [
            'mine' => $this->configurationUsedBy([$this->mine['experiment']]),
            'theirs' => $this->configurationUsedBy([$this->theirs['experiment']]),
            'shared' => $this->configurationUsedBy([$this->mine['experiment'], $this->theirs['experiment']]),
            'unattached mine' => $this->configurationCreatedBy($this->researcher),
            'unattached theirs' => $this->configurationCreatedBy($this->otherResearcher),
        ];

        $this->enableIsolation();
        Passport::actingAs($this->researcher);
    }

    /** @return array<string, array{0: string, 1: bool}> */
    public static function cases(): array
    {
        return [
            'used only by my experiment' => ['mine', true],
            'used only by theirs' => ['theirs', false],
            'shared with theirs' => ['shared', false],
            'unattached, created by me' => ['unattached mine', true],
            'unattached, created by them' => ['unattached theirs', false],
        ];
    }

    /**
     * @dataProvider cases
     */
    public function test_updating_params(string $key, bool $allowed): void
    {
        $response = $this->putJson('/api/v1/params/'.$this->paramOf($key)->id, ['pi' => 0.4]);

        $allowed ? $response->assertOk() : $response->assertForbidden();
    }

    /**
     * @dataProvider cases
     */
    public function test_deleting_params(string $key, bool $allowed): void
    {
        $response = $this->deleteJson('/api/v1/params/'.$this->paramOf($key)->id);

        $allowed ? $response->assertOk() : $response->assertForbidden();
    }

    /**
     * @dataProvider cases
     */
    public function test_creating_params(string $key, bool $allowed): void
    {
        $response = $this->postJson('/api/v1/params', ['configuration_id' => $this->configs[$key]->id]);

        if ($allowed) {
            $response->assertCreated();
        } else {
            // "not found", not "forbidden": do not confirm that someone else's id exists.
            $response->assertUnprocessable()->assertJsonValidationErrors('configuration_id');
        }
    }

    /**
     * @dataProvider cases
     */
    public function test_updating_the_configuration(string $key, bool $allowed): void
    {
        $configuration = $this->configs[$key];

        $response = $this->putJson('/api/v1/configurations/'.$configuration->id, [
            'name' => $configuration->name,
            'active' => 1,
            'preferences' => [['name' => 'location', 'value' => 'CH-BE-BERN']],
        ]);

        $allowed ? $response->assertOk() : $response->assertForbidden();
    }

    public function test_nothing_is_enforced_while_isolation_is_off(): void
    {
        $this->disableIsolation();

        $this->putJson('/api/v1/params/'.$this->paramOf('theirs')->id, ['pi' => 0.4])->assertOk();
    }

    public function test_the_service_account_writes_anything(): void
    {
        Passport::actingAs($this->serviceAccount);

        $this->putJson('/api/v1/params/'.$this->paramOf('theirs')->id, ['pi' => 0.4])->assertOk();
    }

    /** @param array<int, Experiment> $experiments */
    private function configurationUsedBy(array $experiments): Configuration
    {
        // Stamped with the service account, like everything the upload pipeline creates.
        $configuration = $this->configurationCreatedBy($this->serviceAccount);

        foreach ($experiments as $experiment) {
            Crawler::factory()->create([
                'user_id' => $this->serviceAccount->id,
                'experiment_id' => $experiment->id,
                'proxy_id' => $this->proxy->id,
                'agent_id' => $this->agent->id,
                'configuration_id' => $configuration->id,
            ]);
        }

        return $configuration;
    }

    private function configurationCreatedBy(User $user): Configuration
    {
        $configuration = Configuration::factory()->create(['user_id' => $user->id]);
        Param::factory()->create(['user_id' => $user->id, 'configuration_id' => $configuration->id]);

        return $configuration;
    }

    private function paramOf(string $key): Param
    {
        return $this->configs[$key]->params()->sole();
    }
}
