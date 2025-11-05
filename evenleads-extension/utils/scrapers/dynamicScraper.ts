/**
 * Dynamic Scraper
 * Uses platform schemas to extract content instead of hardcoded selectors
 */

import type { Lead, Platform, PageType } from '../../types';

interface SchemaElement {
  element_type: string;
  css_selector: string;
  xpath_selector: string;
  is_required: boolean;
  fallback_value?: string;
  parent_element?: string;
  multiple: boolean;
}

interface PlatformSchema {
  [pageType: string]: {
    [elementType: string]: SchemaElement;
  };
}

export class DynamicScraper {
  private platform: Platform;
  private schema: PlatformSchema | null = null;

  constructor(platform: Platform, schema: PlatformSchema | null = null) {
    this.platform = platform;
    this.schema = schema;
  }

  /**
   * Extract elements using schema with proper wrapper-relative support
   */
  private extractElement(elementType: string, pageType: PageType, container?: Element): string | null {
    if (!this.schema || !this.schema[pageType]) {
      return null;
    }

    const schemaElement = this.schema[pageType][elementType];
    if (!schemaElement) {
      return null;
    }

    const root = container || document;
    let element: Element | null = null;

    // Try CSS selector first
    if (schemaElement.css_selector) {
      try {
        // If selector is relative (starts with space or >), it needs a container
        const selector = schemaElement.css_selector.trim();
        if (container && (selector.startsWith(' ') || selector.startsWith('>'))) {
          // Relative selector - search within container
          element = container.querySelector(selector);
        } else {
          // Absolute or container-based selector
          element = root.querySelector(selector);
        }
      } catch (e) {
        console.warn(`Failed to use CSS selector for ${elementType}:`, e);
      }
    }

    // Fallback to XPath if CSS failed
    if (!element && schemaElement.xpath_selector) {
      try {
        const xpath = schemaElement.xpath_selector.trim();
        // If XPath is relative (starts with .), use container context
        const contextNode = (container && xpath.startsWith('.')) ? container : document;

        const result = document.evaluate(
          xpath,
          contextNode as Node,
          null,
          XPathResult.FIRST_ORDERED_NODE_TYPE,
          null
        );
        element = result.singleNodeValue as Element;
      } catch (e) {
        console.warn(`Failed to use XPath selector for ${elementType}:`, e);
      }
    }

    // Extract content
    if (element) {
      // For URL elements, try href or src attribute first
      if (elementType.includes('_url') || elementType.includes('_avatar')) {
        const href = element.getAttribute('href');
        const src = element.getAttribute('src');
        const dataUrl = element.getAttribute('data-url');
        return href || src || dataUrl || element.textContent?.trim() || null;
      }

      // For count elements, extract number
      if (elementType.includes('_count')) {
        const text = element.textContent?.trim() || '';
        const match = text.match(/\d+/);
        return match ? match[0] : '0';
      }

      // Default: extract text content
      return element.textContent?.trim() || null;
    }

    // Use fallback value if element not found
    return schemaElement.fallback_value || null;
  }

  /**
   * Extract multiple elements (like posts in a feed)
   */
  private extractMultipleElements(elementType: string, pageType: PageType): Element[] {
    if (!this.schema || !this.schema[pageType]) {
      return [];
    }

    const schemaElement = this.schema[pageType][elementType];
    if (!schemaElement || !schemaElement.multiple) {
      return [];
    }

    let elements: Element[] = [];

    // Try CSS selector first
    if (schemaElement.css_selector) {
      try {
        elements = Array.from(document.querySelectorAll(schemaElement.css_selector));
      } catch (e) {
        console.warn(`Failed to use CSS selector for ${elementType}:`, e);
      }
    }

    // Fallback to XPath if CSS failed
    if (elements.length === 0 && schemaElement.xpath_selector) {
      try {
        const result = document.evaluate(
          schemaElement.xpath_selector,
          document,
          null,
          XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
          null
        );

        for (let i = 0; i < result.snapshotLength; i++) {
          const node = result.snapshotItem(i);
          if (node && node.nodeType === Node.ELEMENT_NODE) {
            elements.push(node as Element);
          }
        }
      } catch (e) {
        console.warn(`Failed to use XPath selector for ${elementType}:`, e);
      }
    }

    return elements;
  }

  /**
   * Scrape posts from a feed/search page
   */
  async scrapePosts(keywords: string[]): Promise<Lead[]> {
    if (!this.schema) {
      console.warn('No schema available for dynamic scraping');
      return [];
    }

    const leads: Lead[] = [];
    const pageType: PageType = 'search_list'; // Default to search_list scraping

    // Get all post wrappers
    const postElements = this.extractMultipleElements('post_wrapper', pageType);

    console.log(`[DynamicScraper] Found ${postElements.length} posts using schema`);

    for (const postElement of postElements) {
      try {
        // Extract all post data using schema
        const title = this.extractElement('post_title', pageType, postElement);
        const description = this.extractElement('post_description', pageType, postElement);
        const content = this.extractElement('post_content', pageType, postElement);
        const url = this.extractElement('post_url', pageType, postElement);
        const authorName = this.extractElement('author_name', pageType, postElement);
        const authorUrl = this.extractElement('author_url', pageType, postElement);

        // Combine description and content
        const fullDescription = [description, content].filter(Boolean).join('\n\n');

        // Get platform_id from URL or generate one
        const platformId = this.extractPlatformId(url || window.location.href);

        // Check if matches any keywords
        const matchedKeywords = this.findMatchingKeywords(
          title || '',
          fullDescription,
          keywords
        );

        if (matchedKeywords.length === 0) {
          continue; // Skip if no keywords match
        }

        // Create lead
        const lead: Lead = {
          platform: this.platform,
          platform_id: platformId,
          title: title || 'Untitled',
          description: fullDescription || '',
          url: url || window.location.href,
          author: authorName || 'Unknown',
          matched_keywords: matchedKeywords,
          confidence_score: 0.8, // Will be validated by AI later
        };

        leads.push(lead);
      } catch (error) {
        console.error('[DynamicScraper] Error extracting post:', error);
      }
    }

    return leads;
  }

  /**
   * Scrape a single post page
   */
  async scrapePost(): Promise<Lead | null> {
    if (!this.schema) {
      console.warn('No schema available for dynamic scraping');
      return null;
    }

    const pageType: PageType = 'post_page';

    try {
      const title = this.extractElement('post_title', pageType);
      const description = this.extractElement('post_description', pageType);
      const content = this.extractElement('post_content', pageType);
      const authorName = this.extractElement('author_name', pageType);
      const authorUrl = this.extractElement('author_url', pageType);

      const fullDescription = [description, content].filter(Boolean).join('\n\n');
      const platformId = this.extractPlatformId(window.location.href);

      return {
        platform: this.platform,
        platform_id: platformId,
        title: title || 'Untitled',
        description: fullDescription || '',
        url: window.location.href,
        author: authorName || 'Unknown',
        matched_keywords: [],
        confidence_score: 0.8,
      };
    } catch (error) {
      console.error('[DynamicScraper] Error scraping post:', error);
      return null;
    }
  }

  /**
   * Extract platform-specific ID from URL
   */
  private extractPlatformId(url: string): string {
    const match = url.match(/[\/\-](\d+|[\w\-]{8,})/);
    return match ? match[1] : `scraped_${Date.now()}`;
  }

  /**
   * Find keywords that match in title or description
   */
  private findMatchingKeywords(title: string, description: string, keywords: string[]): string[] {
    const text = `${title} ${description}`.toLowerCase();
    return keywords.filter(keyword =>
      text.includes(keyword.toLowerCase())
    );
  }

  /**
   * Check if schema is available for this platform
   */
  hasSchema(): boolean {
    return this.schema !== null && Object.keys(this.schema).length > 0;
  }

  /**
   * Set schema for this scraper
   */
  setSchema(schema: PlatformSchema | null): void {
    this.schema = schema;
  }
}

/**
 * Create a dynamic scraper instance for a platform
 */
export function createDynamicScraper(platform: Platform, schema: any | null): DynamicScraper {
  return new DynamicScraper(platform, schema);
}
