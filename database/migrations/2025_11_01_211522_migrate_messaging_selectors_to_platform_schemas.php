<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\PlatformSchema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all platforms with messaging selectors
        $platforms = DB::table('evenleads_platforms')
            ->whereNotNull('message_input_selectors')
            ->orWhereNotNull('message_send_button_selectors')
            ->get();

        foreach ($platforms as $platform) {
            $platformName = $platform->name;

            // Migrate message input selectors
            if ($platform->message_input_selectors) {
                $inputSelectors = json_decode($platform->message_input_selectors, true);
                if (is_array($inputSelectors)) {
                    foreach ($inputSelectors as $order => $selector) {
                        PlatformSchema::create([
                            'platform' => $platformName,
                            'page_type' => 'messaging',
                            'element_type' => 'message_input',
                            'css_selector' => $selector,
                            'xpath_selector' => null,
                            'is_required' => $order === 0, // First selector is required
                            'fallback_value' => null,
                            'parent_element' => null,
                            'multiple' => false,
                            'is_wrapper' => false,
                            'version' => '1.0.0',
                            'is_active' => true,
                            'description' => 'Message input field' . ($order > 0 ? ' (fallback ' . $order . ')' : ''),
                            'notes' => 'Migrated from platforms.message_input_selectors',
                            'order' => $order,
                        ]);
                    }
                }
            }

            // Migrate message send button selectors
            if ($platform->message_send_button_selectors) {
                $sendButtonSelectors = json_decode($platform->message_send_button_selectors, true);
                if (is_array($sendButtonSelectors)) {
                    foreach ($sendButtonSelectors as $order => $selector) {
                        PlatformSchema::create([
                            'platform' => $platformName,
                            'page_type' => 'messaging',
                            'element_type' => 'message_send_button',
                            'css_selector' => $selector,
                            'xpath_selector' => null,
                            'is_required' => $order === 0,
                            'fallback_value' => null,
                            'parent_element' => null,
                            'multiple' => false,
                            'is_wrapper' => false,
                            'version' => '1.0.0',
                            'is_active' => true,
                            'description' => 'Message send button' . ($order > 0 ? ' (fallback ' . $order . ')' : ''),
                            'notes' => 'Migrated from platforms.message_send_button_selectors',
                            'order' => $order,
                        ]);
                    }
                }
            }
        }

        \Log::info('Migrated messaging selectors to platform_schemas', [
            'platforms_processed' => $platforms->count(),
        ]);

        // Remove old columns from platforms table (optional - commented out for safety)
        // Schema::table('evenleads_platforms', function (Blueprint $table) {
        //     $table->dropColumn(['message_input_selectors', 'message_send_button_selectors']);
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete migrated messaging selectors
        DB::table('platform_schemas')
            ->whereIn('element_type', ['message_input', 'message_send_button'])
            ->where('page_type', 'messaging')
            ->delete();

        \Log::info('Rolled back messaging selectors migration');
    }
};
