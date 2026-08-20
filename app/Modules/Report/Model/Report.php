<?php

namespace App\Modules\Report\Model;

use App\Events\ModelUpdateEvent;
use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Job\Model\Job;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Concerns\ScopesToOwner;

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
    use ScopesToOwner;

    /**
     * Relation path from this model to the owning experiment.
     *
     * Reports hang off a job (reports.job_id), so the chain is the job's.
     */
    public const OWNERSHIP_PATH = 'job.queue.crawler.experiment';

    use HasFactory;

    public const S3PREFIX = 's3://primemoverrunner/';

    public const S3DISK = 's3runner';

    protected $guarded = [];

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
        //ModelUpdateEvent::dispatch($model);
    }

    public static function queryBuilder(): QueryBuilder
    {
        return QueryBuilder::for(self::class)
            ->allowedSorts(['id', 'created_at', 'updated_at'])
            ->defaultSort('-id');
    }
}
