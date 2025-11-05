/**
 * runSync - Extension-Based Sync Orchestrator
 *
 * This module handles syncing leads by using the browser extension to:
 * 1. Navigate to platforms (LinkedIn, Reddit, X, etc.)
 * 2. Search for keywords
 * 3. Extract leads from search results
 * 4. Submit leads to API in real-time
 * 5. Report progress back to the website
 */

import { api } from './api';
import type { Platform } from '../types';

export interface SyncConfig {
  campaignId: number;
  platform: Platform;
  keywords: string[];
  offering: string;
  intelligentMode: boolean;
  maxLeadsPerKeyword?: number;
  subreddits?: string[]; // For Reddit
}

export interface SyncProgress {
  status: 'preparing' | 'navigating' | 'searching' | 'extracting' | 'submitting' | 'complete' | 'error';
  currentKeyword: string;
  currentKeywordIndex: number;
  totalKeywords: number;
  leadsFound: number;
  leadsSubmitted: number;
  message: string;
  error?: string;
}

export type SyncProgressCallback = (progress: SyncProgress) => void;

export class SyncOrchestrator {
  private config: SyncConfig;
  private progress: SyncProgress;
  private onProgress: SyncProgressCallback;
  private aborted: boolean = false;

  constructor(config: SyncConfig, onProgress: SyncProgressCallback) {
    this.config = config;
    this.onProgress = onProgress;
    this.progress = {
      status: 'preparing',
      currentKeyword: '',
      currentKeywordIndex: 0,
      totalKeywords: config.keywords.length,
      leadsFound: 0,
      leadsSubmitted: 0,
      message: 'Preparing to sync...',
    };
  }

  /**
   * Start the sync process
   */
  async start(): Promise<void> {
    try {
      this.updateProgress({ status: 'preparing', message: 'Loading campaign data...' });

      // NEW: Fetch campaign context (includes search terms + schemas)
      const context = await api.getCampaignContext(this.config.campaignId);

      if (!context.success) {
        throw new Error('Failed to load campaign data');
      }

      // Extract data from context
      const campaign = context.campaign;
      const searchTerms = context.search_terms || [];
      const schemas = context.schemas || {};

      console.log('[RunSync] Campaign context loaded:', {
        platform: this.config.platform,
        searchTerms: searchTerms.length,
        hasSchema: !!schemas[this.config.platform],
      });

      // Check if schema exists for this platform
      if (!schemas[this.config.platform] || !schemas[this.config.platform]['search_list']) {
        throw new Error(
          `Schema not configured for ${this.config.platform}. Please create a schema in DEV mode first.`
        );
      }

      // Use search terms from context
      this.config.keywords = searchTerms;
      this.progress.totalKeywords = searchTerms.length;
      this.updateProgress({
        totalKeywords: this.progress.totalKeywords,
        message: `Ready to sync with ${searchTerms.length} search terms`,
      });

      if (this.progress.totalKeywords === 0) {
        throw new Error('No search terms available');
      }

      // Record sync start with backend (counts as manual sync)
      this.updateProgress({ status: 'preparing', message: 'Recording sync start...' });
      await api.recordSyncStart(this.config.campaignId);
      console.log('[RunSync] Manual sync recorded successfully');

      // Open platform in new tab
      this.updateProgress({ status: 'navigating', message: `Opening ${this.config.platform}...` });
      await this.openPlatformInNewTab(schemas[this.config.platform]);

      // Note: Sync continues in the new tab via content script

      // Complete
      this.updateProgress({
        status: 'complete',
        message: `Sync complete! Found ${this.progress.leadsFound} leads, submitted ${this.progress.leadsSubmitted}`,
      });
    } catch (error: any) {
      console.error('[RunSync] Sync failed:', error);
      this.updateProgress({
        status: 'error',
        message: 'Sync failed',
        error: error.message,
      });
      throw error;
    }
  }

  /**
   * Abort the sync
   */
  abort(): void {
    this.aborted = true;
    this.updateProgress({
      status: 'error',
      message: 'Sync aborted by user',
    });
  }

  /**
   * Update progress and notify callback
   */
  private updateProgress(updates: Partial<SyncProgress>): void {
    this.progress = { ...this.progress, ...updates };
    this.onProgress(this.progress);
  }

  /**
   * Generate search terms using AI if no keywords provided
   * NOW USES campaign_id only - server handles everything
   */
  private async generateSearchTerms(): Promise<void> {
    this.updateProgress({ status: 'preparing', message: 'Generating search terms with AI...' });

    try {
      const response = await api.generateSearchTerms(this.config.campaignId);

      this.config.keywords = response.keywords || [];
      console.log('[RunSync] Generated keywords:', this.config.keywords);
    } catch (error) {
      console.error('[RunSync] Failed to generate search terms:', error);
      throw new Error('Failed to generate search terms. Please add keywords manually.');
    }
  }

  /**
   * Open platform in new tab with schema context
   */
  private async openPlatformInNewTab(platformSchemas: any): Promise<void> {
    const platformUrls: Record<string, string> = {
      linkedin: 'https://www.linkedin.com/feed/',
      reddit: 'https://www.reddit.com/',
      x: 'https://x.com/home',
      facebook: 'https://www.facebook.com/',
      fiverr: 'https://www.fiverr.com/',
      upwork: 'https://www.upwork.com/nx/find-work/',
    };

    const url = platformUrls[this.config.platform];
    if (!url) {
      throw new Error(`Unsupported platform: ${this.config.platform}`);
    }

    // Store sync config in storage for new tab to access
    await browser.storage.local.set({
      'pending_sync': {
        campaignId: this.config.campaignId,
        platform: this.config.platform,
        keywords: this.config.keywords,
        offering: this.config.offering,
        schemas: platformSchemas,
        timestamp: Date.now(),
      }
    });

    // Use browser.runtime.sendMessage to ask background script to create tab
    // Content scripts cannot use chrome.tabs API
    await browser.runtime.sendMessage({
      type: 'CREATE_TAB',
      url: url,
    });

    // Complete in current tab (new tab will continue sync)
    this.updateProgress({
      status: 'complete',
      message: `Opening ${this.config.platform} in new tab. Sync will continue automatically...`,
    });
  }

  /**
   * Navigate to the platform homepage
   */
  private async navigateToPlatform(): Promise<void> {
    this.updateProgress({ status: 'navigating', message: `Navigating to ${this.config.platform}...` });

    const platformUrls: Record<string, string> = {
      linkedin: 'https://www.linkedin.com',
      reddit: 'https://www.reddit.com',
      x: 'https://x.com',
      facebook: 'https://www.facebook.com',
      fiverr: 'https://www.fiverr.com',
      upwork: 'https://www.upwork.com',
    };

    const url = platformUrls[this.config.platform];
    if (!url) {
      throw new Error(`Unsupported platform: ${this.config.platform}`);
    }

    // Navigate to platform
    window.location.href = url;

    // Wait for page to load
    await this.waitForPageLoad();
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
   * Execute sync based on platform
   */
  private async executePlatformSync(): Promise<void> {
    switch (this.config.platform) {
      case 'linkedin':
        await this.syncLinkedIn();
        break;
      case 'reddit':
        await this.syncReddit();
        break;
      case 'x':
        await this.syncX();
        break;
      default:
        throw new Error(`Sync not yet implemented for ${this.config.platform}`);
    }
  }

  /**
   * Sync LinkedIn (placeholder - will be implemented in linkedin.ts)
   */
  private async syncLinkedIn(): Promise<void> {
    // Import dynamically to avoid circular dependencies
    const { LinkedInScraper } = await import('./scrapers/linkedin');
    const scraper = new LinkedInScraper(this.config, (update) => this.updateProgress(update));
    await scraper.sync();
    this.progress.leadsFound = scraper.getLeadsFound();
    this.progress.leadsSubmitted = scraper.getLeadsSubmitted();
  }

  /**
   * Sync Reddit (placeholder - will be implemented in reddit.ts)
   */
  private async syncReddit(): Promise<void> {
    const { RedditScraper } = await import('./scrapers/reddit');
    const scraper = new RedditScraper(this.config, (update) => this.updateProgress(update));
    await scraper.sync();
    this.progress.leadsFound = scraper.getLeadsFound();
    this.progress.leadsSubmitted = scraper.getLeadsSubmitted();
  }

  /**
   * Sync X/Twitter (placeholder - will be implemented in x.ts)
   */
  private async syncX(): Promise<void> {
    const { XScraper } = await import('./scrapers/x');
    const scraper = new XScraper(this.config, (update) => this.updateProgress(update));
    await scraper.sync();
    this.progress.leadsFound = scraper.getLeadsFound();
    this.progress.leadsSubmitted = scraper.getLeadsSubmitted();
  }

  /**
   * Utility: Sleep for a given time
   */
  protected async sleep(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }
}

/**
 * Start a sync from external trigger (e.g., website postMessage)
 */
export async function startSync(config: SyncConfig, onProgress: SyncProgressCallback): Promise<void> {
  const orchestrator = new SyncOrchestrator(config, onProgress);
  await orchestrator.start();
}
