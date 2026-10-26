<?php

namespace Tests\Feature\Isolation;

use App\Models\User;
use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Proxy\Model\Proxy;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\Passport;

/**
 * Which agents, proxies and configurations a researcher may attach to a bot.
 *
 * What a bot does with them is shared with every other bot using them: a
 * configuration's params are the treatment, an agent is one browser profile, a
 * proxy one exit IP. So the rule is ConfigurationPolicy's (LibraryOwnership):
 * attachable when no crawler outside the caller's experiments uses it, and
 * either one of theirs does or, unattached, they created it.
 *
 * @internal
 *
 * @coversDefaultClass \App\Rules\AttachableToCrawler
 */
class CrawlerSetupOwnershipTest extends IsolationTestCase
{
    /** @var array<string, array{0: class-string<Model>, 1: string}> */
    private const FIELDS = [
        'agent' => [Agent::class, 'agent_id'],
        'proxy' => [Proxy::class, 'proxy_id'],
        'configuration' => [Configuration::class, 'configuration_id'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // These assert the HTTP answer (422), so let the handler render it.
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
        foreach (array_keys(self::FIELDS) as $field) {
            foreach ($owners as $label => [$owner, $allowed]) {
                $cases["{$field} {$label}"] = [$field, $owner, $allowed];
            }
        }

        return $cases;
    }

    /**
     * @dataProvider cases
     */
    public function test_updating_a_bots_setup(string $field, string $owner, bool $allowed): void
    {
        [, $column] = self::FIELDS[$field];
        $item = $this->item($field, $owner);

        $response = $this->putJson('/api/v1/crawlers/'.$this->mine['crawler']->id, [
            'name' => 'renamed',
            'experiment_id' => $this->mine['experiment']->id,
            $column => $item->id,
        ]);

        if ($allowed) {
            $response->assertOk();
            $this->assertSame($item->id, (int) $this->mine['crawler']->fresh()->{$column});
        } else {
            // "not found", not "forbidden": do not confirm that someone else's id exists.
            $response->assertUnprocessable()->assertJsonValidationErrors($column);
            $this->assertNotSame($item->id, (int) $this->mine['crawler']->fresh()->{$column});
        }
    }

    /**
     * @dataProvider cases
     */
    public function test_creating_a_bot(string $field, string $owner, bool $allowed): void
    {
        [, $column] = self::FIELDS[$field];
        // The other two fields get items of my own, so only $field decides.
        $payload = ['name' => 'new bot', 'experiment_id' => $this->mine['experiment']->id];
        foreach (self::FIELDS as $other => [, $otherColumn]) {
            $payload[$otherColumn] = $this->item($other, $other === $field ? $owner : 'unattached mine')->id;
        }

        $response = $this->postJson('/api/v1/crawlers', $payload);

        if ($allowed) {
            $response->assertCreated();
        } else {
            $response->assertUnprocessable()->assertJsonValidationErrors($column);
        }
    }

    /**
     * The base fixtures share one agent, proxy and configuration across every
     * researcher's crawler -- a legacy shared setup. Keeping it is not attaching.
     *
     * @covers ::validate
     */
    public function test_resending_the_bots_current_items_passes_even_when_shared(): void
    {
        $crawler = $this->mine['crawler'];

        $this->putJson('/api/v1/crawlers/'.$crawler->id, [
            'name' => 'renamed',
            'experiment_id' => $this->mine['experiment']->id,
            'agent_id' => $crawler->agent_id,
            'proxy_id' => $crawler->proxy_id,
            'configuration_id' => $crawler->configuration_id,
        ])->assertOk();
    }

    public function test_nothing_is_enforced_while_isolation_is_off(): void
    {
        $this->disableIsolation();

        $this->putJson('/api/v1/crawlers/'.$this->mine['crawler']->id, [
            'name' => 'renamed',
            'experiment_id' => $this->mine['experiment']->id,
            'configuration_id' => $this->item('configuration', 'theirs')->id,
        ])->assertOk();
    }

    public function test_the_service_account_attaches_anything(): void
    {
        Passport::actingAs($this->serviceAccount);

        $this->putJson('/api/v1/crawlers/'.$this->mine['crawler']->id, [
            'name' => 'renamed',
            'experiment_id' => $this->mine['experiment']->id,
            'agent_id' => $this->item('agent', 'theirs')->id,
        ])->assertOk();
    }

    /**
     * A fresh item of $field, owned as $owner describes.
     */
    private function item(string $field, string $owner): Model
    {
        return match ($owner) {
            'mine' => $this->usedBy($field, [$this->mine['experiment']]),
            'theirs' => $this->usedBy($field, [$this->theirs['experiment']]),
            'shared' => $this->usedBy($field, [$this->mine['experiment'], $this->theirs['experiment']]),
            'unattached mine' => $this->createdBy($field, $this->researcher),
            'unattached theirs' => $this->createdBy($field, $this->otherResearcher),
        };
    }

    /** @param array<int, Experiment> $experiments */
    private function usedBy(string $field, array $experiments): Model
    {
        [, $column] = self::FIELDS[$field];
        // Stamped with the service account, like everything the upload pipeline creates.
        $item = $this->createdBy($field, $this->serviceAccount);

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

    private function createdBy(string $field, User $user): Model
    {
        [$model] = self::FIELDS[$field];

        return $model::factory()->create(['user_id' => $user->id]);
    }
}
