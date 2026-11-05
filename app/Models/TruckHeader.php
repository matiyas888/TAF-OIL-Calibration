<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TruckHeader extends Model
{
    protected $guarded = [];

    /**
     * @return HasMany<TruckCalibration, $this>
     */
    public function calibrations(): HasMany
    {
        return $this->hasMany(TruckCalibration::class, 'plate_no', 'plate_no');
    }
}
