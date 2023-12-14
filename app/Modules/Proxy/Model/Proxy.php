<?php

namespace App\Modules\Proxy\Model;

use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int user_id
 * @property string name
 * @property string description
 * @property string hostname
 * @property string port
 * @property string username
 * @property string password
 * @property string type
 * @property bool active
 */
class Proxy extends BaseModel
{
    use HasFactory;

    public const TYPES = ['http', 'geosurf', 'brightdata', 'oxylabs'];

    protected $guarded = [];

    protected $visible = ['id', 'name', 'description', 'hostname', 'port', 'username', 'type', 'password'];

    public function crawler(): HasOne
    {
        return $this->hasOne(Crawler::class);
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
}
