import { storage } from 'wxt/storage';
import type { AuthState, Campaign, DevModeState, SchemaElement, Platform, PageType } from '../types';

export const storageKeys = {
  AUTH_STATE: 'auth:state',
  CAMPAIGNS: 'campaigns',
  SELECTED_CAMPAIGN: 'selected:campaign',
  DEV_MODE_ENABLED: 'devmode:enabled',
  DEV_MODE_SCHEMA: 'devmode:schema',
  DEV_MODE_PANEL_POSITION: 'devmode:panel_position',
  DEV_MODE_CURRENT_PLATFORM: 'devmode:current_platform',
  DEV_MODE_CURRENT_PAGE_TYPE: 'devmode:current_page_type',
  DEV_MODE_TEST_SECTION_EXPANDED: 'devmode:test_section_expanded',
  PLATFORM_ENGINE_TEST: 'platform_engine:test',
  MESSAGING_TEST: 'messaging:test',
  SIDEBAR_OPEN: 'sidebar:open',
} as const;

export const authStorage = {
  async get(): Promise<AuthState | null> {
    return await storage.getItem<AuthState>(`local:${storageKeys.AUTH_STATE}`);
  },

  async set(state: AuthState): Promise<void> {
    await storage.setItem(`local:${storageKeys.AUTH_STATE}`, state);
  },

  async clear(): Promise<void> {
    await storage.removeItem(`local:${storageKeys.AUTH_STATE}`);
  },

  async getToken(): Promise<string | null> {
    const state = await this.get();
    return state?.token ?? null;
  },

  async isAuthenticated(): Promise<boolean> {
    const state = await this.get();
    return state?.isAuthenticated ?? false;
  },
};

export const campaignStorage = {
  async get(): Promise<Campaign[]> {
    return (await storage.getItem<Campaign[]>(`local:${storageKeys.CAMPAIGNS}`)) ?? [];
  },

  async set(campaigns: Campaign[]): Promise<void> {
    await storage.setItem(`local:${storageKeys.CAMPAIGNS}`, campaigns);
  },

  async add(campaign: Campaign): Promise<void> {
    const campaigns = await this.get();
    campaigns.push(campaign);
    await this.set(campaigns);
  },

  async clear(): Promise<void> {
    await storage.removeItem(`local:${storageKeys.CAMPAIGNS}`);
  },

  async getSelected(): Promise<number | null> {
    return await storage.getItem<number>(`local:${storageKeys.SELECTED_CAMPAIGN}`);
  },

  async setSelected(campaignId: number): Promise<void> {
    await storage.setItem(`local:${storageKeys.SELECTED_CAMPAIGN}`, campaignId);
  },

  async clearSelected(): Promise<void> {
    await storage.removeItem(`local:${storageKeys.SELECTED_CAMPAIGN}`);
  },

  async getSelectedCampaign(): Promise<Campaign | null> {
    const selectedId = await this.getSelected();
    if (!selectedId) return null;

    const campaigns = await this.get();
    return campaigns.find(c => c.id === selectedId) ?? null;
  },
};

export const devModeStorage = {
  async isEnabled(): Promise<boolean> {
    return (await storage.getItem<boolean>(`local:${storageKeys.DEV_MODE_ENABLED}`)) ?? false;
  },

  async setEnabled(enabled: boolean): Promise<void> {
    await storage.setItem(`local:${storageKeys.DEV_MODE_ENABLED}`, enabled);
  },

  async getSchemaElements(): Promise<SchemaElement[]> {
    return (await storage.getItem<SchemaElement[]>(`local:${storageKeys.DEV_MODE_SCHEMA}`)) ?? [];
  },

  async setSchemaElements(elements: SchemaElement[]): Promise<void> {
    await storage.setItem(`local:${storageKeys.DEV_MODE_SCHEMA}`, elements);
  },

  async addSchemaElement(element: SchemaElement): Promise<void> {
    const elements = await this.getSchemaElements();
    elements.push(element);
    await this.setSchemaElements(elements);
  },

  async removeSchemaElement(index: number): Promise<void> {
    const elements = await this.getSchemaElements();
    elements.splice(index, 1);
    await this.setSchemaElements(elements);
  },

  async clearSchema(): Promise<void> {
    await storage.removeItem(`local:${storageKeys.DEV_MODE_SCHEMA}`);
  },

  async exportSchema(platform: Platform, pageType: PageType): Promise<string> {
    const elements = await this.getSchemaElements();
    const schema = {
      platform,
      page_type: pageType,
      version: '1.0.0',
      elements,
    };
    return JSON.stringify(schema, null, 2);
  },

  async exportCompleteSchema(platform: Platform): Promise<string> {
    const allElements = await this.getSchemaElements();

    // Group elements by page type
    const byPageType: Record<string, SchemaElement[]> = {};

    allElements.forEach(element => {
      const pageType = element.page_type || 'general';
      if (!byPageType[pageType]) {
        byPageType[pageType] = [];
      }
      byPageType[pageType].push(element);
    });

    // Create organized schema with comments
    const PAGE_TYPE_DESCRIPTIONS: Record<string, string> = {
      general: 'Elements available on ALL pages (navigation, search, common UI)',
      search_list: 'Elements for post/result listing pages',
      post_page: 'Elements for individual post/detail pages',
      profile: 'Elements for user profile pages',
      group: 'Elements for group pages',
      person_feed: 'Elements for person feed pages',
      feed_page: 'Elements for general feed pages',
    };

    const schema = {
      platform,
      version: '2.0.0',
      schemas: Object.entries(byPageType)
        .sort(([a], [b]) => {
          // Sort: general first, then alphabetically
          if (a === 'general') return -1;
          if (b === 'general') return 1;
          return a.localeCompare(b);
        })
        .map(([pageType, elements]) => ({
          page_type: pageType,
          description: PAGE_TYPE_DESCRIPTIONS[pageType] || pageType,
          element_count: elements.length,
          elements: elements.map(el => ({
            ...(el.name && { name: el.name }),
            element_type: el.element_type,
            css_selector: el.css_selector,
            xpath_selector: el.xpath_selector,
            is_required: el.is_required,
            multiple: el.multiple,
            ...(el.parent_element && { parent_element: el.parent_element }),
            ...(el.description && { description: el.description }),
            ...(el.is_wrapper && { is_wrapper: el.is_wrapper }),
            ...(el.relative_to_wrapper && { relative_to_wrapper: el.relative_to_wrapper }),
          })),
        })),
    };

    return JSON.stringify(schema, null, 2);
  },

  async getPanelPosition(): Promise<{ x: number; y: number } | null> {
    return await storage.getItem<{ x: number; y: number }>(`local:${storageKeys.DEV_MODE_PANEL_POSITION}`);
  },

  async setPanelPosition(position: { x: number; y: number }): Promise<void> {
    await storage.setItem(`local:${storageKeys.DEV_MODE_PANEL_POSITION}`, position);
  },

  async getCurrentPlatform(): Promise<Platform | null> {
    return await storage.getItem<Platform>(`local:${storageKeys.DEV_MODE_CURRENT_PLATFORM}`);
  },

  async setCurrentPlatform(platform: Platform | null): Promise<void> {
    if (platform) {
      await storage.setItem(`local:${storageKeys.DEV_MODE_CURRENT_PLATFORM}`, platform);
    }
  },

  async getCurrentPageType(): Promise<PageType | null> {
    return await storage.getItem<PageType>(`local:${storageKeys.DEV_MODE_CURRENT_PAGE_TYPE}`);
  },

  async setCurrentPageType(pageType: PageType): Promise<void> {
    await storage.setItem(`local:${storageKeys.DEV_MODE_CURRENT_PAGE_TYPE}`, pageType);
  },

  async getTestSectionExpanded(): Promise<boolean> {
    return (await storage.getItem<boolean>(`local:${storageKeys.DEV_MODE_TEST_SECTION_EXPANDED}`)) ?? false;
  },

  async setTestSectionExpanded(expanded: boolean): Promise<void> {
    await storage.setItem(`local:${storageKeys.DEV_MODE_TEST_SECTION_EXPANDED}`, expanded);
  },
};

export interface PlatformEngineTestState {
  active: boolean;
  platform: string;
  mode: 'openAndMessage' | 'sendMessage';
  profileUrls: string[];
  currentIndex: number;
  testMessage: string;
  results: any[];
  startedAt: number;
  tabId?: number;
}

export const platformEngineTestStorage = {
  async getTestState(): Promise<PlatformEngineTestState | null> {
    return await storage.getItem<PlatformEngineTestState>(`local:${storageKeys.PLATFORM_ENGINE_TEST}`);
  },

  async setTestState(state: PlatformEngineTestState): Promise<void> {
    await storage.setItem(`local:${storageKeys.PLATFORM_ENGINE_TEST}`, state);
  },

  async clearTestState(): Promise<void> {
    await storage.removeItem(`local:${storageKeys.PLATFORM_ENGINE_TEST}`);
  },

  async isTestActive(): Promise<boolean> {
    const state = await this.getTestState();
    return state?.active ?? false;
  },

  async updateCurrentIndex(index: number): Promise<void> {
    const state = await this.getTestState();
    if (state) {
      state.currentIndex = index;
      await this.setTestState(state);
    }
  },

  async addResult(result: any): Promise<void> {
    const state = await this.getTestState();
    if (state) {
      state.results.push(result);
      await this.setTestState(state);
    }
  },
};

export interface MessagingTestState {
  active: boolean;
  profileUrl: string;
  testMessage: string;
  openDevMode: boolean;
  testId: string;
  timestamp: number;
  platform: string;
}

export const messagingTestStorage = {
  async getTestState(): Promise<MessagingTestState | null> {
    return await storage.getItem<MessagingTestState>(`local:${storageKeys.MESSAGING_TEST}`);
  },

  async setTestState(state: MessagingTestState): Promise<void> {
    await storage.setItem(`local:${storageKeys.MESSAGING_TEST}`, state);
  },

  async clearTestState(): Promise<void> {
    await storage.removeItem(`local:${storageKeys.MESSAGING_TEST}`);
  },

  async isTestActive(): Promise<boolean> {
    const state = await this.getTestState();
    return state?.active ?? false;
  },
};

export const sidebarStorage = {
  async isOpen(): Promise<boolean> {
    return (await storage.getItem<boolean>(`local:${storageKeys.SIDEBAR_OPEN}`)) ?? false;
  },

  async setOpen(isOpen: boolean): Promise<void> {
    await storage.setItem(`local:${storageKeys.SIDEBAR_OPEN}`, isOpen);
  },
};
