<?php

namespace Tests\Feature\Modules\Queue\Model;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Queue\Model\Queue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A queue's start_at is the INSTANT the caller meant, not its digits.
 *
 * start_at, started_at and finished_at are varchar columns with a datetime
 * cast, so Eloquent parsed the incoming string and formatted it back using the
 * Carbon's own zone. An offset survived parsing and was then discarded. Probed
 * against the live API before this change, every case:
 *
 *     sent 2030-01-15T05:00:00+01:00  ->  stored 2030-01-15 05:00:00
 *     sent 2030-01-15T04:00:00Z       ->  stored 2030-01-15 04:00:00   1h early
 *     sent 2030-01-15T05:00:00        ->  stored 2030-01-15 05:00:00
 *     sent 2030-07-15T05:00:00+02:00  ->  stored 2030-07-15 05:00:00
 *     sent 2030-07-15T03:00:00Z       ->  stored 2030-07-15 03:00:00   2h early
 *     sent 2030-07-15T05:00:00-05:00  ->  stored 2030-07-15 05:00:00   7h early
 *
 * Nothing errored. The queue started at the wrong time, or landed in the past
 * and had its jobs marked skipped -- a symptom that points at the runner, not
 * at a timestamp, which is what made it expensive.
 *
 * The half of this suite that matters most is the unchanged half: every client
 * writing today sends either a naive local time (the GUI, the MCP) or the app
 * zone with its matching offset (the spec DSL, the e2e, the canary), and none
 * of them may move by a second.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Queue\Model\Queue
 */
class StartAtTimezoneTest extends TestCase
{
    use RefreshDatabase;

    /** Named to avoid shadowing TestCase::$crawler, which is protected. */
    private Crawler $tzCrawler;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Europe/Zurich']);

        $user = User::factory()->create();
        $this->tzCrawler = Crawler::factory()->create(['user_id' => $user->id]);
    }

    /**
     * @dataProvider timestamps
     *
     * @covers ::setStartAtAttribute
     */
    public function test_start_at_is_stored_as_the_application_wall_clock(
        string $sent,
        string $expected,
        string $why
    ): void {
        $queue = Queue::query()->create([
            'name' => 'tz',
            'description' => 'tz',
            'crawler_id' => $this->tzCrawler->id,
            'start_at' => $sent,
        ]);

        $this->assertSame($expected, $queue->fresh()->getRawOriginal('start_at'), $why);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function timestamps(): array
    {
        return [
            // The cases that were WRONG. Each is the same instant as the
            // matching correct row below it.
            'UTC in winter' => [
                '2030-01-15T04:00:00Z', '2030-01-15 05:00:00',
                'Z is an instant; it was stored as 04:00, an hour early',
            ],
            'UTC in summer' => [
                '2030-07-15T03:00:00Z', '2030-07-15 05:00:00',
                'Z is an instant; it was stored as 03:00, two hours early',
            ],
            'a distant zone' => [
                '2030-07-15T05:00:00-05:00', '2030-07-15 12:00:00',
                'was stored as 05:00, seven hours early',
            ],

            // The cases that must NOT move. These are what every client sends.
            'the app zone in winter' => [
                '2030-01-15T05:00:00+01:00', '2030-01-15 05:00:00',
                'the spec DSL and the e2e send exactly this',
            ],
            'the app zone in summer' => [
                '2030-07-15T05:00:00+02:00', '2030-07-15 05:00:00',
                'the spec DSL and the e2e send exactly this',
            ],
            'a naive local time' => [
                '2030-07-15T05:00:00', '2030-07-15 05:00:00',
                'the GUI and the MCP send this; its digits ARE its wall clock',
            ],
            'a naive local time, space separated' => [
                '2030-07-15 05:00:00', '2030-07-15 05:00:00',
                'the runner echoes back what it read from the API',
            ],
        ];
    }

    /**
     * @covers ::setStartAtAttribute
     */
    public function test_dst_is_resolved_per_date_and_not_by_a_fixed_offset(): void
    {
        // The same UTC hour is +01:00 in January and +02:00 in July. A fixed
        // offset would put one of them an hour out -- which is exactly how the
        // e2e broke before it started using zoneinfo.
        $winter = $this->queueWith('2030-01-15T12:00:00Z');
        $summer = $this->queueWith('2030-07-15T12:00:00Z');

        $this->assertSame('2030-01-15 13:00:00', $winter);
        $this->assertSame('2030-07-15 14:00:00', $summer);
    }

    /**
     * @covers ::setStartAtAttribute
     */
    public function test_an_unparseable_value_is_kept_rather_than_rejected(): void
    {
        // These are varchar columns and the runner has been seen writing odd
        // things into them. Losing a status update over a bad timestamp would
        // trade a wrong start time for no update at all.
        $this->assertSame('not a timestamp', $this->queueWith('not a timestamp'));
    }

    /**
     * @covers ::setStartAtAttribute
     */
    public function test_null_stays_null(): void
    {
        $this->assertNull($this->queueWith(null));
    }

    private function queueWith(?string $startAt): ?string
    {
        $queue = Queue::query()->create([
            'name' => 'tz',
            'description' => 'tz',
            'crawler_id' => $this->tzCrawler->id,
            'start_at' => $startAt,
        ]);

        return $queue->fresh()->getRawOriginal('start_at');
    }
}
