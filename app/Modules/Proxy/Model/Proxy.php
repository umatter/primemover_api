<?php

namespace App\Modules\Proxy\Model;

use App\Access\Isolation;
use App\Models\BaseModel;
use App\Models\Concerns\ScopesToLibraryOwner;
use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @property int    $user_id
 * @property string $name
 * @property string $description
 * @property string $hostname
 * @property string $port
 * @property string $username
 * @property string $password
 * @property string $type
 * @property bool   $active
 */
class Proxy extends BaseModel
{
    use HasFactory;
    use ScopesToLibraryOwner;

    public const TYPES = ['http', 'brightdata', 'oxylabs'];

    protected $guarded = [];

    protected $visible = ['id', 'name', 'description', 'hostname', 'port', 'username', 'type', 'password'];

    /**
     * The password goes only to callers that bypass isolation: the runners, which
     * log in as service accounts and need it to open the connection. A researcher
     * never needs it back -- the GUI only writes it -- and returning it gave every
     * login every proxy's credentials (umatter/primemover_api#96). Nothing changes
     * while isolation is off.
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        if ( ! Isolation::bypasses()) {
            unset($array['password']);
        }

        return $array;
    }

    public function crawler(): HasOne
    {
        return $this->hasOne(Crawler::class);
    }

    /** Every crawler using this item; crawler -> item is not unique. */
    public function crawlers(): HasMany
    {
        return $this->hasMany(Crawler::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        return $query->where('id', 'LIKE', "%{$keyword}%")
            ->orWhere('name', 'LIKE', "%{$keyword}%")
            ->orWhere('hostname', 'LIKE', "%{$keyword}%")
            ->orWhere('type', 'LIKE', "%{$keyword}%")
            ->orWhere('username', 'LIKE', "%{$keyword}%")
            ->orWhere('description', 'LIKE', "%{$keyword}%");
    }

    public function scopeCrawler(Builder $query, int $crawlerId): Builder
    {
        return $query->whereHas('crawler', fn ($query) => $query->where('id', $crawlerId));
    }

    public static function queryBuilder(): QueryBuilder
    {
        return QueryBuilder::for(Proxy::class)
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
