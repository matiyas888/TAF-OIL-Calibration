<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\DensityCalc;
use PHPUnit\Framework\TestCase;

class DensityCalcTest extends TestCase
{
    public function test_rnd_rounds_half_away_from_zero(): void
    {
        $this->assertSame(1.235, DensityCalc::rnd(1.2345, 3));
        $this->assertSame(1.234, DensityCalc::rnd(1.2344, 3));
        $this->assertSame(-1.235, DensityCalc::rnd(-1.2345, 3));
        $this->assertSame(12.0, DensityCalc::rnd(12.4));
        $this->assertSame(13.0, DensityCalc::rnd(12.5));
    }

    public function test_trc_truncates_towards_zero(): void
    {
        $this->assertSame(1.234, DensityCalc::trc(1.2349, 3));
        $this->assertSame(-1.234, DensityCalc::trc(-1.2349, 3));
        $this->assertSame(12.0, DensityCalc::trc(12.9));
        $this->assertSame(-12.0, DensityCalc::trc(-12.9));
    }

    public function test_compute_calculates_standard_density_vcf_and_volume_conversions(): void
    {
        $result = DensityCalc::compute(789.0, 19.0, 1654.0);

        $this->assertEqualsWithDelta(0.7919, $result['density15'], 0.0001);
        $this->assertSame(0.9962, $result['vcf15']);
        $this->assertSame(0.7883, $result['density20']);
        $this->assertSame(1.001, $result['vcf20']);
        $this->assertEqualsWithDelta(1647.7148, $result['volume15'], 0.0001);
        $this->assertEqualsWithDelta(1655.654, $result['volume20'], 0.001);
        $this->assertEqualsWithDelta(1.304825, $result['tonnes'], 0.000001);
    }

    public function test_to15_calculates_density_and_vcf_for_gasoline_product_class(): void
    {
        // Density <= 770 falls into Gasoline class
        $result = DensityCalc::to15(730.0, 20.0);

        $this->assertIsFloat($result['density']);
        $this->assertIsFloat($result['vcf']);
        $this->assertLessThanOrEqual(770.0, $result['density']);
        $this->assertLessThan(1.0, $result['vcf']);
    }

    public function test_to15_calculates_density_and_vcf_for_transition_zone_product_class(): void
    {
        // Transition zone (between 770.5 and 787.5)
        $result = DensityCalc::to15(775.0, 15.0);

        $this->assertEqualsWithDelta(775.0, $result['density'], 0.1);
        $this->assertSame(1.0, $result['vcf']);
    }

    public function test_to15_calculates_density_and_vcf_for_jet_fuel_product_class(): void
    {
        // Jet fuel class: 770 .. 839
        $result = DensityCalc::to15(800.0, 25.0);

        $this->assertGreaterThan(770.0, $result['density']);
        $this->assertLessThan(839.0, $result['density']);
        $this->assertLessThan(1.0, $result['vcf']);
    }

    public function test_to15_calculates_density_and_vcf_for_fuel_oil_product_class(): void
    {
        // Fuel oil class: >= 839
        $result = DensityCalc::to15(860.0, 15.0);

        $this->assertGreaterThanOrEqual(839.0, $result['density']);
        $this->assertSame(1.0, $result['vcf']);
    }

    public function test_density20_calculates_standard_20_c_density(): void
    {
        $d20Gas = DensityCalc::density20(740.0, 15.0);
        $d20Transition = DensityCalc::density20(775.0, 20.0);
        $d20Jet = DensityCalc::density20(800.0, 25.0);
        $d20FuelOil = DensityCalc::density20(860.0, 20.0);

        $this->assertIsFloat($d20Gas);
        $this->assertIsFloat($d20Transition);
        $this->assertIsFloat($d20Jet);
        $this->assertIsFloat($d20FuelOil);
        $this->assertEqualsWithDelta(775.0, $d20Transition, 0.5);
        $this->assertEqualsWithDelta(860.0, $d20FuelOil, 0.5);
    }

    public function test_vcf20_handles_temperatures_above_and_below_20_degrees(): void
    {
        // For temp < 20°C, liquid expands when warmed to 20°C, so VCF >= 1
        $vcfCool = DensityCalc::vcf20(788.3, 19.0);
        $this->assertGreaterThanOrEqual(1.0, $vcfCool);

        // For temp > 20°C, liquid contracts when cooled to 20°C, so VCF < 1
        $vcfWarm = DensityCalc::vcf20(788.3, 25.0);
        $this->assertLessThan(1.0, $vcfWarm);

        // In transition zone: 770 < rho20 < 788
        $vcfTransition = DensityCalc::vcf20(775.0, 22.0);
        $this->assertIsFloat($vcfTransition);

        // Outside transition zone
        $vcfOutside = DensityCalc::vcf20(850.0, 22.0);
        $this->assertIsFloat($vcfOutside);
    }
}
