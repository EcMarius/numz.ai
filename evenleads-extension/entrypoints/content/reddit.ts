import { defineContentScript } from 'wxt/sandbox';
import { authStorage, campaignStorage } from '../../utils/storage';
import type { Campaign, Lead } from '../../types';

export default defineContentScript({
  matches: ['*://*.reddit.com/*'],
  main() {
    console.log('EvenLeads: Reddit content script loaded');

    // Initialize Reddit monitoring
    initRedditMonitoring();

    // Watch for navigation changes (Reddit is a SPA)
    let lastUrl = location.href;
    new MutationObserver(() => {
      const url = location.href;
      if (url !== lastUrl) {
        lastUrl = url;
        initRedditMonitoring();
      }
    }).observe(document, { subtree: true, childList: true });
  },
});

async function initRedditMonitoring() {
  const isAuth = await authStorage.isAuthenticated();
  if (!isAuth) {
    console.log('EvenLeads: Not authenticated, skipping monitoring');
    return;
  }

  // Get selected campaign or all Reddit campaigns
  const selectedCampaign = await campaignStorage.getSelectedCampaign();
  const allCampaigns = await campaignStorage.get();

  let redditCampaigns = selectedCampaign
    ? [selectedCampaign].filter(c => c.platforms.includes('reddit'))
    : allCampaigns.filter(c => c.platforms.includes('reddit'));

  if (redditCampaigns.length === 0) {
    console.log('EvenLeads: No Reddit campaigns active');
    return;
  }

  // Extract current subreddit from URL
  const subredditMatch = window.location.href.match(/\/r\/([^/?]+)/);
  const currentSubreddit = subredditMatch ? subredditMatch[1].toLowerCase() : null;

  console.log(`EvenLeads: Current subreddit: ${currentSubreddit || 'home feed'}`);

  // Filter campaigns by subreddit if we're in a specific subreddit
  if (currentSubreddit) {
    const unfilteredCount = redditCampaigns.length;
    redditCampaigns = redditCampaigns.filter(campaign => {
      // If campaign has no subreddit restrictions, include it
      if (!campaign.reddit_subreddits || campaign.reddit_subreddits.length === 0) {
        return true;
      }
      // Check if current subreddit is in the campaign's target list
      const isTargetSubreddit = campaign.reddit_subreddits.some(
        sub => sub.toLowerCase().replace(/^r\//, '') === currentSubreddit
      );
      if (!isTargetSubreddit) {
        console.log(`EvenLeads: Campaign "${campaign.name}" doesn't target r/${currentSubreddit}, skipping`);
      }
      return isTargetSubreddit;
    });

    if (unfilteredCount > 0 && redditCampaigns.length === 0) {
      console.log(`EvenLeads: No campaigns targeting r/${currentSubreddit}`);
      return;
    }
  }

  console.log(`EvenLeads: Monitoring Reddit for ${redditCampaigns.length} campaign(s)`);

  // Detect page type and monitor accordingly
  const currentUrl = window.location.href;

  if (currentUrl.includes('/r/') && currentUrl.includes('/comments/')) {
    // Single post view
    monitorPost(redditCampaigns);
  } else if (currentUrl.includes('/r/')) {
    // Subreddit feed
    monitorSubredditFeed(redditCampaigns);
  } else if (currentUrl.match(/reddit\.com\/?$/)) {
    // Home feed
    monitorFeed(redditCampaigns);
  }
}

function monitorFeed(campaigns: Campaign[]) {
  console.log('EvenLeads: Monitoring Reddit home feed');
  scanPosts(campaigns);
  watchForNewPosts(campaigns);
}

function monitorSubredditFeed(campaigns: Campaign[]) {
  console.log('EvenLeads: Monitoring subreddit feed');
  scanPosts(campaigns);
  watchForNewPosts(campaigns);
}

function monitorPost(campaigns: Campaign[]) {
  console.log('EvenLeads: Monitoring single post');
  scanPosts(campaigns);
}

function scanPosts(campaigns: Campaign[]) {
  // Reddit post selectors (update these based on Reddit's current DOM structure)
  const posts = document.querySelectorAll('[data-testid="post-container"], shreddit-post');

  console.log(`EvenLeads: Found ${posts.length} posts to scan`);

  posts.forEach((post) => {
    try {
      // Extract post data
      const titleElement = post.querySelector('h3, [slot="title"]');
      const title = titleElement?.textContent?.trim() || '';

      const contentElement = post.querySelector('[data-test-id="post-content"]');
      const content = contentElement?.textContent?.trim() || '';

      // Get post URL
      const linkElement = post.querySelector('a[href*="/comments/"]');
      const postUrl = linkElement?.getAttribute('href') || '';
      const fullUrl = postUrl.startsWith('http') ? postUrl : `https://www.reddit.com${postUrl}`;

      // Extract subreddit and post ID
      const urlMatch = postUrl.match(/\/r\/([^/]+)\/comments\/([^/]+)/);
      const subreddit = urlMatch ? urlMatch[1] : '';
      const postId = urlMatch ? urlMatch[2] : '';

      // Get author
      const authorElement = post.querySelector('[data-testid="post_author_link"], a[href*="/user/"]');
      const author = authorElement?.textContent?.trim().replace('u/', '') || 'unknown';

      const fullText = `${title} ${content}`.toLowerCase();
      const normalizedSubreddit = subreddit.toLowerCase();

      // Check each campaign
      campaigns.forEach((campaign) => {
        // Double-check subreddit filtering (should already be filtered, but extra safety)
        if (campaign.reddit_subreddits && campaign.reddit_subreddits.length > 0 && subreddit) {
          const isTargetSubreddit = campaign.reddit_subreddits.some(
            sub => sub.toLowerCase().replace(/^r\//, '') === normalizedSubreddit
          );
          if (!isTargetSubreddit) {
            return; // Skip this campaign for this post
          }
        }

        const matchedKeywords = campaign.keywords.filter((keyword) =>
          fullText.includes(keyword.toLowerCase())
        );

        if (matchedKeywords.length > 0) {
          const lead: Partial<Lead> = {
            platform: 'reddit',
            platform_id: postId,
            title: title,
            description: content.substring(0, 1000),
            url: fullUrl,
            author: author,
            subreddit: subreddit,
            matched_keywords: matchedKeywords,
            confidence_score: calculateConfidence(matchedKeywords, campaign.keywords),
          };

          // Submit lead
          submitLead(campaign.id, lead);
        }
      });
    } catch (error) {
      console.error('EvenLeads: Error scanning post', error);
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
            element.getAttribute('data-testid') === 'post-container' ||
            element.tagName.toLowerCase() === 'shreddit-post'
          ) {
            hasNewPosts = true;
          }
        }
      });
    });

    if (hasNewPosts) {
      // Scan new posts after a short delay
      setTimeout(() => scanPosts(campaigns), 1000);
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

      // Show subtle notification
      showLeadDetectedNotification(lead.title || 'New lead detected');
    }
  } catch (error) {
    console.error('EvenLeads: Failed to submit lead', error);
  }
}

function showLeadDetectedNotification(title: string) {
  // Create a temporary notification element
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

  // Remove after 3 seconds
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
