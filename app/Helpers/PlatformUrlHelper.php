<?php

namespace App\Helpers;

class PlatformUrlHelper
{
    /**
     * Platform domain mappings
     */
    private static array $platformDomains = [
        'reddit' => 'https://reddit.com',
        'linkedin' => 'https://linkedin.com',
        'x' => 'https://twitter.com',
        'twitter' => 'https://twitter.com',
        'facebook' => 'https://facebook.com',
        'fiverr' => 'https://fiverr.com',
        'upwork' => 'https://upwork.com',
    ];

    /**
     * Convert relative URLs in HTML content to absolute URLs based on platform
     *
     * @param string|null $htmlContent The HTML content to process
     * @param string|null $platform The platform name (reddit, linkedin, x, facebook, etc.)
     * @return string The processed HTML content with absolute URLs
     */
    public static function convertRelativeUrls(?string $htmlContent, ?string $platform): string
    {
        if (empty($htmlContent) || empty($platform)) {
            return $htmlContent ?? '';
        }

        // Get platform domain
        $platformLower = strtolower($platform);
        $platformDomain = self::$platformDomains[$platformLower] ?? null;

        if (!$platformDomain) {
            // If platform not recognized, return original content
            return $htmlContent;
        }

        // Use DOMDocument to parse HTML properly
        $dom = new \DOMDocument();

        // Suppress errors for malformed HTML
        libxml_use_internal_errors(true);

        // Load HTML with UTF-8 encoding
        $dom->loadHTML('<?xml encoding="UTF-8">' . $htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Clear errors
        libxml_clear_errors();

        // Find all <a> tags
        $links = $dom->getElementsByTagName('a');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            if (empty($href)) {
                continue;
            }

            // Check if URL is relative (starts with /) or has no protocol
            if (self::isRelativeUrl($href)) {
                // Convert to absolute URL
                $absoluteUrl = self::makeAbsoluteUrl($href, $platformDomain);
                $link->setAttribute('href', $absoluteUrl);
            }
        }

        // Get the modified HTML
        $modifiedHtml = $dom->saveHTML();

        // Remove the XML encoding declaration that was added
        $modifiedHtml = str_replace('<?xml encoding="UTF-8">', '', $modifiedHtml);

        return $modifiedHtml;
    }

    /**
     * Check if a URL is relative (needs to be converted)
     *
     * @param string $url
     * @return bool
     */
    private static function isRelativeUrl(string $url): bool
    {
        // URL is relative if:
        // 1. Starts with / (e.g., /r/ChatGPT)
        // 2. Doesn't start with http:// or https://
        // 3. Doesn't start with // (protocol-relative URLs like //example.com)
        // 4. Doesn't start with mailto:, tel:, javascript:, #, etc.

        $url = trim($url);

        // Check if it's an anchor link
        if (str_starts_with($url, '#')) {
            return false;
        }

        // Check if it has a protocol
        if (preg_match('/^(https?:\/\/|mailto:|tel:|javascript:|data:)/i', $url)) {
            return false;
        }

        // Check if it's protocol-relative (//example.com)
        if (str_starts_with($url, '//')) {
            return false;
        }

        // If it starts with / or has no protocol, it's relative
        return true;
    }

    /**
     * Convert a relative URL to absolute using platform domain
     *
     * @param string $relativeUrl
     * @param string $platformDomain
     * @return string
     */
    private static function makeAbsoluteUrl(string $relativeUrl, string $platformDomain): string
    {
        $relativeUrl = trim($relativeUrl);

        // If it starts with /, just prepend the domain
        if (str_starts_with($relativeUrl, '/')) {
            return $platformDomain . $relativeUrl;
        }

        // Otherwise, add both domain and /
        return $platformDomain . '/' . $relativeUrl;
    }

    /**
     * Batch convert multiple content items (for performance)
     *
     * @param array $items Array of items with 'content' and 'platform' keys
     * @return array The items with converted content
     */
    public static function batchConvert(array $items): array
    {
        foreach ($items as &$item) {
            if (isset($item['content']) && isset($item['platform'])) {
                $item['content'] = self::convertRelativeUrls($item['content'], $item['platform']);
            }
        }

        return $items;
    }

    /**
     * Add a custom platform domain mapping
     *
     * @param string $platform
     * @param string $domain
     */
    public static function addPlatformDomain(string $platform, string $domain): void
    {
        self::$platformDomains[strtolower($platform)] = $domain;
    }

    /**
     * Get all platform domain mappings
     *
     * @return array
     */
    public static function getPlatformDomains(): array
    {
        return self::$platformDomains;
    }
}
