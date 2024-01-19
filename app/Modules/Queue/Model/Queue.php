<?php

namespace App\Modules\Queue\Model;

use App\Events\ModelUpdateEvent;
use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Job\Model\Job;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @property int    $user_id
 * @property int    $crawler_id
 * @property string $name
 * @property string $description
 * @property bool   $new
 * @property bool   $processed
 * @property bool   $processing
 * @property bool   $reviewed
 * @property string $status_code
 * @property string $status_message
 * @property bool   $active
 * @property int    $order
 * @property Carbon $start_at
 * @property Carbon $started_at
 * @property Carbon $finished_at
 */
class Queue extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function crawler(): BelongsTo
    {
        return $this->belongsTo(Crawler::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class)
            ->with('behaviors', 'reports:id,job_id,filename,path,created_at,updated_at');
    }

    public function scopeCrawler(Builder $query, int $crawlerId): Builder
    {
        return $query->where('crawler_id', $crawlerId);
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
            ->allowedSorts(['id', 'name', 'created_at', 'updated_at'])
            ->defaultSort('-id')
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
                AllowedFilter::scope('crawler'),
            ]);
    }
}
