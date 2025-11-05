import { defineContentScript } from 'wxt/sandbox';
import { authStorage, campaignStorage } from '../../utils/storage';
import type { Campaign, Lead } from '../../types';

export default defineContentScript({
  matches: ['*://*.twitter.com/*', '*://*.x.com/*'],
  main() {
    console.log('EvenLeads: Twitter/X content script loaded');

    // Initialize Twitter monitoring
    initTwitterMonitoring();

    // Watch for navigation changes (Twitter is a SPA)
    let lastUrl = location.href;
    new MutationObserver(() => {
      const url = location.href;
      if (url !== lastUrl) {
        lastUrl = url;
        initTwitterMonitoring();
      }
    }).observe(document, { subtree: true, childList: true });
  },
});

async function initTwitterMonitoring() {
  const isAuth = await authStorage.isAuthenticated();
  if (!isAuth) {
    console.log('EvenLeads: Not authenticated, skipping monitoring');
    return;
  }

  // Get selected campaign or all Twitter campaigns
  const selectedCampaign = await campaignStorage.getSelectedCampaign();
  const allCampaigns = await campaignStorage.get();

  let twitterCampaigns = selectedCampaign
    ? [selectedCampaign].filter(c => c.platforms.includes('twitter') || c.platforms.includes('x'))
    : allCampaigns.filter(c => c.platforms.includes('twitter') || c.platforms.includes('x'));

  if (twitterCampaigns.length === 0) {
    console.log('EvenLeads: No Twitter/X campaigns active');
    return;
  }

  // Extract current community from URL (if in a community)
  const communityMatch = window.location.href.match(/\/i\/communities\/(\d+)/);
  const currentCommunity = communityMatch ? communityMatch[1] : null;

  console.log(`EvenLeads: Current community: ${currentCommunity || 'none (home timeline)'}`);

  // Filter campaigns by community if we're in a specific community
  if (currentCommunity) {
    const unfilteredCount = twitterCampaigns.length;
    twitterCampaigns = twitterCampaigns.filter(campaign => {
      // If campaign has no community restrictions, include it
      if (!campaign.twitter_communities || campaign.twitter_communities.length === 0) {
        return true;
      }
      // Check if current community is in the campaign's target list
      const isTargetCommunity = campaign.twitter_communities.includes(currentCommunity);
      if (!isTargetCommunity) {
        console.log(`EvenLeads: Campaign "${campaign.name}" doesn't target community ${currentCommunity}, skipping`);
      }
      return isTargetCommunity;
    });

    if (unfilteredCount > 0 && twitterCampaigns.length === 0) {
      console.log(`EvenLeads: No campaigns targeting community ${currentCommunity}`);
      return;
    }
  }

  console.log(`EvenLeads: Monitoring Twitter/X for ${twitterCampaigns.length} campaign(s)`);

  // Detect page type and initialize appropriate monitoring
  const currentUrl = window.location.href;

  if (currentUrl.includes('/status/')) {
    // Single tweet view
    monitorTweet(twitterCampaigns, currentCommunity);
  } else if (currentUrl.match(/\/[A-Za-z0-9_]+$/)) {
    // Profile view
    monitorProfile(twitterCampaigns, currentCommunity);
  } else {
    // Home timeline or search results
    monitorTimeline(twitterCampaigns, currentCommunity);
  }
}

function monitorTimeline(campaigns: Campaign[], currentCommunity: string | null) {
  console.log('EvenLeads: Monitoring Twitter timeline');
  scanTweets(campaigns, currentCommunity);
  watchForNewTweets(campaigns, currentCommunity);
}

function monitorTweet(campaigns: Campaign[], currentCommunity: string | null) {
  console.log('EvenLeads: Monitoring single tweet');
  scanTweets(campaigns, currentCommunity);
}

function monitorProfile(campaigns: Campaign[], currentCommunity: string | null) {
  console.log('EvenLeads: Monitoring Twitter profile');
  scanTweets(campaigns, currentCommunity);
  watchForNewTweets(campaigns, currentCommunity);
}

function scanTweets(campaigns: Campaign[], currentCommunity: string | null) {
  // Twitter/X tweet selectors (updated for new design)
  const tweets = document.querySelectorAll('article[data-testid="tweet"]');

  console.log(`EvenLeads: Found ${tweets.length} tweets to scan`);

  tweets.forEach((tweet) => {
    try {
      // Get tweet text content
      const contentElement = tweet.querySelector('[data-testid="tweetText"]');
      const content = contentElement?.textContent?.trim() || '';

      // Get author username and display name
      const authorElement = tweet.querySelector('[data-testid="User-Name"]');
      const authorName = authorElement?.textContent?.trim() || 'Unknown';

      // Extract username from link
      const authorLink = tweet.querySelector('a[href^="/"][role="link"]');
      const username = authorLink?.getAttribute('href')?.replace('/', '') || '';

      // Get tweet URL
      const timeElement = tweet.querySelector('time');
      const tweetLink = timeElement?.closest('a');
      const href = tweetLink?.getAttribute('href') || '';
      const tweetUrl = href.startsWith('http') ? href : `https://twitter.com${href}`;

      // Extract tweet ID from URL
      const tweetId = href.match(/\/status\/(\d+)/)?.[1] || `twitter_${Date.now()}`;

      // Get engagement metrics
      const replyCount = tweet.querySelector('[data-testid="reply"]')?.textContent?.trim() || '0';
      const retweetCount = tweet.querySelector('[data-testid="retweet"]')?.textContent?.trim() || '0';
      const likeCount = tweet.querySelector('[data-testid="like"]')?.textContent?.trim() || '0';

      // Get title (first line or limited content)
      const title = content.split('\n')[0].substring(0, 200) || 'Twitter Post';

      const fullText = content.toLowerCase();

      // Check each campaign
      campaigns.forEach((campaign) => {
        // Double-check community filtering (should already be filtered, but extra safety)
        if (campaign.twitter_communities && campaign.twitter_communities.length > 0 && currentCommunity) {
          const isTargetCommunity = campaign.twitter_communities.includes(currentCommunity);
          if (!isTargetCommunity) {
            return; // Skip this campaign for this tweet
          }
        }

        const matchedKeywords = campaign.keywords.filter((keyword) =>
          fullText.includes(keyword.toLowerCase())
        );

        if (matchedKeywords.length > 0) {
          const lead: Partial<Lead> = {
            platform: 'twitter',
            platform_id: tweetId,
            title: title,
            description: content.substring(0, 1000),
            url: tweetUrl,
            author: username || authorName,
            twitter_community: currentCommunity || undefined, // Add community field
            matched_keywords: matchedKeywords,
            confidence_score: calculateConfidence(matchedKeywords, campaign.keywords),
          };

          submitLead(campaign.id, lead);
        }
      });
    } catch (error) {
      console.error('EvenLeads: Error scanning tweet', error);
    }
  });
}

function watchForNewTweets(campaigns: Campaign[], currentCommunity: string | null) {
  const observer = new MutationObserver((mutations) => {
    let hasNewTweets = false;

    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          const element = node as Element;
          if (
            element.getAttribute('data-testid') === 'tweet' ||
            element.tagName.toLowerCase() === 'article'
          ) {
            hasNewTweets = true;
          }
        }
      });
    });

    if (hasNewTweets) {
      setTimeout(() => scanTweets(campaigns, currentCommunity), 1000);
    }
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true,
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
`;
document.head.appendChild(style);
