# Messaging Service Examples

Real-world usage examples for the EvenLeads messaging service.

## Example 1: Simple Message Send

Send a basic message with default configuration:

```typescript
import { linkedInMessaging } from '@/utils/services/messaging';

async function sendQuickMessage() {
  const result = await linkedInMessaging.sendMessage({
    message: "Hi! I noticed your post about React development. I'd love to connect!",
  });

  if (result.success) {
    console.log('✅ Message sent successfully!');
  } else {
    console.error('❌ Failed:', result.error);
  }
}
```

## Example 2: Custom Typing Speed

Slow down typing for more realistic behavior:

```typescript
import { linkedInMessaging } from '@/utils/services/messaging';

async function sendNaturalMessage() {
  const result = await linkedInMessaging.sendMessage({
    message: "Hello! Your skills look impressive. Are you open to freelance opportunities?",
    config: {
      perCharDelayMs: 60,  // Slower, more natural typing
      sendDelayMs: 500,    // Wait half a second before sending
    },
  });

  return result;
}
```

## Example 3: Send and Track in Database

Send a message and record it for the lead:

```typescript
import { linkedInMessaging } from '@/utils/services/messaging';

async function sendTrackedMessage(leadId: number, profileUrl: string) {
  const result = await linkedInMessaging.sendAndRecordMessage({
    message: "Hi! I saw your question about Next.js performance. I've worked on similar projects and might have some insights to share.",
    leadId: leadId,
    recipientUrl: profileUrl,
    metadata: {
      source: 'automated_outreach',
      campaign: 'nextjs_developers',
      followUpScheduled: true,
    },
  });

  if (result.success) {
    console.log(`✅ Message sent and saved with ID: ${result.messageId}`);
    return result.messageId;
  } else {
    console.error('❌ Error:', result.error);
    return null;
  }
}

// Usage
sendTrackedMessage(123, 'https://linkedin.com/in/johndoe');
```

## Example 4: Content Script Integration

Use in a content script to send messages when user clicks a button:

```typescript
// In your content script
import { linkedInMessaging } from '@/utils/services/messaging';

// Listen for messages from extension popup/sidebar
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'SEND_LINKEDIN_MESSAGE') {
    handleSendMessage(request.data)
      .then(sendResponse)
      .catch(error => sendResponse({ success: false, error: error.message }));

    return true; // Async response
  }
});

async function handleSendMessage(data: {
  message: string;
  leadId: number;
  recipientUrl: string;
}) {
  // Check if we're on the right page
  if (!linkedInMessaging.isOnMessagingPage()) {
    return {
      success: false,
      error: 'Not on LinkedIn messaging page',
    };
  }

  // Send the message
  const result = await linkedInMessaging.sendAndRecordMessage({
    message: data.message,
    leadId: data.leadId,
    recipientUrl: data.recipientUrl,
  });

  return result;
}
```

## Example 5: Batch Message Sending

Send messages to multiple leads with delays:

```typescript
import { linkedInMessaging } from '@/utils/services/messaging';

interface Lead {
  id: number;
  name: string;
  profileUrl: string;
  message: string;
}

async function sendBatchMessages(leads: Lead[], delayBetweenMs: number = 5000) {
  const results = [];

  for (const lead of leads) {
    console.log(`Sending message to ${lead.name}...`);

    const result = await linkedInMessaging.sendAndRecordMessage({
      message: lead.message,
      leadId: lead.id,
      recipientUrl: lead.profileUrl,
      config: {
        perCharDelayMs: 50 + Math.random() * 30, // Random variation: 50-80ms
      },
    });

    results.push({ lead, result });

    if (result.success) {
      console.log(`✅ Sent to ${lead.name}`);
    } else {
      console.error(`❌ Failed for ${lead.name}:`, result.error);
    }

    // Wait before next message
    if (delayBetweenMs > 0) {
      await new Promise(resolve => setTimeout(resolve, delayBetweenMs));
    }
  }

  return results;
}

// Usage
const leadsToContact = [
  {
    id: 1,
    name: 'John Doe',
    profileUrl: 'https://linkedin.com/in/johndoe',
    message: 'Hi John! Saw your post about React...',
  },
  {
    id: 2,
    name: 'Jane Smith',
    profileUrl: 'https://linkedin.com/in/janesmith',
    message: 'Hi Jane! Your portfolio is impressive...',
  },
];

sendBatchMessages(leadsToContact, 10000); // 10 second delay between messages
```

## Example 6: AI-Generated Messages

Integrate with AI to generate personalized messages:

```typescript
import { linkedInMessaging } from '@/utils/services/messaging';
import { api } from '@/utils/api';

async function sendAIGeneratedMessage(leadId: number, leadContext: any) {
  // First, generate AI message (assumes you have this API endpoint)
  const aiResponse = await api.request('/api/leads/' + leadId + '/generate-message', {
    method: 'POST',
    body: JSON.stringify({
      context: leadContext,
      tone: 'professional',
      length: 'medium',
    }),
  });

  if (!aiResponse.success || !aiResponse.message) {
    throw new Error('Failed to generate AI message');
  }

  // Then send the generated message
  const result = await linkedInMessaging.sendAndRecordMessage({
    message: aiResponse.message,
    leadId: leadId,
    recipientUrl: leadContext.profileUrl,
    metadata: {
      generatedBy: 'ai',
      aiModel: aiResponse.model,
      context: leadContext,
    },
  });

  return result;
}

// Usage
sendAIGeneratedMessage(123, {
  profileUrl: 'https://linkedin.com/in/developer',
  postTitle: 'Looking for React developers',
  postContent: 'We need help building a SaaS platform...',
  skills: ['React', 'Node.js', 'TypeScript'],
});
```

## Example 7: Message with Retry Logic

Add retry logic for failed sends:

```typescript
import { linkedInMessaging } from '@/utils/services/messaging';

async function sendMessageWithRetry(
  options: any,
  maxRetries: number = 3,
  retryDelayMs: number = 2000
): Promise<any> {
  let lastError;

  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      console.log(`Attempt ${attempt}/${maxRetries}`);

      const result = await linkedInMessaging.sendMessage(options);

      if (result.success) {
        console.log('✅ Success!');
        return result;
      }

      lastError = result.error;
      console.warn(`Attempt ${attempt} failed:`, result.error);

    } catch (error) {
      lastError = error;
      console.error(`Attempt ${attempt} threw error:`, error);
    }

    // Wait before retrying (except on last attempt)
    if (attempt < maxRetries) {
      console.log(`Waiting ${retryDelayMs}ms before retry...`);
      await new Promise(resolve => setTimeout(resolve, retryDelayMs));
    }
  }

  throw new Error(`Failed after ${maxRetries} attempts: ${lastError}`);
}

// Usage
sendMessageWithRetry({
  message: "Hello! Interested in collaborating?",
  leadId: 123,
}, 3, 2000);
```

## Example 8: Check Page Before Sending

Always verify you're on the correct page:

```typescript
import { linkedInMessaging } from '@/utils/services/messaging';

async function safeSendMessage(message: string, leadId: number) {
  // Check if on messaging page
  if (!linkedInMessaging.isOnMessagingPage()) {
    return {
      success: false,
      error: 'Please navigate to LinkedIn messaging page first',
    };
  }

  // Get current conversation URL
  const conversationUrl = linkedInMessaging.getCurrentConversationUrl();

  if (!conversationUrl) {
    return {
      success: false,
      error: 'No active conversation detected',
    };
  }

  console.log('Sending message in conversation:', conversationUrl);

  // Send message
  const result = await linkedInMessaging.sendMessage({
    message,
    leadId,
  });

  return result;
}
```

## Example 9: Testing Message Typing (Without Sending)

Test the typing without actually sending:

```typescript
import { linkedInMessaging } from '@/utils/services/messaging';

async function testTyping(message: string) {
  const result = await linkedInMessaging.sendMessage({
    message,
    config: {
      perCharDelayMs: 100,      // Slow typing for visibility
      pressEnterWhenDone: false, // DON'T send the message
    },
  });

  console.log('Message typed (not sent):', result);
}

// Usage - this will type the message but NOT send it
testTyping("This is a test message that won't be sent");
```

## Example 10: Background Script Integration

Send messages from the background script:

```typescript
// In background.ts
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'SEND_MESSAGE_TO_LEAD') {
    sendMessageToLead(request.data)
      .then(sendResponse)
      .catch(error => sendResponse({ success: false, error: error.message }));
    return true;
  }
});

async function sendMessageToLead(data: {
  tabId: number;
  message: string;
  leadId: number;
}) {
  // Inject content script if not already injected
  try {
    await chrome.scripting.executeScript({
      target: { tabId: data.tabId },
      files: ['content-scripts/linkedin.js'],
    });
  } catch (error) {
    console.log('Content script already injected');
  }

  // Send message to content script to execute the messaging
  const response = await chrome.tabs.sendMessage(data.tabId, {
    action: 'EXECUTE_MESSAGE_SEND',
    data: {
      message: data.message,
      leadId: data.leadId,
    },
  });

  return response;
}
```

## Tips for Production Use

1. **Always add delays between messages** to avoid rate limiting
2. **Use randomized typing speeds** for more natural behavior
3. **Validate page state** before sending
4. **Log all attempts** for debugging and analytics
5. **Handle errors gracefully** with user-friendly messages
6. **Track success/failure rates** to optimize
7. **Test thoroughly** before deploying to users
8. **Respect platform limits** and terms of service
