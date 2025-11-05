/**
 * X (Twitter) Scraper for runSync
 *
 * Automates X/Twitter search and lead extraction:
 * 1. Navigate to X search
 * 2. Search for keywords
 * 3. Extract tweets from results
 * 4. Submit leads to API in real-time
 */

import { api } from '../api';
import type { SyncConfig, SyncProgress } from '../runSync';

export class XScraper {
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
    console.log('[X Scraper] Starting sync with', this.config.keywords.length, 'keywords');

    for (let i = 0; i < this.config.keywords.length; i++) {
      const keyword = this.config.keywords[i];

      this.onProgress({
        status: 'searching',
        currentKeyword: keyword,
        currentKeywordIndex: i,
        message: `Searching X for "${keyword}"...`,
      });

      try {
        await this.searchKeyword(keyword);
      } catch (error) {
        console.error(`[X] Failed to search for "${keyword}":`, error);
      }

      // Rate limiting: Wait 4-6 seconds between searches (X is very strict)
      if (i < this.config.keywords.length - 1) {
        await this.sleep(4000 + Math.random() * 2000);
      }
    }
  }

  /**
   * Search for keyword on X
   */
  private async searchKeyword(keyword: string): Promise<void> {
    const searchUrl = `https://x.com/search?q=${encodeURIComponent(keyword)}&src=typed_query&f=live`;

    // Navigate to search
    window.location.href = searchUrl;
    await this.waitForPageLoad();
    await this.sleep(3000); // X loads slowly with dynamic content

    // Extract tweets
    await this.extractTweets(keyword);
  }

  /**
   * Extract tweets from search results
   */
  private async extractTweets(keyword: string): Promise<void> {
    this.onProgress({
      status: 'extracting',
      message: `Extracting tweets for "${keyword}"...`,
    });

    // X tweet selectors
    const tweets = Array.from(document.querySelectorAll('article[data-testid="tweet"], div[data-testid="cellInnerDiv"]'));

    console.log('[X] Found', tweets.length, 'tweets');

    const maxLeads = this.config.maxLeadsPerKeyword || 10;
    let extracted = 0;

    for (const tweet of tweets) {
      if (extracted >= maxLeads) break;

      try {
        const lead = await this.extractLeadFromTweet(tweet as HTMLElement, keyword);
        if (lead) {
          this.leadsFound++;
          extracted++;

          // Submit to API
          await this.submitLead(lead);

          this.onProgress({
            leadsFound: this.leadsFound,
            leadsSubmitted: this.leadsSubmitted,
            message: `Found ${this.leadsFound} tweets`,
          });
        }
      } catch (error) {
        console.error('[X] Failed to extract tweet:', error);
      }
    }
  }

  /**
   * Extract lead data from a tweet
   */
  private async extractLeadFromTweet(tweet: HTMLElement, keyword: string): Promise<any | null> {
    try {
      // Extract author
      const authorElement = tweet.querySelector('[data-testid="User-Name"] a[href^="/"]');
      const author = authorElement?.textContent?.trim()?.replace(/@/g, '') || 'Unknown';

      // Extract tweet text
      const textElement = tweet.querySelector('[data-testid="tweetText"], [lang]');
      const text = textElement?.textContent?.trim() || '';

      // Extract URL
      const timeElement = tweet.querySelector('time');
      const tweetLink = timeElement?.parentElement as HTMLAnchorElement;
      let url = tweetLink?.href || '';

      // Make URL absolute
      if (url.startsWith('/')) {
        url = 'https://x.com' + url;
      }

      // Check if matches keyword
      const textLower = text.toLowerCase();
      const keywordLower = keyword.toLowerCase();

      if (!textLower.includes(keywordLower)) {
        return null;
      }

      return {
        platform: 'x',
        platform_id: this.extractPlatformId(url),
        title: `Tweet by @${author}`,
        description: text.substring(0, 5000),
        url,
        author: author.substring(0, 255),
        confidence: 8,
      };
    } catch (error) {
      console.error('[X] Failed to extract tweet data:', error);
      return null;
    }
  }

  /**
   * Extract platform ID from URL
   */
  private extractPlatformId(url: string): string {
    const match = url.match(/status\/(\d+)/);
    return match ? match[1] : url.substring(url.length - 20);
  }

  /**
   * Submit lead to API
   */
  private async submitLead(lead: any): Promise<void> {
    try {
      // Respect rate limiting (max 15 requests per 15 minutes - very conservative for X)
      await this.respectRateLimit(15, 15 * 60 * 1000);

      await api.submitLead(this.config.campaignId, lead);
      this.leadsSubmitted++;

      console.log('[X] Lead submitted:', lead.title);
    } catch (error) {
      console.error('[X] Failed to submit lead:', error);
    }
  }

  /**
   * Rate limiting
   */
  private async respectRateLimit(maxRequests: number, timeWindowMs: number): Promise<void> {
    const now = Date.now();
    this.requestTimestamps = this.requestTimestamps.filter((ts) => now - ts < timeWindowMs);

    if (this.requestTimestamps.length >= maxRequests) {
      const oldestTimestamp = this.requestTimestamps[0];
      const waitTime = timeWindowMs - (now - oldestTimestamp) + 1000;
      console.log('[X] Rate limit reached, waiting', Math.round(waitTime / 1000), 'seconds');
      await this.sleep(waitTime);
    }

    this.requestTimestamps.push(now);
  }

  /**
   * Wait for page load
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
   * Sleep utility
   */
  private async sleep(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }
}
