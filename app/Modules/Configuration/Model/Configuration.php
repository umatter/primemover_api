<?php

namespace App\Modules\Configuration\Model;

use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Param\Model\Param;
use App\Modules\Preference\Model\Preference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @property int    $user_id
 * @property string $name
 * @property string $description
 * @property bool   $active
 */
class Configuration extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    protected $visible = ['id', 'name', 'description', 'params', 'preferences', 'crawlers_count'];

    public function crawler(): HasOne
    {
        return $this->hasOne(Crawler::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Every crawler using this configuration. crawler() above is the legacy
     * has-one; nothing enforces it, and one configuration can sit under several
     * crawlers -- in several experiments.
     */
    public function crawlers(): HasMany
    {
        return $this->hasMany(Crawler::class);
    }

    public function params(): HasMany
    {
        return $this->hasMany(Param::class);
    }

    public function preferences(): MorphMany
    {
        return $this->morphMany(Preference::class, 'preferencable');
    }

    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        return $query->where('id', 'LIKE', "%{$keyword}%")
            ->orWhere('name', 'LIKE', "%{$keyword}%")
            ->orWhere('description', 'LIKE', "%{$keyword}%");
    }

    public function scopeCrawler(Builder $query, int $crawlerId): Builder
    {
        return $query->whereHas('crawler', fn ($query) => $query->where('id', $crawlerId));
    }

    public static function queryBuilder(): QueryBuilder
    {
        return QueryBuilder::for(self::class)
            ->allowedSorts(['id', 'name', 'created_at', 'updated_at'])
            ->defaultSort('-id')
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
                AllowedFilter::scope('crawler'),
            ]);
    }
}
