<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service to calculate daily petroleum stock reconciliation and operational variance.
 *
 * Rules:
 * Available = receipts + opening + returns.
 * Closing balance = Available - sales (+/- adjustment).
 * Operational variance = total stock - closing balance.
 */
final class DailyStockCalculator
{
    /**
     * Calculate available stock.
     *
     * Rule: Available = receipts + opening + returns.
     *
     * @param  float  $receipts  Stock received (litres)
     * @param  float  $opening  Opening stock balance (litres)
     * @param  float  $returns  Stock returned (litres)
     * @return float Available stock in litres
     */
    public function calculateAvailable(float $receipts, float $opening, float $returns = 0.0): float
    {
        return $receipts + $opening + $returns;
    }

    /**
     * Calculate book closing balance.
     *
     * Rule: Closing balance = Available - sales (+/- adjustment).
     *
     * @param  float  $available  Available stock in litres
     * @param  float  $sales  Total sales in litres
     * @param  float  $adjustment  Inventory adjustment (+ or -)
     * @return float Closing book balance in litres
     */
    public function calculateClosingBalance(float $available, float $sales, float $adjustment = 0.0): float
    {
        return $available - $sales + $adjustment;
    }

    /**
     * Calculate operational variance between physical dip stock and book balance.
     *
     * Rule: Operational variance = total stock - closing balance.
     *
     * @param  float  $totalStock  Total measured physical stock (tanks + pipeline)
     * @param  float  $closingBalance  Closing book balance
     * @return float Operational variance in litres (positive = gain, negative = shortage)
     */
    public function calculateOperationalVariance(float $totalStock, float $closingBalance): float
    {
        return $totalStock - $closingBalance;
    }

    /**
     * Full stock reconciliation calculation.
     *
     * @param  array{
     *     opening: float,
     *     receipts: float,
     *     returns?: float,
     *     sales: float,
     *     adjustment?: float,
     *     physical_tank_stock: float,
     *     pipeline_stock?: float
     * } $data
     * @return array{
     *     available: float,
     *     closing_balance: float,
     *     total_physical_stock: float,
     *     operational_variance: float
     * }
     */
    public function reconcile(array $data): array
    {
        $returns = (float) ($data['returns'] ?? 0.0);
        $adjustment = (float) ($data['adjustment'] ?? 0.0);
        $pipeline = (float) ($data['pipeline_stock'] ?? 0.0);

        $available = $this->calculateAvailable(
            (float) $data['receipts'],
            (float) $data['opening'],
            $returns
        );

        $closing = $this->calculateClosingBalance(
            $available,
            (float) $data['sales'],
            $adjustment
        );

        $totalPhysical = (float) $data['physical_tank_stock'] + $pipeline;
        $variance = $this->calculateOperationalVariance($totalPhysical, $closing);

        return [
            'available' => round($available, 2),
            'closing_balance' => round($closing, 2),
            'total_physical_stock' => round($totalPhysical, 2),
            'operational_variance' => round($variance, 2),
        ];
    }
}
