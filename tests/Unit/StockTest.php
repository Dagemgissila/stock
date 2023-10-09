<?php
namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\WarehouseStock;
use App\Models\StockAdjustment;

class StockTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_increments_on_addition_adjustment(): void
    {
        $stock = WarehouseStock::factory()->create(['quantity' => 10]);

        StockAdjustment::create([
            'company_id'   => $stock->company_id,
            'warehouse_id' => $stock->warehouse_id,
            'product_id'   => $stock->product_id,
            'quantity'     => 5,
            'type'         => 'addition',
            'adjusted_by'  => 1,
        ]);

        $this->assertEquals(15, $stock->fresh()->quantity);
    }

    public function test_stock_decrements_on_subtraction_adjustment(): void
    {
        $stock = WarehouseStock::factory()->create(['quantity' => 10]);

        StockAdjustment::create([
            'company_id'   => $stock->company_id,
            'warehouse_id' => $stock->warehouse_id,
            'product_id'   => $stock->product_id,
            'quantity'     => 3,
            'type'         => 'subtraction',
            'adjusted_by'  => 1,
        ]);

        $this->assertEquals(7, $stock->fresh()->quantity);
    }
}
