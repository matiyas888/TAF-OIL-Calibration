<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TransferOrderCalculator;
use PHPUnit\Framework\TestCase;

class TransferOrderCalculatorTest extends TestCase
{
    private TransferOrderCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new TransferOrderCalculator;
    }

    public function test_calculate_metered_total_sums_meters_with_drained_volume(): void
    {
        // Meter 1: to=15000, from=10000, drained=50 -> 5050
        // Meter 2: to=8500, from=5000, drained=25 -> 3525
        // Total = 8575
        $meters = [
            ['from_meter' => 10000, 'to_meter' => 15000, 'drained' => 50],
            ['from_meter' => 5000, 'to_meter' => 8500, 'drained' => 25],
        ];

        $this->assertSame(8575.0, $this->calculator->calculateMeteredTotal($meters));
    }

    public function test_calculate_volume20_multiplies_litres_by_factor(): void
    {
        $vol20 = $this->calculator->calculateVolume20(10000.0, 0.995000);
        $this->assertEqualsWithDelta(9950.0, $vol20, 0.001);
    }

    public function test_calculate_loss_is_absolute_difference(): void
    {
        $this->assertSame(120.0, $this->calculator->calculateLoss(10000.0, 9880.0));
        $this->assertSame(50.0, $this->calculator->calculateLoss(9950.0, 10000.0));
    }

    public function test_calculate_allowed_loss_rounds_to_whole_litre(): void
    {
        // Vol20_start = 45000, percent = 0.08%
        // Exact = 45000 * 0.08 / 100 = 36.0 -> 36 L
        $this->assertSame(36, $this->calculator->calculateAllowedLoss(45000.0, 0.08));

        // Vol20_start = 45678, percent = 0.08%
        // Exact = 45678 * 0.08 / 100 = 36.5424 -> rounds to 37 L
        $this->assertSame(37, $this->calculator->calculateAllowedLoss(45678.0, 0.08));

        // 2-decimal figure
        $this->assertSame(36.54, $this->calculator->calculateAllowedLoss2dp(45678.0, 0.08));
    }

    public function test_debit_volume_under_150_l_with_at_least_0_5_l_excess(): void
    {
        // Vol20_start = 40000, percent = 0.08%
        // Allowed 2dp = 32.00, Allowed rounded = 32
        // Loss = 80 L (<= 150 L)
        // Excess = 80 - 32.00 = 48.0 L >= 0.5 L
        // Debit = loss - allowed = 80 - 32 = 48 L
        $debit = $this->calculator->calculateDebitVolume(80.0, 40000.0, 0.08);
        $this->assertSame(48.0, $debit);
    }

    public function test_debit_volume_under_150_l_when_excess_is_below_0_5_l_returns_zero(): void
    {
        // Vol20_start = 40000, percent = 0.08%
        // Allowed 2dp = 32.00
        // Loss = 32.40 L (excess = 32.40 - 32.00 = 0.40 L < 0.5 L)
        // Rule: only if loss exceeds 2-decimal allowed by at least 0.5 L -> debit is 0!
        $debit = $this->calculator->calculateDebitVolume(32.40, 40000.0, 0.08);
        $this->assertSame(0.0, $debit);
    }

    public function test_debit_volume_when_loss_is_within_allowance_returns_zero(): void
    {
        // Loss = 20 L, Allowed = 32 L
        $debit = $this->calculator->calculateDebitVolume(20.0, 40000.0, 0.08);
        $this->assertSame(0.0, $debit);
    }

    public function test_debit_volume_over_150_l_debits_entire_loss_without_allowance(): void
    {
        // Vol20_start = 40000, percent = 0.08%
        // Allowed = 32 L
        // Loss = 185.5 L (> 150 L)
        // Rule: debit is the entire loss (185.5 L), with NO allowance deducted!
        $debit = $this->calculator->calculateDebitVolume(185.5, 40000.0, 0.08);
        $this->assertSame(185.5, $debit);
    }

    public function test_calculate_debit_amount_multiplies_volume_by_price(): void
    {
        // Debit volume = 48 L, price per litre = 85.50 ETB
        // Total = 48 * 85.50 = 4104.00
        $amount = $this->calculator->calculateDebitAmount(48.0, 85.50);
        $this->assertSame(4104.0, $amount);
    }

    public function test_tank_steel_expansion(): void
    {
        // Vol20 = 100000 L, T = 30°C (10°C above 20°C)
        // Expansion factor = 1 + 3 * 0.000011 * 10 = 1 + 0.00033 = 1.00033
        // Corrected = 100033 L
        $expanded = $this->calculator->calculateSteelExpansion(100000.0, 30.0);
        $this->assertEqualsWithDelta(100033.0, $expanded, 0.01);
    }

    public function test_evaluate_transfer_order_complete_pipeline(): void
    {
        $data = [
            'start_observed_litres' => 45000.0,
            'start_factor' => 0.998200,
            'meters' => [
                ['from_meter' => 10000, 'to_meter' => 35000, 'drained' => 0],
                ['from_meter' => 5000, 'to_meter' => 24800, 'drained' => 20],
            ],
            'final_factor' => 0.998100,
            'allowable_loss_percent' => 0.08,
            'price_per_litre' => 90.0,
            'use_metered' => true,
        ];

        // Start Vol20 = 45000 * 0.998200 = 44919.00
        // Final Litres = (25000) + (19820) = 44820.00
        // Final Vol20 = 44820 * 0.998100 = 44734.84
        // Loss = 44919.00 - 44734.84 = 184.16 L
        // Since Loss (184.16) > 150 L -> debit is the entire 184.16 L!
        // Debit amount = 184.16 * 90.0 = 16574.40

        $result = $this->calculator->evaluateTransferOrder($data);

        $this->assertSame(44919.0, $result['start_vol20']);
        $this->assertSame(44820.0, $result['final_litres']);
        $this->assertSame(44734.84, $result['final_vol20']);
        $this->assertSame(184.16, $result['loss_volume']);
        $this->assertSame(184.16, $result['debit_volume']);
        $this->assertSame(16574.4, $result['debit_amount']);
    }
}
