import { defineContentScript } from 'wxt/sandbox';
import { authStorage, campaignStorage, messagingTestStorage, devModeStorage } from '../../utils/storage';
import type { Campaign, Lead } from '../../types';

export default defineContentScript({
  matches: ['*://*.linkedin.com/*'],
  async main() {
    console.log('[LinkedIn Content] ========== LinkedIn content script loaded ==========');
    console.log('[LinkedIn Content] URL:', window.location.href);
    console.log('[LinkedIn Content] Timestamp:', new Date().toISOString());

    // Check for messaging test with retry logic (handles storage race conditions)
    console.log('[LinkedIn Content] Checking for messaging test state (with retries)...');

    let messagingTest = null;
    let retryCount = 0;
    const maxRetries = 3;

    while (retryCount < maxRetries) {
      messagingTest = await messagingTestStorage.getTestState();

      if (messagingTest && messagingTest.active) {
        console.log(`[LinkedIn Content] ✅ Messaging test found on attempt ${retryCount + 1}!`);
        break;
      }

      if (retryCount < maxRetries - 1) {
        console.log(`[LinkedIn Content] No test found (attempt ${retryCount + 1}/${maxRetries}), retrying in 500ms...`);
        await new Promise(resolve => setTimeout(resolve, 500));
      }

      retryCount++;
    }

    console.log('[LinkedIn Content] Final messaging test state:', messagingTest);

    if (messagingTest && messagingTest.active) {
      console.log('[LinkedIn Content] ✅ Messaging test detected! Starting test execution...');
      console.log('[LinkedIn Content] Test details:', {
        profileUrl: messagingTest.profileUrl,
        testMessage: messagingTest.testMessage,
        openDevMode: messagingTest.openDevMode,
        platform: messagingTest.platform,
        testId: messagingTest.testId,
      });
      await handleMessagingTest(messagingTest);
      return; // Don't run normal monitoring during test
    } else {
      console.log('[LinkedIn Content] No active messaging test found after all retries, proceeding with normal monitoring');
    }

    // Initialize lead detection
    initLinkedInMonitoring();

    // REMOVED: MutationObserver - this was causing the invalid extension errors
    // LinkedIn will only work on page load, not SPA navigation
    // Users need to refresh the page when navigating
  },
});

// Helper function to normalize URLs for comparison
function normalizeUrl(url: string): string {
  return url
    .toLowerCase()
    .replace(/^https?:\/\//, '') // Remove protocol
    .replace(/^www\./, '')        // Remove www.
    .replace(/\/$/, '')           // Remove trailing slash
    .replace(/\?.*$/, '')         // Remove query params
    .replace(/#.*$/, '');         // Remove hash
}

async function handleMessagingTest(testState: any) {
  try {
    console.log('[Messaging Test] ========== Starting test execution ==========');

    // Enhanced URL matching with better normalization
    const currentUrl = window.location.href;
    const currentNormalized = normalizeUrl(currentUrl);
    const testNormalized = normalizeUrl(testState.profileUrl);

    console.log('[Messaging Test] URL Comparison:');
    console.log('[Messaging Test]   Current URL:    ', currentUrl);
    console.log('[Messaging Test]   Current (norm): ', currentNormalized);
    console.log('[Messaging Test]   Test URL:       ', testState.profileUrl);
    console.log('[Messaging Test]   Test (norm):    ', testNormalized);

    // Check if URLs match (either exact or one contains the other)
    const urlsMatch = currentNormalized === testNormalized ||
                      currentNormalized.includes(testNormalized) ||
                      testNormalized.includes(currentNormalized);

    if (!urlsMatch) {
      console.log('[Messaging Test] ❌ URL mismatch, skipping test');
      console.log('[Messaging Test] Clearing test state...');
      await messagingTestStorage.clearTestState();
      return;
    }

    console.log('[Messaging Test] ✅ URL matches, proceeding with test');

    // Wait for sidebar to load before sending DevMode message
    // This prevents the message from being lost
    console.log('[Messaging Test] Waiting 2 seconds for sidebar to load...');
    await new Promise(resolve => setTimeout(resolve, 2000));

    // Open dev mode if requested
    if (testState.openDevMode) {
      console.log('[Messaging Test] Opening dev mode...');
      await devModeStorage.setEnabled(true);

      // Wait a bit more to ensure storage is set
      await new Promise(resolve => setTimeout(resolve, 300));

      // Broadcast to open dev mode
      console.log('[Messaging Test] Sending OPEN_DEV_MODE message...');
      window.postMessage({ type: 'OPEN_DEV_MODE' }, '*');

      // Give sidebar time to receive and process the message
      await new Promise(resolve => setTimeout(resolve, 1000));
      console.log('[Messaging Test] DevMode should be open now');
    }

    // Additional wait for page to fully stabilize
    console.log('[Messaging Test] Waiting for page to stabilize...');
    await new Promise(resolve => setTimeout(resolve, 1000));

    // Dynamically import LinkedInEngine
    const { LinkedInEngine } = await import('../../utils/engines/LinkedInEngine');

    console.log('[Messaging Test] Initializing LinkedIn engine...');
    const engine = new LinkedInEngine();

    // Execute the messaging test
    console.log('[Messaging Test] Opening profile and sending message...');
    const result = await engine.openProfileAndMessage(testState.testMessage);

    console.log('[Messaging Test] Test completed:', result);

    // Show result notification
    showTestResultNotification(result.success, result.message);

    // Clear test state after completion
    await messagingTestStorage.clearTestState();

  } catch (error) {
    console.error('[Messaging Test] Error during test:', error);
    showTestResultNotification(false, `Test failed: ${(error as Error).message}`);

    // Clear test state on error
    await messagingTestStorage.clearTestState();
  }
}

function showTestResultNotification(success: boolean, message: string) {
  const notification = document.createElement('div');
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    padding: 16px 20px;
    background: ${success ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'};
    color: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 14px;
    font-weight: 500;
    max-width: 400px;
    animation: slideInFromRight 0.3s ease-out;
  `;

  notification.innerHTML = `
    <div style="display: flex; align-items: start; gap: 12px;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        ${success
          ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'
          : '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>'
        }
      </svg>
      <div>
        <div style="font-weight: 600; margin-bottom: 4px;">
          ${success ? 'Messaging Test Passed ✓' : 'Messaging Test Failed ✗'}
        </div>
        <div style="font-size: 12px; opacity: 0.95;">
          ${message}
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.animation = 'slideOutToRight 0.3s ease-out';
    setTimeout(() => notification.remove(), 300);
  }, 5000);
}

async function initLinkedInMonitoring() {
  const isAuth = await authStorage.isAuthenticated();
  if (!isAuth) {
    console.log('EvenLeads: Not authenticated, skipping monitoring');
    return;
  }

  // Get selected campaign or all LinkedIn campaigns
  const selectedCampaign = await campaignStorage.getSelectedCampaign();
  const allCampaigns = await campaignStorage.get();

  const linkedInCampaigns = selectedCampaign
    ? [selectedCampaign].filter(c => c.platforms.includes('linkedin'))
    : allCampaigns.filter(c => c.platforms.includes('linkedin'));

  if (linkedInCampaigns.length === 0) {
    console.log('EvenLeads: No LinkedIn campaigns active');
    return;
  }

  console.log(`EvenLeads: Monitoring LinkedIn for ${linkedInCampaigns.length} campaign(s)`);

  // Detect page type and initialize appropriate monitoring
  const currentUrl = window.location.href;

  if (currentUrl.includes('/feed') || currentUrl.match(/linkedin\.com\/?$/)) {
    monitorFeed(linkedInCampaigns);
  } else if (currentUrl.includes('/in/')) {
    monitorProfile(linkedInCampaigns);
  } else if (currentUrl.includes('/jobs')) {
    monitorJobs(linkedInCampaigns);
  } else if (currentUrl.includes('/search/results/')) {
    monitorSearch(linkedInCampaigns);
  }
}

function monitorFeed(campaigns: Campaign[]) {
  console.log('EvenLeads: Monitoring LinkedIn feed');
  scanLinkedInPosts(campaigns);
  watchForNewPosts(campaigns);
}

function scanLinkedInPosts(campaigns: Campaign[]) {
  // LinkedIn post selectors (these may need updates based on LinkedIn's DOM)
  const posts = document.querySelectorAll('.feed-shared-update-v2, [data-id^="urn:li:activity"]');

  console.log(`EvenLeads: Found ${posts.length} LinkedIn posts to scan`);

  posts.forEach((post) => {
    try {
      // Get post text content
      const contentElement = post.querySelector('.feed-shared-text, .update-components-text, .feed-shared-inline-show-more-text');
      const content = contentElement?.textContent?.trim() || '';

      // Get author
      const authorElement = post.querySelector('.update-components-actor__name, .feed-shared-actor__name, .update-components-actor__title');
      const author = authorElement?.textContent?.trim() || 'Unknown';

      // Get post URL
      const linkElement = post.querySelector('a[href*="/posts/"], a[href*="/activity-"]');
      const href = linkElement?.getAttribute('href') || '';
      const postUrl = href.startsWith('http') ? href : `https://www.linkedin.com${href}`;

      // Extract post ID
      const postId = href.match(/activity-(\d+)/)?.[1] || `linkedin_${Date.now()}`;

      // Get title (first line or sentence)
      const title = content.split('\n')[0].substring(0, 200) || 'LinkedIn Post';

      const fullText = content.toLowerCase();

      // Check each campaign
      campaigns.forEach((campaign) => {
        const matchedKeywords = campaign.keywords.filter((keyword) =>
          fullText.includes(keyword.toLowerCase())
        );

        if (matchedKeywords.length > 0) {
          const lead: Partial<Lead> = {
            platform: 'linkedin',
            platform_id: postId,
            title: title,
            description: content.substring(0, 1000),
            url: postUrl,
            author: author,
            matched_keywords: matchedKeywords,
            confidence_score: calculateConfidence(matchedKeywords, campaign.keywords),
          };

          submitLead(campaign.id, lead);
        }
      });
    } catch (error) {
      console.error('EvenLeads: Error scanning LinkedIn post', error);
    }
  });
}

function watchForNewPosts(campaigns: Campaign[]) {
  const observer = new MutationObserver((mutations) => {
    let hasNewPosts = false;

    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          const element = node as Element;
          if (
            element.classList.contains('feed-shared-update-v2') ||
            element.getAttribute('data-id')?.startsWith('urn:li:activity')
          ) {
            hasNewPosts = true;
          }
        }
      });
    });

    if (hasNewPosts) {
      setTimeout(() => scanLinkedInPosts(campaigns), 1000);
    }
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });
}

function monitorProfile(campaigns: Campaign[]) {
  console.log('EvenLeads: Monitoring LinkedIn profile');
  // Profile posts are similar to feed posts
  scanLinkedInPosts(campaigns);
}

function monitorJobs(campaigns: Campaign[]) {
  console.log('EvenLeads: Monitoring LinkedIn jobs');
  // Jobs can be monitored for relevant opportunities
  scanJobListings(campaigns);
}

function monitorSearch(campaigns: Campaign[]) {
  console.log('EvenLeads: Monitoring LinkedIn search results');
  // Search results can contain posts or profiles
  scanLinkedInPosts(campaigns);
}

function scanJobListings(campaigns: Campaign[]) {
  const jobCards = document.querySelectorAll('.job-card-container, .jobs-search-results__list-item');

  jobCards.forEach((card) => {
    try {
      const titleElement = card.querySelector('.job-card-list__title, .job-card-container__link');
      const title = titleElement?.textContent?.trim() || '';

      const descElement = card.querySelector('.job-card-list__footer-wrapper, .job-card-container__metadata-wrapper');
      const description = descElement?.textContent?.trim() || '';

      const linkElement = card.querySelector('a');
      const href = linkElement?.getAttribute('href') || '';
      const jobUrl = href.startsWith('http') ? href : `https://www.linkedin.com${href}`;

      const jobId = href.match(/jobs\/view\/(\d+)/)?.[1] || `job_${Date.now()}`;

      const fullText = `${title} ${description}`.toLowerCase();

      campaigns.forEach((campaign) => {
        const matchedKeywords = campaign.keywords.filter((keyword) =>
          fullText.includes(keyword.toLowerCase())
        );

        if (matchedKeywords.length > 0) {
          const lead: Partial<Lead> = {
            platform: 'linkedin',
            platform_id: jobId,
            title: title,
            description: description.substring(0, 1000),
            url: jobUrl,
            author: 'LinkedIn Job',
            matched_keywords: matchedKeywords,
            confidence_score: calculateConfidence(matchedKeywords, campaign.keywords),
          };

          submitLead(campaign.id, lead);
        }
      });
    } catch (error) {
      console.error('EvenLeads: Error scanning job listing', error);
    }
  });
}

function calculateConfidence(matchedKeywords: string[], allKeywords: string[]): number {
  const matchRatio = matchedKeywords.length / allKeywords.length;
  return Math.min(Math.round(matchRatio * 10) + 5, 10);
}

async function submitLead(campaignId: number, lead: Partial<Lead>) {
  try {
    const response = await chrome.runtime.sendMessage({
      type: 'SUBMIT_LEAD',
      payload: { campaignId, lead },
    });

    if (response?.error) {
      console.error('EvenLeads: Failed to submit lead:', response.error);
    } else {
      console.log('EvenLeads: Lead submitted successfully', lead);
      showLeadDetectedNotification(lead.title || 'New lead detected');
    }
  } catch (error) {
    console.error('EvenLeads: Failed to submit lead', error);
  }
}

function showLeadDetectedNotification(title: string) {
  const notification = document.createElement('div');
  notification.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 10000;
    padding: 12px 16px;
    background: linear-gradient(135deg, #000000 0%, #333333 100%);
    color: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 13px;
    font-weight: 500;
    max-width: 300px;
    animation: slideIn 0.3s ease-out;
  `;

  notification.innerHTML = `
    <div style="display: flex; align-items: center; gap: 8px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
      </svg>
      <span>Lead detected: ${title.substring(0, 50)}${title.length > 50 ? '...' : ''}</span>
    </div>
  `;

  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease-out';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(400px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(400px);
      opacity: 0;
    }
  }
  @keyframes slideInFromRight {
    from {
      transform: translateX(400px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  @keyframes slideOutToRight {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(400px);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);
