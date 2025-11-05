/**
 * Messaging Services Entry Point
 *
 * Centralized exports for all platform messaging services
 */

// Export types
export type {
  MessageConfig,
  MessageSendOptions,
  MessageResult,
  MessageRecordData,
  MessageInputSelectors,
  PlatformMessagingConfig,
} from './types';

export { DEFAULT_MESSAGE_CONFIG } from './types';

// Export base class (for extending to other platforms)
export { BaseMessagingService } from './baseMessaging';

// Export LinkedIn service (class only - create instances as needed)
export { default as LinkedInMessagingService } from './linkedinMessaging';
