<?php

namespace App\Modules\Preference\Model;

use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Experiment\Model\Experiment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @property int    $user_id
 * @property int    $configuration_id
 * @property string $name
 * @property string $description
 * @property string $value
 * @property string $preferencable_type
 * @property int    $preferencable_id
 * @property bool   $active
 */
class Preference extends BaseModel
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(Configuration::class);
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    public function preferencable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function queryBuilder(): QueryBuilder
    {
        return QueryBuilder::for(self::class)
            ->allowedSorts(['id', 'name', 'created_at', 'updated_at'])
            ->defaultSort('-id');
    }
}
