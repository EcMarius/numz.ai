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
            $table->string('type'); // revenue, customers, products, services, custom
            $table->string('category'); // financial, operations, marketing, etc.

            // Report configuration
            $table->json('data_sources')->nullable(); // Tables/models to query
            $table->json('columns')->nullable(); // Which columns to show
            $table->json('filters')->nullable(); // Filter configuration
            $table->json('grouping')->nullable(); // GROUP BY configuration
            $table->json('sorting')->nullable(); // ORDER BY configuration
            $table->json('calculations')->nullable(); // SUM, AVG, COUNT, etc.

            // Visualization
            $table->string('chart_type')->nullable(); // line, bar, pie, table, etc.
            $table->json('chart_config')->nullable();

            // Access control
            $table->boolean('is_public')->default(false);
            $table->json('shared_with_users')->nullable(); // User IDs
            $table->json('shared_with_roles')->nullable(); // Role names

            $table->boolean('is_favorite')->default(false);
            $table->integer('view_count')->default(0);
            $table->timestamp('last_generated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('category');
            $table->index('created_by');
        });

        // Report schedules (automated reports)
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_report_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            $table->string('name');
            $table->string('frequency'); // daily, weekly, monthly, quarterly, yearly
            $table->string('day_of_week')->nullable(); // For weekly
            $table->integer('day_of_month')->nullable(); // For monthly
            $table->time('time_of_day')->default('09:00:00');

            // Recipients
            $table->json('recipients')->nullable(); // Email addresses
            $table->json('formats')->nullable(); // pdf, csv, xlsx, json

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();

            $table->timestamps();

            $table->index('custom_report_id');
            $table->index('next_run_at');
        });

        // Report executions (history)
        Schema::create('report_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_report_id')->constrained()->onDelete('cascade');
            $table->foreignId('executed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('report_schedule_id')->nullable()->constrained()->onDelete('set null');

            $table->string('status'); // running, completed, failed
            $table->integer('rows_returned')->nullable();
            $table->float('execution_time')->nullable(); // seconds
            $table->text('error_message')->nullable();

            $table->string('file_path')->nullable(); // If exported
            $table->string('file_format')->nullable();
            $table->integer('file_size')->nullable(); // bytes

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index('custom_report_id');
            $table->index('status');
        });

        // Revenue metrics (pre-calculated for performance)
        Schema::create('revenue_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('metric_date');
            $table->string('period_type'); // daily, weekly, monthly, quarterly, yearly

            // Revenue breakdown
            $table->decimal('new_sales', 12, 2)->default(0);
            $table->decimal('renewals', 12, 2)->default(0);
            $table->decimal('upgrades', 12, 2)->default(0);
            $table->decimal('downgrades', 12, 2)->default(0);
            $table->decimal('refunds', 12, 2)->default(0);
            $table->decimal('chargebacks', 12, 2)->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('net_revenue', 12, 2)->default(0);

            // MRR/ARR
            $table->decimal('mrr', 12, 2)->default(0); // Monthly Recurring Revenue
            $table->decimal('arr', 12, 2)->default(0); // Annual Recurring Revenue
            $table->decimal('mrr_growth', 12, 2)->default(0); // Growth %
            $table->decimal('arr_growth', 12, 2)->default(0);

            // Customer metrics
            $table->integer('new_customers')->default(0);
            $table->integer('churned_customers')->default(0);
            $table->integer('active_customers')->default(0);
            $table->decimal('churn_rate', 5, 2)->default(0);
            $table->decimal('customer_ltv', 10, 2)->default(0); // Lifetime Value

            // Payment metrics
            $table->integer('successful_payments')->default(0);
            $table->integer('failed_payments')->default(0);
            $table->decimal('payment_success_rate', 5, 2)->default(0);

            $table->timestamps();

            $table->unique(['metric_date', 'period_type']);
            $table->index('metric_date');
            $table->index('period_type');
        });

        // Product performance metrics
        Schema::create('product_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('hosting_products')->onDelete('cascade');
            $table->date('metric_date');
            $table->string('period_type'); // daily, weekly, monthly

            $table->integer('new_sales')->default(0);
            $table->integer('renewals')->default(0);
            $table->integer('cancellations')->default(0);
            $table->integer('active_subscriptions')->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->decimal('churn_rate', 5, 2)->default(0);

            $table->timestamps();

            $table->unique(['product_id', 'metric_date', 'period_type']);
            $table->index('product_id');
            $table->index('metric_date');
        });

        // Customer segments (for targeted analysis)
        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('criteria'); // Conditions to match segment
            $table->string('segment_type'); // behavioral, demographic, revenue, etc.

            $table->integer('customer_count')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('avg_revenue_per_customer', 10, 2)->default(0);
            $table->decimal('avg_ltv', 10, 2)->default(0);

            $table->boolean('is_auto_update')->default(true);
            $table->timestamp('last_calculated_at')->nullable();

            $table->timestamps();

            $table->index('slug');
        });

        // Customer segment membership
        Schema::create('customer_segment_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_segment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->unique(['customer_segment_id', 'user_id']);
            $table->index('customer_segment_id');
            $table->index('user_id');
        });

        // A/B tests
        Schema::create('ab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('test_type'); // pricing, feature, design, email, etc.
            $table->string('status'); // draft, running, paused, completed

            // Variants
            $table->json('variant_a')->nullable(); // Control
            $table->json('variant_b')->nullable(); // Test
            $table->integer('traffic_split_percentage')->default(50); // 50/50 split

            // Results
            $table->integer('variant_a_participants')->default(0);
            $table->integer('variant_b_participants')->default(0);
            $table->integer('variant_a_conversions')->default(0);
            $table->integer('variant_b_conversions')->default(0);
            $table->decimal('variant_a_revenue', 10, 2)->default(0);
            $table->decimal('variant_b_revenue', 10, 2)->default(0);

            // Statistical significance
            $table->decimal('confidence_level', 5, 2)->nullable();
            $table->string('winner')->nullable(); // a, b, or null

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();

            $table->index('status');
        });

        // Revenue forecasts
        Schema::create('revenue_forecasts', function (Blueprint $table) {
            $table->id();
            $table->date('forecast_date');
            $table->string('forecast_type'); // linear, exponential, seasonal, ml
            $table->string('period'); // daily, weekly, monthly

            $table->decimal('forecasted_revenue', 12, 2);
            $table->decimal('forecasted_mrr', 12, 2)->nullable();
            $table->decimal('forecasted_customers', 10, 2)->nullable();
            $table->decimal('confidence_low', 12, 2)->nullable(); // Lower bound
            $table->decimal('confidence_high', 12, 2)->nullable(); // Upper bound

            $table->decimal('actual_revenue', 12, 2)->nullable();
            $table->decimal('variance', 12, 2)->nullable(); // Actual vs Forecast
            $table->decimal('accuracy_percentage', 5, 2)->nullable();

            $table->json('model_parameters')->nullable();
            $table->timestamp('generated_at');

            $table->timestamps();

            $table->index('forecast_date');
            $table->index('forecast_type');
        });

        // Analytics dashboards
        Schema::create('analytics_dashboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('widgets'); // Array of widget configurations
            $table->json('layout')->nullable(); // Grid layout
            $table->boolean('is_default')->default(false);
            $table->boolean('is_public')->default(false);
            $table->integer('view_count')->default(0);
            $table->timestamps();

            $table->index('created_by');
        });

        // Report exports (for download history)
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_report_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('exported_by')->constrained('users')->onDelete('cascade');

            $table->string('file_name');
            $table->string('file_path');
            $table->string('format'); // pdf, csv, xlsx, json
            $table->integer('file_size'); // bytes
            $table->integer('row_count')->nullable();

            $table->timestamp('expires_at')->nullable();
            $table->integer('download_count')->default(0);

            $table->timestamps();

            $table->index('exported_by');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('analytics_dashboards');
        Schema::dropIfExists('revenue_forecasts');
        Schema::dropIfExists('ab_tests');
        Schema::dropIfExists('customer_segment_members');
        Schema::dropIfExists('customer_segments');
        Schema::dropIfExists('product_metrics');
        Schema::dropIfExists('revenue_metrics');
        Schema::dropIfExists('report_executions');
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('custom_reports');
    }
};
