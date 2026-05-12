<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRestock extends Model
{
    protected $fillable = ['product_id', 'qty_added', 'date'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
