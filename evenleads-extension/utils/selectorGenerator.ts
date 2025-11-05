/**
 * Selector Generator Utility
 * Generates CSS and XPath selectors for DOM elements
 * Prioritizes stable attributes over classes to avoid breaking when CSS changes
 */

export interface SelectorResult {
  cssSelector: string;
  xpathSelector: string;
  confidence: 'high' | 'medium' | 'low';
  warnings: string[];
}

/**
 * Generate both CSS and XPath selectors for an element
 */
export function generateSelectors(element: Element): SelectorResult {
  const warnings: string[] = [];
  let confidence: 'high' | 'medium' | 'low' = 'high';

  const cssSelector = generateCSSSelector(element, warnings);
  const xpathSelector = generateXPath(element, warnings);

  // Determine confidence based on selector quality
  if (cssSelector.includes(':nth-child') || xpathSelector.includes('[position()')) {
    confidence = 'low';
    warnings.push('Selector uses positional selectors which may break if page structure changes');
  } else if (!hasStableAttribute(element)) {
    confidence = 'medium';
    warnings.push('Element lacks stable attributes (data-*, id). Selector may be fragile.');
  }

  return {
    cssSelector,
    xpathSelector,
    confidence,
    warnings,
  };
}

/**
 * Generate a CSS selector for an element
 * Avoids class names when possible, prefers data-* attributes and IDs
 */
export function generateCSSSelector(element: Element, warnings: string[] = []): string {
  // Strategy priority:
  // 1. ID (if unique and not randomly generated)
  // 2. data-* attributes
  // 3. Specific attributes (role, type, name, aria-label, etc.)
  // 4. Tag + attributes combination
  // 5. Tag + nth-child (last resort)

  // Check for stable ID
  if (element.id && !isRandomId(element.id)) {
    if (isUniqueSelector(`#${CSS.escape(element.id)}`)) {
      return `#${CSS.escape(element.id)}`;
    }
  }

  // Check for data-* attributes
  const dataAttr = getStableDataAttribute(element);
  if (dataAttr) {
    const selector = `${element.tagName.toLowerCase()}[${dataAttr.name}="${CSS.escape(dataAttr.value)}"]`;
    if (isUniqueSelector(selector)) {
      return selector;
    }
  }

  // Check for other stable attributes
  const stableAttr = getStableAttribute(element);
  if (stableAttr) {
    const selector = `${element.tagName.toLowerCase()}[${stableAttr.name}="${CSS.escape(stableAttr.value)}"]`;
    if (isUniqueSelector(selector)) {
      return selector;
    }
  }

  // Build selector path from root to element
  const path: string[] = [];
  let current: Element | null = element;

  while (current && current.nodeType === Node.ELEMENT_NODE) {
    let selector = current.tagName.toLowerCase();

    // Try to make each step unique with attributes
    const attr = getStableDataAttribute(current) || getStableAttribute(current);
    if (attr) {
      selector += `[${attr.name}="${CSS.escape(attr.value)}"]`;
    } else {
      // Use nth-child as last resort
      const parent = current.parentElement;
      if (parent) {
        const siblings = Array.from(parent.children).filter(
          child => child.tagName === current!.tagName
        );
        if (siblings.length > 1) {
          const index = siblings.indexOf(current) + 1;
          selector += `:nth-child(${index})`;
          warnings.push(`Using :nth-child(${index}) for ${selector} - may be fragile`);
        }
      }
    }

    path.unshift(selector);
    current = current.parentElement;

    // Stop at a unique parent to keep selector short
    if (path.length > 1 && isUniqueSelector(path.join(' > '))) {
      break;
    }

    // Limit depth to avoid overly long selectors
    if (path.length >= 5) {
      break;
    }
  }

  return path.join(' > ');
}

/**
 * Generate an XPath expression for an element
 */
export function generateXPath(element: Element, warnings: string[] = []): string {
  // Strategy:
  // 1. Use ID if available and stable
  // 2. Use data-* or stable attributes
  // 3. Build path with predicates
  // 4. Use position() as last resort

  // Check for stable ID
  if (element.id && !isRandomId(element.id)) {
    return `//*[@id="${element.id}"]`;
  }

  // Check for data-* attributes
  const dataAttr = getStableDataAttribute(element);
  if (dataAttr) {
    const xpath = `//${element.tagName.toLowerCase()}[@${dataAttr.name}="${dataAttr.value}"]`;
    if (isUniqueXPath(xpath)) {
      return xpath;
    }
  }

  // Check for other stable attributes
  const stableAttr = getStableAttribute(element);
  if (stableAttr) {
    const xpath = `//${element.tagName.toLowerCase()}[@${stableAttr.name}="${stableAttr.value}"]`;
    if (isUniqueXPath(xpath)) {
      return xpath;
    }
  }

  // Build FULL path from root to element (more stable than short paths)
  const path: string[] = [];
  let current: Element | null = element;
  let depth = 0;
  const MAX_DEPTH = 15; // Increased from 5 for fuller paths

  while (current && current.nodeType === Node.ELEMENT_NODE && depth < MAX_DEPTH) {
    const tagName = current.tagName.toLowerCase();

    // Skip html and body tags (not useful in XPath)
    if (tagName === 'html' || tagName === 'body') {
      current = current.parentElement;
      depth++;
      continue;
    }

    // Try to use attributes first
    const attr = getStableDataAttribute(current) || getStableAttribute(current);
    if (attr) {
      path.unshift(`${tagName}[@${attr.name}="${attr.value}"]`);
    } else {
      // Use position as last resort
      const parent = current.parentElement;
      if (parent) {
        const siblings = Array.from(parent.children).filter(
          child => child.tagName === current!.tagName
        );
        if (siblings.length > 1) {
          const index = siblings.indexOf(current) + 1;
          path.unshift(`${tagName}[${index}]`);
          warnings.push(`Using position [${index}] for ${tagName} - may be fragile`);
        } else {
          path.unshift(tagName);
        }
      } else {
        path.unshift(tagName);
      }
    }

    current = current.parentElement;
    depth++;

    // DON'T stop early - build full path for maximum stability
    // Only stop if we have a very unique combination
    if (path.length >= 3 && isUniqueXPath('//' + path.join('/'))) {
      // Found a unique path with good depth - we can stop here
      if (depth >= 3) { // Must be at least 3 levels deep
        return '//' + path.join('/');
      }
    }
  }

  return '//' + path.join('/');
}

/**
 * Check if an ID looks randomly generated
 */
function isRandomId(id: string): boolean {
  // Common patterns for random IDs
  const randomPatterns = [
    /^[a-f0-9]{8,}$/i,        // Long hex strings
    /^[\w-]+-[\w-]+-[\w-]+$/, // UUID-like
    /^\d{10,}$/,               // Long number sequences
    /^react-/,                 // React auto-generated
    /^radix-/,                 // Radix UI auto-generated
    /^:r[0-9]+:$/,             // React 18+ auto-generated IDs (e.g., :r1:, :r2:, :r12:)
    /^mui-[0-9]+$/,            // Material-UI auto-generated
    /^headlessui-/,            // HeadlessUI auto-generated
    /^[a-z0-9]{20,}$/i,        // Very long random strings
  ];

  return randomPatterns.some(pattern => pattern.test(id));
}

/**
 * Get the most stable data-* attribute from an element
 */
function getStableDataAttribute(element: Element): { name: string; value: string } | null {
  // Priority order for data attributes (most stable first)
  const priorityDataAttrs = [
    'data-testid',
    'data-test',
    'data-id',
    'data-key',
    'data-urn',
    'data-item-id',
    'data-post-id',
    'data-user-id',
    'data-element-handle',
  ];

  // First check priority attributes
  for (const attr of priorityDataAttrs) {
    const value = element.getAttribute(attr);
    if (value) {
      return { name: attr, value };
    }
  }

  // Then check all other data-* attributes
  for (const attr of element.attributes) {
    if (attr.name.startsWith('data-') && !isRandomValue(attr.value)) {
      return { name: attr.name, value: attr.value };
    }
  }

  return null;
}

/**
 * Get a stable non-data attribute from an element
 */
function getStableAttribute(element: Element): { name: string; value: string } | null {
  // Priority order for attributes (placeholder moved to top for inputs)
  const tagName = element.tagName.toLowerCase();
  const isInput = tagName === 'input' || tagName === 'textarea' || tagName === 'select';

  const priorityAttrs = isInput
    ? [
        'placeholder',  // Highest priority for input elements
        'name',
        'type',
        'aria-label',
        'role',
        'aria-labelledby',
        'title',
        'alt',
        'for',
      ]
    : [
        'name',
        'type',
        'role',
        'aria-label',
        'aria-labelledby',
        'placeholder',
        'title',
        'alt',
        'for',
        'href',
      ];

  for (const attrName of priorityAttrs) {
    const value = element.getAttribute(attrName);
    if (value && !isRandomValue(value)) {
      return { name: attrName, value };
    }
  }

  return null;
}

/**
 * Check if a value looks randomly generated
 */
function isRandomValue(value: string): boolean {
  return value.length > 50 || /^[a-f0-9]{16,}$/i.test(value);
}

/**
 * Check if an element has any stable attribute
 */
function hasStableAttribute(element: Element): boolean {
  return !!(
    (element.id && !isRandomId(element.id)) ||
    getStableDataAttribute(element) ||
    getStableAttribute(element)
  );
}

/**
 * Test if a CSS selector is unique in the document
 */
function isUniqueSelector(selector: string): boolean {
  try {
    const elements = document.querySelectorAll(selector);
    return elements.length === 1;
  } catch (e) {
    return false;
  }
}

/**
 * Test if an XPath is unique in the document
 */
function isUniqueXPath(xpath: string): boolean {
  try {
    const result = document.evaluate(
      xpath,
      document,
      null,
      XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
      null
    );
    return result.snapshotLength === 1;
  } catch (e) {
    return false;
  }
}

/**
 * Test a selector on the current page
 */
export function testCSSSelector(selector: string): {
  success: boolean;
  matchCount: number;
  elements: Element[];
  error?: string;
} {
  try {
    const elements = Array.from(document.querySelectorAll(selector));
    return {
      success: true,
      matchCount: elements.length,
      elements,
    };
  } catch (error) {
    return {
      success: false,
      matchCount: 0,
      elements: [],
      error: error instanceof Error ? error.message : 'Unknown error',
    };
  }
}

/**
 * Test an XPath selector on the current page
 */
export function testXPath(xpath: string): {
  success: boolean;
  matchCount: number;
  elements: Element[];
  error?: string;
} {
  try {
    const result = document.evaluate(
      xpath,
      document,
      null,
      XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
      null
    );

    const elements: Element[] = [];
    for (let i = 0; i < result.snapshotLength; i++) {
      const node = result.snapshotItem(i);
      if (node && node.nodeType === Node.ELEMENT_NODE) {
        elements.push(node as Element);
      }
    }

    return {
      success: true,
      matchCount: result.snapshotLength,
      elements,
    };
  } catch (error) {
    return {
      success: false,
      matchCount: 0,
      elements: [],
      error: error instanceof Error ? error.message : 'Unknown error',
    };
  }
}

/**
 * Extract preview content from an element
 */
export function getElementPreview(element: Element): {
  tag: string;
  text: string;
  html: string;
  attributes: Record<string, string>;
} {
  const attributes: Record<string, string> = {};
  for (const attr of element.attributes) {
    attributes[attr.name] = attr.value;
  }

  return {
    tag: element.tagName.toLowerCase(),
    text: (element.textContent || '').trim().substring(0, 200),
    html: element.outerHTML.substring(0, 500),
    attributes,
  };
}
