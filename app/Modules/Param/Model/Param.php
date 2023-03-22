<?php

namespace App\Modules\Param\Model;

use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Configuration\Model\Configuration;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int user_id
 * @property int configuration_id
 * @property string pi
 * @property string psi
 * @property string kappa
 * @property string tau
 * @property string beta
 * @property string alpha
 * @property array search_terms
 * @property array media_outlet_urls
 */
class Param extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'search_terms' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(Configuration::class);
    }
}
