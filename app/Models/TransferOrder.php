<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferOrder extends Model
{
    protected $guarded = [];

    /**
     * @return HasMany<TransferOrderCompartment, $this>
     */
    public function compartments(): HasMany
    {
        return $this->hasMany(TransferOrderCompartment::class);
    }

    /**
     * @return BelongsTo<AllowableLossProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(AllowableLossProduct::class, 'product_item_number', 'item_number');
    }
}
