<?php

namespace Tests\Feature\RunnerAssignment;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Repository\CrawlerRepository;

/**
 * Two runners, one API: each must fetch only the experiments assigned to it.
 * crawlers-unprocessed has no atomic claim, so an overlap means every queue
 * runs twice. NULL assignment belongs to the default runner.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Crawler\Model\Crawler
 */
class WorkPartitionTest extends RunnerAssignmentTestCase
{
    /**
     * @covers ::scopeAssignedToCallingRunner
     */
    public function test_default_runner_gets_unassigned_and_own_assignments_only(): void
    {
        $this->enableIsolation();
        $this->actingAs($this->defaultRunner);

        $ids = $this->scopedCrawlerIds();

        $this->assertContains((int) $this->unassigned['crawler']->id, $ids);
        $this->assertContains((int) $this->forDefault['crawler']->id, $ids);
        $this->assertNotContains((int) $this->forSecond['crawler']->id, $ids);
    }

    /**
     * @covers ::scopeAssignedToCallingRunner
     */
    public function test_second_runner_gets_only_its_assignments(): void
    {
        $this->enableIsolation();
        $this->actingAs($this->secondRunner);

        $ids = $this->scopedCrawlerIds();

        $this->assertSame([(int) $this->forSecond['crawler']->id], $ids);
    }

    /**
     * The scope is a partitioning rule, not an isolation rule: it applies with
     * isolation off too, or a second runner on an un-isolated API would double-run.
     *
     * @covers ::scopeAssignedToCallingRunner
     */
    public function test_partition_applies_with_isolation_disabled(): void
    {
        $this->disableIsolation();
        $this->actingAs($this->secondRunner);

        $this->assertSame([(int) $this->forSecond['crawler']->id], $this->scopedCrawlerIds());
    }

    /**
     * @covers ::scopeAssignedToCallingRunner
     */
    public function test_a_non_runner_caller_gets_nothing(): void
    {
        $this->enableIsolation();
        $this->actingAs($this->researcher);

        $this->assertSame([], $this->scopedCrawlerIds());
    }

    /**
     * @covers ::scopeAssignedToCallingRunner
     */
    public function test_a_guest_gets_nothing(): void
    {
        $this->enableIsolation();
        // Don't call actingAs() to test as a guest

        $this->assertSame([], $this->scopedCrawlerIds());
    }

    /**
     * @covers \App\Modules\Crawler\Repository\CrawlerRepository::unprocessed
     */
    public function test_unprocessed_is_partitioned_per_runner(): void
    {
        $this->enableIsolation();
        $repo = new CrawlerRepository(new Crawler());

        $this->actingAs($this->defaultRunner);
        $forDefault = $this->idsOf($repo->unprocessed());
        $this->assertContains((int) $this->unassigned['crawler']->id, $forDefault);
        $this->assertContains((int) $this->forDefault['crawler']->id, $forDefault);
        $this->assertNotContains((int) $this->forSecond['crawler']->id, $forDefault);

        $this->actingAs($this->secondRunner);
        $this->assertSame([(int) $this->forSecond['crawler']->id], $this->idsOf($repo->unprocessed()));
    }

    /**
     * @covers \App\Modules\Crawler\Repository\CrawlerRepository::testing
     */
    public function test_testing_is_partitioned_per_runner(): void
    {
        $this->enableIsolation();
        foreach ([$this->unassigned, $this->forDefault, $this->forSecond] as $tree) {
            $tree['crawler']->update(['testing' => 1]);
        }
        $repo = new CrawlerRepository(new Crawler());

        $this->actingAs($this->secondRunner);
        $this->assertSame([(int) $this->forSecond['crawler']->id], $this->idsOf($repo->testing()->items()));

        $this->actingAs($this->defaultRunner);
        $forDefault = $this->idsOf($repo->testing()->items());
        $this->assertContains((int) $this->unassigned['crawler']->id, $forDefault);
        $this->assertNotContains((int) $this->forSecond['crawler']->id, $forDefault);
    }

    /**
     * Through HTTP, as the Java runner does it.
     */
    public function test_endpoints_serve_each_runner_its_own_slice(): void
    {
        $this->enableIsolation();

        $this->actingAs($this->secondRunner)
            ->getJson('/api/v1/crawlers-unprocessed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->forSecond['crawler']->id);

        $response = $this->actingAs($this->defaultRunner)->getJson('/api/v1/crawlers-unprocessed')->assertOk();
        $ids = array_map('intval', array_column($response->json('data'), 'id'));
        $this->assertNotContains((int) $this->forSecond['crawler']->id, $ids);
        $this->assertContains((int) $this->unassigned['crawler']->id, $ids);
    }

    /** @return array<int, int> */
    private function scopedCrawlerIds(): array
    {
        return Crawler::query()
            ->assignedToCallingRunner()
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
