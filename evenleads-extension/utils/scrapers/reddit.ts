/**
 * Reddit Scraper for runSync
 *
 * Automates Reddit search and lead extraction:
 * 1. Navigate to Reddit search or specific subreddits
 * 2. Search for keywords
 * 3. Extract posts from results
 * 4. Submit leads to API in real-time
 */

import { api } from '../api';
import type { SyncConfig, SyncProgress } from '../runSync';

export class RedditScraper {
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
    console.log('[Reddit Scraper] Starting sync with', this.config.keywords.length, 'keywords');

    // If subreddits are specified, search within them
    if (this.config.subreddits && this.config.subreddits.length > 0) {
      await this.syncSubreddits();
    } else {
      // Global Reddit search
      await this.syncGlobal();
    }
  }

  /**
   * Sync specific subreddits
   */
  private async syncSubreddits(): Promise<void> {
    for (const subreddit of this.config.subreddits!) {
      for (let i = 0; i < this.config.keywords.length; i++) {
        const keyword = this.config.keywords[i];

        this.onProgress({
          status: 'searching',
          currentKeyword: keyword,
          currentKeywordIndex: i,
          message: `Searching r/${subreddit} for "${keyword}"...`,
        });

        try {
          await this.searchSubreddit(subreddit, keyword);
        } catch (error) {
          console.error(`[Reddit] Failed to search r/${subreddit} for "${keyword}":`, error);
        }

        // Rate limiting: Wait 2-4 seconds between searches
        if (i < this.config.keywords.length - 1) {
          await this.sleep(2000 + Math.random() * 2000);
        }
      }
    }
  }

  /**
   * Sync with global Reddit search
   */
  private async syncGlobal(): Promise<void> {
    for (let i = 0; i < this.config.keywords.length; i++) {
      const keyword = this.config.keywords[i];

      this.onProgress({
        status: 'searching',
        currentKeyword: keyword,
        currentKeywordIndex: i,
        message: `Searching all of Reddit for "${keyword}"...`,
      });

      try {
        await this.globalSearch(keyword);
      } catch (error) {
        console.error(`[Reddit] Failed to search for "${keyword}":`, error);
      }

      // Rate limiting: Wait 2-4 seconds between searches
      if (i < this.config.keywords.length - 1) {
        await this.sleep(2000 + Math.random() * 2000);
      }
    }
  }

  /**
   * Search specific subreddit for keyword
   */
  private async searchSubreddit(subreddit: string, keyword: string): Promise<void> {
    const searchUrl = `https://www.reddit.com/r/${subreddit}/search/?q=${encodeURIComponent(keyword)}&restrict_sr=1&sort=relevance&t=week`;

    // Navigate to search
    window.location.href = searchUrl;
    await this.waitForPageLoad();
    await this.sleep(2000); // Wait for dynamic content

    // Extract posts
    await this.extractPosts(keyword);
  }

  /**
   * Global Reddit search
   */
  private async globalSearch(keyword: string): Promise<void> {
    const searchUrl = `https://www.reddit.com/search/?q=${encodeURIComponent(keyword)}&type=link&sort=relevance&t=week`;

    // Navigate to search
    window.location.href = searchUrl;
    await this.waitForPageLoad();
    await this.sleep(2000); // Wait for dynamic content

    // Extract posts
    await this.extractPosts(keyword);
  }

  /**
   * Extract posts from current page
   */
  private async extractPosts(keyword: string): Promise<void> {
    this.onProgress({
      status: 'extracting',
      message: `Extracting leads for "${keyword}"...`,
    });

    // Reddit post selectors (new and old Reddit)
    const posts = Array.from(document.querySelectorAll('[data-testid="post-container"], shreddit-post, div[data-click-id="background"]'));

    console.log('[Reddit] Found', posts.length, 'posts');

    const maxLeads = this.config.maxLeadsPerKeyword || 10;
    let extracted = 0;

    for (const post of posts) {
      if (extracted >= maxLeads) break;

      try {
        const lead = await this.extractLeadFromPost(post as HTMLElement, keyword);
        if (lead) {
          this.leadsFound++;
          extracted++;

          // Submit to API
          await this.submitLead(lead);

          this.onProgress({
            leadsFound: this.leadsFound,
            leadsSubmitted: this.leadsSubmitted,
            message: `Found ${this.leadsFound} leads`,
          });
        }
      } catch (error) {
        console.error('[Reddit] Failed to extract post:', error);
      }
    }
  }

  /**
   * Extract lead data from a Reddit post
   */
  private async extractLeadFromPost(post: HTMLElement, keyword: string): Promise<any | null> {
    try {
      // Extract title
      const titleElement = post.querySelector('h3, [slot="title"], a[data-click-id="body"]');
      const title = titleElement?.textContent?.trim() || '';

      // Extract description/selftext
      const descElement = post.querySelector('[data-test-id="post-content"], .md, [slot="text-body"]');
      const description = descElement?.textContent?.trim() || title;

      // Extract author
      const authorElement = post.querySelector('[data-testid="post_author_link"], a[href*="/user/"], a[href*="/u/"]');
      const author = authorElement?.textContent?.trim()?.replace(/^u\//, '') || 'Unknown';

      // Extract URL
      const linkElement = post.querySelector('a[data-click-id="body"], shreddit-post') as HTMLAnchorElement;
      let url = linkElement?.href || post.querySelector('a[href*="/comments/"]')?.getAttribute('href') || '';

      // Make URL absolute
      if (url.startsWith('/')) {
        url = 'https://www.reddit.com' + url;
      }

      // Extract subreddit
      const subredditMatch = url.match(/\/r\/([\w-]+)\//);
      const subreddit = subredditMatch ? subredditMatch[1] : '';

      // Check if matches keyword
      const titleLower = title.toLowerCase();
      const descLower = description.toLowerCase();
      const keywordLower = keyword.toLowerCase();

      if (!titleLower.includes(keywordLower) && !descLower.includes(keywordLower)) {
        return null;
      }

      return {
        platform: 'reddit',
        platform_id: this.extractPlatformId(url),
        title: title.substring(0, 255),
        description: description.substring(0, 5000),
        url,
        author: author.substring(0, 255),
        subreddit,
        confidence: 8,
      };
    } catch (error) {
      console.error('[Reddit] Failed to extract post data:', error);
      return null;
    }
  }

  /**
   * Extract platform ID from URL
   */
  private extractPlatformId(url: string): string {
    const match = url.match(/comments\/([\w-]+)/);
    return match ? match[1] : url.substring(url.length - 20);
  }

  /**
   * Submit lead to API
   */
  private async submitLead(lead: any): Promise<void> {
    try {
      // Respect rate limiting (max 60 requests per minute)
      await this.respectRateLimit(60, 60000);

      await api.submitLead(this.config.campaignId, lead);
      this.leadsSubmitted++;

      console.log('[Reddit] Lead submitted:', lead.title);
    } catch (error) {
      console.error('[Reddit] Failed to submit lead:', error);
      // Continue anyway
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
      console.log('[Reddit] Rate limit reached, waiting', Math.round(waitTime / 1000), 'seconds');
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
