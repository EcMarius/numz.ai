<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AI predictions
        Schema::create('ai_predictions', function (Blueprint $table) {
            $table->id();
            $table->string('prediction_type'); // churn, upsell, payment_failure, support_urgency
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('hosting_service_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('prediction_score', 5, 4); // 0-1 probability
            $table->json('factors')->nullable(); // Contributing factors
            $table->string('recommended_action')->nullable();
            $table->boolean('action_taken')->default(false);
            $table->timestamp('action_taken_at')->nullable();
            $table->boolean('prediction_correct')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['prediction_type', 'prediction_score']);
            $table->index('user_id');
        });

        // AI recommendations
        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['product_upsell', 'addon', 'plan_upgrade', 'cost_optimization'])->default('product_upsell');
            $table->string('recommended_item_type'); // HostingProduct, Addon, etc.
            $table->unsignedBigInteger('recommended_item_id');
            $table->text('reasoning');
            $table->decimal('confidence_score', 5, 4);
            $table->decimal('potential_revenue', 10, 2)->nullable();
            $table->enum('status', ['pending', 'shown', 'accepted', 'rejected'])->default('pending');
            $table->timestamp('shown_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Fraud detection
        Schema::create('fraud_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('risk_score', 5, 2); // 0-100
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->json('risk_factors')->nullable();
            $table->enum('status', ['pending_review', 'approved', 'blocked', 'false_positive'])->default('pending_review');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->boolean('automated_action')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'risk_level']);
            $table->index('status');
        });

        // Sentiment analysis
        Schema::create('sentiment_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('analyzable_type'); // SupportTicket, ChatMessage, etc.
            $table->unsignedBigInteger('analyzable_id');
            $table->text('analyzed_text');
            $table->enum('sentiment', ['very_negative', 'negative', 'neutral', 'positive', 'very_positive'])->default('neutral');
            $table->decimal('sentiment_score', 5, 4); // -1 to 1
            $table->json('emotions_detected')->nullable(); // anger, joy, sadness, etc.
            $table->integer('urgency_score')->nullable(); // 0-10
            $table->boolean('requires_escalation')->default(false);
            $table->text('suggested_response')->nullable();
            $table->timestamps();

            $table->index(['analyzable_type', 'analyzable_id']);
            $table->index('sentiment');
        });

        // Smart routing
        Schema::create('smart_routing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['ticket', 'chat', 'call'])->default('ticket');
            $table->json('conditions')->nullable(); // ML-based conditions
            $table->foreignId('route_to_department_id')->nullable()->constrained('support_departments')->onDelete('set null');
            $table->foreignId('route_to_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('success_rate')->default(0); // percentage
            $table->timestamps();

            $table->index(['is_active', 'priority']);
        });

        // Knowledge extraction from tickets
        Schema::create('extracted_knowledge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->onDelete('cascade');
            $table->string('problem_category');
            $table->text('problem_summary');
            $table->text('solution_summary');
            $table->json('keywords')->nullable();
            $table->boolean('converted_to_kb')->default(false);
            $table->foreignId('kb_article_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('similarity_score')->nullable(); // How similar to existing articles
            $table->timestamps();

            $table->index('problem_category');
        });

        // Automated price optimization
        Schema::create('price_optimizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_product_id')->constrained()->onDelete('cascade');
            $table->decimal('current_price', 10, 2);
            $table->decimal('recommended_price', 10, 2);
            $table->decimal('expected_revenue_impact', 10, 2)->nullable();
            $table->decimal('expected_conversion_impact', 5, 2)->nullable();
            $table->json('reasoning')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'implemented'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('implemented_at')->nullable();
            $table->timestamps();

            $table->index(['hosting_product_id', 'status']);
        });

        // Anomaly detection
        Schema::create('anomalies', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // User, HostingService, Invoice, etc.
            $table->unsignedBigInteger('entity_id');
            $table->string('anomaly_type'); // unusual_activity, suspicious_login, resource_spike, etc.
            $table->text('description');
            $table->decimal('severity', 5, 2); // 0-100
            $table->json('detected_patterns')->nullable();
            $table->enum('status', ['detected', 'investigating', 'resolved', 'false_positive'])->default('detected');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['status', 'severity']);
        });

        // AI training data
        Schema::create('ai_training_data', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // churn_predictor, sentiment_analyzer, etc.
            $table->text('input_data');
            $table->text('expected_output');
            $table->text('actual_output')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->boolean('used_for_training')->default(false);
            $table->timestamp('trained_at')->nullable();
            $table->timestamps();

            $table->index('model_type');
        });

        // AI model versions
        Schema::create('ai_model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('model_name');
            $table->string('version');
            $table->text('description')->nullable();
            $table->json('hyperparameters')->nullable();
            $table->decimal('accuracy', 5, 4)->nullable();
            $table->decimal('precision', 5, 4)->nullable();
            $table->decimal('recall', 5, 4)->nullable();
            $table->integer('training_samples')->default(0);
            $table->timestamp('trained_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['model_name', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_model_versions');
        Schema::dropIfExists('ai_training_data');
        Schema::dropIfExists('anomalies');
        Schema::dropIfExists('price_optimizations');
        Schema::dropIfExists('extracted_knowledge');
        Schema::dropIfExists('smart_routing_rules');
        Schema::dropIfExists('sentiment_analyses');
        Schema::dropIfExists('fraud_detections');
        Schema::dropIfExists('ai_recommendations');
        Schema::dropIfExists('ai_predictions');
    }
};
