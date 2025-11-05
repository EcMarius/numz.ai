/**
 * LinkedIn Messaging Service
 *
 * Handles automated message sending on LinkedIn with human-like typing simulation
 */

import { BaseMessagingService } from './baseMessaging';
import type {
  MessageConfig,
  MessageResult,
  MessageSendOptions,
  MessageInputSelectors,
  PlatformMessagingConfig,
  DEFAULT_MESSAGE_CONFIG,
  MessageRecordData,
} from './types';

/**
 * LinkedIn-specific messaging configuration
 */
const LINKEDIN_CONFIG: PlatformMessagingConfig = {
  inputSelectors: {
    primary: 'div.msg-form__contenteditable[contenteditable="true"][aria-multiline="true"]',
    fallbacks: [
      'div.msg-form__contenteditable[contenteditable="true"]',
      '[aria-label="Write a message…"][contenteditable="true"]',
      '[aria-label="Write a message"][contenteditable="true"]',
    ],
    ariaLabel: 'message',
  },
  sendButtonSelectors: {
    primary: 'button.msg-form__send-button',
    fallbacks: [
      'button[type="submit"].msg-form__send-button',
      'button[aria-label="Send"]',
    ],
  },
  defaultConfig: {
    perCharDelayMs: 165,         // ~364 chars/min (realistic human typing)
    useRealisticDelays: true,   // Enable natural variation
    pressEnterWhenDone: true,
    focusDelayMs: 100,
    sendDelayMs: 200,
    postSendDelayMs: 500,
  },
  supportsEnterToSend: true,
};

export class LinkedInMessagingService extends BaseMessagingService {
  protected platformName = 'linkedin';

  /**
   * Get LinkedIn platform configuration
   */
  getPlatformConfig(): PlatformMessagingConfig {
    return LINKEDIN_CONFIG;
  }

  /**
   * Send a message on LinkedIn
   *
   * @example
   * ```typescript
   * const service = new LinkedInMessagingService();
   * const result = await service.sendMessage({
   *   message: "Hello, saw you looking for web dev services!",
   *   leadId: 123,
   *   config: {
   *     perCharDelayMs: 50,  // Slower typing
   *     pressEnterWhenDone: true
   *   }
   * });
   *
   * if (result.success) {
   *   console.log('Message sent!', result.messageId);
   * }
   * ```
   */
  async sendMessage(options: MessageSendOptions): Promise<MessageResult> {
    const startTime = new Date();

    try {
      // Merge config with defaults
      const config: Required<MessageConfig> = {
        ...LINKEDIN_CONFIG.defaultConfig,
        ...options.config,
      } as Required<MessageConfig>;

      // Find the message input element
      const inputElement = this.findMessageInput(LINKEDIN_CONFIG.inputSelectors);

      if (!inputElement) {
        return {
          success: false,
          error: 'Message input element not found on page',
          errorDetails: {
            selectors: LINKEDIN_CONFIG.inputSelectors,
            url: window.location.href,
          },
        };
      }

      console.log('[LinkedIn Messaging] Found input element, preparing to type message...');

      // Focus and clear the input
      await this.focusAndClearElement(inputElement, config.focusDelayMs);

      console.log('[LinkedIn Messaging] Typing message...');

      // Type the message character by character with realistic human-like delays
      await this.typeMessage(
        inputElement,
        options.message,
        config.perCharDelayMs,
        config.useRealisticDelays
      );

      console.log('[LinkedIn Messaging] Message typed successfully');

      // Press Enter to send if configured
      if (config.pressEnterWhenDone) {
        // Wait before sending
        if (config.sendDelayMs > 0) {
          await this.sleep(config.sendDelayMs);
        }

        console.log('[LinkedIn Messaging] Sending message...');
        this.pressEnterKey(inputElement);

        // Wait after sending for UI to update
        if (config.postSendDelayMs > 0) {
          await this.sleep(config.postSendDelayMs);
        }
      }

      const result: MessageResult = {
        success: true,
        sentAt: new Date(),
        platformMessageId: undefined, // LinkedIn doesn't expose message IDs easily
      };

      console.log('[LinkedIn Messaging] Message sent successfully');

      return result;
    } catch (error) {
      console.error('[LinkedIn Messaging] Error sending message:', error);

      return {
        success: false,
        error: error instanceof Error ? error.message : 'Unknown error occurred',
        errorDetails: error,
      };
    }
  }

  /**
   * Send message and record it in the backend
   *
   * @example
   * ```typescript
   * const service = new LinkedInMessagingService();
   * const result = await service.sendAndRecordMessage({
   *   message: "Hey, interested in collaborating?",
   *   leadId: 456,
   *   recipientUrl: "https://linkedin.com/in/johndoe"
   * });
   * ```
   */
  async sendAndRecordMessage(options: MessageSendOptions): Promise<MessageResult> {
    // First, send the message
    const result = await this.sendMessage(options);

    // If successful and leadId provided, record it via API
    if (result.success && options.leadId) {
      try {
        // Import API client dynamically to avoid circular dependencies
        const { api } = await import('../../api');

        const recordData: MessageRecordData = {
          leadId: options.leadId,
          platform: 'linkedin',
          channel: 'direct_message',
          messageText: options.message,
          sentVia: 'extension_automation',
          platformMessageId: result.platformMessageId,
          recipientUrl: options.recipientUrl,
          metadata: options.metadata,
        };

        // Record the message (will implement this in api.ts next)
        const response = await api.recordLeadMessage(recordData);
        result.messageId = response.message_id;

        console.log('[LinkedIn Messaging] Message recorded in backend:', result.messageId);
      } catch (apiError) {
        console.error('[LinkedIn Messaging] Failed to record message in backend:', apiError);
        // Don't fail the whole operation if recording fails
        result.errorDetails = {
          ...result.errorDetails,
          recordingError: apiError,
        };
      }
    }

    return result;
  }

  /**
   * Check if we're on a LinkedIn messaging page
   */
  isOnMessagingPage(): boolean {
    const url = window.location.href;
    return (
      url.includes('linkedin.com/messaging') ||
      url.includes('linkedin.com/in/') ||
      !!this.findMessageInput(LINKEDIN_CONFIG.inputSelectors)
    );
  }

  /**
   * Get current conversation URL (if on messaging page)
   */
  getCurrentConversationUrl(): string | null {
    const url = window.location.href;
    if (url.includes('linkedin.com/messaging')) {
      return url;
    }
    return null;
  }
}

// Export class
export default LinkedInMessagingService;
