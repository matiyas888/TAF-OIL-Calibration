<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\DailyStockCalculator;
use PHPUnit\Framework\TestCase;

class DailyStockCalculatorTest extends TestCase
{
    private DailyStockCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new DailyStockCalculator;
    }

    public function test_calculate_available(): void
    {
        // Available = receipts + opening + returns
        $available = $this->calculator->calculateAvailable(100000.0, 50000.0, 2000.0);
        $this->assertSame(152000.0, $available);
    }

    public function test_calculate_closing_balance(): void
    {
        // Closing = available - sales + adjustment
        $closing = $this->calculator->calculateClosingBalance(152000.0, 60000.0, -500.0);
        $this->assertSame(91500.0, $closing);
    }

    public function test_calculate_operational_variance(): void
    {
        // Operational variance = total physical stock - closing book balance
        // If physical = 91200, closing = 91500 -> variance = -300 (shortage)
        $var = $this->calculator->calculateOperationalVariance(91200.0, 91500.0);
        $this->assertSame(-300.0, $var);
    }

    public function test_reconcile_full_pipeline(): void
    {
        $data = [
            'opening' => 200000.0,
            'receipts' => 150000.0,
            'returns' => 5000.0,
            'sales' => 120000.0,
            'adjustment' => 1000.0,
            'physical_tank_stock' => 234500.0,
            'pipeline_stock' => 1200.0,
        ];

        // Available = 200000 + 150000 + 5000 = 355000.0
        // Closing = 355000 - 120000 + 1000 = 236000.0
        // Total Physical = 234500 + 1200 = 235700.0
        // Variance = 235700 - 236000 = -300.0
        $result = $this->calculator->reconcile($data);

        $this->assertSame(355000.0, $result['available']);
        $this->assertSame(236000.0, $result['closing_balance']);
        $this->assertSame(235700.0, $result['total_physical_stock']);
        $this->assertSame(-300.0, $result['operational_variance']);
    }
}
