<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\LookupTableImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LookupTableImporterTest extends TestCase
{
    use RefreshDatabase;

    private LookupTableImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer = new LookupTableImporter;
    }

    public function test_import_truck_calibrations_from_csv(): void
    {
        $csvContent = "plate_no,compartment_no,dip_mm,litres\n"
            ."3-12345,1,100,500\n"
            ."3-12345,1,200,1000\n"
            ."3-12345,2,100,600\n"
            ."3-12345,2,200,1200\n";

        $tmpFile = tempnam(sys_get_temp_dir(), 'truck_test_').'.csv';
        file_put_contents($tmpFile, $csvContent);

        try {
            $imported = $this->importer->importTruckCalibrations($tmpFile);

            $this->assertSame(4, $imported);
            $this->assertDatabaseHas('truck_calibrations', [
                'plate_no' => '3-12345',
                'compartment_no' => 1,
                'dip_mm' => 100,
                'litres' => 500,
            ]);

            $this->assertDatabaseHas('truck_headers', [
                'plate_no' => '3-12345',
                'compartment_count' => 2,
            ]);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    public function test_import_density_correct_20_from_csv(): void
    {
        $csvContent = "observed_density,temperature,density_20\n"
            ."740.0,20.0,740.0\n"
            ."750.0,20.0,750.0\n"
            ."740.0,25.0,735.8\n";

        $tmpFile = tempnam(sys_get_temp_dir(), 'dens_test_').'.csv';
        file_put_contents($tmpFile, $csvContent);

        try {
            $imported = $this->importer->importDensityCorrect20($tmpFile);

            $this->assertSame(3, $imported);
            $this->assertDatabaseHas('density_correct_20', [
                'observed_density' => 740.0,
                'temperature' => 25.0,
                'density_20' => 735.8,
            ]);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    public function test_import_factor_correct_20_from_csv(): void
    {
        $csvContent = "temperature,density_20,factor\n"
            ."20.0,740.0,1.000000\n"
            ."25.0,740.0,0.993854\n";

        $tmpFile = tempnam(sys_get_temp_dir(), 'vcf_test_').'.csv';
        file_put_contents($tmpFile, $csvContent);

        try {
            $imported = $this->importer->importFactorCorrect20($tmpFile);

            $this->assertSame(2, $imported);
            $this->assertDatabaseHas('factor_correct_20', [
                'temperature' => 25.0,
                'density_20' => 740.0,
                'factor' => 0.993854,
            ]);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    public function test_import_allowable_loss_products_from_csv(): void
    {
        $csvContent = "item_number,product_name,allowable_loss_percent,price_per_litre,opening_balance,adjustment,pipeline_stock\n"
            ."AGO,Gas Oil,0.08,85.50,50000,0,1500\n"
            ."MGR,Gasoline Regular,0.12,92.00,40000,500,2000\n";

        $tmpFile = tempnam(sys_get_temp_dir(), 'prod_test_').'.csv';
        file_put_contents($tmpFile, $csvContent);

        try {
            $imported = $this->importer->importAllowableLossProducts($tmpFile);

            $this->assertSame(2, $imported);
            $this->assertDatabaseHas('allowable_loss_products', [
                'item_number' => 'AGO',
                'product_name' => 'Gas Oil',
                'allowable_loss_percent' => 0.08,
                'price_per_litre' => 85.50,
            ]);
            $this->assertDatabaseHas('allowable_loss_products', [
                'item_number' => 'MGR',
                'allowable_loss_percent' => 0.12,
            ]);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }
}
