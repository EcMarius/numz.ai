<?php

namespace App\Services\GrowthHacking;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Wave\Plugins\EvenLeads\Models\Setting;

class ContactExtractorService
{
    /**
     * Analyze website content with AI and extract contact information
     */
    public function analyzeWebsiteWithAI(string $url, string $content, array $inboundLinks, array $basicContactInfo): array
    {
        try {
            $apiKey = Setting::getValue('openai_api_key');

            if (!$apiKey) {
                throw new \Exception('OpenAI API key not configured');
            }

            $prompt = $this->buildPrompt($url, $content, $basicContactInfo);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert business analyst who extracts structured information from websites. Return only valid JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 1000,
            ]);

            if (!$response->successful()) {
                throw new \Exception('OpenAI API request failed: ' . $response->body());
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                throw new \Exception('Empty response from OpenAI');
            }

            // Parse JSON response
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from AI: ' . json_last_error_msg());
            }

            return [
                'success' => true,
                'data' => $data,
            ];

        } catch (\Exception $e) {
            Log::error("AI analysis failed for {$url}", [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build AI prompt for contact extraction
     */
    protected function buildPrompt(string $url, string $content, array $basicContactInfo): string
    {
        $emailsList = !empty($basicContactInfo['emails']) ? implode(', ', $basicContactInfo['emails']) : 'none found';
        $phonesList = !empty($basicContactInfo['phones']) ? implode(', ', $basicContactInfo['phones']) : 'none found';

        return <<<PROMPT
Analyze this business website and extract the following information. Return ONLY a JSON object with these exact fields:

{
  "business_name": "Company name",
  "industry": "Their industry/niche (e.g., Web Development, SaaS, E-commerce)",
  "contact_email": "Best contact email (or null if none)",
  "contact_person_name": "Name of a person if found (or null)",
  "phone": "Phone number if found (or null)",
  "description": "What they offer in 2-3 sentences",
  "target_market": "Who they sell to (e.g., SMBs, Enterprise, Consumers)",
  "pain_points": ["Pain point 1", "Pain point 2", "Pain point 3"],
  "keywords": ["keyword1", "keyword2", "keyword3"]
}

**Website URL:** {$url}

**Emails found:** {$emailsList}
**Phones found:** {$phonesList}

**Website Content:**
{$content}

Remember: Return ONLY the JSON object, no additional text.
PROMPT;
    }
}
