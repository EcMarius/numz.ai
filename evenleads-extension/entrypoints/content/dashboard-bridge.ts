/**
 * Dashboard Bridge Content Script
 *
 * This script runs on the EvenLeads dashboard (evenleads.com) and facilitates
 * communication between the web dashboard and the browser extension.
 */

export default defineContentScript({
  matches: ['*://evenleads.com/*', '*://*.evenleads.com/*', '*://localhost/*'],
  main() {
    console.log('[EvenLeads Extension] Dashboard bridge loaded');

    // Listen for messages from the dashboard page
    document.addEventListener('evenleads-dashboard-message', async (event: any) => {
      const { type, campaignId, autoSync, timestamp } = event.detail || {};

      console.log('[EvenLeads Extension] Received message from dashboard:', { type, campaignId, autoSync });

      switch (type) {
        case 'EXTENSION_PING':
          // Respond to ping to confirm extension is installed
          document.dispatchEvent(new CustomEvent('evenleads-extension-response', {
            detail: {
              type: 'EXTENSION_PONG',
              timestamp: Date.now(),
            },
          }));
          break;

        case 'OPEN_SIDEBAR':
          try {
            // Send message to background script to open sidebar
            await browser.runtime.sendMessage({
              type: 'OPEN_SIDEBAR',
              campaignId,
              autoSync,
            });

            // Confirm sidebar opened
            document.dispatchEvent(new CustomEvent('evenleads-extension-response', {
              detail: {
                type: 'SIDEBAR_OPENED',
                campaignId,
                timestamp: Date.now(),
              },
            }));
          } catch (error) {
            console.error('[EvenLeads Extension] Error opening sidebar:', error);
          }
          break;

        case 'TRIGGER_SYNC':
          try {
            // Send message to background script to trigger sync
            await browser.runtime.sendMessage({
              type: 'RUN_SYNC',
              campaignId,
            });

            // Confirm sync started
            document.dispatchEvent(new CustomEvent('evenleads-extension-response', {
              detail: {
                type: 'SYNC_STARTED',
                campaignId,
                timestamp: Date.now(),
              },
            }));
          } catch (error) {
            console.error('[EvenLeads Extension] Error triggering sync:', error);
          }
          break;

        default:
          console.warn('[EvenLeads Extension] Unknown message type:', type);
      }
    });

    console.log('[EvenLeads Extension] Dashboard bridge ready');
  },
});
