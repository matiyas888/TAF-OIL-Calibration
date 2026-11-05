<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TransferOrder;
use Database\Seeders\LookupTablesSeeder;
use Database\Seeders\TruckSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetroleumWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('TAF OIL ETHIOPIA');
        $response->assertSee('TRANSHIPMENT ORDER');
    }

    public function test_live_calculation_endpoint_returns_json_with_debit(): void
    {
        $payload = [
            'start_observed_litres' => 45000,
            'start_density' => 745.0,
            'start_temperature' => 22.5,
            'meter_from_1' => 10000,
            'meter_to_1' => 35000,
            'drained_1' => 0,
            'meter_from_2' => 5000,
            'meter_to_2' => 24800,
            'drained_2' => 20,
            'final_density' => 745.0,
            'final_temperature' => 23.0,
            'allowable_loss_percent' => 0.08,
            'price_per_litre' => 90.00,
            'use_metered' => true,
        ];

        $response = $this->postJson('/calculate', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'start_density_20',
            'start_factor',
            'final_density_20',
            'final_factor',
            'calculations' => [
                'start_vol20',
                'final_vol20',
                'loss_volume',
                'allowed_loss_volume',
                'allowed_loss_2dp',
                'debit_volume',
                'debit_amount',
            ],
        ]);
    }

    public function test_store_saves_transfer_order_and_redirects(): void
    {
        $payload = [
            'to_number' => 'TO-20261026-999',
            'order_date' => '2026-10-26',
            'plate_no' => '3-54321',
            'product_item_number' => 'AGO',
            'start_observed_litres' => 45000,
            'start_density' => 745.0,
            'start_temperature' => 22.5,
            'meter_from_1' => 10000,
            'meter_to_1' => 35000,
            'drained_1' => 0,
            'final_density' => 745.0,
            'final_temperature' => 23.0,
            'allowable_loss_percent' => 0.08,
            'price_per_litre' => 85.50,
            'use_metered' => true,
        ];

        $response = $this->post('/transfer-orders', $payload);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('transfer_orders', [
            'to_number' => 'TO-20261026-999',
            'plate_no' => '3-54321',
            'product_item_number' => 'AGO',
        ]);
    }

    public function test_debit_note_page_renders_for_existing_order(): void
    {
        $to = TransferOrder::query()->create([
            'to_number' => 'TO-TEST-001',
            'order_date' => '2026-10-26',
            'plate_no' => '3-11223',
            'product_item_number' => 'AGO',
            'start_observed_density' => 745.0,
            'start_temperature' => 22.0,
            'start_density_20' => 746.5,
            'start_factor' => 0.997500,
            'start_observed_litres' => 40000,
            'start_vol20' => 39900,
            'final_observed_density' => 745.0,
            'final_temperature' => 22.5,
            'final_density_20' => 747.0,
            'final_factor' => 0.997000,
            'final_observed_litres' => 39700,
            'final_vol20' => 39580.9,
            'loss_volume' => 319.1,
            'allowable_loss_percent' => 0.08,
            'allowed_loss_volume' => 32,
            'allowed_loss_2dp' => 31.92,
            'debit_volume' => 319.1,
            'price_per_litre' => 85.50,
            'debit_amount' => 27283.05,
        ]);

        $response = $this->get("/transfer-orders/{$to->id}/debit-note");

        $response->assertStatus(200);
        $response->assertSee('TO-TEST-001');
        $response->assertSee('DN-TO-TEST-001');
        $response->assertSee('3-11223');
        $response->assertSee('27,283.05');
    }

    public function test_dashboard_displays_trucks_and_driver_selection(): void
    {
        $this->seed(TruckSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Select Plate:');
        $response->assertSee('A40983/*41304');
        $response->assertSee('A03843/*29988');
        $response->assertSee('Third Party (Dekebo)');
        $response->assertSee('Mohammed Ahmed');
    }

    public function test_get_truck_endpoint_returns_driver_and_calibrations(): void
    {
        $this->seed(TruckSeeder::class);

        $response = $this->getJson('/trucks/'.urlencode('A40983/*41304'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'truck' => [
                'plate_no' => 'A40983/*41304',
                'truck_no' => 'A40983',
                'trailer_plate_no' => '41304',
                'carrier_name' => 'Third Party (Dekebo)',
                'driver_name' => 'Mohammed Ahmed',
                'driver_licence_no' => 'DL-ETH-88219',
                'compartment_count' => 5,
            ],
        ]);
    }

    public function test_calculate_dip_endpoint_returns_interpolated_litres(): void
    {
        $this->seed(TruckSeeder::class);

        $response = $this->postJson('/calculate-dip', [
            'plate_no' => 'A40983/*41304',
            'compartment_no' => 1,
            'dip_mm' => 1395,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'litres' => 11395,
        ]);
    }

    public function test_store_saves_transfer_order_with_driver_and_carrier_details(): void
    {
        $payload = [
            'to_number' => 'TO-20261027-TRK01',
            'order_date' => '2026-10-27',
            'plate_no' => 'A40983/*41304',
            'trailer_plate_no' => '41304',
            'carrier_name' => 'Third Party (Dekebo)',
            'driver_name' => 'Mohammed Ahmed',
            'driver_licence_no' => 'DL-ETH-88219',
            'product_item_number' => 'P07-0001',
            'start_observed_litres' => 50293,
            'start_density' => 787.0,
            'start_temperature' => 20.0,
            'meter_from_1' => 162856672,
            'meter_to_1' => 162906459,
            'drained_1' => 0,
            'final_density' => 787.0,
            'final_temperature' => 20.0,
            'allowable_loss_percent' => 0.12,
            'price_per_litre' => 319.76,
            'use_metered' => true,
        ];

        $response = $this->post('/transfer-orders', $payload);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('transfer_orders', [
            'to_number' => 'TO-20261027-TRK01',
            'plate_no' => 'A40983/*41304',
            'trailer_plate_no' => '41304',
            'carrier_name' => 'Third Party (Dekebo)',
            'driver_name' => 'Mohammed Ahmed',
            'driver_licence_no' => 'DL-ETH-88219',
        ]);
    }

    public function test_debit_note_displays_driver_and_carrier_information(): void
    {
        $to = TransferOrder::query()->create([
            'to_number' => 'TO-TEST-002',
            'order_date' => '2026-10-27',
            'plate_no' => 'A40983/*41304',
            'trailer_plate_no' => '41304',
            'carrier_name' => 'Third Party (Dekebo)',
            'driver_name' => 'Mohammed Ahmed',
            'driver_licence_no' => 'DL-ETH-88219',
            'product_item_number' => 'P07-0001',
            'start_observed_density' => 787.0,
            'start_temperature' => 20.0,
            'start_density_20' => 787.0,
            'start_factor' => 1.000000,
            'start_observed_litres' => 50293,
            'start_vol20' => 50293,
            'final_observed_density' => 787.0,
            'final_temperature' => 20.0,
            'final_density_20' => 787.0,
            'final_factor' => 1.000000,
            'final_observed_litres' => 50000,
            'final_vol20' => 50000,
            'loss_volume' => 293.0,
            'allowable_loss_percent' => 0.12,
            'allowed_loss_volume' => 60,
            'allowed_loss_2dp' => 60.35,
            'debit_volume' => 293.0,
            'price_per_litre' => 319.76,
            'debit_amount' => 93689.68,
        ]);

        $response = $this->get("/transfer-orders/{$to->id}/debit-note");

        $response->assertStatus(200);
        $response->assertSee('Mohammed Ahmed');
        $response->assertSee('DL-ETH-88219');
        $response->assertSee('Third Party (Dekebo)');
        $response->assertSee('41304');
    }

    public function test_tables_page_renders_successfully(): void
    {
        $this->seed(TruckSeeder::class);
        $this->seed(LookupTablesSeeder::class);

        $response = $this->get('/tables');

        $response->assertStatus(200);
        $response->assertSee('truck_headers');
        $response->assertSee('A40983/*41304');
        $response->assertSee('Mohammed Ahmed');
        $response->assertSee('allowable_loss_products');
        $response->assertSee('density_correct_20');
        $response->assertSee('factor_correct_20');
    }
}
