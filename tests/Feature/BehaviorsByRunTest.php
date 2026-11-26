<?php

namespace Tests\Feature;

use App\Modules\Behavior\Model\Behavior;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * GET behaviors?filter[queue]= and per_page: one call for a whole run's behaviours.
 *
 * The GUI's run composer flags jobs missing a required behaviour, which needs
 * every behaviour of the run. One request per job, ten rows a page, did not
 * scale to a 113-job run.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Behavior\Repository\BehaviorRepository
 */
class BehaviorsByRunTest extends TestCase
{
    /**
     * @covers ::all
     */
    public function test_filter_by_queue_returns_the_runs_behaviours_and_only_those(): void
    {
        $run = Queue::factory()->create(['crawler_id' => $this->crawler->id]);
        $other = Queue::factory()->create(['crawler_id' => $this->crawler->id]);
        $mine = $this->behavioursFor(Job::factory()->count(3)->create(['queue_id' => $run->id]), 2);
        $this->behavioursFor(Job::factory()->count(2)->create(['queue_id' => $other->id]), 2);

        Passport::actingAs($this->user);

        $ids = $this->getJson("/api/v1/behaviors?filter[queue]={$run->id}&per_page=100")
            ->assertOk()
            ->json('data.*.id');

        sort($ids);
        $this->assertSame($mine, $ids);
    }

    /**
     * @covers ::all
     */
    public function test_per_page_is_honoured_clamped_and_defaults_to_ten(): void
    {
        $run = Queue::factory()->create(['crawler_id' => $this->crawler->id]);
        $this->behavioursFor(Job::factory()->count(6)->create(['queue_id' => $run->id]), 2);

        Passport::actingAs($this->user);
        $url = "/api/v1/behaviors?filter[queue]={$run->id}";

        $this->getJson($url)->assertOk()->assertJsonCount(10, 'data');
        $this->getJson($url.'&per_page=12')->assertOk()->assertJsonCount(12, 'data');
        $this->getJson($url.'&per_page=0')->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame(500, $this->getJson($url.'&per_page=100000')->assertOk()->json('meta.per_page'));
    }

    /**
     * @covers ::all
     */
    public function test_filter_by_job_still_works(): void
    {
        $job = Job::factory()->create(['queue_id' => $this->queue->id]);
        $ids = $this->behavioursFor(collect([$job]), 3);

        Passport::actingAs($this->user);

        $got = $this->getJson("/api/v1/behaviors?filter[job]={$job->id}")->assertOk()->json('data.*.id');
        sort($got);
        $this->assertSame($ids, $got);
    }

    /**
     * @param  iterable<Job>   $jobs
     * @return array<int, int> the created ids, ascending
     */
    private function behavioursFor(iterable $jobs, int $each): array
    {
        $ids = [];
        foreach ($jobs as $job) {
            for ($i = 0; $i < $each; $i++) {
                $ids[] = Behavior::query()->create([
                    'name' => "b{$i}",
                    'description' => 'd',
                    'value' => 'v',
                    'active' => 1,
                    'job_id' => $job->id,
                    'user_id' => $this->user->id,
                ])->id;
            }
        }
        sort($ids);

        return $ids;
    }
}
