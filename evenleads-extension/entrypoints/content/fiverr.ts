import { defineContentScript } from 'wxt/sandbox';
import { authStorage, campaignStorage } from '../../utils/storage';
import type { Campaign, Lead } from '../../types';

export default defineContentScript({
  matches: ['*://*.fiverr.com/*'],
  main() {
    console.log('EvenLeads: Fiverr content script loaded');

    // Initialize Fiverr monitoring
    initFiverrMonitoring();

    // Watch for navigation changes (Fiverr is a SPA)
    let lastUrl = location.href;
    new MutationObserver(() => {
      const url = location.href;
      if (url !== lastUrl) {
        lastUrl = url;
        initFiverrMonitoring();
      }
    }).observe(document, { subtree: true, childList: true });
  },
});

async function initFiverrMonitoring() {
  const isAuth = await authStorage.isAuthenticated();
  if (!isAuth) {
    console.log('EvenLeads: Not authenticated, skipping monitoring');
    return;
  }

  // Get selected campaign or all Fiverr campaigns
  const selectedCampaign = await campaignStorage.getSelectedCampaign();
  const allCampaigns = await campaignStorage.get();

  const fiverrCampaigns = selectedCampaign
    ? [selectedCampaign].filter(c => c.platforms.includes('fiverr'))
    : allCampaigns.filter(c => c.platforms.includes('fiverr'));

  if (fiverrCampaigns.length === 0) {
    console.log('EvenLeads: No Fiverr campaigns active');
    return;
  }

  console.log(`EvenLeads: Monitoring Fiverr for ${fiverrCampaigns.length} campaign(s)`);

  // Detect page type and initialize appropriate monitoring
  const currentUrl = window.location.href;

  if (currentUrl.includes('/gigs') || currentUrl.includes('/search')) {
    // Gig search results
    monitorGigSearch(fiverrCampaigns);
  } else if (currentUrl.includes('/categories/')) {
    // Category page
    monitorCategoryPage(fiverrCampaigns);
  } else if (currentUrl.match(/fiverr\.com\/[^/]+\/[^/]+/)) {
    // Single gig page
    monitorSingleGig(fiverrCampaigns);
  }
}

function monitorGigSearch(campaigns: Campaign[]) {
  console.log('EvenLeads: Monitoring Fiverr gig search');
  scanGigs(campaigns);
  watchForNewGigs(campaigns);
}

function monitorCategoryPage(campaigns: Campaign[]) {
  console.log('EvenLeads: Monitoring Fiverr category page');
  scanGigs(campaigns);
  watchForNewGigs(campaigns);
}

function monitorSingleGig(campaigns: Campaign[]) {
  console.log('EvenLeads: Monitoring single Fiverr gig');
  // For single gig pages, we can extract more detailed information
  scanDetailedGig(campaigns);
}

function scanGigs(campaigns: Campaign[]) {
  // Fiverr gig card selectors
  const gigCards = document.querySelectorAll(
    '[data-gig-id], .gig-card-layout, article.gig-card-v2'
  );

  console.log(`EvenLeads: Found ${gigCards.length} gigs to scan`);

  gigCards.forEach((card) => {
    try {
      // Get gig ID
      const gigId = card.getAttribute('data-gig-id') ||
                    card.querySelector('[data-gig-id]')?.getAttribute('data-gig-id') ||
                    `fiverr_${Date.now()}`;

      // Get gig title
      const titleElement = card.querySelector('.gig-card-layout__title, h3 a, .gig-title');
      const title = titleElement?.textContent?.trim() || '';

      // Get gig description/subtitle
      const descElement = card.querySelector('.gig-card-layout__subtitle, .gig-description, p');
      const description = descElement?.textContent?.trim() || '';

      // Get seller name
      const sellerElement = card.querySelector('.seller-name, .username, [data-username]');
      const seller = sellerElement?.textContent?.trim() ||
                     sellerElement?.getAttribute('data-username') ||
                     'Unknown Seller';

      // Get gig URL
      const linkElement = card.querySelector('a[href*="/gigs/"]') ||
                         card.querySelector('a.gig-link');
      const href = linkElement?.getAttribute('href') || '';
      const gigUrl = href.startsWith('http') ? href : `https://www.fiverr.com${href}`;

      // Get pricing
      const priceElement = card.querySelector('.price, [data-price], .starting-at');
      const price = priceElement?.textContent?.trim() || '';

      // Get rating if available
      const ratingElement = card.querySelector('.gig-rating, .star-rating');
      const rating = ratingElement?.textContent?.trim() || '';

      // Get category/tags if available
      const tagElements = card.querySelectorAll('.gig-tag, .tag');
      const tags = Array.from(tagElements).map(el => el.textContent?.trim() || '').filter(Boolean);

      const fullText = `${title} ${description} ${tags.join(' ')}`.toLowerCase();

      // Check each campaign
      campaigns.forEach((campaign) => {
        const matchedKeywords = campaign.keywords.filter((keyword) =>
          fullText.includes(keyword.toLowerCase())
        );

        if (matchedKeywords.length > 0) {
          const lead: Partial<Lead> = {
            platform: 'fiverr',
            platform_id: gigId,
            title: title || 'Fiverr Gig',
            description: `${description}\n\nPrice: ${price}\nRating: ${rating}\nTags: ${tags.join(', ')}`.substring(0, 1000),
            url: gigUrl,
            author: seller,
            matched_keywords: matchedKeywords,
            confidence_score: calculateConfidence(matchedKeywords, campaign.keywords),
            fiverr_gig_id: gigId,
          };

          submitLead(campaign.id, lead);
        }
      });
    } catch (error) {
      console.error('EvenLeads: Error scanning gig', error);
    }
  });
}

function scanDetailedGig(campaigns: Campaign[]) {
  try {
    // Extract detailed information from single gig page
    const titleElement = document.querySelector('h1.text-display-5, h1[data-gig-title]');
    const title = titleElement?.textContent?.trim() || '';

    const descElement = document.querySelector('.description-content, [data-gig-description]');
    const description = descElement?.textContent?.trim() || '';

    const sellerElement = document.querySelector('.seller-name, [data-username]');
    const seller = sellerElement?.textContent?.trim() || 'Unknown Seller';

    // Get URL
    const gigUrl = window.location.href;

    // Extract gig ID from URL
    const gigId = gigUrl.match(/gigs\/([^/?]+)/)?.[1] || `fiverr_${Date.now()}`;

    // Get pricing packages
    const packages = document.querySelectorAll('.package-type, [data-package]');
    const priceInfo = Array.from(packages).map(pkg => {
      const pkgTitle = pkg.querySelector('.package-title')?.textContent?.trim() || '';
      const pkgPrice = pkg.querySelector('.price')?.textContent?.trim() || '';
      return `${pkgTitle}: ${pkgPrice}`;
    }).join(', ');

    // Get tags/categories
    const tagElements = document.querySelectorAll('.gig-tag, .tag-link, [data-tag]');
    const tags = Array.from(tagElements).map(el => el.textContent?.trim() || '').filter(Boolean);

    const fullText = `${title} ${description} ${tags.join(' ')}`.toLowerCase();

    // Check each campaign
    campaigns.forEach((campaign) => {
      const matchedKeywords = campaign.keywords.filter((keyword) =>
        fullText.includes(keyword.toLowerCase())
      );

      if (matchedKeywords.length > 0) {
        const lead: Partial<Lead> = {
          platform: 'fiverr',
          platform_id: gigId,
          title: title || 'Fiverr Gig',
          description: `${description}\n\n${priceInfo}\n\nTags: ${tags.join(', ')}`.substring(0, 1000),
          url: gigUrl,
          author: seller,
          matched_keywords: matchedKeywords,
          confidence_score: calculateConfidence(matchedKeywords, campaign.keywords),
          fiverr_gig_id: gigId,
        };

        submitLead(campaign.id, lead);
      }
    });
  } catch (error) {
    console.error('EvenLeads: Error scanning detailed gig', error);
  }
}

function watchForNewGigs(campaigns: Campaign[]) {
  const observer = new MutationObserver((mutations) => {
    let hasNewGigs = false;

    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          const element = node as Element;
          if (
            element.classList.contains('gig-card-layout') ||
            element.classList.contains('gig-card-v2') ||
            element.hasAttribute('data-gig-id') ||
            element.tagName.toLowerCase() === 'article'
          ) {
            hasNewGigs = true;
          }
        }
      });
    });

    if (hasNewGigs) {
      setTimeout(() => scanGigs(campaigns), 1000);
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
    background: linear-gradient(135deg, #1DBF73 0%, #00B058 100%);
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
