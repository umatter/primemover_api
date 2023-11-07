<?php

namespace App\Modules\Report\Model;

use App\Events\ModelUpdateEvent;
use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Job\Model\Job;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        ModelUpdateEvent::dispatch($model);
    }
}
