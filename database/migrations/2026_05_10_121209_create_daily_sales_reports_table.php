<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_sales_reports', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('total_orders')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->string('pdf_path')->nullable();
            $table->string('processing_mode')->default('batch');
            $table->dateTime('export_start_time')->nullable();
            $table->dateTime('export_end_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_sales_reports');
    }
};
