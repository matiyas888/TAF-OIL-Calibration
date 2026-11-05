<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\TransferOrder;
use App\Models\TruckHeader;
use App\Services\DensityCalc;
use App\Services\TransferOrderCalculator;
use Illuminate\Contracts\Console\Kernel;

echo "========================================================\n";
echo "   TEST INSERTING REAL TRANSHIPMENT DATA INTO DATABASE  \n";
echo "========================================================\n\n";

// 1. Fetch Truck and Product
$truck = TruckHeader::where('plate_no', 'like', 'A03843%')->first();
echo 'Selected Truck: '.($truck ? $truck->plate_no : 'A03843/*29988')."\n";
echo 'Driver: '.($truck->driver_name ?? 'Abebe Bikila')."\n";
echo 'Trailer: '.($truck->trailer_plate_no ?? '3-29988')."\n\n";

// 2. Prepare Data (Screenshot 1: FDC-99995)
$toNum = 'TO-'.date('Ymd').'-TEST01';
TransferOrder::where('to_number', $toNum)->delete(); // Clean up if exists

$data = [
    'to_number' => $toNum,
    'reference' => 'FDC-99995',
    'order_date' => date('Y-m-d'),
    'plate_no' => $truck ? $truck->plate_no : 'A03843/*29988',
    'product_item_number' => 'P07-0001',
    'start_observed_density' => 789.0,
    'start_temperature' => 19.5,
    'start_observed_litres' => 48710,
    'origin_vol20' => 48710,
    'use_metered' => true,
    'meter_reading_from_1' => 162807512,
    'meter_reading_to_1' => 162856152,
    'drained_1' => 0,
    'final_observed_density' => 787.5,
    'final_temperature' => 20.0,
    'allowable_loss_percent' => 0.12,
    'price_per_litre' => 319.76,
    'sg_15_dj_1' => 0.7916,
    'sg_15_dj_2' => 0.7923,
    'driver_name' => $truck->driver_name ?? 'Abebe Bikila',
    'carrier_name' => $truck->carrier_name ?? 'TAF Transport',
];

// 3. Compute ASTM 54B/D & Loss
$calcService = app(TransferOrderCalculator::class);

$startD20 = DensityCalc::density20((float) $data['start_observed_density'], (float) $data['start_temperature']);
$startFactor = DensityCalc::vcf20($startD20, (float) $data['start_temperature']);

$finalD20 = DensityCalc::density20((float) $data['final_observed_density'], (float) $data['final_temperature']);
$finalFactor = DensityCalc::vcf20($finalD20, (float) $data['final_temperature']);

$meteredTotal = ($data['meter_reading_to_1'] - $data['meter_reading_from_1']) + $data['drained_1'];
$finalVol20 = round($meteredTotal * $finalFactor);

$originVol20 = (float) $data['origin_vol20'];
$loss = abs($originVol20 - $finalVol20);
$allowedVol = $calcService->calculateAllowedLoss($originVol20, (float) $data['allowable_loss_percent']);
$debitVol = $calcService->calculateDebitVolume($loss, $originVol20, (float) $data['allowable_loss_percent']);
$debitAmt = $calcService->calculateDebitAmount($debitVol, (float) $data['price_per_litre']);

// 4. Insert into database
$record = TransferOrder::create(array_merge($data, [
    'start_density_20' => $startD20,
    'start_factor' => $startFactor,
    'start_vol20' => $originVol20,
    'metered_total' => $meteredTotal,
    'final_density_20' => $finalD20,
    'final_factor' => $finalFactor,
    'final_vol20' => $finalVol20,
    'loss_volume' => $loss,
    'allowed_loss_volume' => $allowedVol,
    'debit_volume' => $debitVol,
    'debit_amount' => $debitAmt,
    'status' => 'completed',
]));

echo "SUCCESS! Transhipment Order Inserted into Database:\n";
echo "--------------------------------------------------------\n";
echo 'ID:                     '.$record->id."\n";
echo 'TO Number:              '.$record->to_number."\n";
echo 'Reference:              '.$record->reference."\n";
echo 'Truck Plate:            '.$record->plate_no."\n";
echo 'Loading Vol @ 20°C:     '.number_format($record->origin_vol20)." L\n";
echo 'Discharge Metered:      '.number_format($record->metered_total)." L\n";
echo 'Discharge Vol @ 20°C:   '.number_format($record->final_vol20)." L\n";
echo 'Physical Shortage Loss: '.$record->loss_volume." L\n";
echo 'Contract Allowed Loss:  '.$record->allowed_loss_volume." L\n";
echo 'Debited Volume:         '.$record->debit_volume." L\n";
echo 'Debit Amount:           '.number_format($record->debit_amount, 2)." ETB\n";
echo "--------------------------------------------------------\n\n";

// 5. Test another insert: High Shortage (> 150 L) triggering Debit Penalty
$toNum2 = 'TO-'.date('Ymd').'-TEST02';
TransferOrder::where('to_number', $toNum2)->delete();

$data2 = array_merge($data, [
    'to_number' => $toNum2,
    'reference' => 'FDC-SHORTAGE-EXCESS',
    'origin_vol20' => 50000,
    'meter_reading_from_1' => 100000,
    'meter_reading_to_1' => 149800, // 49,800 L metered
]);

$meteredTotal2 = 49800;
$finalVol20_2 = round($meteredTotal2 * $finalFactor);
$originVol20_2 = 50000;
$loss2 = abs($originVol20_2 - $finalVol20_2); // 200 L loss!
$allowedVol2 = $calcService->calculateAllowedLoss($originVol20_2, 0.12);
$debitVol2 = $calcService->calculateDebitVolume($loss2, $originVol20_2, 0.12);
$debitAmt2 = $calcService->calculateDebitAmount($debitVol2, 319.76);

$record2 = TransferOrder::create(array_merge($data2, [
    'start_density_20' => $startD20,
    'start_factor' => $startFactor,
    'start_vol20' => $originVol20_2,
    'metered_total' => $meteredTotal2,
    'final_density_20' => $finalD20,
    'final_factor' => $finalFactor,
    'final_vol20' => $finalVol20_2,
    'loss_volume' => $loss2,
    'allowed_loss_volume' => $allowedVol2,
    'debit_volume' => $debitVol2,
    'debit_amount' => $debitAmt2,
    'status' => 'completed',
]));

echo "SUCCESS! Second Order (Loss > 150 L) Inserted into Database:\n";
echo "--------------------------------------------------------\n";
echo 'TO Number:              '.$record2->to_number."\n";
echo 'Loading Vol @ 20°C:     '.number_format($record2->origin_vol20)." L\n";
echo 'Discharge Vol @ 20°C:   '.number_format($record2->final_vol20)." L\n";
echo 'Physical Shortage Loss: '.$record2->loss_volume." L (> 150 L threshold!)\n";
echo 'Contract Allowed Loss:  '.$record2->allowed_loss_volume." L\n";
echo 'Debited Volume:         '.$record2->debit_volume." L (100% debited, NO allowance!)\n";
echo 'Debit Amount:           '.number_format($record2->debit_amount, 2)." ETB\n";
echo 'Debit Note URL:         http://127.0.0.1:8000/transfer-orders/'.$record2->id."/debit-note\n";
echo "========================================================\n";
