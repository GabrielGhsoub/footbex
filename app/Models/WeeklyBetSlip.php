<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyBetSlip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'week_identifier',
        'betting_closes_at',
        'is_submitted',
        'total_score',
        'status',
    ];

    protected $casts = [
        'betting_closes_at' => 'datetime',
        'is_submitted' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(WeeklyBetPrediction::class);
    }
}