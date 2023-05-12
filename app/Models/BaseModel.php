<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property Carbon updated_at
 * @property Carbon created_at
 */
class BaseModel extends Model
{
    public function scopeAfter(Builder $query, $date): Builder
    {
        return $query->where('created_at', '>=', Carbon::parse($date));
    }

    public function scopeRecent(Builder $query, $daysInPast = 30): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($daysInPast));
    }

    /** @noinspection PhpPossiblePolymorphicInvocationInspection */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->toIso8601String();
    }
}
