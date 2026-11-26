<?php

namespace Tests\Feature\Isolation;

use App\Access\LibraryOwnership;
use App\Models\User;
use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Proxy\Model\Proxy;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\Passport;

/**
 * The Library lists show a researcher what they may attach, and nothing else.
 *
 * Unscoped, the GUI's setup pickers offered every researcher's items, and since
 * the attach rule (AttachableToCrawler) most of them were then refused. The list
 * scope and LibraryOwnership::owns() are one rule written twice (SQL and PHP), so
 * a test pins that they agree.
 *
 * @internal
 *
 * @coversDefaultClass \App\Models\Concerns\ScopesToLibraryOwner
 */
class LibraryListScopeTest extends IsolationTestCase
{
    /** @var array<string, array{0: class-string<Model>, 1: string, 2: string}> */
    private const KINDS = [
        'agent' => [Agent::class, 'agent_id', 'agents'],
        'proxy' => [Proxy::class, 'proxy_id', 'proxies'],
        'configuration' => [Configuration::class, 'configuration_id', 'configurations'],
    ];

    /** @var array<string, array<string, Model>> per kind, per ownership case */
    private array $items = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (array_keys(self::KINDS) as $kind) {
            $this->items[$kind] = [
                'mine' => $this->usedBy($kind, [$this->mine['experiment']]),
                'theirs' => $this->usedBy($kind, [$this->theirs['experiment']]),
                'shared' => $this->usedBy($kind, [$this->mine['experiment'], $this->theirs['experiment']]),
                'unattached mine' => $this->createdBy($kind, $this->researcher),
                'unattached theirs' => $this->createdBy($kind, $this->otherResearcher),
            ];
        }
        $this->enableIsolation();
        Passport::actingAs($this->researcher);
    }

    /** @return array<string, array{0: string}> */
    public static function kinds(): array
    {
        return ['agent' => ['agent'], 'proxy' => ['proxy'], 'configuration' => ['configuration']];
    }

    /**
     * @dataProvider kinds
     */
    public function test_a_researcher_lists_only_what_they_own(string $kind): void
    {
        [, , $route] = self::KINDS[$kind];

        $ids = $this->listedIds($route);

        $this->assertSame($this->idsOfCases($kind, ['mine', 'unattached mine']), $ids);
    }

    /**
     * A search ORs many columns. Laravel wraps each scope's wheres in their own group
     * (Builder::callScope), which is what keeps a match from escaping the ownership scope;
     * this pins that.
     *
     * @dataProvider kinds
     */
    public function test_a_search_cannot_widen_the_list(string $kind): void
    {
        [, , $route] = self::KINDS[$kind];
        // A token in a later column, which is how an ungrouped "owned AND id LIKE t OR name
        // LIKE t" would let their item in.
        $term = 'zqx'.$kind;
        $this->items[$kind]['theirs']->update(['name' => "their {$term} item"]);
        $this->items[$kind]['mine']->update(['name' => "my {$term} item"]);

        $ids = $this->listedIds($route.'?filter%5Bsearch%5D='.$term);

        $this->assertSame([(int) $this->items[$kind]['mine']->id], $ids);
    }

    /**
     * Edit setup loads a bot's current item by id; a legacy shared one must still show.
     *
     * @dataProvider kinds
     */
    public function test_reading_one_item_by_id_is_not_scoped(string $kind): void
    {
        [, , $route] = self::KINDS[$kind];

        $this->getJson("/api/v1/{$route}/".$this->items[$kind]['shared']->id)->assertOk();
    }

    /**
     * @dataProvider kinds
     */
    public function test_the_scope_and_owns_agree(string $kind): void
    {
        [$model, $column] = self::KINDS[$kind];

        $scoped = $model::query()->ownedByCaller()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        foreach ($this->items[$kind] as $case => $item) {
            $this->assertSame(
                LibraryOwnership::owns($this->researcher, $item, $column),
                in_array($item->id, $scoped, true),
                "{$kind}: {$case}"
            );
        }
    }

    /**
     * @dataProvider kinds
     */
    public function test_everything_is_listed_while_isolation_is_off(string $kind): void
    {
        $this->disableIsolation();
        [$model, , $route] = self::KINDS[$kind];

        $this->assertCount(min(15, $model::count()), $this->listedIds($route));
    }

    /**
     * @dataProvider kinds
     */
    public function test_the_service_account_lists_everything(string $kind): void
    {
        Passport::actingAs($this->serviceAccount);
        [$model, , $route] = self::KINDS[$kind];

        $this->assertCount(min(15, $model::count()), $this->listedIds($route));
    }

    /** @return array<int, int> */
    private function listedIds(string $route): array
    {
        $ids = $this->getJson("/api/v1/{$route}")->assertOk()->json('data.*.id');
        $ids = array_map('intval', $ids);
        sort($ids);

        return $ids;
    }

    /**
     * @param  array<int, string> $cases
     * @return array<int, int>
     */
    private function idsOfCases(string $kind, array $cases): array
    {
        $ids = array_map(fn (string $case): int => (int) $this->items[$kind][$case]->id, $cases);
        sort($ids);

        return $ids;
    }

    /** @param array<int, \App\Modules\Experiment\Model\Experiment> $experiments */
    private function usedBy(string $kind, array $experiments): Model
    {
        [, $column] = self::KINDS[$kind];
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
