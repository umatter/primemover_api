<?php

namespace App\Modules\Outlet\Model;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int user_id
 * @property string title
 * @property string outlet
 * @property string yhat_n
 * @property string congress
 * @property string domain
 * @property string url
 * @property string redirect_url
 * @property string media_type
 * @property string pub_state
 * @property string avg_reach_rank
 * @property string avg_reach_permillion
 * @property string awis_categories
 * @property string source
 * @property string pi
 */
class Outlet extends BaseModel
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
