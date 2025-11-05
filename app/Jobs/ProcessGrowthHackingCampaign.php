<?php

namespace App\Jobs;

use App\Models\GrowthHackingCampaign;
use App\Models\GrowthHackingProspect;
use App\Services\GrowthHacking\WebsiteScraperService;
use App\Services\GrowthHacking\ContactExtractorService;
use App\Services\GrowthHacking\ProspectCampaignService;
use App\Services\GrowthHacking\ProspectLeadScannerService;
use App\Services\GrowthHacking\EmailContentGeneratorService;
use App\Services\GrowthHacking\ProspectAccountService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGrowthHackingCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout

    /**
     * Create a new job instance.
     */
    public function __construct(
        public GrowthHackingCampaign $campaign
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        WebsiteScraperService $scraper,
        ContactExtractorService $contactExtractor,
        ProspectCampaignService $campaignService,
        ProspectLeadScannerService $leadScanner,
        EmailContentGeneratorService $emailGenerator,
        ProspectAccountService $accountService
    ): void {
        try {
            Log::info("Processing growth hacking campaign {$this->campaign->id}");

            $urls = $this->campaign->website_urls_array;
            $totalUrls = count($urls);
            $processedCount = 0;

            foreach ($urls as $url) {
                try {
                    // Step 1: Scrape website
                    Log::info("Scraping website: {$url}");
                    $scrapeResult = $scraper->scrapeWebsite($url);

                    if (!$scrapeResult['success']) {
                        Log::warning("Scraping failed for {$url}: " . $scrapeResult['error']);
                        continue;
                    }

                    // Step 2: Extract contact info with AI
                    Log::info("Analyzing website with AI: {$url}");
                    $analysisResult = $contactExtractor->analyzeWebsiteWithAI(
                        $url,
                        $scrapeResult['content'],
                        $scrapeResult['inbound_links'],
                        $scrapeResult['contact_info']
                    );

                    if (!$analysisResult['success']) {
                        Log::warning("AI analysis failed for {$url}: " . $analysisResult['error']);
                        continue;
                    }

                    $aiData = $analysisResult['data'];

                    // Step 3: Create prospect record
                    $prospect = GrowthHackingProspect::create([
                        'campaign_id' => $this->campaign->id,
                        'website_url' => $url,
                        'business_name' => $aiData['business_name'] ?? null,
                        'email' => $scrapeResult['contact_info']['emails'][0] ?? null,
                        'phone' => $scrapeResult['contact_info']['phones'][0] ?? null,
                        'contact_person_name' => $aiData['contact_person_name'] ?? null,
                        'contact_person_email' => $aiData['contact_email'] ?? null,
                        'inbound_links' => $scrapeResult['inbound_links'],
                        'website_content' => $scrapeResult['content'],
                        'ai_analysis' => $aiData,
                        'status' => 'analyzed',
                    ]);

                    Log::info("Prospect created: {$prospect->id} for {$url}");

                    // Step 4: Generate campaign settings for prospect
                    $campaignResult = $campaignService->generateCampaignForProspect($prospect);

                    if ($campaignResult['success']) {
                        $campaignData = $campaignResult['campaign_data'];

                        // Step 5: Scan and create leads for prospect
                        Log::info("Scanning leads for prospect {$prospect->id}");
                        $leadsCount = $leadScanner->scanAndCreateLeads($prospect, $campaignData);

                        Log::info("Found {$leadsCount} leads for prospect {$prospect->id}");

                        // Only proceed if we found leads
                        if ($leadsCount > 0) {
                            // Step 6: Generate email content
                            $emailResult = $emailGenerator->generateEmail($prospect);

                            if ($emailResult['success']) {
                                // Store email template for later review
                                $this->campaign->update([
                                    'email_subject_template' => $emailResult['subject'],
                                    'email_body_template' => $emailResult['body'],
                                ]);
                            }

                            // Step 7: Create user account if enabled
                            if ($this->campaign->auto_create_accounts && $prospect->primary_email) {
                                Log::info("Creating user account for prospect {$prospect->id}");
                                $accountService->createProspectAccount($prospect);
                            }
                        } else {
                            Log::info("No leads found for prospect {$prospect->id}, skipping");
                            $prospect->update(['status' => 'skipped']);
                        }
                    }

                    $processedCount++;

                } catch (\Exception $e) {
                    Log::error("Error processing URL {$url}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    continue;
                }

                // Add small delay to avoid overwhelming external APIs
                sleep(2);
            }

            // Update campaign status and stats
            $this->campaign->update([
                'status' => 'review',
                'total_prospects' => $this->campaign->prospects()->count(),
            ]);

            Log::info("Growth hacking campaign {$this->campaign->id} processed successfully", [
                'total_urls' => $totalUrls,
                'processed' => $processedCount,
                'prospects' => $this->campaign->total_prospects,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to process growth hacking campaign {$this->campaign->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->campaign->update(['status' => 'draft']);

            throw $e;
        }
    }
}
