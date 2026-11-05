<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Density / VCF calculation – PHP port of CALCUL_DE_DENSITE_STANDATD.xlsx
 * (sheets: dens_15, vcf_15, dens_20, vcf_20, CALCUL).
 *
 * All Excel ROUND / TRUNC steps are reproduced so results match the sheet.
 */
final class DensityCalc
{
    // Product-class constants (K0, K1)
    private const array GAS = [346.4228, 0.4388];    // rho15 <= 770

    private const array JET = [594.5418, 0.0];       // 770 .. 839

    private const array FO = [186.9696, 0.4862];     // >= 839

    private const float TA = -0.00336312;            // transition zone  alpha = A + B/rho^2

    private const float TB = 2680.3206;

    private const float SEED = 778.84;                // starting density, transition zone

    /**
     * Excel works on 15 significant digits.
     */
    private static function p15(float $x): float
    {
        return (float) sprintf('%.15g', $x);
    }

    /**
     * Excel ROUND(x, n) – half away from zero.
     */
    public static function rnd(float $x, int $n = 0): float
    {
        $f = 10 ** $n;

        return round(self::p15($x * $f)) / $f;
    }

    /**
     * Excel TRUNC(x, n) – truncate towards zero.
     */
    public static function trc(float $x, int $n = 0): float
    {
        $f = 10 ** $n;
        $v = self::p15($x * $f);

        return ($v < 0 ? ceil($v) : floor($v)) / $f;
    }

    /**
     * Alpha15 product constants for column keys:
     * 'G' gasoline, 'T' transition, 'J' jet, 'F' fuel oil.
     *
     * @return array<string, array{float, float}|null>
     */
    private static function cols(): array
    {
        return ['G' => self::GAS, 'T' => null, 'J' => self::JET, 'F' => self::FO];
    }

    private static function alpha(string $c, float $rho): float
    {
        if ($c === 'T') {
            return self::rnd(self::TA + self::TB / ($rho * $rho), 7);
        }

        [$k0, $k1] = self::cols()[$c];

        return self::rnd($k0 / ($rho * $rho) + $k1 / $rho, 7);
    }

    private static function vcfStd(float $a, float $d): float
    {
        return self::rnd(exp(-$a * $d - self::rnd(0.8 * ($a * $d) ** 2, 9)), 6);
    }

    /**
     * Hydrometer correction + rounding of inputs.
     *
     * @return array{0: float, 1: float} [rT, Tround]
     */
    private static function prepare(float $rho, float $t, float $tRef): array
    {
        $r = self::rnd($rho / 5, 1) * 5;
        $tRound = self::rnd($t * 2, 1) / 2;
        $d = $tRound - $tRef;
        $hy = 1 - self::rnd(23e-6 * $d, 9) - self::rnd(2e-8 * $d * $d, 9);
        $rT = self::trc(self::rnd($r * $hy, 2), 2);

        return [$rT, $tRound];
    }

    /**
     * 7-pass iteration rT -> rho15 for one column.
     *
     * @return array{a: float, vcf: float, r: float, rr: float}
     */
    private static function iterate(string $c, float $rT, float $d15): array
    {
        $r = [0 => 0.0];   // 3-dp densities per pass
        $rr = [0 => 0.0];  // 2-dp densities per pass
        $a = 0.0;
        $v = 0.0;

        for ($k = 1; $k <= 7; $k++) {
            if ($k === 1) {
                $a = self::alpha($c, $c === 'T' ? self::SEED : $rT);
            } elseif ($k === 2) {
                $a = self::alpha($c, $rr[1]);
            } else {
                // the sheet's transition column compares the unrounded r(k-2) with rr(k-1)
                $prev = ($c === 'T') ? $r[$k - 2] : $rr[$k - 2];
                if (abs($prev - $rr[$k - 1]) > 0.05) {
                    $a = self::alpha($c, $rr[$k - 1]);
                }
            }

            $v = self::vcfStd($a, $d15);
            $r[$k] = self::trc($rT / $v, 3);
            $rr[$k] = self::rnd($r[$k], 2);
        }

        return ['a' => $a, 'vcf' => $v, 'r' => $r[7], 'rr' => $rr[7]];
    }

    /**
     * Calculate 15 °C density and volume correction factor (VCF).
     *
     * @return array{density: float, vcf: float}
     */
    public static function to15(float $rho, float $t): array
    {
        [$rT, $tRound] = self::prepare($rho, $t, 15);
        $d = $tRound - 15;
        $results = [];

        foreach (['G', 'T', 'J', 'F'] as $c) {
            $results[$c] = self::iterate($c, $rT, $d);
        }

        $pick = self::pick15($results);

        return [
            'density' => self::rnd($results[$pick]['r'], 1),
            'vcf' => self::rnd($results[$pick]['vcf'], 5),
        ];
    }

    /**
     * @param  array<string, array{a: float, vcf: float, r: float, rr: float}>  $results
     */
    private static function pick15(array $results): string
    {
        if ($results['T']['r'] > 770.5 && $results['T']['r'] < 787.5) {
            return 'T';
        }

        if ($results['G']['r'] <= 770) {
            return 'G';
        }

        return $results['J']['r'] < 839 ? 'J' : 'F';
    }

    /**
     * Calculate density at 20 °C in kg/m3.
     */
    public static function density20(float $rho, float $t): float
    {
        [$rT, $tRound] = self::prepare($rho, $t, 20);
        $d20 = $tRound - 20;
        $d15 = $tRound - 15;
        $out = [];

        foreach (['G', 'T', 'J', 'F'] as $c) {
            $it = self::iterate($c, $rT, $d15);
            $a = self::alpha($c, $it['rr']);                                   // step 11
            $v = self::rnd(exp(-$a * $d20 - 8 * $a * $a * $d20 - 0.8 * $a * $a * $d20 * $d20), 6);
            $out[$c] = ['rr' => $it['rr'], 'r20' => self::trc($rT / $v, 3)];
        }

        if ($out['T']['rr'] > 770.5 && $out['T']['rr'] < 787.5) {
            $pick = 'T';
        } elseif ($out['G']['rr'] <= 770) {
            $pick = 'G';
        } else {
            $pick = ($out['F']['rr'] < 839) ? 'J' : 'F';   // sheet tests the F column here
        }

        return self::rnd($out[$pick]['r20'], 1);
    }

    /**
     * VCF volume at T -> 20 °C, from density at 20 °C ($rho20) and observed temp.
     */
    public static function vcf20(float $rho20, float $t): float
    {
        $tRound = self::rnd($t * 2, 1) / 2;
        $r0 = self::rnd($rho20 / 5, 1) * 5;           // C15
        $d = 5.0;                                     // 20 - 15

        $aV = function (array $k, float $rho): float {  // truncated alpha (C/H/I columns)
            return self::rnd(
                self::trc(self::trc($k[0] / $rho, 8) / $rho, 10) + self::trc($k[1] / $rho, 10),
                7
            );
        };

        $aG = function (float $rho): float {            // transition column
            return self::rnd(self::TA + self::rnd(self::trc(self::TB / $rho, 6) / $rho, 8), 7);
        };

        $vc = function (float $a) use ($d): float {
            return self::rnd(exp(-$a * $d - self::rnd($a * $d * 0.8 * $a * $d, 9)), 6);
        };

        $ks = ['C' => self::GAS, 'H' => self::JET, 'I' => self::FO];

        $A = [];
        $RR = [];
        foreach (['C', 'G', 'H', 'I'] as $c) {
            $A[$c] = [0 => 0.0];
            $RR[$c] = [0 => 0.0];
        }

        for ($k = 1; $k <= 8; $k++) {
            foreach (['C', 'G', 'H', 'I'] as $c) {
                $alpha = fn (float $rho): float => $c === 'G' ? $aG($rho) : $aV($ks[$c], $rho);
                if ($k === 1) {
                    $a = $c === 'G' ? $aG(self::SEED) : $aV($ks[$c], $r0);
                } elseif ($k === 2) {
                    $a = $alpha($RR[$c][1]);
                } else {
                    $ref = ($c === 'G') ? 'C' : $c;     // sheet's G column tests/falls back on column C
                    if (abs($RR[$ref][$k - 2] - $RR[$ref][$k - 1]) > 0.05) {
                        $a = $alpha($RR[$c][$k - 1]);
                    } else {
                        $a = $A[$ref][$k - 1];
                    }
                }
                $A[$c][$k] = $a;
                $RR[$c][$k] = self::rnd(self::trc($r0 / $vc($a), 3), 2);
            }
        }

        // step 7: choose the density class
        if ($RR['G'][8] > 770.5 && $RR['G'][8] < 787.5) {
            $r = self::rnd($RR['G'][8], 1);
        } elseif ($RR['C'][8] <= 770) {
            $r = self::rnd($RR['C'][8], 1);
        } else {
            $r = self::rnd($RR['I'][8] < 839 ? $RR['H'][8] : $RR['I'][8], 1);
        }

        // steps 8-9
        $k = $r > 770 ? ($r > 839 ? self::FO : self::JET) : self::GAS;
        $aC = $aV($k, $r);
        $aT = self::rnd(self::TA + self::rnd(self::trc(self::TB / $r, 6) / $r, 8), 7);

        // steps 10-12
        $d20 = $tRound - 20;
        $comp = function (float $a) use ($d20): float {
            $t1 = self::trc($a * $d20, 9);
            $t2 = self::rnd(8 * self::trc($a * $t1, 9), 9);
            $t3 = self::rnd($t1 * self::trc(0.8 * self::trc($a * $d20, 10), 9), 9);
            $v = self::rnd(exp(-$t1 - $t2 - $t3), 6);

            return $v >= 1 ? self::rnd($v, 4) : self::rnd($v, 5);
        };

        return ($rho20 > 770 && $rho20 < 788)
            ? self::rnd($comp($aT), 4)
            : self::rnd($comp($aC), 4);
    }

    /**
     * Full CALCUL sheet: calculates density and volume conversions to 15 °C and 20 °C, and mass in tonnes.
     *
     * @return array{
     *     density15: float,
     *     vcf15: float,
     *     density20: float,
     *     vcf20: float,
     *     volume15: float,
     *     volume20: float,
     *     tonnes: float
     * }
     */
    public static function compute(float $density, float $tempC, float $volume): array
    {
        $d15 = self::to15($density, $tempC);
        $d20 = self::density20($density, $tempC);
        $vcf20 = self::vcf20($d20, $tempC);

        $dens15 = $d15['density'] / 1000;     // CALCUL!B6
        $dens20 = $d20 / 1000;                // CALCUL!B9
        $v15 = $volume * $d15['vcf'];         // CALCUL!D6
        $v20 = $volume * $vcf20;              // CALCUL!D9

        return [
            'density15' => $dens15,
            'vcf15' => $d15['vcf'],
            'density20' => $dens20,
            'vcf20' => $vcf20,
            'volume15' => $v15,
            'volume20' => $v20,
            'tonnes' => $v15 * $dens15 / 1000,   // CALCUL!D12
        ];
    }
}
