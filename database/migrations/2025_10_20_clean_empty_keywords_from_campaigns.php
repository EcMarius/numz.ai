<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Clean empty keywords from existing campaigns
     */
    public function up(): void
    {
        $campaigns = DB::table('evenleads_campaigns')->get();

        foreach ($campaigns as $campaign) {
            $keywords = json_decode($campaign->keywords, true) ?? [];
            $negativeKeywords = json_decode($campaign->negative_keywords, true) ?? [];

            // Clean keywords - filter out empty strings
            $cleanedKeywords = array_values(array_filter($keywords, function($k) {
                return !empty(trim($k ?? ''));
            }));

            // Clean negative keywords
            $cleanedNegative = array_values(array_filter($negativeKeywords, function($k) {
                return !empty(trim($k ?? ''));
            }));

            DB::table('evenleads_campaigns')
                ->where('id', $campaign->id)
                ->update([
                    'keywords' => json_encode($cleanedKeywords),
                    'negative_keywords' => json_encode($cleanedNegative),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - cleaning data is always good
    }
};
