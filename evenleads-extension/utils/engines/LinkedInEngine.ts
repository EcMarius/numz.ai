/**
 * LinkedIn Platform Engine
 *
 * Handles LinkedIn-specific automation including:
 * - Opening profiles and clicking "Message" button
 * - Sending messages with realistic typing
 */

import { BasePlatformEngine } from './BasePlatformEngine';
import { LinkedInMessagingService } from '../services/messaging';
import type { MessageResult, MessageSendOptions } from '../services/messaging/types';

export class LinkedInEngine extends BasePlatformEngine {
  private messagingService = new LinkedInMessagingService();

  constructor() {
    super('linkedin');
  }

  /**
   * Open a LinkedIn profile and click the "Message" button
   *
   * @param profileUrl - Full LinkedIn profile URL
   * @returns MessageResult indicating success/failure
   */
  async openProfileAndMessage(profileUrl: string): Promise<MessageResult> {
    const startTime = new Date();

    try {
      console.log('[LinkedInEngine] Opening profile:', profileUrl);

      // Ensure schemas are loaded
      if (!this.isSchemaLoaded) {
        console.log('[LinkedInEngine] Schemas not loaded, loading now...');
        await this.loadSchemas(['profile', 'messaging']);
      }

      // Navigate to profile URL
      await this.navigateTo(profileUrl, true);

      console.log('[LinkedInEngine] Waiting for profile page to load...');

      // Wait for and find the "Message" button
      const messageButton = await this.waitForElement(
        'profile_message_button',
        'profile',
        {
          requireText: 'Message',
          timeout: 15000, // 15 seconds
        }
      );

      if (!messageButton) {
        return {
          success: false,
          error: 'Message button not found on profile page',
          errorDetails: {
            profileUrl,
            pageType: 'profile',
            elementType: 'profile_message_button',
          },
        };
      }

      console.log('[LinkedInEngine] Found Message button, clicking...');

      // Click the Message button
      await this.clickElement(messageButton, 1000);

      // Wait for messaging interface to appear
      console.log('[LinkedInEngine] Waiting for messaging interface...');
      const messageInput = await this.waitForElement(
        'message_input',
        'messaging',
        {
          timeout: 10000,
        }
      );

      if (!messageInput) {
        return {
          success: false,
          error: 'Messaging interface did not open after clicking Message button',
          errorDetails: {
            profileUrl,
            buttonClicked: true,
          },
        };
      }

      console.log('[LinkedInEngine] Messaging interface opened successfully!');

      return {
        success: true,
        sentAt: new Date(),
        platformMessageId: undefined,
      };
    } catch (error) {
      console.error('[LinkedInEngine] Error in openProfileAndMessage:', error);

      return {
        success: false,
        error: error instanceof Error ? error.message : 'Unknown error occurred',
        errorDetails: error,
      };
    }
  }

  /**
   * Send a message in the current messaging interface
   *
   * @param message - Message text to send
   * @param config - Optional messaging configuration
   * @returns MessageResult indicating success/failure
   */
  async sendMessage(message: string, config?: any): Promise<MessageResult> {
    try {
      console.log('[LinkedInEngine] Sending message...');

      // Ensure schemas are loaded
      if (!this.isSchemaLoaded) {
        console.log('[LinkedInEngine] Schemas not loaded, loading now...');
        await this.loadSchemas(['messaging']);
      }

      // Check if on messaging page
      if (!this.messagingService.isOnMessagingPage()) {
        return {
          success: false,
          error: 'Not on LinkedIn messaging page',
          errorDetails: {
            currentUrl: window.location.href,
          },
        };
      }

      // Use the LinkedInMessagingService to send the message
      const options: MessageSendOptions = {
        message,
        config: config || {
          perCharDelayMs: 165,
          useRealisticDelays: true,
          pressEnterWhenDone: true,
        },
      };

      const result = await this.messagingService.sendMessage(options);

      return result;
    } catch (error) {
      console.error('[LinkedInEngine] Error in sendMessage:', error);

      return {
        success: false,
        error: error instanceof Error ? error.message : 'Unknown error occurred',
        errorDetails: error,
      };
    }
  }

  /**
   * Combined operation: Open profile, click Message, wait, then send message
   *
   * @param profileUrl - LinkedIn profile URL
   * @param message - Message to send
   * @param config - Optional messaging configuration
   * @returns MessageResult
   */
  async openProfileAndSendMessage(
    profileUrl: string,
    message: string,
    config?: any
  ): Promise<MessageResult> {
    // First, open profile and click Message button
    const openResult = await this.openProfileAndMessage(profileUrl);

    if (!openResult.success) {
      return openResult;
    }

    // Wait a bit for messaging interface to stabilize
    await this.sleep(2000);

    // Then send the message
    return await this.sendMessage(message, config);
  }
}

export default LinkedInEngine;
