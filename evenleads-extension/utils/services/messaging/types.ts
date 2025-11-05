/**
 * Messaging Service Types
 *
 * Type definitions for platform messaging automation
 */

/**
 * Configuration for message typing behavior
 */
export interface MessageConfig {
  /**
   * Base delay in milliseconds between each character typed
   * Default: 165ms (simulates ~364 chars/min)
   */
  perCharDelayMs?: number;

  /**
   * Use realistic human-like typing with variable delays
   * When true, adds natural variation based on character types
   * Default: true
   */
  useRealisticDelays?: boolean;

  /** Automatically press Enter after typing message */
  pressEnterWhenDone?: boolean;

  /** Delay in milliseconds after focusing the input element */
  focusDelayMs?: number;

  /** Delay in milliseconds before pressing Enter to send */
  sendDelayMs?: number;

  /** Delay in milliseconds after sending before returning */
  postSendDelayMs?: number;
}

/**
 * Default configuration values
 */
export const DEFAULT_MESSAGE_CONFIG: Required<MessageConfig> = {
  perCharDelayMs: 165, // ~364 characters per minute (realistic human typing)
  useRealisticDelays: true, // Enable realistic variable delays
  pressEnterWhenDone: true,
  focusDelayMs: 100,
  sendDelayMs: 200,
  postSendDelayMs: 500,
};

/**
 * Options for sending a message
 */
export interface MessageSendOptions {
  /** The message text to send */
  message: string;

  /** Lead ID to associate with this message */
  leadId?: number;

  /** Recipient profile URL or identifier */
  recipientUrl?: string;

  /** Override default typing configuration */
  config?: MessageConfig;

  /** Additional metadata */
  metadata?: Record<string, any>;
}

/**
 * Result of a message send operation
 */
export interface MessageResult {
  /** Whether the message was sent successfully */
  success: boolean;

  /** Platform-specific message ID if available */
  platformMessageId?: string;

  /** Backend database message ID if recorded */
  messageId?: number;

  /** Error message if failed */
  error?: string;

  /** Additional error details */
  errorDetails?: any;

  /** Timestamp when message was sent */
  sentAt?: Date;
}

/**
 * Data to record a sent message in the backend
 */
export interface MessageRecordData {
  leadId: number;
  platform: 'linkedin' | 'reddit' | 'twitter' | 'facebook';
  channel: 'direct_message' | 'comment' | 'post_reply';
  messageText: string;
  sentVia: 'extension_automation' | 'manual';
  platformMessageId?: string;
  recipientUrl?: string;
  metadata?: Record<string, any>;
}

/**
 * Selector patterns for finding message input elements
 */
export interface MessageInputSelectors {
  /** Primary selector (most common/stable) */
  primary: string;

  /** Fallback selectors to try if primary fails */
  fallbacks: string[];

  /** Optional aria-label to verify correct element */
  ariaLabel?: string;
}

/**
 * Platform-specific messaging configuration
 */
export interface PlatformMessagingConfig {
  /** Selectors for finding the message input */
  inputSelectors: MessageInputSelectors;

  /** Selectors for finding the send button (if needed) */
  sendButtonSelectors?: MessageInputSelectors;

  /** Platform-specific typing config overrides */
  defaultConfig?: Partial<MessageConfig>;

  /** Whether this platform supports Enter key to send */
  supportsEnterToSend: boolean;
}
