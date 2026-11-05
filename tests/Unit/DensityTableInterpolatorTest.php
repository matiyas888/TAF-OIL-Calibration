<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\DensityBrackets;
use App\Services\DensityTableInterpolator;
use PHPUnit\Framework\TestCase;

class DensityTableInterpolatorTest extends TestCase
{
    private DensityTableInterpolator $interpolator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->interpolator = new DensityTableInterpolator;
    }

    public function test_bilinear_interpolation_at_exact_grid_point(): void
    {
        $xGrid = [740.0, 750.0];
        $yGrid = [20.0, 25.0];
        $table = [
            '740_20' => 740.0,
            '750_20' => 750.0,
            '740_25' => 735.8,
            '750_25' => 745.8,
        ];

        $val = $this->interpolator->interpolate2D(740.0, 20.0, $xGrid, $yGrid, $table, 1);
        $this->assertSame(740.0, $val);
    }

    public function test_bilinear_interpolation_in_middle_of_cell(): void
    {
        $xGrid = [740.0, 750.0];
        $yGrid = [20.0, 25.0];
        $table = [
            '740_20' => 740.0,
            '750_20' => 750.0,
            '740_25' => 736.0,
            '750_25' => 746.0,
        ];

        // Midpoint at density=745, temp=22.5
        // u = 0.5, v = 0.5
        // corners: 740, 750, 736, 746
        // average = 0.25 * (740 + 750 + 736 + 746) = 743.0
        $val = $this->interpolator->interpolate2D(745.0, 22.5, $xGrid, $yGrid, $table, 1);
        $this->assertSame(743.0, $val);
    }

    public function test_density20_rounds_to_one_decimal_place(): void
    {
        $xGrid = [740.0, 750.0];
        $yGrid = [20.0, 25.0];
        $table = [
            '740_20' => 740.123,
            '750_20' => 750.456,
            '740_25' => 735.789,
            '750_25' => 745.321,
        ];

        $tableData = [
            'x_grid' => $xGrid,
            'y_grid' => $yGrid,
            'table' => $table,
        ];

        $d20 = $this->interpolator->density20(742.0, 21.0, $tableData);
        $this->assertSame(round($d20, 1), $d20);
    }

    public function test_factor20_rounds_to_six_decimal_places(): void
    {
        $xGrid = [740.0, 750.0];
        $yGrid = [20.0, 25.0];
        $table = [
            '740_20' => 1.000000,
            '750_20' => 1.000000,
            '740_25' => 0.993854,
            '750_25' => 0.994123,
        ];

        $tableData = [
            'x_grid' => $xGrid,
            'y_grid' => $yGrid,
            'table' => $table,
        ];

        $vcf = $this->interpolator->factor20(745.0, 22.5, $tableData);
        $this->assertSame(round($vcf, 6), $vcf);
    }

    public function test_density_brackets_corrected_mode_covers_701_to_703(): void
    {
        // 702 kg/m³ should properly fall between 700 and 705
        $bracket = DensityBrackets::resolveCorrectedBracket(702.0);
        $this->assertSame(700.0, $bracket['lower']);
        $this->assertSame(705.0, $bracket['upper']);
    }

    public function test_density_brackets_legacy_access_simulation_replicates_vba_bug(): void
    {
        // Legacy Access VBA flaw: 701-703 was shifted into 703-705 column
        $bracket = DensityBrackets::resolveLegacyAccessBracket(702.0);
        $this->assertTrue($bracket['legacy_adjusted']);
        $this->assertSame(700.0, $bracket['lower']);
        $this->assertSame(705.0, $bracket['upper']);
    }
}
