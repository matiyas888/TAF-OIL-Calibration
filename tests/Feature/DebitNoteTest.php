<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TransferOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebitNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_preview_debit_note_from_form_inputs(): void
    {
        $response = $this->post(route('petroleum.debit-notes.preview'), [
            'plate_no' => 'A03843/*29988',
            'product_item_number' => 'P07-0001',
            'origin_vol20' => 48710,
            'start_density' => 789.0,
            'start_temperature' => 19.5,
            'meter_from_1' => 162807512,
            'meter_to_1' => 162856152,
            'drained_1' => 0,
            'final_density' => 787.5,
            'final_temperature' => 20.0,
            'allowable_loss_percent' => 0.12,
            'price_per_litre' => 319.76,
        ]);

        $response->assertOk();
        $response->assertSee('Debit Note Voucher');
        $response->assertSee('A03843/*29988');
    }

    public function test_can_store_manual_debit_note(): void
    {
        $response = $this->post(route('petroleum.debit-notes.manual'), [
            'plate_no' => '3-ET-MANUAL',
            'reference' => 'FDC-MANUAL-001',
            'carrier_name' => 'National Transport',
            'driver_name' => 'Almaz Tefera',
            'product_item_number' => 'P07-0001',
            'origin_vol20' => 45000,
            'loss_volume' => 220,
            'debit_volume' => 220,
            'price_per_litre' => 319.76,
            'order_date' => '2026-11-05',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('transfer_orders', [
            'plate_no' => '3-ET-MANUAL',
            'reference' => 'FDC-MANUAL-001',
            'debit_volume' => 220,
            'debit_amount' => round(220 * 319.76, 2),
        ]);
    }

    public function test_can_view_debit_notes_register(): void
    {
        TransferOrder::query()->create([
            'to_number' => 'TO-DEBIT-TEST',
            'reference' => 'FDC-REF',
            'order_date' => '2026-11-05',
            'plate_no' => 'A03843/*29988',
            'product_item_number' => 'P07-0001',
            'start_observed_density' => 789.0,
            'start_temperature' => 20.0,
            'start_density_20' => 789.0,
            'start_factor' => 1.0,
            'final_density_20' => 789.0,
            'final_factor' => 1.0,
            'start_observed_litres' => 50000,
            'start_vol20' => 50000,
            'final_vol20' => 49800,
            'loss_volume' => 200,
            'allowable_loss_percent' => 0.12,
            'allowed_loss_volume' => 60,
            'debit_volume' => 200,
            'price_per_litre' => 319.76,
            'debit_amount' => 63952.00,
            'status' => 'completed',
        ]);

        $response = $this->get(route('petroleum.tables', ['table' => 'debit_notes']));
        $response->assertOk();
        $response->assertSee('Transporter Debit Notes Register');
        $response->assertSee('TO-DEBIT-TEST');
        $response->assertSee('63,952.00 ETB');
    }
}
