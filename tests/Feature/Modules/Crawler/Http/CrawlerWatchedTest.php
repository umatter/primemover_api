<?php

namespace Tests\Feature\Modules\Crawler\Http;

use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A bot's watch history, which the already_watched filter needs.
 *
 * That filter has been an empty TO-DO in lambda_function.py since the
 * beginning, honestly reporting not_implemented, because the chosen video lived
 * only inside the MinIO object and a bot's history was not answerable in one
 * query. jobs.decision_selected_id changed that; this endpoint is what the
 * runner reads to put the history in the lambda payload.
 *
 * Scope is "selected by this bot, EVER" -- every run, every experiment. A bot
 * that has seen a video has seen it, and that does not expire at a run
 * boundary.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Crawler\Http\Controller\Api\v1\CrawlerController
 */
class CrawlerWatchedTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Crawler $bot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->bot = Crawler::factory()->create(['user_id' => $this->owner->id]);
    }

    /**
     * @covers ::watched
     */
    public function test_it_lists_what_the_bot_chose(): void
    {
        $this->watch('aaaaaaaaaaa');
        $this->watch('bbbbbbbbbbb');

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/crawlers/{$this->bot->id}/watched")
            ->assertOk();

        $this->assertEqualsCanonicalizing(
            ['aaaaaaaaaaa', 'bbbbbbbbbbb'],
            $response->json('data')
        );
    }

    /**
     * @covers ::watched
     */
    public function test_a_video_watched_twice_appears_once(): void
    {
        // The caller filters with it; a duplicate would only make the payload
        // bigger. 150 such pairs exist in the live table.
        $this->watch('aaaaaaaaaaa');
        $this->watch('aaaaaaaaaaa');

        $this->actingAs($this->owner)
            ->getJson("/api/v1/crawlers/{$this->bot->id}/watched")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /**
     * @covers ::watched
     */
    public function test_it_crosses_runs_because_the_scope_is_ever(): void
    {
        $this->watch('aaaaaaaaaaa', $this->aRun());
        $this->watch('bbbbbbbbbbb', $this->aRun());

        $this->actingAs($this->owner)
            ->getJson("/api/v1/crawlers/{$this->bot->id}/watched")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * @covers ::watched
     */
    public function test_another_bots_history_is_not_included(): void
    {
        $other = Crawler::factory()->create(['user_id' => $this->owner->id]);
        $otherRun = Queue::factory()->create([
            'crawler_id' => $other->id,
            'user_id' => $this->owner->id,
        ]);

        $this->watch('aaaaaaaaaaa');
        $this->watch('zzzzzzzzzzz', $otherRun);

        $this->actingAs($this->owner)
            ->getJson("/api/v1/crawlers/{$this->bot->id}/watched")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['aaaaaaaaaaa']);
    }

    /**
     * @covers ::watched
     */
    public function test_jobs_with_no_recorded_video_are_left_out(): void
    {
        // NULL is unknown, not a video. Most jobs never call the lambda at all.
        Job::factory()->create([
            'queue_id' => $this->aRun()->id,
            'user_id' => $this->owner->id,
            'decision_selected_id' => null,
        ]);

        $this->actingAs($this->owner)
            ->getJson("/api/v1/crawlers/{$this->bot->id}/watched")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @covers ::watched
     */
    public function test_a_bot_that_has_watched_nothing_answers_with_an_empty_list(): void
    {
        // Distinct from the 409 below: here the column exists and the answer is
        // genuinely "nothing yet".
        $this->actingAs($this->owner)
            ->getJson("/api/v1/crawlers/{$this->bot->id}/watched")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    private function aRun(): Queue
    {
        return Queue::factory()->create([
            'crawler_id' => $this->bot->id,
            'user_id' => $this->owner->id,
        ]);
    }

    private function watch(string $videoId, ?Queue $run = null): Job
    {
        return Job::factory()->create([
            'queue_id' => ($run ?? $this->aRun())->id,
            'user_id' => $this->owner->id,
            'decision_selected_id' => $videoId,
        ]);
    }
}
