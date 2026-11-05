<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferOrderCompartment extends Model
{
    protected $guarded = [];

    /**
     * @return BelongsTo<TransferOrder, $this>
     */
    public function transferOrder(): BelongsTo
    {
        return $this->belongsTo(TransferOrder::class);
    }
}
