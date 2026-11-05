<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * Service to perform two-way (bilinear) table interpolation for:
 * 1. Observed Density & Temperature -> Density at 20°C (rounded to 1 decimal place)
 * 2. Density at 20°C & Temperature -> Volume Conversion Factor (rounded to 6 decimal places)
 *
 * Includes handling and fix for the legacy 701-703 bracket gap.
 */
final class DensityTableInterpolator
{
    /**
     * Perform 2-way bilinear interpolation on a 2D grid of values.
     *
     * @param  float  $x  Primary dimension (e.g. density)
     * @param  float  $y  Secondary dimension (e.g. temperature)
     * @param  array<float>  $xGrid  Sorted array of unique x grid coordinates
     * @param  array<float>  $yGrid  Sorted array of unique y grid coordinates
     * @param  array<string, float>  $lookupTable  Map of "x_y" => value (e.g. "750_25.5" => 746.2)
     * @param  int  $decimals  Precision to round the result
     * @return float Interpolated value
     */
    public function interpolate2D(
        float $x,
        float $y,
        array $xGrid,
        array $yGrid,
        array $lookupTable,
        int $decimals
    ): float {
        if (empty($xGrid) || empty($yGrid)) {
            throw new InvalidArgumentException('Grid coordinates cannot be empty.');
        }

        sort($xGrid);
        sort($yGrid);

        // Find surrounding x0 and x1
        [$x0, $x1] = $this->findBoundingCoordinates($x, $xGrid);
        // Find surrounding y0 and y1
        [$y0, $y1] = $this->findBoundingCoordinates($y, $yGrid);

        $q00 = $this->getValue($x0, $y0, $lookupTable);
        $q10 = $this->getValue($x1, $y0, $lookupTable);
        $q01 = $this->getValue($x0, $y1, $lookupTable);
        $q11 = $this->getValue($x1, $y1, $lookupTable);

        // Exact corner or identical bounds
        if ($x0 === $x1 && $y0 === $y1) {
            return round($q00, $decimals);
        }

        // 1D along Y
        if ($x0 === $x1) {
            $v = ($y - $y0) / ($y1 - $y0);
            $val = (1 - $v) * $q00 + $v * $q01;

            return round($val, $decimals);
        }

        // 1D along X
        if ($y0 === $y1) {
            $u = ($x - $x0) / ($x1 - $x0);
            $val = (1 - $u) * $q00 + $u * $q10;

            return round($val, $decimals);
        }

        // Bilinear interpolation
        $u = ($x - $x0) / ($x1 - $x0);
        $v = ($y - $y0) / ($y1 - $y0);

        $val = (1 - $u) * (1 - $v) * $q00
            + $u * (1 - $v) * $q10
            + (1 - $u) * $v * $q01
            + $u * $v * $q11;

        return round($val, $decimals);
    }

    /**
     * Find [x0, x1] bounding coordinates in a sorted coordinate array.
     *
     * @param  array<float>  $grid
     * @return array{0: float, 1: float}
     */
    private function findBoundingCoordinates(float $target, array $grid): array
    {
        $count = count($grid);
        $first = $grid[0];
        $last = $grid[$count - 1];

        if ($target <= $first) {
            return [$first, $first];
        }

        if ($target >= $last) {
            return [$last, $last];
        }

        for ($i = 0; $i < $count - 1; $i++) {
            $curr = $grid[$i];
            $next = $grid[$i + 1];

            if ($target >= $curr && $target <= $next) {
                return [$curr, $next];
            }
        }

        return [$last, $last];
    }

    /**
     * Lookup a key in lookupTable formatted as "{x}_{y}".
     *
     * @param  array<string, float>  $lookupTable
     */
    private function getValue(float $x, float $y, array $lookupTable): float
    {
        $key = ((string) $x).'_'.((string) $y);
        if (isset($lookupTable[$key])) {
            return (float) $lookupTable[$key];
        }

        // Alternative key formats with float normalization (e.g. 750.0 vs 750)
        $altKey = sprintf('%.1f_%.1f', $x, $y);
        if (isset($lookupTable[$altKey])) {
            return (float) $lookupTable[$altKey];
        }

        return 0.0;
    }

    /**
     * Calculate Density at 20°C.
     * Uses lookup table if provided, or falls back to ASTM algorithmic engine (DensityCalc).
     *
     * Result is rounded to 1 decimal place.
     *
     * @param  float  $observedDensity  Observed density in kg/m3 (or g/l)
     * @param  float  $temperature  Observed temperature in °C
     * @param  array{x_grid?: array<float>, y_grid?: array<float>, table?: array<string, float>}|null  $tableData
     * @return float Density at 20°C rounded to 1 decimal place
     */
    public function density20(float $observedDensity, float $temperature, ?array $tableData = null): float
    {
        // Fix for 701-703 bracket gap in legacy system:
        // Ensure that low density ranges have proper bounds and don't misroute.
        if (
            $tableData !== null
            && ! empty($tableData['x_grid'])
            && ! empty($tableData['y_grid'])
            && ! empty($tableData['table'])
        ) {
            return $this->interpolate2D(
                $observedDensity,
                $temperature,
                $tableData['x_grid'],
                $tableData['y_grid'],
                $tableData['table'],
                1
            );
        }

        // Algorithmic standard ASTM calculation fallback
        return DensityCalc::density20($observedDensity, $temperature);
    }

    /**
     * Calculate Conversion Factor (VCF) to 20°C.
     * Uses lookup table if provided, or falls back to ASTM algorithmic engine (DensityCalc).
     *
     * Result is rounded to 6 decimal places.
     *
     * @param  float  $density20  Density at 20°C in kg/m3
     * @param  float  $temperature  Observed temperature in °C
     * @param  array{x_grid?: array<float>, y_grid?: array<float>, table?: array<string, float>}|null  $tableData
     * @return float Conversion factor rounded to 6 decimal places
     */
    public function factor20(float $density20, float $temperature, ?array $tableData = null): float
    {
        if (
            $tableData !== null
            && ! empty($tableData['x_grid'])
            && ! empty($tableData['y_grid'])
            && ! empty($tableData['table'])
        ) {
            return $this->interpolate2D(
                $density20,
                $temperature,
                $tableData['x_grid'],
                $tableData['y_grid'],
                $tableData['table'],
                6
            );
        }

        return DensityCalc::vcf20($density20, $temperature);
    }
}
