<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ProductVarianceCalculator;
use PHPUnit\Framework\TestCase;

class ProductVarianceCalculatorTest extends TestCase
{
    private ProductVarianceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ProductVarianceCalculator;
    }

    public function test_calculate_net_loss_and_net_loss_percent(): void
    {
        // Rule: Net loss = Voldebit + (Vol20_final - Vol20_start)
        // Vol20_start = 50000, Vol20_final = 49900 (physical diff = -100 L)
        // Voldebit = 60 L
        // Net loss = 60 + (49900 - 50000) = 60 - 100 = -40 L
        $netLoss = $this->calculator->calculateNetLoss(60.0, 50000.0, 49900.0);
        $this->assertSame(-40.0, $netLoss);

        // Net loss % = -40 / 50000 * 100 = -0.08%
        $pct = $this->calculator->calculateNetLossPercent($netLoss, 50000.0);
        $this->assertSame(-0.08, $pct);
    }

    public function test_generate_report_groups_by_product(): void
    {
        $records = [
            [
                'product_id' => 'AGO',
                'product_name' => 'Automotive Gas Oil',
                'vol20_start' => 50000.0,
                'vol20_final' => 49900.0,
                'debit_volume' => 60.0,
                'sales_volume' => 45000.0,
                'temperature_loss' => 15.0,
            ],
            [
                'product_id' => 'AGO',
                'product_name' => 'Automotive Gas Oil',
                'vol20_start' => 40000.0,
                'vol20_final' => 39950.0,
                'debit_volume' => 20.0,
                'sales_volume' => 38000.0,
                'temperature_loss' => 10.0,
            ],
            [
                'product_id' => 'MGR',
                'product_name' => 'Motor Gasoline Regular',
                'vol20_start' => 30000.0,
                'vol20_final' => 29970.0,
                'debit_volume' => 10.0,
                'sales_volume' => 28000.0,
                'temperature_loss' => 5.0,
            ],
        ];

        $report = $this->calculator->generateReport($records);

        $this->assertArrayHasKey('AGO', $report);
        $this->assertArrayHasKey('MGR', $report);

        // AGO totals:
        // start = 90000, final = 89850 (diff = -150)
        // debit = 80
        // net loss = 80 + (-150) = -70
        // net loss % = -70 / 90000 * 100 = -0.0778%
        $this->assertSame(90000.0, $report['AGO']['total_start_vol20']);
        $this->assertSame(89850.0, $report['AGO']['total_final_vol20']);
        $this->assertSame(80.0, $report['AGO']['total_debit_volume']);
        $this->assertSame(-70.0, $report['AGO']['net_loss']);
        $this->assertSame(-0.0778, $report['AGO']['net_loss_percent']);
        $this->assertSame(83000.0, $report['AGO']['total_sales_volume']);
    }
}
