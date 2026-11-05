<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TruckCalibration;
use InvalidArgumentException;

/**
 * Service to calculate truck compartment volume from dip measurements in mm
 * using straight-line linear interpolation against calibration points.
 */
final class TruckDipCalculator
{
    /**
     * Interpolate litres for a single compartment given its dip measurement in mm
     * and calibration points array [[dip_mm, litres], ...].
     *
     * @param  float|int  $dipMm  Observed dip in millimeters.
     * @param  array<array{dip_mm: float|int, litres: float|int}>  $calibrationPoints  List of calibration points for the compartment.
     * @return int Litres rounded to the nearest whole litre.
     */
    public function interpolateLitres(float|int $dipMm, array $calibrationPoints): int
    {
        if ($dipMm <= 0) {
            return 0;
        }

        if (empty($calibrationPoints)) {
            throw new InvalidArgumentException('Calibration points cannot be empty.');
        }

        // Sort calibration points ascending by dip_mm
        usort($calibrationPoints, fn (array $a, array $b): int => $a['dip_mm'] <=> $b['dip_mm']);

        // Check if dip matches an exact calibration point
        foreach ($calibrationPoints as $point) {
            if ((float) $point['dip_mm'] === (float) $dipMm) {
                return (int) round((float) $point['litres']);
            }
        }

        $minPoint = $calibrationPoints[0];
        $maxPoint = $calibrationPoints[count($calibrationPoints) - 1];

        // Below lowest calibration point
        if ($dipMm < $minPoint['dip_mm']) {
            if ($minPoint['dip_mm'] > 0) {
                // Interpolate from (0, 0) to lowest calibration point
                $fraction = $dipMm / $minPoint['dip_mm'];
                $litres = $fraction * $minPoint['litres'];

                return (int) round($litres);
            }

            return (int) round((float) $minPoint['litres']);
        }

        // Above highest calibration point: extrapolate using the last slope
        if ($dipMm > $maxPoint['dip_mm']) {
            if (count($calibrationPoints) > 1) {
                $secondLast = $calibrationPoints[count($calibrationPoints) - 2];
                $dx = (float) $maxPoint['dip_mm'] - (float) $secondLast['dip_mm'];
                $dy = (float) $maxPoint['litres'] - (float) $secondLast['litres'];
                $slope = $dx > 0 ? $dy / $dx : 0;
                $litres = (float) $maxPoint['litres'] + ($dipMm - (float) $maxPoint['dip_mm']) * $slope;

                return (int) round($litres);
            }

            return (int) round((float) $maxPoint['litres']);
        }

        // Find surrounding points [x0, x1]
        for ($i = 0; $i < count($calibrationPoints) - 1; $i++) {
            $p0 = $calibrationPoints[$i];
            $p1 = $calibrationPoints[$i + 1];

            $x0 = (float) $p0['dip_mm'];
            $x1 = (float) $p1['dip_mm'];
            $y0 = (float) $p0['litres'];
            $y1 = (float) $p1['litres'];

            if ($dipMm >= $x0 && $dipMm <= $x1) {
                $dx = $x1 - $x0;
                if ($dx == 0.0) {
                    return (int) round($y0);
                }

                $litres = $y0 + (($dipMm - $x0) / $dx) * ($y1 - $y0);

                return (int) round($litres);
            }
        }

        return (int) round((float) $maxPoint['litres']);
    }

    /**
     * Calculate volume for all compartments and sum total truck volume.
     *
     * @param  array<int, array{dip_mm: float|int, calibration_points: array<array{dip_mm: float|int, litres: float|int}>}>  $compartments
     * @return array{compartments: array<int, int>, total_litres: int}
     */
    public function calculateTruckVolume(array $compartments): array
    {
        $compartmentVolumes = [];
        $total = 0;

        foreach ($compartments as $compNo => $data) {
            $vol = $this->interpolateLitres($data['dip_mm'], $data['calibration_points']);
            $compartmentVolumes[$compNo] = $vol;
            $total += $vol;
        }

        return [
            'compartments' => $compartmentVolumes,
            'total_litres' => $total,
        ];
    }

    /**
     * Calculate litres for a truck compartment given its plate number and dip mm.
     * Looks up calibration points from database and interpolates.
     */
    public function calculateLitres(string $plateNo, int $compartmentNo, float|int $dipMm): int
    {
        if ($dipMm <= 0) {
            return 0;
        }

        $records = TruckCalibration::query()
            ->where('plate_no', $plateNo)
            ->where('compartment_no', $compartmentNo)
            ->orderBy('dip_mm')
            ->get(['dip_mm', 'litres'])
            ->map(fn ($r) => ['dip_mm' => (float) $r->dip_mm, 'litres' => (float) $r->litres])
            ->toArray();

        if (empty($records)) {
            return (int) round((float) $dipMm);
        }

        return $this->interpolateLitres($dipMm, $records);
    }
}
