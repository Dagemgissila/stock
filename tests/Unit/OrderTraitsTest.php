<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Order;

class OrderTraitsTest extends TestCase
{
    public function test_grand_total_calculation(): void
    {
        $order = new Order([
            'subtotal'   => 1000,
            'discount'   => 100,
            'tax_amount' => 50,
            'shipping'   => 20,
        ]);
        $this->assertEquals(970.0, $order->calculateGrandTotal());
    }

    public function test_grand_total_never_negative(): void
    {
        $order = new Order([
            'subtotal'   => 100,
            'discount'   => 500, // discount exceeds subtotal
            'tax_amount' => 0,
            'shipping'   => 0,
        ]);
        $this->assertEquals(0.0, $order->calculateGrandTotal());
    }

    public function test_is_paid_when_due_is_zero(): void
    {
        $order = new Order(['grand_total'=>100,'due_amount'=>0]);
        $this->assertTrue($order->isPaid());
    }

    public function test_is_not_paid_when_due_is_positive(): void
    {
        $order = new Order(['grand_total'=>100,'due_amount'=>50]);
        $this->assertFalse($order->isPaid());
    }
}
