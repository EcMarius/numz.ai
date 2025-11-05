/**
 * Base Messaging Service
 *
 * Abstract base class providing common messaging functionality
 * for all platform-specific messaging services
 */

import type {
  MessageConfig,
  MessageResult,
  MessageSendOptions,
  MessageInputSelectors,
  DEFAULT_MESSAGE_CONFIG,
} from './types';

export abstract class BaseMessagingService {
  protected abstract platformName: string;

  /**
   * Sleep utility
   */
  protected sleep(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }

  /**
   * Find message input element using selector strategy
   */
  protected findMessageInput(selectors: MessageInputSelectors): HTMLElement | null {
    // Try primary selector first
    let el = document.querySelector<HTMLElement>(selectors.primary);

    // Try fallback selectors if primary fails
    if (!el && selectors.fallbacks) {
      for (const fallback of selectors.fallbacks) {
        el = document.querySelector<HTMLElement>(fallback);
        if (el) break;
      }
    }

    // Verify aria-label if specified
    if (el && selectors.ariaLabel) {
      const ariaLabel = el.getAttribute('aria-label');
      if (ariaLabel && !ariaLabel.includes(selectors.ariaLabel)) {
        console.warn('Found element but aria-label does not match:', { expected: selectors.ariaLabel, found: ariaLabel });
      }
    }

    return el;
  }

  /**
   * Focus element and clear existing text
   */
  protected async focusAndClearElement(el: HTMLElement, focusDelayMs: number = 100): Promise<void> {
    // Focus the element
    el.focus();

    // Wait for focus to settle
    if (focusDelayMs > 0) {
      await this.sleep(focusDelayMs);
    }

    // Clear existing text using execCommand (works in contenteditable)
    try {
      document.execCommand('selectAll', false, null);
      document.execCommand('delete', false, null);
    } catch (e) {
      console.warn('execCommand failed, trying alternative clear method', e);
    }

    // Set up selection range for typing
    const sel = window.getSelection();
    if (!sel) return;

    const range = document.createRange();
    range.selectNodeContents(el);
    range.collapse(true);
    sel.removeAllRanges();
    sel.addRange(range);
  }

  /**
   * Type a single character into the active element
   */
  protected typeCharacter(el: HTMLElement, ch: string): void {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) {
      console.error('No selection range available for typing');
      return;
    }

    const range = sel.getRangeAt(0);
    let success = false;

    // Try using execCommand first (preferred method)
    try {
      success = document.execCommand('insertText', false, ch);
    } catch (e) {
      // execCommand may not be supported
    }

    // Fallback: manually insert text node
    if (!success) {
      const textNode = document.createTextNode(ch);
      range.insertNode(textNode);
      range.setStartAfter(textNode);
      range.setEndAfter(textNode);
      sel.removeAllRanges();
      sel.addRange(range);
    }

    // Dispatch input event to trigger any listeners
    el.dispatchEvent(
      new InputEvent('input', {
        bubbles: true,
        inputType: 'insertText',
        data: ch,
        cancelable: true,
      })
    );
  }

  /**
   * Calculate realistic typing delay simulating burst typing with thinking pauses
   * Real typing: fast bursts within words + pauses BEFORE words
   * Target: ~360 characters per minute
   */
  protected getRealisticTypingDelay(
    char: string,
    nextChar: string | null,
    isStartOfWord: boolean
  ): number {
    // Fast typing WITHIN words (50-80ms per character)
    const inWordDelay = 50 + Math.random() * 30; // 50-80ms

    // Check if this is a word boundary (space, punctuation, etc.)
    const isWordBoundary = /[\s.,!?;:\n]/.test(char);

    // If starting a new word (after space/punctuation), add thinking pause
    if (isStartOfWord && !isWordBoundary) {
      // Random pause before word: 200-600ms (thinking time)
      const thinkingPause = 200 + Math.random() * 400;

      // Occasional longer thinking pauses (10% chance)
      if (Math.random() < 0.10) {
        return inWordDelay + thinkingPause + (Math.random() * 500 + 300); // +500-800ms extra
      }

      return inWordDelay + thinkingPause;
    }

    // Extra pause after punctuation (but not adding to next char, already in isStartOfWord)
    if (['.', '!', '?'].includes(char)) {
      return inWordDelay + 100 + Math.random() * 200; // +100-300ms after sentence
    }

    // Extra pause after comma/semicolon
    if ([',', ';', ':'].includes(char)) {
      return inWordDelay + 50 + Math.random() * 100; // +50-150ms after comma
    }

    // Line breaks get longer pauses
    if (char === '\n') {
      return inWordDelay + 300 + Math.random() * 300; // +300-600ms
    }

    // Regular character within a word - fast typing
    return inWordDelay;
  }

  /**
   * Type entire message with realistic burst typing
   * Simulates ~360 characters per minute with burst typing + thinking pauses
   */
  protected async typeMessage(
    el: HTMLElement,
    message: string,
    perCharDelayMs: number = 167, // Base parameter (unused for realistic mode)
    useRealisticDelays: boolean = true
  ): Promise<void> {
    if (!useRealisticDelays) {
      // Fallback to simple uniform delay
      for (const ch of message) {
        this.typeCharacter(el, ch);
        if (perCharDelayMs > 0) {
          await this.sleep(perCharDelayMs);
        }
      }
      return;
    }

    // Realistic burst typing with thinking pauses
    let isStartOfWord = true; // First character is always start of word

    for (let i = 0; i < message.length; i++) {
      const char = message[i];
      const nextChar = i < message.length - 1 ? message[i + 1] : null;

      // Type the character
      this.typeCharacter(el, char);

      // Calculate realistic delay
      const delay = this.getRealisticTypingDelay(char, nextChar, isStartOfWord);
      await this.sleep(delay);

      // Determine if next character is start of a new word
      // Start of word = after space, punctuation, or newline
      isStartOfWord = /[\s.,!?;:\n]/.test(char);
    }
  }

  /**
   * Simulate pressing Enter key
   */
  protected pressEnterKey(el: HTMLElement): void {
    const fireKeyEvent = (eventType: string) => {
      el.dispatchEvent(
        new KeyboardEvent(eventType, {
          key: 'Enter',
          code: 'Enter',
          keyCode: 13,
          which: 13,
          bubbles: true,
          cancelable: true,
        })
      );
    };

    fireKeyEvent('keydown');
    fireKeyEvent('keypress');
    fireKeyEvent('keyup');
  }

  /**
   * Send a message (to be implemented by platform-specific services)
   */
  abstract sendMessage(options: MessageSendOptions): Promise<MessageResult>;

  /**
   * Get platform-specific configuration
   */
  abstract getPlatformConfig(): any;
}
