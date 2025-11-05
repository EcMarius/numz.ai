/**
 * Base Platform Engine
 *
 * Abstract base class for platform-specific automation engines.
 * Each platform (LinkedIn, Reddit, Twitter, etc.) extends this class.
 */

import { api } from '../api';
import type { MessageResult } from '../services/messaging/types';

export interface PlatformSchema {
  platform: string;
  page_type: string;
  element_type: string;
  css_selector: string;
  xpath_selector?: string;
  is_required: boolean;
  fallback_value?: string;
  parent_element?: string;
  multiple: boolean;
  is_wrapper: boolean;
  version: string;
  is_active: boolean;
  description?: string;
  notes?: string;
  order: number;
}

export interface SchemaResponse {
  success: boolean;
  schema: Record<string, PlatformSchema[]>;
  messaging?: {
    input_selectors: string[];
    send_button_selectors: string[];
    supports_enter_to_send: boolean;
  };
}

export abstract class BasePlatformEngine {
  protected platform: string;
  protected schemas: Map<string, PlatformSchema[]> = new Map();
  protected isSchemaLoaded: boolean = false;

  constructor(platform: string) {
    this.platform = platform;
  }

  /**
   * Load schemas for this platform from API
   */
  async loadSchemas(pageTypes: string[] = ['messaging', 'profile']): Promise<void> {
    console.log(`[${this.platform}Engine] 🔄 loadSchemas() called for page types:`, pageTypes);
    console.log(`[${this.platform}Engine] isSchemaLoaded before:`, this.isSchemaLoaded);

    for (const pageType of pageTypes) {
      try {
        const url = `/api/extension/schemas/${this.platform}/${pageType}`;
        console.log(`[${this.platform}Engine] 🌐 Making API request to:`, url);

        const response = await api.request<any>(url);

        console.log(`[${this.platform}Engine] 📡 API response for ${pageType}:`, response);

        if (response.success && response.schema) {
          // Convert schema object to array
          const schemaArray: PlatformSchema[] = Object.values(response.schema);
          console.log(`[${this.platform}Engine] ✅ Got ${schemaArray.length} selectors for ${pageType}`);
          this.schemas.set(pageType, schemaArray);
        } else {
          console.warn(`[${this.platform}Engine] ⚠️ Invalid response for ${pageType}:`, response);
        }
      } catch (error) {
        console.error(`[${this.platform}Engine] ❌ Failed to load schema for ${pageType}:`, error);
      }
    }

    this.isSchemaLoaded = true;
    console.log(`[${this.platform}Engine] ✅ Schema loading complete. Loaded page types:`, Array.from(this.schemas.keys()));
    console.log(`[${this.platform}Engine] Total schemas in memory:`, this.schemas);
  }

  /**
   * Find element using schema selectors with fallbacks
   */
  protected findElement(
    elementType: string,
    pageType: string,
    options: { requireText?: string; multiple?: boolean } = {}
  ): HTMLElement | HTMLElement[] | null {
    const schemaElements = this.schemas.get(pageType);
    if (!schemaElements) {
      console.error(`[${this.platform}Engine] No schema loaded for page type: ${pageType}`);
      return null;
    }

    // Filter by element_type and sort by order
    const selectors = schemaElements
      .filter(s => s.element_type === elementType && s.is_active)
      .sort((a, b) => a.order - b.order);

    if (selectors.length === 0) {
      console.error(`[${this.platform}Engine] No selectors found for element_type: ${elementType}`);
      return null;
    }

    // Try each selector in order (primary first, then fallbacks)
    for (const selector of selectors) {
      try {
        let elements: NodeListOf<HTMLElement> | HTMLElement[] = [] as any;

        // Try CSS selector first
        if (selector.css_selector) {
          // Support wildcard attribute selectors: [aria-label="Message *"] -> [aria-label*="Message"]
          let cssSelector = selector.css_selector;

          // Convert wildcard syntax: [attr="value *"] -> [attr*="value"]
          cssSelector = cssSelector.replace(/\[([^\]]+)="([^"]+)\s+\*"\]/g, '[$1*="$2"]');
          cssSelector = cssSelector.replace(/\[([^\]]+)="\*\s+([^"]+)"\]/g, '[$1*="$2"]');

          elements = document.querySelectorAll<HTMLElement>(cssSelector);
        }

        // If CSS failed and XPath is available, try XPath
        if (elements.length === 0 && selector.xpath_selector) {
          console.log(`[${this.platform}Engine] CSS failed, trying XPath:`, selector.xpath_selector);

          const xpathResult = document.evaluate(
            selector.xpath_selector,
            document,
            null,
            XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
            null
          );

          const xpathElements: HTMLElement[] = [];
          for (let i = 0; i < xpathResult.snapshotLength; i++) {
            const node = xpathResult.snapshotItem(i);
            if (node instanceof HTMLElement) {
              xpathElements.push(node);
            }
          }
          elements = xpathElements;
        }

        if (elements.length > 0) {
          // If text matching is required
          if (options.requireText) {
            const matchingElements = Array.from(elements).filter(el =>
              el.textContent?.trim().includes(options.requireText!)
            );

            if (matchingElements.length > 0) {
              console.log(`[${this.platform}Engine] Found element with text "${options.requireText}" using selector:`, selector.css_selector || selector.xpath_selector);
              return options.multiple ? matchingElements : matchingElements[0];
            }
          } else {
            // No text matching required
            console.log(`[${this.platform}Engine] Found element using selector:`, selector.css_selector || selector.xpath_selector);
            return options.multiple ? Array.from(elements) : elements[0];
          }
        }
      } catch (error) {
        console.warn(`[${this.platform}Engine] Selector failed:`, selector.css_selector || selector.xpath_selector, error);
      }
    }

    console.error(`[${this.platform}Engine] Element not found: ${elementType} on page type: ${pageType}`);
    return null;
  }

  /**
   * Wait for element to appear (with timeout)
   */
  protected async waitForElement(
    elementType: string,
    pageType: string,
    options: {
      requireText?: string;
      timeout?: number;
      interval?: number;
    } = {}
  ): Promise<HTMLElement | null> {
    const timeout = options.timeout || 10000; // 10 seconds default
    const interval = options.interval || 500; // Check every 500ms
    const startTime = Date.now();

    while (Date.now() - startTime < timeout) {
      const element = this.findElement(elementType, pageType, options);

      if (element && !Array.isArray(element)) {
        return element;
      }

      // Wait before trying again
      await this.sleep(interval);
    }

    console.error(`[${this.platform}Engine] Timeout waiting for element: ${elementType}`);
    return null;
  }

  /**
   * Sleep utility
   */
  protected sleep(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  /**
   * Navigate to URL and wait for page load
   */
  protected async navigateTo(url: string, waitForLoad: boolean = true): Promise<void> {
    window.location.href = url;

    if (waitForLoad) {
      // Wait for page to start loading
      await this.sleep(1000);

      // Wait for page to be ready
      await new Promise<void>((resolve) => {
        if (document.readyState === 'complete') {
          resolve();
        } else {
          window.addEventListener('load', () => resolve(), { once: true });
        }
      });

      // Additional wait for dynamic content
      await this.sleep(2000);
    }
  }

  /**
   * Click element with optional delay
   */
  protected async clickElement(element: HTMLElement, delayAfter: number = 500): Promise<void> {
    element.click();

    if (delayAfter > 0) {
      await this.sleep(delayAfter);
    }
  }

  /**
   * Abstract methods to be implemented by platform-specific engines
   */
  abstract openProfileAndMessage(profileUrl: string): Promise<MessageResult>;
  abstract sendMessage(message: string, config?: any): Promise<MessageResult>;
}
