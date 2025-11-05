# Messaging Services

Automated messaging services for sending messages on various platforms with human-like typing simulation.

## Features

- ✅ **Human-like typing**: Character-by-character typing with configurable delays
- ✅ **Configurable behavior**: Control typing speed, auto-send, and delays
- ✅ **Error handling**: Comprehensive error handling and logging
- ✅ **Backend integration**: Automatically records messages in database
- ✅ **Multi-platform ready**: Extensible architecture for Reddit, Twitter, Facebook
- ✅ **TypeScript**: Full type safety

## Supported Platforms

- ✅ **LinkedIn** - Direct messages in messenger
- 🔜 **Reddit** - Direct messages and comments
- 🔜 **Twitter/X** - Direct messages
- 🔜 **Facebook** - Messages

## Installation

The messaging services are already part of the EvenLeads extension. No installation required.

## Usage

### Basic Usage (LinkedIn)

```typescript
import { linkedInMessaging } from '@/utils/services/messaging';

// Send a message with default settings
const result = await linkedInMessaging.sendMessage({
  message: "Hello! Saw you were looking for web development services. Would love to chat!",
});

if (result.success) {
  console.log('Message sent successfully!');
} else {
  console.error('Failed to send:', result.error);
}
```

### With Custom Configuration

```typescript
import { linkedInMessaging } from '@/utils/services/messaging';

const result = await linkedInMessaging.sendMessage({
  message: "Hey there! Interested in collaborating?",
  config: {
    perCharDelayMs: 50,        // Slower typing (default: 30)
    pressEnterWhenDone: true,  // Auto-send (default: true)
    focusDelayMs: 200,         // Longer focus delay (default: 100)
    sendDelayMs: 300,          // Wait longer before sending (default: 200)
  },
});
```

### Send and Record in Backend

```typescript
import { linkedInMessaging } from '@/utils/services/messaging';

const result = await linkedInMessaging.sendAndRecordMessage({
  message: "Hello! Saw your post about looking for React developers.",
  leadId: 123,  // Lead ID from your database
  recipientUrl: "https://linkedin.com/in/johndoe",
  metadata: {
    campaignId: 456,
    messageType: 'initial_outreach',
  },
});

if (result.success) {
  console.log('Message sent and recorded!', {
    messageId: result.messageId,  // Database ID
    sentAt: result.sentAt,
  });
}
```

### Check if on Messaging Page

```typescript
import { linkedInMessaging } from '@/utils/services/messaging';

if (linkedInMessaging.isOnMessagingPage()) {
  console.log('User is on LinkedIn messaging page');

  const conversationUrl = linkedInMessaging.getCurrentConversationUrl();
  console.log('Current conversation:', conversationUrl);
}
```

## Configuration Options

### MessageConfig

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `perCharDelayMs` | number | 30 | Delay in ms between each character |
| `pressEnterWhenDone` | boolean | true | Auto-send message after typing |
| `focusDelayMs` | number | 100 | Delay after focusing input element |
| `sendDelayMs` | number | 200 | Delay before pressing Enter |
| `postSendDelayMs` | number | 500 | Delay after sending |

### Typing Speed Presets

```typescript
// Ultra-fast (bot-like, not recommended)
const ultraFast = { perCharDelayMs: 10 };

// Fast (default)
const fast = { perCharDelayMs: 30 };

// Natural (recommended for realism)
const natural = { perCharDelayMs: 50 };

// Slow (very human-like)
const slow = { perCharDelayMs: 80 };

// Very slow (cautious)
const verySlow = { perCharDelayMs: 120 };
```

## Architecture

### Directory Structure

```
utils/services/messaging/
├── index.ts                  # Main entry point
├── types.ts                  # TypeScript type definitions
├── baseMessaging.ts          # Abstract base class
├── linkedinMessaging.ts      # LinkedIn implementation
├── redditMessaging.ts        # Reddit implementation (future)
├── twitterMessaging.ts       # Twitter implementation (future)
└── README.md                 # This file
```

### Class Hierarchy

```
BaseMessagingService (abstract)
├── findMessageInput()
├── focusAndClearElement()
├── typeCharacter()
├── typeMessage()
├── pressEnterKey()
└── sendMessage() (abstract)

LinkedInMessagingService extends BaseMessagingService
├── sendMessage()
├── sendAndRecordMessage()
├── isOnMessagingPage()
└── getCurrentConversationUrl()
```

## Error Handling

The service returns a `MessageResult` object:

```typescript
interface MessageResult {
  success: boolean;
  platformMessageId?: string;
  messageId?: number;
  error?: string;
  errorDetails?: any;
  sentAt?: Date;
}
```

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| "Message input element not found" | Not on messaging page | Navigate to LinkedIn messaging first |
| "No selection range available" | Focus failed | Retry or check page state |
| "Failed to record message" | Backend API error | Message sent but not recorded (non-critical) |

## Backend Integration

### API Endpoint

The service calls: `POST /api/extension/leads/{leadId}/messages`

### Request Format

```json
{
  "platform": "linkedin",
  "channel": "direct_message",
  "message_text": "Hello!",
  "sent_via": "extension_automation",
  "platform_message_id": null,
  "recipient_url": "https://linkedin.com/in/johndoe",
  "metadata": {}
}
```

### Response Format

```json
{
  "success": true,
  "message_id": 123,
  "message": "Message recorded successfully"
}
```

## Extending to Other Platforms

To add support for a new platform:

1. **Create a new service file** (e.g., `redditMessaging.ts`)
2. **Extend BaseMessagingService**
3. **Define platform-specific selectors**
4. **Implement sendMessage() method**
5. **Export in index.ts**

Example:

```typescript
import { BaseMessagingService } from './baseMessaging';
import type { MessageSendOptions, MessageResult } from './types';

export class RedditMessagingService extends BaseMessagingService {
  protected platformName = 'reddit';

  async sendMessage(options: MessageSendOptions): Promise<MessageResult> {
    // Implementation here
  }

  getPlatformConfig() {
    return {
      inputSelectors: {
        primary: 'textarea[name="message"]',
        fallbacks: ['textarea.m-message-input'],
      },
      supportsEnterToSend: true,
    };
  }
}

export const redditMessaging = new RedditMessagingService();
```

## Best Practices

1. **Use natural typing speed**: Set `perCharDelayMs` to 50+ for realistic behavior
2. **Add random variation**: Consider adding ±10ms random jitter to delays
3. **Check page state**: Verify you're on the right page before sending
4. **Handle errors gracefully**: Always check `result.success`
5. **Record messages**: Use `sendAndRecordMessage()` for proper tracking
6. **Respect rate limits**: Don't send messages too quickly
7. **Test in development**: Verify behavior before deploying

## Security Considerations

- ✅ Messages are sent through legitimate browser automation (not API scraping)
- ✅ Uses real user session (not bot accounts)
- ✅ Typing simulation appears human-like
- ⚠️ Avoid sending too many messages in short time (rate limiting)
- ⚠️ Always get user consent before automated messaging

## Troubleshooting

### Message not sending

1. Check if input element exists: `linkedInMessaging.isOnMessagingPage()`
2. Verify selectors in browser console
3. Check console for error messages
4. Try manual typing to test page responsiveness

### Backend recording fails

- Non-critical error - message still sent
- Check API endpoint exists
- Verify authentication token
- Check server logs for details

## Future Enhancements

- [ ] Random typing speed variation
- [ ] Support for message templates
- [ ] Retry logic for failed sends
- [ ] Rate limiting to avoid detection
- [ ] Message scheduling
- [ ] Bulk messaging support
- [ ] Analytics and reporting

## Support

For issues or questions, contact the development team or check the main EvenLeads documentation.
