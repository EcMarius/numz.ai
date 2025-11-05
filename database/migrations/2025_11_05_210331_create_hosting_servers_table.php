<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosting_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hostname');
            $table->string('ip_address');
            $table->integer('port')->default(2087);
            $table->enum('type', ['cpanel', 'plesk', 'directadmin', 'oneprovider', 'custom'])->default('cpanel');
            $table->text('username')->nullable();
            $table->text('access_key')->nullable();
            $table->boolean('ssl_enabled')->default(true);
            $table->integer('max_accounts')->default(100);
            $table->integer('active_accounts')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('nameserver1')->nullable();
            $table->string('nameserver2')->nullable();
            $table->string('nameserver3')->nullable();
            $table->string('nameserver4')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_servers');
    }
};
