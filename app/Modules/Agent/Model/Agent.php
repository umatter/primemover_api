<?php

namespace App\Modules\Agent\Model;

use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Preference\Model\Preference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int user_id
 * @property string name
 * @property string description
 * @property string identification
 * @property string multilogin_id
 * @property string multilogin_profile
 * @property string location
 * @property bool active
 */
class Agent extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    protected $visible = ['id', 'name', 'description', 'identification', 'multilogin_id', 'multilogin_profile', 'location', 'preferences', 'active'];

    public function crawler(): HasOne
    {
        return $this->hasOne(Crawler::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preferences(): MorphMany
    {
        return $this->morphMany(Preference::class, 'preferencable');
    }

    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        return $query->where('id', 'LIKE', "%{$keyword}%")
            ->orWhere('name', 'LIKE', "%{$keyword}%")
            ->orWhere('identification', 'LIKE', "%{$keyword}%")
            ->orWhere('multilogin_id', 'LIKE', "%{$keyword}%")
            ->orWhere('multilogin_profile', 'LIKE', "%{$keyword}%")
            ->orWhere('location', 'LIKE', "%{$keyword}%")
            ->orWhere('description', 'LIKE', "%{$keyword}%");
    }
}
