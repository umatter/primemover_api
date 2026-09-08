<?php

namespace App\Modules\Report\Model;

use App\Events\ModelUpdateEvent;
use App\Models\BaseModel;
use App\Models\Concerns\ScopesToOwner;
use App\Models\User;
use App\Modules\Job\Model\Job;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @property int    $user_id
 * @property int    $job_id
 * @property string $description
 * @property string $path
 * @property string $filename
 * @property string $filecontent
 * @property string $mimetype
 */
class Report extends BaseModel
{
    use HasFactory;
    use ScopesToOwner;

    /**
     * Relation path from this model to the owning experiment.
     *
     * Reports hang off a job (reports.job_id), so the chain is the job's.
     */
    public const OWNERSHIP_PATH = 'job.queue.crawler.experiment';

    public const S3PREFIX = 's3://primemoverrunner/';

    public const S3DISK = 's3runner';

    /**
     * The mimetype the runner stamps on a POINTER row.
     *
     * Not a media type -- there is no "primemover" type registry. It marks a
     * reports row whose filecontent is NULL because the bytes live in the
     * object store at $path, which is every row the runner has written since
     * job data moved to MinIO. Serving it as the Content-Type of the download
     * would tell the browser nothing and confuse anything that reads it.
     */
    public const S3MIMETYPE = 'primemover/s3';

    /**
     * The only two key prefixes the runner writes objects under.
     *
     * From its own configuration -- primemover_runner
     * config/application.properties: awsS3.jobdata.subFolder = reports/ and
     * awsS3.finalsource.subFolder = finalsource/, both in bucket
     * primemoverrunner. Verified against the live store on 2026-09-08: all
     * 54,138 objects under reports/ and every object under finalsource/ sit
     * directly beneath one of these two.
     */
    public const OBJECT_PREFIXES = ['reports/', 'finalsource/'];

    protected $guarded = [];

    /**
     * The object key this row may be read from, or null when it may not.
     *
     * reports.path is NOT trustworthy. Every reader that resolved it used to
     * take it verbatim: ReportRepository::getFile(), JobDecisionService and the
     * job ZIP pipeline all authorised the reports ROW -- through
     * job -> queue -> crawler -> experiment -- and then streamed whatever key
     * the row happened to name. The two are not the same question. POST
     * /v1/reports lets an authenticated caller choose both job_id and path, and
     * Report is $guarded = [], so a row could be attached to a job the caller
     * owns while pointing at another researcher's HAR or decision payload; the
     * policy passed and the bytes belonged to someone else.
     *
     * The check therefore lives at READ time, not only on the write path: the
     * ~70,400 rows already in the table were written before any rule existed,
     * so a reader may not assume its input was ever validated.
     *
     * A key is readable when all of these hold:
     *
     * - it names this bucket. Either the runner's own
     *   "s3://primemoverrunner/<key>" or a bare relative key; anything with
     *   another scheme, another bucket, a leading slash or a drive letter is a
     *   pointer at something that is not the runner's object store.
     * - it stays inside it. No "..", no "." and no empty segment, so no key
     *   can be walked out of the prefix it claims to be under.
     * - it is under reports/ or finalsource/ -- the only two prefixes the
     *   runner writes.
     * - it ends in THIS report's own job id. Every object the runner writes is
     *   "<prefix>-<jobtype>-<task>-<jobId>" or "<prefix>_<jobId>"
     *   (primemover_runner StorageUtility::getIdentifier, LambdaResultWriter,
     *   SelectionWriter), and reports.job_id is the id from the same
     *   job.getId() call that named the object (CrawlerService::writeS3JobData).
     *   This is the term that actually stops the cross-tenant read: the first
     *   three only keep the key inside the bucket, which is where the other
     *   researcher's object lives too.
     *
     * null is "do not read this", never "the object is missing" -- the callers
     * turn it into a 404 or a DecisionPayloadException, which is the same
     * answer they already give for an object the store does not hold.
     */
    public function storageKey(): ?string
    {
        return self::storageKeyFor($this->path, (int) $this->job_id);
    }

    /**
     * storageKey() for values that are not (yet) a row -- the write path.
     *
     * @param mixed $path the raw candidate, from a request or a stored row
     */
    public static function storageKeyFor(mixed $path, int $jobId): ?string
    {
        if ( ! is_string($path) || $jobId <= 0) {
            return null;
        }

        $path = trim($path);

        // Control characters have no business in an object key and are how a
        // header or a log line gets split.
        if ('' === $path || 1 === preg_match('/[\\x00-\\x1f\\x7f]/', $path)) {
            return null;
        }

        if (str_starts_with($path, self::S3PREFIX)) {
            $key = mb_substr($path, mb_strlen(self::S3PREFIX));
        } elseif (self::looksAbsoluteOrForeign($path)) {
            // A URL, another bucket, a filesystem path. The legacy rows shaped
            // like "https://siaw.primemover.example/api/v1/file/1" land here: they were
            // never readable off this disk either.
            return null;
        } else {
            // A bare key. The disk is rooted at the bucket, so this is the same
            // object the prefixed form names, and rows predating the prefix
            // stay readable.
            $key = $path;
        }

        if ('' === $key || str_contains($key, '\\') || str_contains($key, '://')) {
            return null;
        }

        foreach (explode('/', $key) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                return null;
            }
        }

        if ( ! self::underObjectPrefix($key)) {
            return null;
        }

        // Ends in the job id, and the character before it is not a digit -- so
        // job 226 cannot read job 34226's object.
        if (1 !== preg_match('/(?:^|\D)'.preg_quote((string) $jobId, '/').'$/', $key)) {
            return null;
        }

        return $key;
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function booted(): void
    {
        self::saved(fn (Model $model) => self::bootedSaved($model));
    }

    public static function bootedSaved(Model $model): void
    {
        // ModelUpdateEvent::dispatch($model);
    }

    public static function queryBuilder(): QueryBuilder
    {
        return QueryBuilder::for(self::class)
            ->allowedSorts(['id', 'created_at', 'updated_at'])
            ->defaultSort('-id');
    }

    private static function looksAbsoluteOrForeign(string $path): bool
    {
        return str_contains($path, '://')
            || str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || self::hasDriveLetter($path);
    }

    /** "C:/..." or "C:\..." -- an absolute path on the other kind of host. */
    private static function hasDriveLetter(string $path): bool
    {
        return mb_strlen($path) > 2
            && ctype_alpha($path[0])
            && ':' === $path[1]
            && ('/' === $path[2] || '\\' === $path[2]);
    }

    private static function underObjectPrefix(string $key): bool
    {
        foreach (self::OBJECT_PREFIXES as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
