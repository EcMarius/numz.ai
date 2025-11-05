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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hostname');
            $table->string('ip_address');
            $table->integer('port')->default(2087);
            $table->string('type')->default('cpanel');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->text('access_hash')->nullable();
            $table->boolean('secure')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('max_accounts')->nullable();
            $table->integer('accounts_count')->default(0);
            $table->string('nameserver1')->nullable();
            $table->string('nameserver2')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
