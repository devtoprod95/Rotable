<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    protected $table    = 'visitor_logs';
    protected $fillable = [];
    protected $hidden   = [];
    protected $guarded  = [];

    protected $casts = [
        'visited_at' => 'date',
    ];

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('visited_at', now()->toDateString());
    }

    public function scopeYesterday(Builder $query): Builder
    {
        return $query->whereDate('visited_at', now()->subDay()->toDateString());
    }
}
