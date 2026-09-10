<?php

namespace Tests\Feature\Modules\Job\Service;

use App\Modules\Job\Model\Job;
use App\Modules\Job\Service\DecisionVerdict;
use App\Modules\Job\Service\JobDecisionService;
use Tests\TestCase;

/**
 * Which video the decision actually chose.
 *
 * The verdict columns say whether a treatment applied. Nothing said what the
 * bot then watched: decision_report carries applied/filters/llm/requested/
 * warnings, and the chosen video lived only inside the MinIO object. So "which
 * videos has bot X watched?" -- close to the central question of a
 * personalisation study -- meant decoding thousands of zips, and the
 * already_watched filter could not be written at all.
 *
 * NULL is UNKNOWN here, never "watched nothing". A payload from before the
 * field existed, an unreadable object, and a decision that genuinely selected
 * nothing are indistinguishable through this column; the last is already
 * reported by degraded with an empty xpath.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Job\Service\DecisionVerdict
 */
class SelectedVideoTest extends TestCase
{
    /**
     * @covers ::fromPayload
     */
    public function test_it_reads_the_chosen_video_id(): void
    {
        $verdict = DecisionVerdict::fromPayload([
            'degraded' => false,
            'selected' => ['id' => 'XcHVvv06WVI', 'title' => 'something'],
        ]);

        $this->assertSame('XcHVvv06WVI', $verdict->selectedId);
    }

    /**
     * @dataProvider payloadsWithoutAnId
     *
     * @covers ::fromPayload
     */
    public function test_it_is_null_when_no_id_can_be_read(array $payload, string $why): void
    {
        $this->assertNull(DecisionVerdict::fromPayload($payload)->selectedId, $why);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function payloadsWithoutAnId(): array
    {
        return [
            'no selected key at all' => [
                ['degraded' => false],
                'a payload predating the field is unknown, not "watched nothing"',
            ],
            'selected is not a map' => [
                ['degraded' => false, 'selected' => 'XcHVvv06WVI'],
                'a shape this does not understand must not be guessed at',
            ],
            'no id inside' => [
                ['degraded' => false, 'selected' => ['title' => 'something']],
                'a title is not an id',
            ],
            'an empty id' => [
                ['degraded' => false, 'selected' => ['id' => '   ']],
                'whitespace is not an id',
            ],
            'a non-string id' => [
                ['degraded' => false, 'selected' => ['id' => 12345]],
                'the column is a string; a number here means a producer changed',
            ],
        ];
    }

    /**
     * @covers ::fromPayload
     */
    public function test_it_is_trimmed(): void
    {
        $verdict = DecisionVerdict::fromPayload([
            'degraded' => false,
            'selected' => ['id' => " XcHVvv06WVI\n"],
        ]);

        $this->assertSame('XcHVvv06WVI', $verdict->selectedId);
    }

    /**
     * @covers \App\Modules\Job\Service\JobDecisionService::columnsFor
     */
    public function test_the_column_is_written_when_the_schema_has_it(): void
    {
        $columns = app(JobDecisionService::class)->columnsFor(DecisionVerdict::fromPayload([
            'degraded' => false,
            'selected' => ['id' => 'XcHVvv06WVI'],
        ]));

        $this->assertArrayHasKey('decision_selected_id', $columns);
        $this->assertSame('XcHVvv06WVI', $columns['decision_selected_id']);
    }

    /**
     * @covers \App\Modules\Job\Service\JobDecisionService::columnsFor
     */
    public function test_a_reportless_payload_writes_nothing_at_all(): void
    {
        // Unchanged rule: no degradation report means the verdict is unknown and
        // no column is written, the selected id included.
        $columns = app(JobDecisionService::class)->columnsFor(
            DecisionVerdict::fromPayload(['selected' => ['id' => 'XcHVvv06WVI']])
        );

        $this->assertSame([], $columns);
    }

    /**
     * @covers \App\Modules\Job\Model\Job::hasSelectedIdColumn
     */
    public function test_the_schema_probe_is_separate_from_the_other_four(): void
    {
        // They arrive in different migrations, so one probe cannot answer for
        // both -- and writing a column that does not exist fails the runner's
        // whole status PUT.
        $this->assertNotSame(Job::DECISION_CAPABILITY_COLUMN, Job::SELECTED_ID_COLUMN);
        $this->assertTrue(Job::hasSelectedIdColumn(), 'the test schema is migrated');
    }
}
