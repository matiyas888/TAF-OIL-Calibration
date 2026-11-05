<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TruckCalibration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TruckCalibrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_single_calibration_point(): void
    {
        $response = $this->post(route('petroleum.calibrations.store'), [
            'plate_no' => '3-ET-99999',
            'compartment_no' => 1,
            'dip_mm' => 1250.5,
            'litres' => 9850,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('truck_headers', [
            'plate_no' => '3-ET-99999',
        ]);

        $this->assertDatabaseHas('truck_calibrations', [
            'plate_no' => '3-ET-99999',
            'compartment_no' => 1,
            'dip_mm' => 1250.5,
            'litres' => 9850,
        ]);
    }

    public function test_can_bulk_import_calibration_points(): void
    {
        $bulkData = '
            100, 750
            200, 1500
            300, 2250
            1000, 7500
        ';

        $response = $this->post(route('petroleum.calibrations.bulk'), [
            'plate_no' => '3-ET-BULK01',
            'compartment_no' => 2,
            'bulk_data' => $bulkData,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('truck_calibrations', [
            'plate_no' => '3-ET-BULK01',
            'compartment_no' => 2,
            'dip_mm' => 100,
            'litres' => 750,
        ]);

        $this->assertDatabaseHas('truck_calibrations', [
            'plate_no' => '3-ET-BULK01',
            'compartment_no' => 2,
            'dip_mm' => 1000,
            'litres' => 7500,
        ]);
    }

    public function test_can_delete_calibration_point(): void
    {
        $calib = TruckCalibration::query()->create([
            'plate_no' => '3-ET-DELETE',
            'compartment_no' => 1,
            'dip_mm' => 500,
            'litres' => 3500,
        ]);

        $response = $this->delete(route('petroleum.calibrations.destroy', $calib->id));
        $response->assertRedirect();

        $this->assertDatabaseMissing('truck_calibrations', [
            'id' => $calib->id,
        ]);
    }
}
