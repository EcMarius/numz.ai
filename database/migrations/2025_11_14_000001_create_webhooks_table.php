<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('url');
            $table->string('secret')->nullable();
            $table->json('events'); // ['invoice.paid', 'service.created', etc.]
            $table->boolean('active')->default(true);
            $table->integer('retry_attempts')->default(3);
            $table->timestamps();
        });

        Schema::create('webhook_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->onDelete('cascade');
            $table->string('event');
            $table->json('payload');
            $table->integer('status_code')->nullable();
            $table->text('response')->nullable();
            $table->integer('attempt')->default(1);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['webhook_id', 'event']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_calls');
        Schema::dropIfExists('webhooks');
    }
};
