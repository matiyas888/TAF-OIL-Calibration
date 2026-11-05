<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TruckCalibration extends Model
{
    protected $guarded = [];

    /**
     * @return BelongsTo<TruckHeader, $this>
     */
    public function truckHeader(): BelongsTo
    {
        return $this->belongsTo(TruckHeader::class, 'plate_no', 'plate_no');
    }
}
