<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoublePointWeeklyMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'week_identifier',
        'match_id',
        'home_team_name',
        'away_team_name',
        'set_by',
    ];

    /**
     * Get the admin who set this match
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}
