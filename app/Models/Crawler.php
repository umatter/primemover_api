<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crawler extends BaseModel
{
    protected $guarded = [];

    public function proxy(): BelongsTo
    {
        return $this->belongsTo(Proxy::class)
            ->where('active', 1);
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(Configuration::class)
            ->where('active', 1)
            ->with('params');
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('active', 1);
    }

    public function queuesNew(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('new', 1);
    }

    public function queuesProcessed(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('processed', 1);
    }

    public function queuesNotProcessed(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('processed', 0);
    }

    public function queuesProcessing(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('processing', 1);
    }

    public function queuesNotProcessing(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('processing', 0);
    }

    public function queuesReviewed(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('reviewed', 1);
    }

    public function queuesNotReviewed(): HasMany
    {
        return $this->hasMany(Queue::class)
            ->with('jobs')
            ->where('reviewed', 0);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class)
            ->where('active', 1);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }
}
