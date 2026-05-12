<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['branch_id', 'name', 'price', 'stock'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function restocks(): HasMany
    {
        return $this->hasMany(ProductRestock::class);
    }
}
