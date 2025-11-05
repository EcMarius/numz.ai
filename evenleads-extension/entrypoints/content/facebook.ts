/**
 * Facebook Content Script
 *
 * Since Facebook's TOS prohibits automated scraping, this script provides
 * a manual submission UI for users to submit posts they find relevant.
 */

import { defineContentScript } from 'wxt/sandbox';
import { authStorage, campaignStorage } from '../../utils/storage';
import { initManualSubmitUI } from './manual-submit';

export default defineContentScript({
  matches: ['*://*.facebook.com/*', '*://*.fb.com/*'],
  async main() {
    console.log('EvenLeads: Facebook content script loaded');

    // Check if user is authenticated and has Facebook campaigns
    const isAuth = await authStorage.isAuthenticated();
    if (!isAuth) {
      console.log('EvenLeads: Not authenticated');
      return;
    }

    const campaigns = await campaignStorage.get();
    const facebookCampaigns = campaigns.filter(c => c.platforms.includes('facebook'));

    if (facebookCampaigns.length === 0) {
      console.log('EvenLeads: No Facebook campaigns found');
      return;
    }

    // Initialize manual submission UI
    // Only show on group pages or pages where posts are visible
    const currentUrl = window.location.href;
    if (currentUrl.includes('/groups/') || currentUrl.includes('/posts/')) {
      initManualSubmitUI('facebook');
      console.log('EvenLeads: Manual submission UI initialized for Facebook');
    }

    // Watch for navigation (Facebook is a SPA)
    let lastUrl = location.href;
    new MutationObserver(() => {
      const url = location.href;
      if (url !== lastUrl) {
        lastUrl = url;

        // Show/hide UI based on page
        const container = document.getElementById('evenleads-manual-submit');
        if (url.includes('/groups/') || url.includes('/posts/')) {
          if (!container) {
            initManualSubmitUI('facebook');
          }
        } else {
          container?.remove();
        }
      }
    }).observe(document, { subtree: true, childList: true });
  },
});
