<?php

namespace App\Modules\Experiment\Model;

use App\Events\ModelUpdateEvent;
use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Preference\Model\Preference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Concerns\ScopesToOwner;

/**
 * @property int    $user_id
 * @property string $name
 * @property string $description
 * @property string $contact
 * @property bool   $active
 */
class Experiment extends BaseModel
{
    use ScopesToOwner;

    /**
     * Relation path from this model to the owning experiment.
     *
     * The experiment IS the ownership root.
     */
    public const OWNERSHIP_PATH = '';

    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function crawlers(): HasMany
    {
        return $this->hasMany(Crawler::class);
    }

    public function preferences(): MorphMany
    {
        return $this->morphMany(Preference::class, 'preferencable');
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
            ]);
    }
}
