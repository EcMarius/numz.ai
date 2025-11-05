import { authStorage } from './storage';
import { getApiBaseUrl } from '../config';
import { cache } from './cache';
import type { User, Subscription, Campaign, Lead } from '../types';

interface RetryConfig {
  maxRetries: number;
  initialDelay: number;
  maxDelay: number;
  backoffMultiplier: number;
}

const DEFAULT_RETRY_CONFIG: RetryConfig = {
  maxRetries: 3,
  initialDelay: 1000, // 1 second
  maxDelay: 10000, // 10 seconds
  backoffMultiplier: 2,
};

class ApiClient {
  private baseUrl: string = getApiBaseUrl();
  private requestQueue: Array<() => Promise<any>> = [];
  private isOnline: boolean = typeof navigator !== 'undefined' ? navigator.onLine : true;

  constructor() {
    // Listen for online/offline events (only in contexts where window is available)
    // Service workers (background scripts) don't have window, so we skip event listeners there
    if (typeof window !== 'undefined') {
      window.addEventListener('online', () => {
        console.log('[API] Network back online');
        this.isOnline = true;
        this.processQueue();
      });

      window.addEventListener('offline', () => {
        console.log('[API] Network offline');
        this.isOnline = false;
      });
    }
  }

  async init() {
    // Base URL is now hardcoded in config
    this.baseUrl = getApiBaseUrl();
  }

  private async getHeaders(): Promise<HeadersInit> {
    const token = await authStorage.getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  private async sleep(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  private calculateDelay(attempt: number, config: RetryConfig): number {
    const delay = config.initialDelay * Math.pow(config.backoffMultiplier, attempt);
    return Math.min(delay, config.maxDelay);
  }

  private async retryRequest<T>(
    requestFn: () => Promise<T>,
    config: Partial<RetryConfig> = {}
  ): Promise<T> {
    const retryConfig = { ...DEFAULT_RETRY_CONFIG, ...config };
    let lastError: Error | null = null;

    for (let attempt = 0; attempt <= retryConfig.maxRetries; attempt++) {
      try {
        return await requestFn();
      } catch (error: any) {
        lastError = error;

        // Don't retry on client errors (4xx except 429)
        if (error.message.includes('401') ||
            error.message.includes('403') ||
            error.message.includes('404') ||
            error.message.includes('422')) {
          throw error;
        }

        // Retry on network errors, 5xx errors, and 429 (rate limit)
        if (attempt < retryConfig.maxRetries) {
          const delay = this.calculateDelay(attempt, retryConfig);
          console.log(`[API] Retry attempt ${attempt + 1}/${retryConfig.maxRetries} after ${delay}ms`);
          await this.sleep(delay);
        }
      }
    }

    throw lastError || new Error('Request failed after retries');
  }

  async request<T>(
    endpoint: string,
    options: RequestInit = {},
    retry: boolean = true
  ): Promise<T> {
    if (!this.baseUrl) await this.init();

    // Check if offline
    if (!this.isOnline) {
      throw new Error('You are offline. Please check your internet connection.');
    }

    const url = `${this.baseUrl}${endpoint}`;
    const headers = await this.getHeaders();

    const requestFn = async () => {
      // Add timeout protection (30 seconds default)
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 30000);

      try {
        const response = await fetch(url, {
          ...options,
          signal: controller.signal,
          headers: {
            ...headers,
            ...options.headers,
          },
        });

        clearTimeout(timeoutId);

        if (!response.ok) {
          if (response.status === 401) {
            // Unauthorized - clear auth state
            await authStorage.clear();
            throw new Error('Your session has expired. Please sign in again.');
          }

          if (response.status === 403) {
            throw new Error('You do not have permission to access this resource.');
          }

          if (response.status === 404) {
            throw new Error('The requested resource was not found.');
          }

          if (response.status === 422) {
            const errorData = await response.json().catch(() => ({ message: 'Validation failed' }));
            throw new Error(errorData.message || 'Validation failed. Please check your input.');
          }

          if (response.status === 429) {
            throw new Error('Too many requests. Please try again in a few moments.');
          }

          if (response.status >= 500) {
            throw new Error('Server error. Please try again later.');
          }

          const error = await response.json().catch(() => ({
            message: `Request failed: ${response.statusText}`,
          }));
          throw new Error(error.message || `Request failed: ${response.statusText}`);
        }

        return response.json();
      } catch (error: any) {
        clearTimeout(timeoutId);

        if (error.name === 'AbortError') {
          throw new Error('Request timed out. Please check your internet connection and try again.');
        }

        if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
          throw new Error('Unable to connect to the server. Please check your internet connection.');
        }

        throw error;
      }
    };

    if (retry) {
      return this.retryRequest(requestFn);
    } else {
      return requestFn();
    }
  }

  private async processQueue() {
    console.log(`[API] Processing ${this.requestQueue.length} queued requests`);

    while (this.requestQueue.length > 0 && this.isOnline) {
      const requestFn = this.requestQueue.shift();
      if (requestFn) {
        try {
          await requestFn();
        } catch (error) {
          console.error('[API] Queued request failed:', error);
        }
      }
    }
  }

  // Queue a request to be executed when back online
  private queueRequest(requestFn: () => Promise<any>) {
    this.requestQueue.push(requestFn);
    console.log(`[API] Request queued (${this.requestQueue.length} total)`);
  }

  // Auth endpoints
  async login(email: string, password: string): Promise<{ token: string; user: User }> {
    return this.request('/api/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    }, false); // Don't retry login attempts
  }

  async getUser(): Promise<User> {
    return this.request('/api/auth/user');
  }

  async getSubscription(): Promise<Subscription> {
    return this.request('/api/auth/subscription');
  }

  async validateExtensionToken(): Promise<{ valid: boolean; user?: User; token_info?: any }> {
    return this.request('/api/extension/validate-token');
  }

  async logout(): Promise<void> {
    await this.request('/api/auth/logout', { method: 'POST' });
    await authStorage.clear();
  }

  // Campaign endpoints
  async getCampaigns(useCache: boolean = true): Promise<Campaign[]> {
    const cacheKey = 'campaigns';

    // Check cache first
    if (useCache) {
      const cached = cache.get<Campaign[]>(cacheKey);
      if (cached) {
        console.log('[API] Returning cached campaigns');
        return cached;
      }
    }

    // Fetch from API
    const response: any = await this.request('/api/campaigns');
    // Handle nested response format: { success: true, data: { campaigns: [...], pagination: {...} } }
    const campaigns = response.data?.campaigns || response.campaigns || response.data || response || [];

    // Cache for 5 minutes
    cache.set(cacheKey, campaigns, 5 * 60 * 1000);

    return campaigns;
  }

  /**
   * Invalidate campaigns cache
   */
  invalidateCampaignsCache(): void {
    cache.invalidate('campaigns');
  }

  async getCampaign(id: number): Promise<Campaign> {
    const response: any = await this.request(`/api/campaigns/${id}`);
    // API returns { success: true, data: campaign }
    return response.data || response;
  }

  // Lead endpoints
  async submitLead(campaignId: number, lead: Lead): Promise<void> {
    try {
      return await this.request(`/api/campaigns/${campaignId}/leads`, {
        method: 'POST',
        body: JSON.stringify(lead),
      });
    } catch (error: any) {
      // If offline, queue the request
      if (!this.isOnline) {
        this.queueRequest(async () => {
          await this.submitLead(campaignId, lead);
        });
        throw new Error('Lead queued for submission when back online');
      }
      throw error;
    }
  }

  async submitLeadsBulk(campaignId: number, leads: Lead[]): Promise<void> {
    try {
      return await this.request(`/api/campaigns/${campaignId}/leads/bulk`, {
        method: 'POST',
        body: JSON.stringify({ leads }),
      });
    } catch (error: any) {
      // If offline, queue the request
      if (!this.isOnline) {
        this.queueRequest(async () => {
          await this.submitLeadsBulk(campaignId, leads);
        });
        throw new Error('Leads queued for submission when back online');
      }
      throw error;
    }
  }

  // Settings endpoints
  async getSettings(): Promise<{ logo?: string; name?: string; [key: string]: any }> {
    try {
      return this.request('/api/settings', {}, false); // Don't retry settings fetch
    } catch (error) {
      console.error('Failed to fetch settings:', error);
      return {};
    }
  }

  // Plan validation
  async validatePlan(): Promise<{
    valid: boolean;
    subscription: Subscription;
    limits: {
      campaigns: { used: number; limit: number };
      syncs: { used: number; limit: number };
    };
  }> {
    return this.request('/api/auth/validate-plan');
  }

  // Stats endpoint
  async getStats(activityPage: number = 1, activityPerPage: number = 5, useCache: boolean = true): Promise<{
    totalLeads: number;
    leadsByPlatform: Record<string, number>;
    activeCampaigns: number;
    recentActivity: {
      data: Array<{
        id: number;
        type: string;
        message: string;
        timestamp: string;
      }>;
      current_page: number;
      per_page: number;
      total: number;
      last_page: number;
    };
  }> {
    const cacheKey = `stats_activity_${activityPage}_${activityPerPage}`;

    // Check cache first (only cache first page)
    if (useCache && activityPage === 1) {
      const cached = cache.get(cacheKey);
      if (cached) {
        console.log('[API] Returning cached stats');
        return cached;
      }
    }

    // Build URL with query parameters
    const params = new URLSearchParams();
    params.append('activity_page', activityPage.toString());
    params.append('activity_per_page', activityPerPage.toString());

    // Fetch from API
    const stats = await this.request(`/api/stats?${params.toString()}`);

    // Cache for 2 minutes (only first page)
    if (activityPage === 1) {
      cache.set(cacheKey, stats, 2 * 60 * 1000);
    }

    return stats;
  }

  /**
   * Invalidate stats cache
   */
  invalidateStatsCache(): void {
    cache.invalidate('stats');
  }

  // Leads endpoint
  async getLeads(
    perPage: number = 20,
    page: number = 1,
    filters: {
      search?: string;
      platform?: string;
      status?: string;
    } = {},
    useCache: boolean = true
  ): Promise<{
    leads: Lead[];
    pagination: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
      from: number;
      to: number;
    };
  }> {
    // Build query parameters
    const params = new URLSearchParams();
    params.append('per_page', perPage.toString());
    params.append('page', page.toString());

    if (filters.search) params.append('search', filters.search);
    if (filters.platform && filters.platform !== 'all') params.append('platform', filters.platform);
    if (filters.status && filters.status !== 'all') params.append('status', filters.status);

    const cacheKey = `leads_${params.toString()}`;

    // Check cache first (only cache first page without filters)
    if (useCache && page === 1 && !filters.search && filters.platform === 'all' && filters.status === 'all') {
      const cached = cache.get<any>(cacheKey);
      if (cached) {
        console.log('[API] Returning cached leads');
        return cached;
      }
    }

    // Fetch from API
    const response: any = await this.request(`/api/leads?${params.toString()}`);
    // API returns { success, data: { leads: [...], pagination: {...} } }
    const leads = response.data?.leads || response.data || response.leads || [];
    const pagination = response.data?.pagination || {
      current_page: page,
      last_page: 1,
      per_page: perPage,
      total: Array.isArray(leads) ? leads.length : 0,
      from: 1,
      to: Array.isArray(leads) ? leads.length : 0,
    };

    const result = {
      leads: Array.isArray(leads) ? leads : [],
      pagination,
    };

    // Cache for 3 minutes (only first page without filters)
    if (page === 1 && !filters.search && filters.platform === 'all' && filters.status === 'all') {
      cache.set(cacheKey, result, 3 * 60 * 1000);
    }

    return result;
  }

  /**
   * Invalidate leads cache
   */
  invalidateLeadsCache(): void {
    cache.invalidatePattern('leads_');
  }

  // Extension Helper Endpoints

  /**
   * Get campaign context (campaign data + search terms + schemas)
   * This is the main endpoint for the extension to get all data needed for syncing
   */
  async getCampaignContext(campaignId: number): Promise<{
    success: boolean;
    campaign: any;
    search_terms: string[];
    schemas: any;
  }> {
    return this.request(`/api/extension/campaigns/${campaignId}/context`);
  }

  /**
   * Generate search terms for a campaign
   * Now accepts only campaign_id - server handles everything
   */
  async generateSearchTerms(campaignId: number): Promise<{
    success: boolean;
    keywords: string[];
  }> {
    return this.request('/api/extension/generate-search-terms', {
      method: 'POST',
      body: JSON.stringify({ campaign_id: campaignId }),
    });
  }

  /**
   * Validate lead relevance using AI
   */
  async validateLead(offering: string, title: string, description: string): Promise<{
    success: boolean;
    is_relevant: boolean;
    confidence: number;
    reasoning: string;
  }> {
    return this.request('/api/extension/validate-lead', {
      method: 'POST',
      body: JSON.stringify({ offering, title, description }),
    });
  }

  /**
   * Record manual sync start (for quota tracking)
   * No retries - if this fails, we should stop immediately
   */
  async recordSyncStart(campaignId: number): Promise<{
    success: boolean;
    message: string;
  }> {
    return this.request('/api/extension/record-sync-start', {
      method: 'POST',
      body: JSON.stringify({ campaign_id: campaignId }),
    }, false); // Disable retries - fail fast
  }

  // Get queued request count (for UI display)
  getQueuedRequestCount(): number {
    return this.requestQueue.length;
  }

  // Check if online
  checkOnlineStatus(): boolean {
    return this.isOnline;
  }

  /**
   * Record a message sent to a lead
   * Used by messaging services to track automated messages
   */
  async recordLeadMessage(data: {
    leadId: number;
    platform: 'linkedin' | 'reddit' | 'twitter' | 'facebook';
    channel: 'direct_message' | 'comment' | 'post_reply';
    messageText: string;
    sentVia: 'extension_automation' | 'manual';
    platformMessageId?: string;
    recipientUrl?: string;
    metadata?: Record<string, any>;
  }): Promise<{
    success: boolean;
    message_id: number;
    message: string;
  }> {
    return this.request(`/api/extension/leads/${data.leadId}/messages`, {
      method: 'POST',
      body: JSON.stringify({
        platform: data.platform,
        channel: data.channel,
        message_text: data.messageText,
        sent_via: data.sentVia,
        platform_message_id: data.platformMessageId,
        recipient_url: data.recipientUrl,
        metadata: data.metadata,
      }),
    }, true); // Enable retries - message recording is important
  }
}

export const api = new ApiClient();
