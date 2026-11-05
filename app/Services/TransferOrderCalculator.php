<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service to calculate Transfer Order (TO) volumes, losses, debit notes,
 * and tank expansion according to legacy Access business rules.
 */
final class TransferOrderCalculator
{
    /**
     * Thermal cubic expansion coefficient of steel (per degree Celsius).
     * Rule: (1 + 3 * 0.000011 * (T - 20)) * Vol20.
     */
    public const float STEEL_EXPANSION_COEFFICIENT = 0.000011;

    /**
     * Debit threshold in litres. If loss exceeds this threshold,
     * no allowance is deducted and the entire loss is debited.
     */
    public const float DEBIT_LOSS_THRESHOLD = 150.0;

    /**
     * Threshold buffer in litres over the 2-decimal allowed loss.
     */
    public const float DEBIT_BUFFER = 0.5;

    public function __construct(
        private readonly ?DensityTableInterpolator $interpolator = null,
    ) {}

    /**
     * Calculate metered total across meters.
     *
     * Rule: (TOMET - FROMMET + DRAINED) summed over meters.
     *
     * @param  array<array{from_meter: float|int, to_meter: float|int, drained?: float|int}>  $meters
     * @return float Total metered litres
     */
    public function calculateMeteredTotal(array $meters): float
    {
        $total = 0.0;
        foreach ($meters as $meter) {
            $from = (float) ($meter['from_meter'] ?? 0.0);
            $to = (float) ($meter['to_meter'] ?? 0.0);
            $drained = (float) ($meter['drained'] ?? 0.0);

            $diff = $to - $from + $drained;
            $total += $diff;
        }

        return $total;
    }

    /**
     * Calculate Volume at 20°C.
     *
     * @param  float  $volume  Observed or metered volume in litres
     * @param  float  $factor  Volume conversion factor to 20°C
     * @return float Volume at 20°C
     */
    public function calculateVolume20(float $volume, float $factor): float
    {
        return $volume * $factor;
    }

    /**
     * Calculate absolute loss between start volume at 20°C and final volume at 20°C.
     *
     * Rule: Vol = |Vol20_start - Vol20_final|.
     *
     * @param  float  $vol20Start  Start volume at 20°C
     * @param  float  $vol20Final  Final volume at 20°C
     * @return float Absolute volume loss
     */
    public function calculateLoss(float $vol20Start, float $vol20Final): float
    {
        return abs($vol20Start - $vol20Final);
    }

    /**
     * Calculate allowed loss.
     *
     * Rule: round(Vol20_start * percent / 100).
     *
     * @param  float  $vol20Start  Start volume at 20°C
     * @param  float  $percent  Allowable loss percentage (e.g. 0.08 or 0.12)
     * @return int Rounded allowed loss volume in whole litres
     */
    public function calculateAllowedLoss(float $vol20Start, float $percent): int
    {
        return (int) round(($vol20Start * $percent) / 100.0);
    }

    /**
     * Calculate allowed loss with 2-decimal precision used for debit note thresholding.
     *
     * @param  float  $vol20Start  Start volume at 20°C
     * @param  float  $percent  Allowable loss percentage
     * @return float Allowed loss to 2 decimal places
     */
    public function calculateAllowedLoss2dp(float $vol20Start, float $percent): float
    {
        return round(($vol20Start * $percent) / 100.0, 2);
    }

    /**
     * Calculate debit volume according to the two-branch rule:
     * - Loss <= 150 L: debit is Vol - allowed, but ONLY if loss exceeds the 2-decimal
     *   allowed figure by at least 0.5 L.
     * - Loss > 150 L: debit is the entire loss, with no allowance deducted.
     *
     * @param  float  $loss  Absolute loss volume (Vol20_start - Vol20_final)
     * @param  float  $vol20Start  Start volume at 20°C
     * @param  float  $percent  Allowable loss percentage
     * @return float Debit volume in litres
     */
    public function calculateDebitVolume(float $loss, float $vol20Start, float $percent): float
    {
        if ($loss <= 0) {
            return 0.0;
        }

        if ($loss > self::DEBIT_LOSS_THRESHOLD) {
            // Over 150 L: entire loss, no allowance
            return round($loss, 2);
        }

        // Loss of 150 L or less
        $allowed2dp = $this->calculateAllowedLoss2dp($vol20Start, $percent);
        $allowedRounded = (float) $this->calculateAllowedLoss($vol20Start, $percent);

        // Check if loss exceeds the 2-decimal allowed figure by at least 0.5 L
        if (($loss - $allowed2dp) >= self::DEBIT_BUFFER) {
            $debit = $loss - $allowedRounded;

            return max(0.0, round($debit, 2));
        }

        return 0.0;
    }

    /**
     * Calculate debit amount: Voldebit * price per litre.
     *
     * @param  float  $debitVolume  Debit volume in litres
     * @param  float  $pricePerLitre  Unit price per litre
     * @return float Total debit amount in currency units
     */
    public function calculateDebitAmount(float $debitVolume, float $pricePerLitre): float
    {
        return round($debitVolume * $pricePerLitre, 2);
    }

    /**
     * Tank steel expansion volume correction.
     * Rule: (1 + 3 * 0.000011 * (T - 20)) * Vol20.
     *
     * @param  float  $vol20  Volume at 20°C
     * @param  float  $temperature  Tank temperature in °C
     * @return float Corrected volume accounting for steel expansion
     */
    public function calculateSteelExpansion(float $vol20, float $temperature): float
    {
        $factor = 1.0 + 3.0 * self::STEEL_EXPANSION_COEFFICIENT * ($temperature - 20.0);

        return $vol20 * $factor;
    }

    /**
     * Full Transfer Order evaluation.
     *
     * @param  array{
     *     start_observed_litres: float,
     *     start_factor: float,
     *     meters?: array<array{from_meter: float|int, to_meter: float|int, drained?: float|int}>,
     *     final_observed_litres?: float,
     *     final_factor: float,
     *     allowable_loss_percent: float,
     *     price_per_litre: float,
     *     use_metered?: bool
     * } $data
     * @return array{
     *     start_vol20: float,
     *     final_litres: float,
     *     final_vol20: float,
     *     loss_volume: float,
     *     allowed_loss_volume: int,
     *     allowed_loss_2dp: float,
     *     debit_volume: float,
     *     debit_amount: float
     * }
     */
    public function evaluateTransferOrder(array $data): array
    {
        $startVol20 = $this->calculateVolume20(
            $data['start_observed_litres'],
            $data['start_factor']
        );

        $useMetered = $data['use_metered'] ?? ! empty($data['meters']);

        if ($useMetered && ! empty($data['meters'])) {
            $finalLitres = $this->calculateMeteredTotal($data['meters']);
        } else {
            $finalLitres = (float) ($data['final_observed_litres'] ?? 0.0);
        }

        $finalVol20 = $this->calculateVolume20($finalLitres, $data['final_factor']);
        $loss = $this->calculateLoss($startVol20, $finalVol20);

        $percent = $data['allowable_loss_percent'];
        $allowed = $this->calculateAllowedLoss($startVol20, $percent);
        $allowed2dp = $this->calculateAllowedLoss2dp($startVol20, $percent);
        $debitVol = $this->calculateDebitVolume($loss, $startVol20, $percent);
        $debitAmt = $this->calculateDebitAmount($debitVol, $data['price_per_litre']);

        return [
            'start_vol20' => round($startVol20, 2),
            'final_litres' => round($finalLitres, 2),
            'final_vol20' => round($finalVol20, 2),
            'loss_volume' => round($loss, 2),
            'allowed_loss_volume' => $allowed,
            'allowed_loss_2dp' => $allowed2dp,
            'debit_volume' => $debitVol,
            'debit_amount' => $debitAmt,
        ];
    }
}
