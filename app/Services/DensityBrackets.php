<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Density and Temperature brackets ported from Access VBA.
 *
 * Provides bracket resolution for:
 * - Clean corrected mode (fixing the missing 701-703 bracket gap)
 * - Legacy Access simulation mode (replicating the 701-703 bug to validate historical records)
 */
final class DensityBrackets
{
    /**
     * Standard corrected density grid boundaries (kg/m³).
     * Continuous 5-step grid from 700 to 920.
     *
     * @var list<float>
     */
    public const array STANDARD_DENSITY_GRID = [
        700.0, 705.0, 710.0, 715.0, 720.0, 725.0, 730.0, 735.0, 740.0, 745.0,
        750.0, 755.0, 760.0, 765.0, 770.0, 775.0, 780.0, 785.0, 790.0, 795.0,
        800.0, 805.0, 810.0, 815.0, 820.0, 825.0, 830.0, 835.0, 840.0, 845.0,
        850.0, 855.0, 860.0, 865.0, 870.0, 875.0, 880.0, 885.0, 890.0, 895.0,
        900.0, 905.0, 910.0, 915.0, 920.0,
    ];

    /**
     * Standard temperature grid boundaries (°C).
     * Step of 0.5°C or 1.0°C from 0.0 to 50.0.
     *
     * @var list<float>
     */
    public const array STANDARD_TEMPERATURE_GRID = [
        0.0, 5.0, 10.0, 15.0, 16.0, 17.0, 18.0, 19.0, 20.0, 21.0, 22.0, 23.0,
        24.0, 25.0, 26.0, 27.0, 28.0, 29.0, 30.0, 31.0, 32.0, 33.0, 34.0, 35.0,
        36.0, 37.0, 38.0, 39.0, 40.0, 45.0, 50.0,
    ];

    /**
     * Resolve lower and upper density brackets without gaps (Corrected Port).
     *
     * @param  float  $density  Observed density in kg/m³
     * @return array{lower: float, upper: float}
     */
    public static function resolveCorrectedBracket(float $density): array
    {
        $grid = self::STANDARD_DENSITY_GRID;
        $count = count($grid);

        if ($density <= $grid[0]) {
            return ['lower' => $grid[0], 'upper' => $grid[1]];
        }

        if ($density >= $grid[$count - 1]) {
            return ['lower' => $grid[$count - 2], 'upper' => $grid[$count - 1]];
        }

        for ($i = 0; $i < $count - 1; $i++) {
            if ($density >= $grid[$i] && $density <= $grid[$i + 1]) {
                return ['lower' => $grid[$i], 'upper' => $grid[$i + 1]];
            }
        }

        return ['lower' => $grid[0], 'upper' => $grid[1]];
    }

    /**
     * Resolve legacy Access bracket, reproducing the VBA flaw where 701-703
     * silently mapped into the 703-705 column bracket.
     *
     * @param  float  $density  Observed density in kg/m³
     * @return array{lower: float, upper: float, legacy_adjusted: bool}
     */
    public static function resolveLegacyAccessBracket(float $density): array
    {
        $adjusted = false;

        // Legacy Access VBA flaw: 701 to 703 used 703 to 705 bracket
        if ($density > 701.0 && $density < 703.0) {
            $density = 703.1;
            $adjusted = true;
        }

        $bracket = self::resolveCorrectedBracket($density);

        return [
            'lower' => $bracket['lower'],
            'upper' => $bracket['upper'],
            'legacy_adjusted' => $adjusted,
        ];
    }
}
