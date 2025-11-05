<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the threshold from config (default 8)
        $threshold = config('evenleads.scoring.strong_match_threshold', 8);

        // Fix leads that should be 'strong' but are marked as 'partial'
        $fixedToStrong = DB::table('evenleads_leads')
            ->where('confidence_score', '>=', $threshold)
            ->where('match_type', 'partial')
            ->update(['match_type' => 'strong']);

        // Fix leads that should be 'partial' but are marked as 'strong'
        $fixedToPartial = DB::table('evenleads_leads')
            ->where('confidence_score', '<', $threshold)
            ->where('match_type', 'strong')
            ->update(['match_type' => 'partial']);

        if ($fixedToStrong > 0 || $fixedToPartial > 0) {
            \Log::info('Fixed inconsistent lead match_types', [
                'fixed_to_strong' => $fixedToStrong,
                'fixed_to_partial' => $fixedToPartial,
                'threshold' => $threshold,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration fixes data inconsistencies, so there's no reversal needed
        // The previous state was incorrect, so we don't want to revert to it
    }
};
