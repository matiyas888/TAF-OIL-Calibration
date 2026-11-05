<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service to calculate Product Variance Reports.
 *
 * Rules:
 * Net loss = Voldebit + (Vol20_final - Vol20_start).
 * Net loss % = Net loss / Vol20_start * 100.
 * Totals are grouped by product.
 */
final class ProductVarianceCalculator
{
    /**
     * Calculate net loss for a transfer order or aggregate product row.
     *
     * Rule: Net loss = Voldebit + (Vol20_final - Vol20_start).
     *
     * @param  float  $debitVolume  Volume charged on debit note (Voldebit)
     * @param  float  $vol20Start  Starting volume at 20°C
     * @param  float  $vol20Final  Final volume at 20°C
     * @return float Net loss volume
     */
    public function calculateNetLoss(float $debitVolume, float $vol20Start, float $vol20Final): float
    {
        return $debitVolume + ($vol20Final - $vol20Start);
    }

    /**
     * Calculate net loss percentage.
     *
     * Rule: Net loss % = Net loss / Vol20_start * 100.
     *
     * @param  float  $netLoss  Net loss volume
     * @param  float  $vol20Start  Starting volume at 20°C
     * @return float Net loss percentage
     */
    public function calculateNetLossPercent(float $netLoss, float $vol20Start): float
    {
        if ($vol20Start == 0.0) {
            return 0.0;
        }

        return ($netLoss / $vol20Start) * 100.0;
    }

    /**
     * Group and summarize transfer order variance lines by product.
     *
     * @param  array<array{
     *     product_id: string|int,
     *     product_name?: string,
     *     vol20_start: float,
     *     vol20_final: float,
     *     debit_volume: float,
     *     sales_volume?: float,
     *     temperature_loss?: float
     * }>  $records
     * @return array<string|int, array{
     *     product_name: string,
     *     total_start_vol20: float,
     *     total_final_vol20: float,
     *     total_debit_volume: float,
     *     total_sales_volume: float,
     *     total_temperature_loss: float,
     *     net_loss: float,
     *     net_loss_percent: float
     * }>
     */
    public function generateReport(array $records): array
    {
        $grouped = [];

        foreach ($records as $row) {
            $prodId = $row['product_id'];
            if (! isset($grouped[$prodId])) {
                $grouped[$prodId] = [
                    'product_name' => $row['product_name'] ?? (string) $prodId,
                    'total_start_vol20' => 0.0,
                    'total_final_vol20' => 0.0,
                    'total_debit_volume' => 0.0,
                    'total_sales_volume' => 0.0,
                    'total_temperature_loss' => 0.0,
                    'net_loss' => 0.0,
                    'net_loss_percent' => 0.0,
                ];
            }

            $grouped[$prodId]['total_start_vol20'] += (float) $row['vol20_start'];
            $grouped[$prodId]['total_final_vol20'] += (float) $row['vol20_final'];
            $grouped[$prodId]['total_debit_volume'] += (float) $row['debit_volume'];
            $grouped[$prodId]['total_sales_volume'] += (float) ($row['sales_volume'] ?? 0.0);
            $grouped[$prodId]['total_temperature_loss'] += (float) ($row['temperature_loss'] ?? 0.0);
        }

        foreach ($grouped as $prodId => &$summary) {
            $summary['net_loss'] = round(
                $this->calculateNetLoss(
                    $summary['total_debit_volume'],
                    $summary['total_start_vol20'],
                    $summary['total_final_vol20']
                ),
                2
            );
            $summary['net_loss_percent'] = round(
                $this->calculateNetLossPercent(
                    $summary['net_loss'],
                    $summary['total_start_vol20']
                ),
                4
            );
            $summary['total_start_vol20'] = round($summary['total_start_vol20'], 2);
            $summary['total_final_vol20'] = round($summary['total_final_vol20'], 2);
            $summary['total_debit_volume'] = round($summary['total_debit_volume'], 2);
        }
        unset($summary);

        return $grouped;
    }
}
