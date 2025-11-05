<?php

namespace App\Services\GrowthHacking;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class WebsiteScraperService
{
    /**
     * Scrape a website and its 1-depth internal links
     */
    public function scrapeWebsite(string $url): array
    {
        try {
            $baseUrl = $this->getBaseUrl($url);

            // Scrape homepage
            $homepageContent = $this->scrapePage($url);

            // Extract 1-depth links
            $internalLinks = $this->extract1DepthLinks($url, $homepageContent);

            // Scrape key pages (About, Contact, Services)
            $aboutPage = $this->findAndScrapePage($internalLinks, ['about', 'about-us', 'company']);
            $contactPage = $this->findAndScrapePage($internalLinks, ['contact', 'contact-us', 'reach-us']);
            $servicesPage = $this->findAndScrapePage($internalLinks, ['services', 'what-we-do', 'solutions', 'products']);

            // Combine all content
            $fullContent = implode("\n\n---PAGE BREAK---\n\n", array_filter([
                "=== HOMEPAGE ===\n" . $homepageContent,
                $aboutPage ? "=== ABOUT PAGE ===\n" . $aboutPage : null,
                $contactPage ? "=== CONTACT PAGE ===\n" . $contactPage : null,
                $servicesPage ? "=== SERVICES PAGE ===\n" . $servicesPage : null,
            ]));

            return [
                'success' => true,
                'content' => $fullContent,
                'inbound_links' => $internalLinks,
                'contact_info' => $this->extractContactInfo($fullContent),
            ];

        } catch (\Exception $e) {
            Log::error("Website scraping failed for {$url}", [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Scrape a single page
     */
    protected function scrapePage(string $url): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; EvenLeadsBot/1.0; +https://evenleads.com/bot)',
                ])
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();

            return $this->extractTextContent($html);

        } catch (\Exception $e) {
            Log::warning("Failed to scrape page: {$url}", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extract text content from HTML
     */
    protected function extractTextContent(string $html): string
    {
        // Remove scripts and styles
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

        // Strip all HTML tags
        $text = strip_tags($html);

        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Limit to first 10,000 characters to avoid excessive token usage
        return substr($text, 0, 10000);
    }

    /**
     * Extract internal links from homepage (1-depth only)
     */
    public function extract1DepthLinks(string $url, string $html): array
    {
        $baseUrl = $this->getBaseUrl($url);
        $links = [];

        // Use DOMDocument to parse HTML
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//a[@href]');

        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');

            // Make absolute URL
            if (str_starts_with($href, '/')) {
                $href = rtrim($baseUrl, '/') . $href;
            } elseif (!str_starts_with($href, 'http')) {
                continue; // Skip relative paths
            }

            // Only include same-domain links
            if (str_starts_with($href, $baseUrl) && $href !== $url) {
                $links[] = $href;
            }
        }

        // Remove duplicates and limit to 20
        return array_slice(array_unique($links), 0, 20);
    }

    /**
     * Extract contact information (emails, phones)
     */
    public function extractContactInfo(string $content): array
    {
        $info = [
            'emails' => [],
            'phones' => [],
        ];

        // Extract emails
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $content, $emailMatches);
        $info['emails'] = array_unique($emailMatches[0]);

        // Filter out common non-personal emails
        $info['emails'] = array_filter($info['emails'], function($email) {
            $blacklist = ['info@', 'contact@', 'support@', 'hello@', 'privacy@', 'noreply@', 'no-reply@'];
            foreach ($blacklist as $prefix) {
                if (str_starts_with(strtolower($email), $prefix)) {
                    return false;
                }
            }
            return true;
        });

        // Extract phone numbers (various formats)
        preg_match_all('/(\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $content, $phoneMatches);
        $info['phones'] = array_unique($phoneMatches[0]);

        return $info;
    }

    /**
     * Find and scrape a page matching keywords
     */
    protected function findAndScrapePage(array $links, array $keywords): ?string
    {
        foreach ($links as $link) {
            $linkLower = strtolower($link);
            foreach ($keywords as $keyword) {
                if (str_contains($linkLower, $keyword)) {
                    return $this->scrapePage($link);
                }
            }
        }
        return null;
    }

    /**
     * Get base URL (protocol + domain)
     */
    protected function getBaseUrl(string $url): string
    {
        $parsed = parse_url($url);
        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
    }
}
