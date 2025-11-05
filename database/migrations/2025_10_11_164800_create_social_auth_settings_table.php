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
        Schema::create('social_auth_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, boolean, encrypted
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        $settings = [
            ['key' => 'google_enabled', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable Google OAuth'],
            ['key' => 'google_client_id', 'value' => null, 'type' => 'encrypted', 'description' => 'Google OAuth Client ID'],
            ['key' => 'google_client_secret', 'value' => null, 'type' => 'encrypted', 'description' => 'Google OAuth Client Secret'],
            ['key' => 'facebook_enabled', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable Facebook OAuth'],
            ['key' => 'facebook_client_id', 'value' => null, 'type' => 'encrypted', 'description' => 'Facebook App ID'],
            ['key' => 'facebook_client_secret', 'value' => null, 'type' => 'encrypted', 'description' => 'Facebook App Secret'],
            ['key' => 'github_enabled', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable GitHub OAuth'],
            ['key' => 'github_client_id', 'value' => null, 'type' => 'encrypted', 'description' => 'GitHub Client ID'],
            ['key' => 'github_client_secret', 'value' => null, 'type' => 'encrypted', 'description' => 'GitHub Client Secret'],
            ['key' => 'twitter_enabled', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable Twitter OAuth'],
            ['key' => 'twitter_client_id', 'value' => null, 'type' => 'encrypted', 'description' => 'Twitter API Key'],
            ['key' => 'twitter_client_secret', 'value' => null, 'type' => 'encrypted', 'description' => 'Twitter API Secret'],
        ];

        foreach ($settings as $setting) {
            DB::table('social_auth_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_auth_settings');
    }
};
