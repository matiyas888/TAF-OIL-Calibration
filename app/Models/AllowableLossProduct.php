<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AllowableLossProduct extends Model
{
    protected $guarded = [];

    /**
     * @return HasMany<TransferOrder, $this>
     */
    public function transferOrders(): HasMany
    {
        return $this->hasMany(TransferOrder::class, 'product_item_number', 'item_number');
    }
}
