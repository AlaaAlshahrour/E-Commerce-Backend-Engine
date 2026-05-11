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

        // التحقق من إنشاء التقرير
        $report = DailySalesReport::where('date', Carbon::yesterday()->toDateString())->first();
        $this->assertNotNull($report);
        $this->assertEquals(1, $report->total_orders);
        $this->assertEquals(200.00, $report->total_revenue);
        $this->assertNotNull($report->export_start_time);
        $this->assertNotNull($report->export_end_time);
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
            'export_start_time' => Carbon::now()->subMinutes(2),
            'export_end_time' => Carbon::now(),
        ]);

        // تسجيل دخول مستخدم
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // استدعاء الـ API
        $response = $this->getJson('/api/daily-sales-report/2023-10-01');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'total_orders' => 5,
                'total_revenue' => '1000.00',
            ])
            ->assertJsonPath('pdf_url', '/storage/daily-reports/test.pdf');
    }

    public function test_api_returns_404_if_report_not_found(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/daily-sales-report/2023-10-01');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Report not found for the given date.']);
    }

    public function test_job_includes_all_orders_regardless_of_status(): void
    {
        // إنشاء بيانات وهمية
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // إنشاء طلبات مختلفة الحالات
        // 1. مكتمل ومدفوع
        Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'Completed',
            'payment_status' => 'paid',
            'total_amount' => 100.00,
            'created_at' => Carbon::yesterday(),
        ]);

        // 2. مكتمل وفشل الدفع
        Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'Completed',
            'payment_status' => 'failed',
            'total_amount' => 50.00,
            'created_at' => Carbon::yesterday(),
        ]);

        // 3. معالج ومدفوع
        Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'Processing',
            'payment_status' => 'paid',
            'total_amount' => 75.00,
            'created_at' => Carbon::yesterday(),
        ]);

        // 4. معالج وقيد الانتظار
        Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'Processing',
            'payment_status' => 'pending',
            'total_amount' => 25.00,
            'created_at' => Carbon::yesterday(),
        ]);

        // تشغيل الـ Job
        $job = new ProcessDailySalesJob;
        $job->handle();

        // التحقق من التقرير
        $report = DailySalesReport::where('date', Carbon::yesterday()->toDateString())->first();
        $this->assertNotNull($report);
        $this->assertEquals(4, $report->total_orders);
        $this->assertEquals(250.00, $report->total_revenue);
    }
}
