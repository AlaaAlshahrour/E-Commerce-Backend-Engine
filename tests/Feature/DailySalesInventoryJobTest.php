<?php

namespace Tests\Feature;

use App\Jobs\DailySalesInventoryJob;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailySalesInventoryJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_sales_inventory_job_updates_inventory_correctly(): void
    {
        // إنشاء بيانات وهمية
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        // إنشاء طلب مكتمل في اليوم السابق
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'Completed',
            'created_at' => Carbon::yesterday(),
        ]);

        $orderItem = OrderItem::factory()->create([
            'product_id' => $product->id,
            'order_id' => $order->id,
            'quantity' => 10,
            'unit_price' => 50.00,
        ]);

        // تشغيل الـ Job
        $job = new DailySalesInventoryJob;
        $job->handle();

        // التحقق من تحديث المخزون
        $inventory->refresh();
        $this->assertEquals(90, $inventory->quantity);
    }

    public function test_job_does_not_update_inventory_for_non_completed_orders(): void
    {
        // إنشاء بيانات وهمية مع طلب غير مكتمل
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'Processing', // غير مكتمل
            'created_at' => Carbon::yesterday(),
        ]);

        OrderItem::factory()->create([
            'product_id' => $product->id,
            'order_id' => $order->id,
            'quantity' => 10,
        ]);

        // تشغيل الـ Job
        $job = new DailySalesInventoryJob;
        $job->handle();

        // التحقق من عدم تغيير المخزون
        $inventory->refresh();
        $this->assertEquals(100, $inventory->quantity);
    }
}
