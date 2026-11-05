<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TruckDipCalculator;
use PHPUnit\Framework\TestCase;

class TruckDipCalculatorTest extends TestCase
{
    private TruckDipCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new TruckDipCalculator;
    }

    public function test_returns_zero_litres_for_zero_or_negative_dip(): void
    {
        $points = [
            ['dip_mm' => 100, 'litres' => 500],
            ['dip_mm' => 200, 'litres' => 1000],
        ];

        $this->assertSame(0, $this->calculator->interpolateLitres(0, $points));
        $this->assertSame(0, $this->calculator->interpolateLitres(-10, $points));
    }

    public function test_exact_calibration_point_match(): void
    {
        $points = [
            ['dip_mm' => 500, 'litres' => 2500],
            ['dip_mm' => 1000, 'litres' => 5000],
        ];

        $this->assertSame(2500, $this->calculator->interpolateLitres(500, $points));
        $this->assertSame(5000, $this->calculator->interpolateLitres(1000, $points));
    }

    public function test_straight_line_interpolation_between_points_rounds_to_whole_litre(): void
    {
        // Between (1000 mm, 5000 L) and (1100 mm, 5550 L)
        // At 1050 mm: 5000 + (50 / 100) * 550 = 5275 L
        $points = [
            ['dip_mm' => 1000, 'litres' => 5000],
            ['dip_mm' => 1100, 'litres' => 5550],
        ];

        $this->assertSame(5275, $this->calculator->interpolateLitres(1050, $points));

        // Test rounding: at 1033 mm -> 5000 + 0.33 * 550 = 5181.5 -> rounds to 5182
        $this->assertSame(5182, $this->calculator->interpolateLitres(1033, $points));

        // At 1032 mm -> 5000 + 0.32 * 550 = 5176.0 -> 5176
        $this->assertSame(5176, $this->calculator->interpolateLitres(1032, $points));
    }

    public function test_interpolation_below_lowest_calibration_point(): void
    {
        $points = [
            ['dip_mm' => 200, 'litres' => 1000],
            ['dip_mm' => 400, 'litres' => 2000],
        ];

        // Dip at 100 mm: (100 / 200) * 1000 = 500 L
        $this->assertSame(500, $this->calculator->interpolateLitres(100, $points));
    }

    public function test_extrapolation_above_highest_calibration_point(): void
    {
        $points = [
            ['dip_mm' => 1000, 'litres' => 5000],
            ['dip_mm' => 1200, 'litres' => 6000],
        ];

        // Slope = 1000 / 200 = 5 L/mm. At 1250 mm: 6000 + 50 * 5 = 6250 L
        $this->assertSame(6250, $this->calculator->interpolateLitres(1250, $points));
    }

    public function test_calculate_multi_compartment_truck_volume(): void
    {
        $compartments = [
            1 => [
                'dip_mm' => 1000,
                'calibration_points' => [
                    ['dip_mm' => 500, 'litres' => 2500],
                    ['dip_mm' => 1000, 'litres' => 5000],
                ],
            ],
            2 => [
                'dip_mm' => 1050,
                'calibration_points' => [
                    ['dip_mm' => 1000, 'litres' => 4000],
                    ['dip_mm' => 1100, 'litres' => 4500],
                ],
            ],
            3 => [
                'dip_mm' => 900,
                'calibration_points' => [
                    ['dip_mm' => 800, 'litres' => 3200],
                    ['dip_mm' => 1000, 'litres' => 4000],
                ],
            ],
        ];

        $result = $this->calculator->calculateTruckVolume($compartments);

        $this->assertSame(5000, $result['compartments'][1]);
        $this->assertSame(4250, $result['compartments'][2]); // 4000 + 0.5 * 500
        $this->assertSame(3600, $result['compartments'][3]); // 3200 + 0.5 * 800
        $this->assertSame(12850, $result['total_litres']);
    }
}
