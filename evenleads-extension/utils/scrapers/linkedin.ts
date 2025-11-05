/**
 * LinkedIn Scraper for runSync
 *
 * Automates LinkedIn search and lead extraction:
 * 1. Navigate to LinkedIn search
 * 2. Enter keywords one by one
 * 3. Extract posts/profiles from results
 * 4. Submit leads to API in real-time
 */

import { api } from '../api';
import type { SyncConfig, SyncProgress } from '../runSync';

export class LinkedInScraper {
  private config: SyncConfig;
  private onProgress: (update: Partial<SyncProgress>) => void;
  private leadsFound: number = 0;
  private leadsSubmitted: number = 0;
  private requestTimestamps: number[] = [];

  constructor(config: SyncConfig, onProgress: (update: Partial<SyncProgress>) => void) {
    this.config = config;
    this.onProgress = onProgress;
  }

  getLeadsFound(): number {
    return this.leadsFound;
  }

  getLeadsSubmitted(): number {
    return this.leadsSubmitted;
  }

  /**
   * Main sync method
   */
  async sync(): Promise<void> {
    console.log('[LinkedIn Scraper] Starting sync with', this.config.keywords.length, 'keywords');

    for (let i = 0; i < this.config.keywords.length; i++) {
      const keyword = this.config.keywords[i];

      this.onProgress({
        status: 'searching',
        currentKeyword: keyword,
        currentKeywordIndex: i,
        message: `Searching LinkedIn for "${keyword}"...`,
      });

      try {
        await this.searchKeyword(keyword);
      } catch (error: any) {
        console.error(`[LinkedIn] Failed to search for "${keyword}":`, error);
        // Continue with next keyword
      }

      // Rate limiting: Wait 3-5 seconds between keywords
      if (i < this.config.keywords.length - 1) {
        await this.sleep(3000 + Math.random() * 2000);
      }
    }
  }

  /**
   * Search for a specific keyword on LinkedIn
   */
  private async searchKeyword(keyword: string): Promise<void> {
    // Navigate to LinkedIn search
    const searchUrl = `https://www.linkedin.com/search/results/content/?keywords=${encodeURIComponent(keyword)}`;
    window.location.href = searchUrl;

    // Wait for page load
    await this.waitForPageLoad();

    // Wait for search results to appear
    await this.waitForSearchResults();

    // Extract leads from results
    await this.extractLeads(keyword);
  }

  /**
   * Wait for page to fully load
   */
  private async waitForPageLoad(): Promise<void> {
    return new Promise((resolve) => {
      if (document.readyState === 'complete') {
        resolve();
      } else {
        window.addEventListener('load', () => resolve(), { once: true });
      }
    });
  }

  /**
   * Wait for search results to appear
   */
  private async waitForSearchResults(): Promise<void> {
    const maxAttempts = 20; // 10 seconds max
    let attempts = 0;

    while (attempts < maxAttempts) {
      // LinkedIn search results container
      const resultsContainer = document.querySelector('.search-results-container, [class*="search-results"]');
      const posts = document.querySelectorAll('.feed-shared-update-v2, [data-id^="urn:li:activity"]');

      if (resultsContainer && posts.length > 0) {
        console.log('[LinkedIn] Search results loaded, found', posts.length, 'posts');
        return;
      }

      attempts++;
      await this.sleep(500);
    }

    console.warn('[LinkedIn] Search results timeout - proceeding anyway');
  }

  /**
   * Extract leads from search results
   */
  private async extractLeads(keyword: string): Promise<void> {
    this.onProgress({
      status: 'extracting',
      message: `Extracting leads for "${keyword}"...`,
    });

    // LinkedIn post selectors
    const postSelectors = [
      '.feed-shared-update-v2',
      '[data-id^="urn:li:activity"]',
      'article[data-id]',
    ];

    let posts: Element[] = [];
    for (const selector of postSelectors) {
      const elements = Array.from(document.querySelectorAll(selector));
      if (elements.length > 0) {
        posts = elements;
        break;
      }
    }

    console.log('[LinkedIn] Found', posts.length, 'posts to extract');

    const maxLeads = this.config.maxLeadsPerKeyword || 10;
    let extracted = 0;

    for (const post of posts) {
      if (extracted >= maxLeads) break;

      try {
        const lead = await this.extractLeadFromPost(post as HTMLElement, keyword);
        if (lead) {
          this.leadsFound++;
          extracted++;

          // Submit lead to API in real-time
          await this.submitLead(lead);

          this.onProgress({
            leadsFound: this.leadsFound,
            leadsSubmitted: this.leadsSubmitted,
            message: `Found ${this.leadsFound} leads for "${keyword}"`,
          });
        }
      } catch (error) {
        console.error('[LinkedIn] Failed to extract lead from post:', error);
        // Continue with next post
      }
    }
  }

  /**
   * Extract lead data from a LinkedIn post
   */
  private async extractLeadFromPost(post: HTMLElement, keyword: string): Promise<any | null> {
    try {
      // Extract title (post content or author name)
      const titleElement = post.querySelector('.update-components-actor__title, .feed-shared-actor__name');
      const title = titleElement?.textContent?.trim() || 'LinkedIn Post';

      // Extract description (post text)
      const descriptionElement = post.querySelector('.feed-shared-text, .update-components-text, [data-test-id="main-feed-activity-card__commentary"]');
      const description = descriptionElement?.textContent?.trim() || '';

      // Extract author
      const authorElement = post.querySelector('.update-components-actor__name, .feed-shared-actor__name');
      const author = authorElement?.textContent?.trim() || 'Unknown';

      // Extract URL
      const linkElement = post.querySelector('a[href*="/posts/"], a[href*="/activity-"], a[data-control-name="search_result"]');
      let url = linkElement?.getAttribute('href') || window.location.href;

      // Make URL absolute if relative
      if (url.startsWith('/')) {
        url = 'https://www.linkedin.com' + url;
      }

      // Check if description contains keyword
      const descLower = description.toLowerCase();
      const keywordLower = keyword.toLowerCase();
      if (!descLower.includes(keywordLower) && !title.toLowerCase().includes(keywordLower)) {
        console.log('[LinkedIn] Post does not match keyword, skipping');
        return null;
      }

      return {
        platform: 'linkedin',
        platform_id: this.extractPlatformId(url),
        title: title.substring(0, 255),
        description: description.substring(0, 5000),
        url,
        author: author.substring(0, 255),
        confidence: 8, // High confidence for keyword match
      };
    } catch (error) {
      console.error('[LinkedIn] Failed to extract lead data:', error);
      return null;
    }
  }

  /**
   * Extract platform ID from URL
   */
  private extractPlatformId(url: string): string {
    const match = url.match(/activity[:-](\d+)/) || url.match(/posts\/([^/?]+)/) || url.match(/urn:li:activity:(\d+)/);
    return match ? match[1] : url.substring(url.length - 20);
  }

  /**
   * Submit lead to API
   */
  private async submitLead(lead: any): Promise<void> {
    this.onProgress({
      status: 'submitting',
      message: `Submitting lead: ${lead.title.substring(0, 50)}...`,
    });

    try {
      // Respect rate limiting (max 20 requests per minute)
      await this.respectRateLimit(20, 60000);

      await api.submitLead(this.config.campaignId, lead);
      this.leadsSubmitted++;

      console.log('[LinkedIn] Lead submitted successfully:', lead.title);
    } catch (error: any) {
      console.error('[LinkedIn] Failed to submit lead:', error);
      // Continue anyway - don't fail the entire sync
    }
  }

  /**
   * Rate limiting - ensure we don't exceed max requests per time period
   */
  private async respectRateLimit(maxRequests: number, timeWindowMs: number): Promise<void> {
    const now = Date.now();

    // Remove timestamps older than time window
    this.requestTimestamps = this.requestTimestamps.filter((ts) => now - ts < timeWindowMs);

    // If we're at the limit, wait
    if (this.requestTimestamps.length >= maxRequests) {
      const oldestTimestamp = this.requestTimestamps[0];
      const waitTime = timeWindowMs - (now - oldestTimestamp) + 1000; // +1s buffer

      console.log('[LinkedIn] Rate limit reached, waiting', Math.round(waitTime / 1000), 'seconds');
      await this.sleep(waitTime);
    }

    // Record this request
    this.requestTimestamps.push(now);
  }

  /**
   * Sleep utility
   */
  private async sleep(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }
}
