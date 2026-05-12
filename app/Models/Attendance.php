<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = ['user_id', 'date', 'check_in', 'check_out', 'daily_base_salary_earned'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
