<?php

namespace Tests\Feature;

use App\Jobs\ProcessDailySalesJob;
use App\Models\DailySalesReport;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessDailySalesJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_daily_sales_job_creates_report_and_updates_inventory(): void
    {
        // إنشاء بيانات وهمية
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        // إنشاء طلب مكتمل ومدفوع في اليوم السابق
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'Completed',
            'payment_status' => 'paid',
            'total_amount' => 200.00,
            'created_at' => Carbon::yesterday(),
        ]);

        $orderItem = OrderItem::factory()->create([
            'product_id' => $product->id,
            'order_id' => $order->id,
            'quantity' => 10,
            'unit_price' => 20.00,
        ]);

        // تشغيل الـ Job
        $job = new ProcessDailySalesJob;
        $job->handle();

        // التحقق من تحديث المخزون
        $inventory->refresh();
        $this->assertEquals(90, $inventory->quantity);

        // التحقق من إنشاء التقرير
        $report = DailySalesReport::where('date', Carbon::yesterday()->toDateString())->first();
        $this->assertNotNull($report);
        $this->assertEquals(1, $report->total_orders);
        $this->assertEquals(200.00, $report->total_revenue);
        $this->assertNotNull($report->pdf_path);
    }

    public function test_job_skips_if_report_already_exists(): void
    {
        // إنشاء تقرير موجود مسبقاً
        DailySalesReport::create([
            'date' => Carbon::yesterday()->toDateString(),
            'total_orders' => 0,
            'total_revenue' => 0.0,
            'pdf_path' => null,
        ]);

        // تشغيل الـ Job
        $job = new ProcessDailySalesJob;
        $job->handle();

        // التحقق من عدم تغيير التقرير
        $report = DailySalesReport::where('date', Carbon::yesterday()->toDateString())->first();
        $this->assertEquals(0, $report->total_orders);
    }

    public function test_api_returns_report_data(): void
    {
        // إنشاء تقرير
        $report = DailySalesReport::create([
            'date' => '2023-10-01',
            'total_orders' => 5,
            'total_revenue' => 1000.00,
            'pdf_path' => 'public/daily-reports/test.pdf',
        ]);

        // تسجيل دخول مستخدم
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // استدعاء الـ API
        $response = $this->getJson('/api/daily-sales-report/2023-10-01');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'total_orders' => 5,
                'pdf_url' => '/storage/daily-reports/test.pdf',
            ]);
    }

    public function test_api_returns_404_if_report_not_found(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/daily-sales-report/2023-10-01');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Report not found for the given date.']);
    }
}
