<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Custom reports
        Schema::create('custom_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['financial', 'operational', 'customer', 'custom'])->default('custom');
            $table->json('columns')->nullable(); // Selected columns
            $table->json('filters')->nullable(); // Applied filters
            $table->json('grouping')->nullable(); // Group by fields
            $table->json('sorting')->nullable(); // Sort order
            $table->string('chart_type')->nullable(); // line, bar, pie, table
            $table->boolean('is_public')->default(false);
            $table->json('scheduled_delivery')->nullable(); // Email schedule
            $table->timestamps();

            $table->index('created_by');
            $table->index('slug');
        });

        // Report schedules
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_report_id')->constrained()->onDelete('cascade');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly'])->default('monthly');
            $table->string('delivery_time')->default('09:00');
            $table->json('recipients')->nullable(); // Email addresses
            $table->string('format')->default('pdf'); // pdf, csv, excel
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_send_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'next_send_at']);
        });

        // Analytics dashboards
        Schema::create('analytics_dashboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('widget_configuration')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });

        // Revenue metrics
        Schema::create('revenue_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('daily_revenue', 10, 2)->default(0);
            $table->decimal('monthly_recurring_revenue', 10, 2)->default(0);
            $table->decimal('annual_recurring_revenue', 10, 2)->default(0);
            $table->integer('new_customers')->default(0);
            $table->integer('churned_customers')->default(0);
            $table->decimal('average_revenue_per_user', 10, 2)->default(0);
            $table->decimal('customer_lifetime_value', 10, 2)->default(0);
            $table->decimal('churn_rate', 5, 2)->default(0);
            $table->integer('active_subscriptions')->default(0);
            $table->timestamps();

            $table->unique('date');
            $table->index('date');
        });

        // Product performance metrics
        Schema::create('product_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_product_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('sales_count')->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->integer('active_services')->default(0);
            $table->integer('cancelled_services')->default(0);
            $table->decimal('churn_rate', 5, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->timestamps();

            $table->index(['hosting_product_id', 'date']);
        });

        // Customer segments
        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('conditions')->nullable(); // Segmentation rules
            $table->integer('customer_count')->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0);
            $table->decimal('average_order_value', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->index('slug');
        });

        // Customer segment assignments
        Schema::create('customer_segment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_segment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->unique(['customer_segment_id', 'user_id']);
        });

        // A/B tests
        Schema::create('ab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['pricing', 'coupon', 'feature', 'ui'])->default('pricing');
            $table->json('variants')->nullable(); // A, B, C configurations
            $table->enum('status', ['draft', 'running', 'paused', 'completed'])->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('winning_variant')->nullable();
            $table->timestamps();

            $table->index('slug');
            $table->index('status');
        });

        // A/B test assignments
        Schema::create('ab_test_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('variant'); // A, B, C
            $table->boolean('converted')->default(false);
            $table->timestamp('converted_at')->nullable();
            $table->decimal('conversion_value', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['ab_test_id', 'user_id']);
        });

        // Forecasts
        Schema::create('forecasts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['revenue', 'churn', 'capacity', 'growth'])->default('revenue');
            $table->date('forecast_date');
            $table->decimal('predicted_value', 15, 2);
            $table->decimal('confidence_level', 5, 2)->default(0); // percentage
            $table->json('factors')->nullable(); // Factors considered
            $table->string('model_used')->nullable(); // ML model name
            $table->decimal('actual_value', 15, 2)->nullable();
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->timestamps();

            $table->index(['type', 'forecast_date']);
        });

        // System health metrics
        Schema::create('system_health_metrics', function (Blueprint $table) {
            $table->id();
            $table->timestamp('recorded_at');
            $table->integer('total_users')->default(0);
            $table->integer('active_users')->default(0);
            $table->integer('total_services')->default(0);
            $table->integer('active_services')->default(0);
            $table->integer('pending_tickets')->default(0);
            $table->decimal('average_response_time', 10, 2)->default(0); // hours
            $table->integer('failed_provisioning')->default(0);
            $table->integer('failed_payments')->default(0);
            $table->decimal('system_uptime', 5, 2)->default(100); // percentage
            $table->json('error_counts')->nullable();
            $table->timestamps();

            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_health_metrics');
        Schema::dropIfExists('forecasts');
        Schema::dropIfExists('ab_test_assignments');
        Schema::dropIfExists('ab_tests');
        Schema::dropIfExists('customer_segment_assignments');
        Schema::dropIfExists('customer_segments');
        Schema::dropIfExists('product_metrics');
        Schema::dropIfExists('revenue_metrics');
        Schema::dropIfExists('analytics_dashboards');
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('custom_reports');
    }
};
