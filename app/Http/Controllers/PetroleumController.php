<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AllowableLossProduct;
use App\Models\DensityCorrect20;
use App\Models\FactorCorrect20;
use App\Models\TransferOrder;
use App\Models\TruckCalibration;
use App\Models\TruckHeader;
use App\Services\DensityCalc;
use App\Services\TransferOrderCalculator;
use App\Services\TruckDipCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PetroleumController extends Controller
{
    public function __construct(
        private readonly TransferOrderCalculator $toCalculator,
        private readonly TruckDipCalculator $truckCalculator
    ) {}

    /**
     * Dashboard and live Transhipment Order view.
     */
    public function index(): View
    {
        $products = AllowableLossProduct::query()->orderBy('item_number')->get();
        $trucks = TruckHeader::query()->with('calibrations')->orderBy('plate_no')->get();
        $recentOrders = TransferOrder::query()->latest()->take(10)->get();

        return view('petroleum.dashboard', [
            'products' => $products,
            'trucks' => $trucks,
            'recentOrders' => $recentOrders,
        ]);
    }

    /**
     * Live calculation endpoint (API / AJAX).
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_observed_litres' => ['required', 'numeric', 'min:0'],
            'start_density' => ['required', 'numeric', 'min:600', 'max:1100'],
            'start_temperature' => ['required', 'numeric', 'min:-10', 'max:60'],
            'origin_vol20' => ['nullable', 'numeric', 'min:0'],
            'final_observed_litres' => ['nullable', 'numeric', 'min:0'],
            'final_density' => ['nullable', 'numeric', 'min:600', 'max:1100'],
            'final_temperature' => ['nullable', 'numeric', 'min:-10', 'max:60'],
            'meter_from_1' => ['nullable', 'numeric', 'min:0'],
            'meter_to_1' => ['nullable', 'numeric', 'min:0'],
            'drained_1' => ['nullable', 'numeric', 'min:0'],
            'meter_from_2' => ['nullable', 'numeric', 'min:0'],
            'meter_to_2' => ['nullable', 'numeric', 'min:0'],
            'drained_2' => ['nullable', 'numeric', 'min:0'],
            'allowable_loss_percent' => ['required', 'numeric', 'min:0', 'max:10'],
            'price_per_litre' => ['required', 'numeric', 'min:0'],
            'use_metered' => ['nullable', 'boolean'],
        ]);

        $startD20 = DensityCalc::density20((float) $validated['start_density'], (float) $validated['start_temperature']);
        $startFactor = DensityCalc::vcf20($startD20, (float) $validated['start_temperature']);

        $finalDens = (float) ($validated['final_density'] ?? $validated['start_density']);
        $finalTemp = (float) ($validated['final_temperature'] ?? $validated['start_temperature']);
        $finalD20 = DensityCalc::density20($finalDens, $finalTemp);
        $finalFactor = DensityCalc::vcf20($finalD20, $finalTemp);

        $meters = [];
        if ($request->boolean('use_metered', true)) {
            if ($request->filled('meter_to_1')) {
                $meters[] = [
                    'from_meter' => (float) ($validated['meter_from_1'] ?? 0),
                    'to_meter' => (float) ($validated['meter_to_1'] ?? 0),
                    'drained' => (float) ($validated['drained_1'] ?? 0),
                ];
            }
            if ($request->filled('meter_to_2')) {
                $meters[] = [
                    'from_meter' => (float) ($validated['meter_from_2'] ?? 0),
                    'to_meter' => (float) ($validated['meter_to_2'] ?? 0),
                    'drained' => (float) ($validated['drained_2'] ?? 0),
                ];
            }
        }

        $result = $this->toCalculator->evaluateTransferOrder([
            'start_observed_litres' => (float) $validated['start_observed_litres'],
            'start_factor' => $startFactor,
            'final_observed_litres' => (float) ($validated['final_observed_litres'] ?? 0),
            'final_factor' => $finalFactor,
            'meters' => $meters,
            'allowable_loss_percent' => (float) $validated['allowable_loss_percent'],
            'price_per_litre' => (float) $validated['price_per_litre'],
            'use_metered' => ! empty($meters),
        ]);

        // If Origin Vol20 is manually specified (e.g. from Djibouti waybill), recalculate loss against it
        if (! empty($validated['origin_vol20']) && (float) $validated['origin_vol20'] > 0) {
            $originVol20 = (float) $validated['origin_vol20'];
            $finalVol20 = $result['final_vol20'];
            $loss = $this->toCalculator->calculateLoss($originVol20, $finalVol20);
            $pct = (float) $validated['allowable_loss_percent'];
            $allowedVol = $this->toCalculator->calculateAllowedLoss($originVol20, $pct);
            $allowed2dp = $this->toCalculator->calculateAllowedLoss2dp($originVol20, $pct);
            $debitVol = $this->toCalculator->calculateDebitVolume($loss, $originVol20, $pct);
            $debitAmt = $this->toCalculator->calculateDebitAmount($debitVol, (float) $validated['price_per_litre']);

            $result['start_vol20'] = $originVol20;
            $result['loss_volume'] = round($loss, 2);
            $result['allowed_loss_volume'] = $allowedVol;
            $result['allowed_loss_2dp'] = $allowed2dp;
            $result['debit_volume'] = $debitVol;
            $result['debit_amount'] = $debitAmt;
        }

        return response()->json([
            'success' => true,
            'start_density_20' => $startD20,
            'start_factor' => $startFactor,
            'final_density_20' => $finalD20,
            'final_factor' => $finalFactor,
            'calculations' => $result,
        ]);
    }

    /**
     * Save a completed Transhipment Order.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'to_number' => ['required', 'string', 'unique:transfer_orders,to_number'],
            'reference' => ['nullable', 'string'],
            'order_date' => ['required', 'date'],
            'plate_no' => ['required', 'string'],
            'product_item_number' => ['required', 'string'],
            'ship_from_storage_point' => ['nullable', 'string'],
            'ship_from_date' => ['nullable', 'date'],
            'ship_from_loc' => ['nullable', 'string'],
            'ship_to_depot' => ['nullable', 'string'],
            'ship_to_date' => ['nullable', 'date'],
            'ship_to_loc' => ['nullable', 'string'],
            'start_observed_litres' => ['required', 'numeric', 'min:0'],
            'start_density' => ['required', 'numeric'],
            'start_temperature' => ['required', 'numeric'],
            'origin_vol20' => ['nullable', 'numeric'],
            'meter_from_1' => ['nullable', 'numeric'],
            'meter_to_1' => ['nullable', 'numeric'],
            'drained_1' => ['nullable', 'numeric'],
            'meter_from_2' => ['nullable', 'numeric'],
            'meter_to_2' => ['nullable', 'numeric'],
            'drained_2' => ['nullable', 'numeric'],
            'final_observed_litres' => ['nullable', 'numeric'],
            'final_density' => ['nullable', 'numeric'],
            'final_temperature' => ['nullable', 'numeric'],
            'allowable_loss_percent' => ['required', 'numeric'],
            'price_per_litre' => ['required', 'numeric'],
            'use_metered' => ['nullable', 'boolean'],
            'insurance_status' => ['nullable', 'string'],
            'via' => ['nullable', 'string'],
            'side_no' => ['nullable', 'string'],
            'transhipment_order_no' => ['nullable', 'string'],
            'delivery_order_no' => ['nullable', 'string'],
            'sg_15_dj_1' => ['nullable', 'numeric'],
            'sg_15_dj_2' => ['nullable', 'numeric'],
            'sg_15_diff' => ['nullable', 'numeric'],
            'driver_name' => ['nullable', 'string'],
            'driver_licence_no' => ['nullable', 'string'],
            'trailer_plate_no' => ['nullable', 'string'],
            'carrier_name' => ['nullable', 'string'],
        ]);

        $startD20 = DensityCalc::density20((float) $validated['start_density'], (float) $validated['start_temperature']);
        $startFactor = DensityCalc::vcf20($startD20, (float) $validated['start_temperature']);

        $finalDens = (float) ($validated['final_density'] ?? $validated['start_density']);
        $finalTemp = (float) ($validated['final_temperature'] ?? $validated['start_temperature']);
        $finalD20 = DensityCalc::density20($finalDens, $finalTemp);
        $finalFactor = DensityCalc::vcf20($finalD20, $finalTemp);

        $meters = [];
        if ($request->boolean('use_metered', true)) {
            $meters[] = [
                'from_meter' => (float) ($validated['meter_from_1'] ?? 0),
                'to_meter' => (float) ($validated['meter_to_1'] ?? 0),
                'drained' => (float) ($validated['drained_1'] ?? 0),
            ];
            if ($request->filled('meter_to_2')) {
                $meters[] = [
                    'from_meter' => (float) ($validated['meter_from_2'] ?? 0),
                    'to_meter' => (float) ($validated['meter_to_2'] ?? 0),
                    'drained' => (float) ($validated['drained_2'] ?? 0),
                ];
            }
        }

        $calc = $this->toCalculator->evaluateTransferOrder([
            'start_observed_litres' => (float) $validated['start_observed_litres'],
            'start_factor' => $startFactor,
            'final_observed_litres' => (float) ($validated['final_observed_litres'] ?? 0),
            'final_factor' => $finalFactor,
            'meters' => $meters,
            'allowable_loss_percent' => (float) $validated['allowable_loss_percent'],
            'price_per_litre' => (float) $validated['price_per_litre'],
            'use_metered' => ! empty($meters),
        ]);

        $startVol20 = ! empty($validated['origin_vol20']) ? (float) $validated['origin_vol20'] : $calc['start_vol20'];
        if (! empty($validated['origin_vol20'])) {
            $loss = $this->toCalculator->calculateLoss($startVol20, $calc['final_vol20']);
            $pct = (float) $validated['allowable_loss_percent'];
            $calc['loss_volume'] = round($loss, 2);
            $calc['allowed_loss_volume'] = $this->toCalculator->calculateAllowedLoss($startVol20, $pct);
            $calc['allowed_loss_2dp'] = $this->toCalculator->calculateAllowedLoss2dp($startVol20, $pct);
            $calc['debit_volume'] = $this->toCalculator->calculateDebitVolume($loss, $startVol20, $pct);
            $calc['debit_amount'] = $this->toCalculator->calculateDebitAmount($calc['debit_volume'], (float) $validated['price_per_litre']);
        }

        $to = TransferOrder::query()->create([
            'to_number' => $validated['to_number'],
            'reference' => $validated['reference'] ?? null,
            'order_date' => $validated['order_date'],
            'plate_no' => $validated['plate_no'],
            'product_item_number' => $validated['product_item_number'],
            'ship_from_storage_point' => $validated['ship_from_storage_point'] ?? null,
            'ship_from_date' => $validated['ship_from_date'] ?? null,
            'ship_from_loc' => $validated['ship_from_loc'] ?? null,
            'ship_to_depot' => $validated['ship_to_depot'] ?? null,
            'ship_to_date' => $validated['ship_to_date'] ?? null,
            'ship_to_loc' => $validated['ship_to_loc'] ?? null,
            'start_observed_density' => $validated['start_density'],
            'start_temperature' => $validated['start_temperature'],
            'start_density_20' => $startD20,
            'start_factor' => $startFactor,
            'start_observed_litres' => $validated['start_observed_litres'],
            'start_vol20' => $startVol20,
            'origin_vol20' => $validated['origin_vol20'] ?? null,
            'use_metered' => ! empty($meters),
            'meter_reading_from_1' => $validated['meter_from_1'] ?? null,
            'meter_reading_to_1' => $validated['meter_to_1'] ?? null,
            'drained_1' => $validated['drained_1'] ?? null,
            'meter_reading_from_2' => $validated['meter_from_2'] ?? null,
            'meter_reading_to_2' => $validated['meter_to_2'] ?? null,
            'drained_2' => $validated['drained_2'] ?? null,
            'metered_total' => ! empty($meters) ? $calc['final_litres'] : null,
            'final_observed_litres' => $validated['final_observed_litres'] ?? null,
            'final_observed_density' => $finalDens,
            'final_temperature' => $finalTemp,
            'final_density_20' => $finalD20,
            'final_factor' => $finalFactor,
            'final_vol20' => $calc['final_vol20'],
            'loss_volume' => $calc['loss_volume'],
            'allowable_loss_percent' => $validated['allowable_loss_percent'],
            'allowed_loss_volume' => $calc['allowed_loss_volume'],
            'allowed_loss_2dp' => $calc['allowed_loss_2dp'],
            'debit_volume' => $calc['debit_volume'],
            'price_per_litre' => $validated['price_per_litre'],
            'debit_amount' => $calc['debit_amount'],
            'sg_15_dj_1' => $validated['sg_15_dj_1'] ?? null,
            'sg_15_dj_2' => $validated['sg_15_dj_2'] ?? null,
            'sg_15_diff' => $validated['sg_15_diff'] ?? null,
            'insurance_status' => $validated['insurance_status'] ?? 'No',
            'via' => $validated['via'] ?? null,
            'side_no' => $validated['side_no'] ?? null,
            'transhipment_order_no' => $validated['transhipment_order_no'] ?? null,
            'delivery_order_no' => $validated['delivery_order_no'] ?? null,
            'driver_name' => $validated['driver_name'] ?? null,
            'driver_licence_no' => $validated['driver_licence_no'] ?? null,
            'trailer_plate_no' => $validated['trailer_plate_no'] ?? null,
            'carrier_name' => $validated['carrier_name'] ?? null,
            'status' => 'completed',
        ]);

        return redirect()->route('petroleum.index')
            ->with('status', "Transhipment Order #{$to->to_number} (Ref: {$to->reference}) saved successfully! Shortage: {$to->loss_volume} L, Debit: {$to->debit_volume} L ({$to->debit_amount} ETB)");
    }

    /**
     * Get details for a specific truck plate.
     */
    public function getTruck(string $plate): JsonResponse
    {
        $truck = TruckHeader::query()
            ->with('calibrations')
            ->where('plate_no', $plate)
            ->first();

        if (! $truck) {
            return response()->json([
                'success' => false,
                'message' => 'Truck not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'truck' => $truck,
        ]);
    }

    /**
     * Calculate litres for a specific compartment dip.
     */
    public function calculateDip(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plate_no' => ['required', 'string'],
            'compartment_no' => ['required', 'integer', 'min:1', 'max:5'],
            'dip_mm' => ['required', 'numeric', 'min:0'],
        ]);

        $litres = $this->truckCalculator->calculateLitres(
            $validated['plate_no'],
            (int) $validated['compartment_no'],
            (float) $validated['dip_mm']
        );

        return response()->json([
            'success' => true,
            'litres' => $litres,
        ]);
    }

    /**
     * Store a single calibration point for a truck.
     */
    public function storeCalibration(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plate_no' => ['required', 'string'],
            'compartment_no' => ['required', 'integer', 'min:1', 'max:10'],
            'dip_mm' => ['required', 'numeric', 'min:0'],
            'litres' => ['required', 'numeric', 'min:0'],
        ]);

        TruckHeader::query()->firstOrCreate(
            ['plate_no' => $validated['plate_no']],
            [
                'carrier_name' => 'TAF Transporter',
                'compartment_count' => $validated['compartment_no'],
                'total_capacity' => $validated['litres'],
            ]
        );

        TruckCalibration::query()->updateOrCreate(
            [
                'plate_no' => $validated['plate_no'],
                'compartment_no' => (int) $validated['compartment_no'],
                'dip_mm' => (float) $validated['dip_mm'],
            ],
            [
                'litres' => (float) $validated['litres'],
            ]
        );

        return redirect()->back()
            ->with('status', "Calibration point added successfully for Plate {$validated['plate_no']} (Comp {$validated['compartment_no']}: {$validated['dip_mm']} mm = {$validated['litres']} L)!");
    }

    /**
     * Bulk import calibration points (CSV or multiline: dip_mm, litres).
     */
    public function storeBulkCalibrations(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plate_no' => ['required', 'string'],
            'compartment_no' => ['required', 'integer', 'min:1', 'max:10'],
            'bulk_data' => ['required', 'string'],
        ]);

        $plate = trim($validated['plate_no']);
        $compNo = (int) $validated['compartment_no'];

        TruckHeader::query()->firstOrCreate(
            ['plate_no' => $plate],
            [
                'carrier_name' => 'TAF Transporter',
                'compartment_count' => $compNo,
                'total_capacity' => 45000,
            ]
        );

        $lines = preg_split('/\r\n|\r|\n/', trim($validated['bulk_data']));
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Split by comma, tab, or space
            $parts = preg_split('/[,\t\s]+/', $line);
            if (count($parts) >= 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                $dip = (float) $parts[0];
                $litres = (float) $parts[1];

                TruckCalibration::query()->updateOrCreate(
                    [
                        'plate_no' => $plate,
                        'compartment_no' => $compNo,
                        'dip_mm' => $dip,
                    ],
                    [
                        'litres' => $litres,
                    ]
                );
                $count++;
            }
        }

        return redirect()->back()
            ->with('status', "Successfully imported {$count} calibration points for {$plate} (Comp {$compNo})!");
    }

    /**
     * Delete a calibration point.
     */
    public function destroyCalibration(int $id): RedirectResponse
    {
        TruckCalibration::query()->whereKey($id)->delete();

        return redirect()->back()->with('status', 'Calibration point deleted successfully.');
    }

    /**
     * Printable Debit Note (replacing Access RPTtempdebitnote).
     */
    public function debitNote(TransferOrder $to): View
    {
        return view('petroleum.debit-note', ['to' => $to]);
    }

    /**
     * Preview Debit Note on the fly from current form inputs without saving.
     */
    public function previewDebitNote(Request $request): View
    {
        $validated = $request->validate([
            'to_number' => ['nullable', 'string'],
            'reference' => ['nullable', 'string'],
            'order_date' => ['nullable', 'date'],
            'plate_no' => ['required', 'string'],
            'product_item_number' => ['required', 'string'],
            'start_observed_litres' => ['nullable', 'numeric'],
            'start_density' => ['nullable', 'numeric'],
            'start_temperature' => ['nullable', 'numeric'],
            'origin_vol20' => ['nullable', 'numeric'],
            'meter_from_1' => ['nullable', 'numeric'],
            'meter_to_1' => ['nullable', 'numeric'],
            'drained_1' => ['nullable', 'numeric'],
            'meter_from_2' => ['nullable', 'numeric'],
            'meter_to_2' => ['nullable', 'numeric'],
            'drained_2' => ['nullable', 'numeric'],
            'final_density' => ['nullable', 'numeric'],
            'final_temperature' => ['nullable', 'numeric'],
            'allowable_loss_percent' => ['nullable', 'numeric'],
            'price_per_litre' => ['nullable', 'numeric'],
            'driver_name' => ['nullable', 'string'],
            'driver_licence_no' => ['nullable', 'string'],
            'trailer_plate_no' => ['nullable', 'string'],
            'carrier_name' => ['nullable', 'string'],
        ]);

        $startDens = (float) ($validated['start_density'] ?? 789.0);
        $startTemp = (float) ($validated['start_temperature'] ?? 20.0);
        $startD20 = DensityCalc::density20($startDens, $startTemp);
        $startFactor = DensityCalc::vcf20($startD20, $startTemp);

        $finalDens = (float) ($validated['final_density'] ?? $startDens);
        $finalTemp = (float) ($validated['final_temperature'] ?? $startTemp);
        $finalD20 = DensityCalc::density20($finalDens, $finalTemp);
        $finalFactor = DensityCalc::vcf20($finalD20, $finalTemp);

        $meters = [];
        if (! empty($validated['meter_to_1'])) {
            $meters[] = [
                'from_meter' => (float) ($validated['meter_from_1'] ?? 0),
                'to_meter' => (float) ($validated['meter_to_1'] ?? 0),
                'drained' => (float) ($validated['drained_1'] ?? 0),
            ];
        }
        if (! empty($validated['meter_to_2'])) {
            $meters[] = [
                'from_meter' => (float) ($validated['meter_from_2'] ?? 0),
                'to_meter' => (float) ($validated['meter_to_2'] ?? 0),
                'drained' => (float) ($validated['drained_2'] ?? 0),
            ];
        }

        $calc = $this->toCalculator->evaluateTransferOrder([
            'start_observed_litres' => (float) ($validated['start_observed_litres'] ?? $validated['origin_vol20'] ?? 48000),
            'start_factor' => $startFactor,
            'final_observed_litres' => (float) ($validated['start_observed_litres'] ?? $validated['origin_vol20'] ?? 48000),
            'final_factor' => $finalFactor,
            'meters' => $meters,
            'allowable_loss_percent' => (float) ($validated['allowable_loss_percent'] ?? 0.12),
            'price_per_litre' => (float) ($validated['price_per_litre'] ?? 319.76),
            'use_metered' => ! empty($meters),
        ]);

        $originVol20 = ! empty($validated['origin_vol20']) ? (float) $validated['origin_vol20'] : $calc['start_vol20'];
        $finalVol20 = $calc['final_vol20'];
        $loss = $this->toCalculator->calculateLoss($originVol20, $finalVol20);
        $pct = (float) ($validated['allowable_loss_percent'] ?? 0.12);
        $allowedVol = $this->toCalculator->calculateAllowedLoss($originVol20, $pct);
        $allowed2dp = $this->toCalculator->calculateAllowedLoss2dp($originVol20, $pct);
        $debitVol = $this->toCalculator->calculateDebitVolume($loss, $originVol20, $pct);
        $debitAmt = $this->toCalculator->calculateDebitAmount($debitVol, (float) ($validated['price_per_litre'] ?? 319.76));

        $to = new TransferOrder([
            'to_number' => $validated['to_number'] ?? ('PREVIEW-'.date('Ymd-His')),
            'reference' => $validated['reference'] ?? 'PREVIEW-VOUCHER',
            'order_date' => $validated['order_date'] ?? date('Y-m-d'),
            'plate_no' => $validated['plate_no'],
            'trailer_plate_no' => $validated['trailer_plate_no'] ?? null,
            'carrier_name' => $validated['carrier_name'] ?? 'TAF Oil Fleet',
            'driver_name' => $validated['driver_name'] ?? null,
            'driver_licence_no' => $validated['driver_licence_no'] ?? null,
            'product_item_number' => $validated['product_item_number'],
            'start_observed_litres' => $validated['start_observed_litres'] ?? $originVol20,
            'start_temperature' => $startTemp,
            'start_density_20' => $startD20,
            'start_factor' => $startFactor,
            'start_vol20' => $originVol20,
            'final_observed_litres' => ! empty($meters) ? $calc['final_litres'] : $originVol20,
            'metered_total' => ! empty($meters) ? $calc['final_litres'] : null,
            'final_temperature' => $finalTemp,
            'final_density_20' => $finalD20,
            'final_factor' => $finalFactor,
            'final_vol20' => $finalVol20,
            'loss_volume' => round($loss, 2),
            'allowable_loss_percent' => $pct,
            'allowed_loss_volume' => $allowedVol,
            'allowed_loss_2dp' => $allowed2dp,
            'debit_volume' => $debitVol,
            'price_per_litre' => (float) ($validated['price_per_litre'] ?? 319.76),
            'debit_amount' => $debitAmt,
        ]);

        return view('petroleum.debit-note', ['to' => $to]);
    }

    /**
     * Store a manually created debit note.
     */
    public function storeManualDebitNote(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plate_no' => ['required', 'string'],
            'reference' => ['nullable', 'string'],
            'carrier_name' => ['nullable', 'string'],
            'driver_name' => ['nullable', 'string'],
            'product_item_number' => ['required', 'string'],
            'origin_vol20' => ['required', 'numeric', 'min:0'],
            'loss_volume' => ['required', 'numeric', 'min:0'],
            'debit_volume' => ['required', 'numeric', 'min:0'],
            'price_per_litre' => ['required', 'numeric', 'min:0'],
            'order_date' => ['required', 'date'],
        ]);

        $debitAmount = round((float) $validated['debit_volume'] * (float) $validated['price_per_litre'], 2);
        $toNum = 'DN-'.date('Ymd').'-'.substr(strtoupper(md5(uniqid())), 0, 6);

        $to = TransferOrder::query()->create([
            'to_number' => $toNum,
            'reference' => $validated['reference'] ?? 'MANUAL-DEBIT',
            'order_date' => $validated['order_date'],
            'plate_no' => $validated['plate_no'],
            'carrier_name' => $validated['carrier_name'] ?? 'TAF Transporter',
            'driver_name' => $validated['driver_name'] ?? null,
            'product_item_number' => $validated['product_item_number'],
            'start_observed_density' => 789.0,
            'start_temperature' => 20.0,
            'start_density_20' => 789.0,
            'start_factor' => 1.0,
            'start_observed_litres' => $validated['origin_vol20'],
            'start_vol20' => $validated['origin_vol20'],
            'final_density_20' => 789.0,
            'final_factor' => 1.0,
            'final_vol20' => (float) $validated['origin_vol20'] - (float) $validated['loss_volume'],
            'loss_volume' => $validated['loss_volume'],
            'allowable_loss_percent' => 0.12,
            'allowed_loss_volume' => round((float) $validated['origin_vol20'] * 0.0012),
            'debit_volume' => $validated['debit_volume'],
            'price_per_litre' => $validated['price_per_litre'],
            'debit_amount' => $debitAmount,
            'status' => 'completed',
        ]);

        return redirect()->route('petroleum.debit-note', $to)
            ->with('status', "Debit Note #{$to->to_number} created successfully!");
    }

    /**
     * Database Tables & Lookups Inspector view.
     */
    public function tables(Request $request): View
    {
        $activeTable = $request->query('table', 'truck_headers');

        $trucks = TruckHeader::query()->orderBy('plate_no')->get();
        $calibrations = TruckCalibration::query()->orderBy('plate_no')->orderBy('compartment_no')->orderBy('dip_mm')->get();
        $products = AllowableLossProduct::query()->orderBy('item_number')->get();
        $orders = TransferOrder::query()->latest()->get();
        $debitNotes = TransferOrder::query()->where('debit_volume', '>', 0)->latest()->get();
        $densitySample = DensityCorrect20::query()->take(100)->get();
        $factorSample = FactorCorrect20::query()->take(100)->get();

        $tableCounts = [
            'truck_headers' => TruckHeader::query()->count(),
            'truck_calibrations' => TruckCalibration::query()->count(),
            'allowable_loss_products' => AllowableLossProduct::query()->count(),
            'transfer_orders' => TransferOrder::query()->count(),
            'debit_notes' => TransferOrder::query()->where('debit_volume', '>', 0)->count(),
            'density_correct_20' => DensityCorrect20::query()->count(),
            'factor_correct_20' => FactorCorrect20::query()->count(),
        ];

        return view('petroleum.tables', compact(
            'activeTable',
            'trucks',
            'calibrations',
            'products',
            'orders',
            'debitNotes',
            'densitySample',
            'factorSample',
            'tableCounts'
        ));
    }
}
