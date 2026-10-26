<?php

namespace Tests\Feature\Isolation;

use App\Models\User;
use App\Modules\Agent\Model\Agent;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Proxy\Model\Proxy;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\Passport;

/**
 * Who may change or delete an agent or a proxy.
 *
 * An agent is one Multilogin browser profile, a proxy one exit IP, and every bot
 * using it shares it: rewriting one in place changes every study that uses it.
 * The rule is the one for attaching (LibraryOwnership): no crawler outside your
 * experiments uses it, and either one of yours does or, unused, you created it.
 *
 * @internal
 *
 * @coversDefaultClass \App\Policies\AgentPolicy
 */
class LibraryWriteOwnershipTest extends IsolationTestCase
{
    /** @var array<string, array{0: class-string<Model>, 1: string, 2: string, 3: array<string, mixed>}> */
    private const KINDS = [
        'agent' => [Agent::class, 'agent_id', 'agents', ['name' => 'renamed', 'identification' => 'chrome']],
        'proxy' => [Proxy::class, 'proxy_id', 'proxies', ['name' => 'renamed', 'hostname' => 'gate.example.org']],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // These assert the HTTP answer (403, 422), so let the handler render it.
        $this->withExceptionHandling();
        $this->enableIsolation();
        Passport::actingAs($this->researcher);
    }

    /** @return array<string, array{0: string, 1: string, 2: bool}> */
    public static function cases(): array
    {
        $owners = [
            'used only by my experiment' => ['mine', true],
            'used only by theirs' => ['theirs', false],
            'shared with theirs' => ['shared', false],
            'unattached, created by me' => ['unattached mine', true],
            'unattached, created by them' => ['unattached theirs', false],
        ];

        $cases = [];
        foreach (array_keys(self::KINDS) as $kind) {
            foreach ($owners as $label => [$owner, $allowed]) {
                $cases["{$kind} {$label}"] = [$kind, $owner, $allowed];
            }
        }

        return $cases;
    }

    /**
     * @dataProvider cases
     */
    public function test_updating(string $kind, string $owner, bool $allowed): void
    {
        [, , $route, $payload] = self::KINDS[$kind];
        $item = $this->item($kind, $owner);

        $response = $this->putJson("/api/v1/{$route}/{$item->id}", $payload);

        $allowed ? $response->assertOk() : $response->assertForbidden();
        $this->assertSame($allowed, 'renamed' === $item->fresh()->name);
    }

    /**
     * @dataProvider cases
     */
    public function test_deleting(string $kind, string $owner, bool $allowed): void
    {
        [, , $route] = self::KINDS[$kind];
        $item = $this->item($kind, $owner);

        $response = $this->deleteJson("/api/v1/{$route}/{$item->id}");

        $allowed ? $response->assertOk() : $response->assertForbidden();
        $this->assertSame($allowed, null === $item->fresh());
    }

    /**
     * Writing user_id would let anyone claim an unused item, and then attach it.
     *
     * @covers \App\Modules\Agent\Http\Request\AgentUpdateRequest
     * @covers \App\Modules\Proxy\Http\Request\ProxyUpdateRequest
     */
    public function test_user_id_is_never_written(): void
    {
        foreach (self::KINDS as $kind => [, , $route, $payload]) {
            $item = $this->item($kind, 'unattached mine');

            $this->putJson("/api/v1/{$route}/{$item->id}", [...$payload, 'user_id' => $this->otherResearcher->id])
                ->assertOk();

            $this->assertSame($this->researcher->id, (int) $item->fresh()->user_id, $kind);
        }
    }

    /**
     * @covers \App\Modules\Proxy\Http\Request\ProxyUpdateRequest
     */
    public function test_a_proxy_update_is_validated_and_drops_unknown_keys(): void
    {
        $proxy = $this->item('proxy', 'unattached mine');

        $this->putJson("/api/v1/proxies/{$proxy->id}", ['port' => 'not a port'])
            ->assertUnprocessable()->assertJsonValidationErrors('port');

        $this->putJson("/api/v1/proxies/{$proxy->id}", ['port' => 8080, 'id' => 999999, 'created_at' => '2001-01-01'])
            ->assertOk();

        $fresh = $proxy->fresh();
        $this->assertSame(8080, (int) $fresh->port);
        $this->assertSame($proxy->id, $fresh->id);
        $this->assertNotSame('2001-01-01', $fresh->created_at?->toDateString());
    }

    public function test_nothing_is_enforced_while_isolation_is_off(): void
    {
        $this->disableIsolation();

        foreach (self::KINDS as $kind => [, , $route, $payload]) {
            $this->putJson("/api/v1/{$route}/".$this->item($kind, 'theirs')->id, $payload)->assertOk();
        }
    }

    public function test_the_service_account_writes_anything(): void
    {
        Passport::actingAs($this->serviceAccount);

        foreach (self::KINDS as $kind => [, , $route, $payload]) {
            $this->putJson("/api/v1/{$route}/".$this->item($kind, 'theirs')->id, $payload)->assertOk();
            $this->deleteJson("/api/v1/{$route}/".$this->item($kind, 'theirs')->id)->assertOk();
        }
    }

    private function item(string $kind, string $owner): Model
    {
        return match ($owner) {
            'mine' => $this->usedBy($kind, [$this->mine['experiment']]),
            'theirs' => $this->usedBy($kind, [$this->theirs['experiment']]),
            'shared' => $this->usedBy($kind, [$this->mine['experiment'], $this->theirs['experiment']]),
            'unattached mine' => $this->createdBy($kind, $this->researcher),
            'unattached theirs' => $this->createdBy($kind, $this->otherResearcher),
        };
    }

    /** @param array<int, Experiment> $experiments */
    private function usedBy(string $kind, array $experiments): Model
    {
        [, $column] = self::KINDS[$kind];
        // Stamped with the service account, like everything the upload pipeline creates.
        $item = $this->createdBy($kind, $this->serviceAccount);

        foreach ($experiments as $experiment) {
            Crawler::factory()->create([
                'user_id' => $this->serviceAccount->id,
                'experiment_id' => $experiment->id,
                'agent_id' => $this->agent->id,
                'proxy_id' => $this->proxy->id,
                'configuration_id' => $this->configuration->id,
                $column => $item->id,
            ]);
        }

        return $item;
    }

    private function createdBy(string $kind, User $user): Model
    {
        [$model] = self::KINDS[$kind];

        return $model::factory()->create(['user_id' => $user->id]);
    }
}
