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
        if (!Schema::hasTable('smtp_configs')) {
            Schema::create('smtp_configs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('host');
                $table->integer('port')->default(587);
                $table->string('username');
                $table->text('password'); // Will be encrypted
                $table->enum('encryption', ['ssl', 'tls', 'none'])->default('tls');
                $table->string('from_address');
                $table->string('from_name');
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smtp_configs');
    }
};
