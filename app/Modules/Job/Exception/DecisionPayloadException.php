<?php

namespace App\Modules\Job\Exception;

/**
 * The decision payload for a job exists but could not be read.
 *
 * Deliberately distinct from "this job has no decision payload". An
 * unreachable or corrupt object store is MISSING EVIDENCE about a job, never
 * evidence that the job was fine, so the two cases must not collapse into the
 * same 404 -- one says "nothing was recorded", the other says "something was
 * recorded and we cannot show it to you".
 */
class DecisionPayloadException extends JobException {}
