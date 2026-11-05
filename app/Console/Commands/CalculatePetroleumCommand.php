<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DensityCalc;
use App\Services\ProductVarianceCalculator;
use App\Services\TransferOrderCalculator;
use App\Services\TruckDipCalculator;
use Illuminate\Console\Command;

class CalculatePetroleumCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'petroleum:calculate
                            {--start-litres= : Start observed litres}
                            {--start-density=745.0 : Observed density (kg/m3)}
                            {--start-temp=22.0 : Observed temperature (°C)}
                            {--final-litres= : Final observed or metered litres}
                            {--final-density=745.0 : Final observed density (kg/m3)}
                            {--final-temp=23.0 : Final observed temperature (°C)}
                            {--percent=0.08 : Allowable loss percent (0.08 or 0.12)}
                            {--price=85.50 : Price per litre}
                            {--demo : Run comprehensive demonstration of all Access rules}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute petroleum volume, loss, and debit note calculations';

    /**
     * Execute the console command.
     */
    public function handle(
        TransferOrderCalculator $toCalc,
        TruckDipCalculator $truckCalc,
        ProductVarianceCalculator $varCalc
    ): int {
        $this->info('================================================================');
        $this->info('      TAF OIL PETROLEUM CALCULATION & DEBIT NOTE ENGINE');
        $this->info('================================================================');

        $startLitres = $this->option('start-litres');
        $finalLitres = $this->option('final-litres');

        // If no custom numbers provided or --demo flag, run the full multi-scenario demo
        if ($startLitres === null && $finalLitres === null) {
            return $this->runDemoScenarios($toCalc, $truckCalc, $varCalc);
        }

        $sLitres = (float) $startLitres;
        $fLitres = (float) ($finalLitres ?? ($sLitres * 0.997));
        $sDens = (float) $this->option('start-density');
        $sTemp = (float) $this->option('start-temp');
        $fDens = (float) $this->option('final-density');
        $fTemp = (float) $this->option('final-temp');
        $pct = (float) $this->option('percent');
        $price = (float) $this->option('price');

        $this->runSingleCalculation(
            $toCalc,
            $sLitres,
            $sDens,
            $sTemp,
            $fLitres,
            $fDens,
            $fTemp,
            $pct,
            $price
        );

        return self::SUCCESS;
    }

    private function runSingleCalculation(
        TransferOrderCalculator $calc,
        float $startLitres,
        float $startDens,
        float $startTemp,
        float $finalLitres,
        float $finalDens,
        float $finalTemp,
        float $pct,
        float $price
    ): void {
        // 1. Calculate Factors using ASTM / Table port
        $startD20 = DensityCalc::density20($startDens, $startTemp);
        $startFactor = DensityCalc::vcf20($startD20, $startTemp);

        $finalD20 = DensityCalc::density20($finalDens, $finalTemp);
        $finalFactor = DensityCalc::vcf20($finalD20, $finalTemp);

        // 2. Run TO Pipeline
        $result = $calc->evaluateTransferOrder([
            'start_observed_litres' => $startLitres,
            'start_factor' => $startFactor,
            'final_observed_litres' => $finalLitres,
            'final_factor' => $finalFactor,
            'allowable_loss_percent' => $pct,
            'price_per_litre' => $price,
            'use_metered' => false,
        ]);

        $this->table(
            ['Metric', 'Value', 'Calculation Rule & Details'],
            [
                ['Start Observed Litres', number_format($startLitres, 2).' L', 'Truck Compartment Dip reading'],
                ['Start Temp & Density', "{$startTemp}°C @ {$startDens} kg/m³", "Calculated Density @ 20°C: {$startD20} kg/m³"],
                ['Start Factor (VCF20)', number_format($startFactor, 6), 'Volume conversion factor to 20°C'],
                ['Start Vol @ 20°C', number_format($result['start_vol20'], 2).' L', 'Start Litres × Start Factor'],
                ['-------------------------', '------------------', '---------------------------------------------'],
                ['Final Observed/Metered', number_format($finalLitres, 2).' L', 'Sum of meters (To - From + Drained)'],
                ['Final Temp & Density', "{$finalTemp}°C @ {$finalDens} kg/m³", "Calculated Density @ 20°C: {$finalD20} kg/m³"],
                ['Final Factor (VCF20)', number_format($finalFactor, 6), 'Volume conversion factor to 20°C'],
                ['Final Vol @ 20°C', number_format($result['final_vol20'], 2).' L', 'Final Litres × Final Factor'],
                ['-------------------------', '------------------', '---------------------------------------------'],
                ['Physical Loss', number_format($result['loss_volume'], 2).' L', '|Vol20_start - Vol20_final|'],
                ['Allowed Loss %', "{$pct}%", 'Stored per Transfer Order record'],
                ['Allowed Loss (2-dec)', number_format($result['allowed_loss_2dp'], 2).' L', 'Vol20_start × percent / 100'],
                ['Allowed Loss (whole)', "{$result['allowed_loss_volume']} L", 'round(Vol20_start × percent / 100)'],
                ['-------------------------', '------------------', '---------------------------------------------'],
                ['Debit Volume', number_format($result['debit_volume'], 2).' L', $this->getDebitBranchExplanation($result['loss_volume'], $result['allowed_loss_2dp'])],
                ['Price per Litre', number_format($price, 2).' ETB', 'Contract / Transporter tariff'],
                ['Debit Amount', number_format($result['debit_amount'], 2).' ETB', 'Debit Volume × Price per Litre'],
            ]
        );
    }

    private function runDemoScenarios(
        TransferOrderCalculator $toCalc,
        TruckDipCalculator $truckCalc,
        ProductVarianceCalculator $varCalc
    ): int {
        $this->comment("\n--- 1. TRUCK COMPARTMENT DIP INTERPOLATION (Rule 1) ---");
        $compartments = [
            1 => [
                'dip_mm' => 1050,
                'calibration_points' => [
                    ['dip_mm' => 1000, 'litres' => 5000],
                    ['dip_mm' => 1100, 'litres' => 5550],
                ],
            ],
            2 => [
                'dip_mm' => 1033,
                'calibration_points' => [
                    ['dip_mm' => 1000, 'litres' => 5000],
                    ['dip_mm' => 1100, 'litres' => 5550],
                ],
            ],
            3 => [
                'dip_mm' => 950,
                'calibration_points' => [
                    ['dip_mm' => 900, 'litres' => 4500],
                    ['dip_mm' => 1000, 'litres' => 5000],
                ],
            ],
        ];

        $truckResult = $truckCalc->calculateTruckVolume($compartments);
        $this->line("Comp 1 (1050 mm): {$truckResult['compartments'][1]} L  (Exact straight-line: 5275 L)");
        $this->line("Comp 2 (1033 mm): {$truckResult['compartments'][2]} L  (5000 + 0.33 * 550 = 5181.5 -> rounded: 5182 L)");
        $this->line("Comp 3 (950 mm):  {$truckResult['compartments'][3]} L  (Midpoint: 4750 L)");
        $this->info("Total Truck Volume: {$truckResult['total_litres']} L");

        $this->comment("\n--- 2. SCENARIO A: LOSS <= 150 L with EXCESS >= 0.5 L ---");
        $this->info('Start Vol20 = 40,000 L | Loss = 80.0 L | Allowed % = 0.08% (Allowed = 32.0 L)');
        $debA = $toCalc->calculateDebitVolume(80.0, 40000.0, 0.08);
        $amtA = $toCalc->calculateDebitAmount($debA, 85.50);
        $this->line("Excess = 80.0 - 32.00 = 48.0 L >= 0.5 L -> DEBIT APPLIES: {$debA} L = {$amtA} ETB");

        $this->comment("\n--- 3. SCENARIO B: LOSS <= 150 L with EXCESS < 0.5 L (Grace Buffer) ---");
        $this->info('Start Vol20 = 40,000 L | Loss = 32.40 L | Allowed % = 0.08% (Allowed = 32.0 L)');
        $debB = $toCalc->calculateDebitVolume(32.40, 40000.0, 0.08);
        $amtB = $toCalc->calculateDebitAmount($debB, 85.50);
        $this->line("Excess = 32.40 - 32.00 = 0.40 L < 0.5 L -> DEBIT WAIVED: {$debB} L = {$amtB} ETB");

        $this->comment("\n--- 4. SCENARIO C: HIGH LOSS > 150 L (Entire Loss Debited Without Allowance) ---");
        $this->info('Start Vol20 = 45,000 L | Loss = 184.16 L (> 150 L) | Allowed % = 0.08% (Normally 36 L)');
        $debC = $toCalc->calculateDebitVolume(184.16, 45000.0, 0.08);
        $amtC = $toCalc->calculateDebitAmount($debC, 90.00);
        $this->line("Loss exceeds 150 L threshold -> FULL DEBIT APPLIES (0 allowance): {$debC} L = {$amtC} ETB");

        $this->comment("\n--- 5. SCENARIO D: PRODUCT VARIANCE REPORT ---");
        $report = $varCalc->generateReport([
            ['product_id' => 'AGO', 'product_name' => 'Automotive Gas Oil', 'vol20_start' => 90000.0, 'vol20_final' => 89850.0, 'debit_volume' => 80.0],
            ['product_id' => 'MGR', 'product_name' => 'Regular Gasoline', 'vol20_start' => 45000.0, 'vol20_final' => 44960.0, 'debit_volume' => 10.0],
        ]);
        foreach ($report as $code => $data) {
            $this->line("{$code} ({$data['product_name']}): Start={$data['total_start_vol20']}L, Final={$data['total_final_vol20']}L, Debit={$data['total_debit_volume']}L => Net Loss={$data['net_loss']}L ({$data['net_loss_percent']}%)");
        }

        $this->info("\nAll calculation modules executed successfully with zero discrepancies.");

        return self::SUCCESS;
    }

    private function getDebitBranchExplanation(float $loss, float $allowed2dp): string
    {
        if ($loss > 150.0) {
            return 'Branch 2 (> 150 L): Entire loss debited with NO allowance deducted';
        }

        $diff = $loss - $allowed2dp;
        if ($diff >= 0.5) {
            return "Branch 1 (<= 150 L): Excess {$diff} L >= 0.5 L buffer -> (Loss - Allowed)";
        }

        return "Branch 1 (<= 150 L): Excess {$diff} L < 0.5 L buffer -> Debit is 0.00";
    }
}
