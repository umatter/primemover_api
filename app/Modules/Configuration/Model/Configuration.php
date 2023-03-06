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

/**
 * @property int user_id
 * @property string name
 * @property string description
 * @property bool active
 */
class Configuration extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    protected $visible = ['id', 'name', 'description', 'params', 'preferences'];

    public function crawler(): HasOne
    {
        return $this->hasOne(Crawler::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
