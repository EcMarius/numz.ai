import { defineConfig } from 'wxt';
import react from '@vitejs/plugin-react';

export default defineConfig({
  manifest: {
    name: 'EvenLeads',
    description: 'Monitor and collect leads from Facebook, LinkedIn, Reddit, Fiverr, and Upwork',
    version: '1.0.0',
    permissions: [
      'storage',
      'tabs',
      'activeTab',
      'notifications',
      'contextMenus',
      'alarms',
    ],
    host_permissions: [
      'https://*.facebook.com/*',
      'https://*.linkedin.com/*',
      'https://*.reddit.com/*',
      'https://*.fiverr.com/*',
      'https://*.upwork.com/*',
      'http://localhost:8000/*',
      'https://localhost:8000/*',
      'https://*.ngrok.io/*',           // ngrok v2 domains
      'https://*.ngrok-free.app/*',     // ngrok v3 domains
      'https://evenleads.com/*',
      'https://*.evenleads.com/*'
    ],
    action: {
      default_title: 'EvenLeads - Toggle Sidebar (Ctrl+Shift+L)',
      // Don't set default_popup - we want to handle clicks in background script
    },
    icons: {
      16: 'icon/16.png',
      32: 'icon/32.png',
      48: 'icon/48.png',
      128: 'icon/128.png',
    },
    commands: {
      'toggle-sidebar': {
        suggested_key: {
          default: 'Ctrl+Shift+L',
          mac: 'Command+Shift+L',
        },
        description: 'Toggle EvenLeads sidebar',
      },
    },
    web_accessible_resources: [
      {
        resources: [
          'assets/*',
          'oauth-callback.html',
          'favicon-inverted.png',
          'favicon.png',
          'evenleads-logo-dark.png',
          'evenleads-logo-dark.svg',
          'icon/*.png'
        ],
        matches: [
          '*://*.reddit.com/*',
          '*://*.facebook.com/*',
          '*://*.twitter.com/*',
          '*://*.x.com/*',
          '*://*.fiverr.com/*',
          '*://*.upwork.com/*',
          '*://*.linkedin.com/*',
          '*://evenleads.com/*',
          '*://*.evenleads.com/*',
        ], // Include all platforms
      },
    ],
  },
  vite: () => ({
    plugins: [react()],
  }),
});
