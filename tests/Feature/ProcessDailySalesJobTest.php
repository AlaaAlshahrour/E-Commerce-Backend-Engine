<?php

namespace Tests\Feature;

use App\Enums\ProcessingMode;
use App\Jobs\ProcessDailySalesJob;
use App\Models\DailySalesReport;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\DailySalesProcessingService;
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
        $job->handle(app(DailySalesProcessingService::class));

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
        $job->handle(app(DailySalesProcessingService::class));

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
        $job->handle(app(DailySalesProcessingService::class));

        // التحقق من التقرير
        $report = DailySalesReport::where('date', Carbon::yesterday()->toDateString())->first();
        $this->assertNotNull($report);
        $this->assertEquals(4, $report->total_orders);
        $this->assertEquals(250.00, $report->total_revenue);
    }

    // Tests for new refactored code

    public function test_processing_mode_enum_values(): void
    {
        $this->assertEquals('batch', ProcessingMode::Batch->value);
        $this->assertEquals('normal', ProcessingMode::Normal->value);
        $this->assertEquals('compare', ProcessingMode::Compare->value);
    }

    public function test_job_dispatch_with_batch_mode(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'Completed',
            'payment_status' => 'paid',
            'total_amount' => 150.00,
            'created_at' => Carbon::yesterday(),
        ]);

        $job = new ProcessDailySalesJob(Carbon::yesterday()->toDateString(), ProcessingMode::Batch);
        $job->handle(app(DailySalesProcessingService::class));

        $report = DailySalesReport::where('date', Carbon::yesterday()->toDateString())->first();
        $this->assertNotNull($report);
        $this->assertEquals('batch', $report->processing_mode);
    }

    public function test_job_dispatch_with_normal_mode(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'Completed',
            'payment_status' => 'paid',
            'total_amount' => 150.00,
            'created_at' => Carbon::yesterday(),
        ]);

        $job = new ProcessDailySalesJob(Carbon::yesterday()->toDateString(), ProcessingMode::Normal);
        $job->handle(app(DailySalesProcessingService::class));

        $report = DailySalesReport::where('date', Carbon::yesterday()->toDateString())->first();
        $this->assertNotNull($report);
        $this->assertEquals('normal', $report->processing_mode);
    }

    public function test_job_dispatch_with_compare_mode(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'Completed',
            'payment_status' => 'paid',
            'total_amount' => 150.00,
            'created_at' => Carbon::yesterday(),
        ]);

        $job = new ProcessDailySalesJob(Carbon::yesterday()->toDateString(), ProcessingMode::Compare);
        $job->handle(app(DailySalesProcessingService::class));

        $report = DailySalesReport::where('date', Carbon::yesterday()->toDateString())->first();
        $this->assertNotNull($report);
        $this->assertEquals('compare', $report->processing_mode);
    }

    public function test_processing_service_batch_returns_correct_structure(): void
    {
        $user = User::factory()->create();

        Order::factory(25)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::yesterday(),
        ]);

        $service = app(DailySalesProcessingService::class);
        $result = $service->process(Carbon::yesterday()->toDateString(), ProcessingMode::Batch);

        $this->assertEquals(ProcessingMode::Batch->value, $result['mode']);
        $this->assertArrayHasKey('batch_result', $result);
        $this->assertArrayHasKey('total_orders', $result['batch_result']);
        $this->assertArrayHasKey('total_revenue', $result['batch_result']);
        $this->assertArrayHasKey('execution_time', $result['batch_result']);
        $this->assertArrayHasKey('peak_memory', $result['batch_result']);
        $this->assertArrayHasKey('memory_used', $result['batch_result']);
        $this->assertArrayHasKey('batches_metrics', $result['batch_result']);
        $this->assertArrayHasKey('orders_data', $result['batch_result']);
    }

    public function test_processing_service_normal_returns_correct_structure(): void
    {
        $user = User::factory()->create();

        Order::factory(25)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::yesterday(),
        ]);

        $service = app(DailySalesProcessingService::class);
        $result = $service->process(Carbon::yesterday()->toDateString(), ProcessingMode::Normal);

        $this->assertEquals(ProcessingMode::Normal->value, $result['mode']);
        $this->assertArrayHasKey('normal_result', $result);
        $this->assertArrayHasKey('total_orders', $result['normal_result']);
        $this->assertArrayHasKey('execution_time', $result['normal_result']);
        $this->assertArrayHasKey('peak_memory', $result['normal_result']);
        $this->assertArrayHasKey('orders_data', $result['normal_result']);
    }

    public function test_orders_sample_limited_to_50(): void
    {
        $user = User::factory()->create();

        Order::factory(75)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::yesterday(),
        ]);

        $service = app(DailySalesProcessingService::class);
        $result = $service->process(Carbon::yesterday()->toDateString(), ProcessingMode::Batch);

        $ordersData = $result['batch_result']['orders_data'];
        $this->assertLessThanOrEqual(50, count($ordersData));
    }

    public function test_batch_processor_tracks_metrics_correctly(): void
    {
        $user = User::factory()->create();

        Order::factory(1025)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::yesterday(),
        ]);

        $service = app(DailySalesProcessingService::class);
        $result = $service->process(Carbon::yesterday()->toDateString(), ProcessingMode::Batch);

        $batchResult = $result['batch_result'];
        $this->assertGreaterThan(1, $batchResult['batches_count']);
        $this->assertNotEmpty($batchResult['batches_metrics']);

        foreach ($batchResult['batches_metrics'] as $batch) {
            $this->assertArrayHasKey('batch_number', $batch);
            $this->assertArrayHasKey('orders_count', $batch);
            $this->assertArrayHasKey('execution_time', $batch);
            $this->assertArrayHasKey('memory_before', $batch);
            $this->assertArrayHasKey('memory_after', $batch);
        }
    }

    public function test_performance_metrics_are_measured_correctly(): void
    {
        $user = User::factory()->create();

        Order::factory(25)->create([
            'user_id' => $user->id,
            'created_at' => Carbon::yesterday(),
        ]);

        $service = app(DailySalesProcessingService::class);
        $result = $service->process(Carbon::yesterday()->toDateString(), ProcessingMode::Batch);

        $metrics = $result['batch_result'];

        $this->assertIsFloat($metrics['execution_time']);
        $this->assertIsFloat($metrics['peak_memory']);
        $this->assertIsFloat($metrics['memory_used']);
        $this->assertGreaterThanOrEqual(0, $metrics['execution_time']);
        $this->assertGreaterThanOrEqual(0, $metrics['peak_memory']);
    }
}
