<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WeeklyBetPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'weekly_bet_slip_id',
        'match_id',
        'home_team_name',
        'away_team_name',
        'predicted_outcome',
        'actual_outcome',
        'points_awarded',
        'match_utc_date_time',
        'is_double_points',
    ];

    protected $casts = [
        'match_utc_date_time' => 'datetime',
        'is_double_points' => 'boolean',
    ];

    public function weeklyBetSlip(): BelongsTo
    {
        return $this->belongsTo(WeeklyBetSlip::class);
    }

    /**
     * Get the double point request for this prediction
     */
    public function doublePointRequest(): HasOne
    {
        return $this->hasOne(DoublePointRequest::class, 'weekly_bet_prediction_id');
    }
}