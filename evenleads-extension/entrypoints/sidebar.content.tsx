import { defineContentScript } from 'wxt/sandbox';
import React, { useState, useEffect, useCallback, useRef } from 'react';
import ReactDOM from 'react-dom/client';
import { X, Loader2, LayoutDashboard, Target, Users as UsersIcon, User, ListChecks, Settings } from 'lucide-react';
import { authStorage, campaignStorage } from '../utils/storage';
import { api } from '../utils/api';
import { getApiBaseUrl } from '../config';
import { detectPageType } from '../utils/pageDetection';
import type { AuthState, Campaign, Platform } from '../types';
import CampaignList from '../components/CampaignList';
import AccountInfo from '../components/AccountInfo';
import Dashboard from '../components/Dashboard';
import LeadsView from '../components/LeadsView';
import LeadCaptureView from '../components/LeadCaptureView';
import PlatformDetector from '../components/PlatformDetector';
import SettingsView from '../components/SettingsView';
import SyncProgressBanner from '../components/SyncProgress';
import DevModePanel from '../components/DevMode/DevModePanel';
import { devModeStorage } from '../utils/storage';
import { startSync } from '../utils/runSync';
import type { SyncProgress as SyncProgressData, SyncConfig } from '../utils/runSync';

type View = 'oauth' | 'welcome' | 'dashboard' | 'campaigns' | 'leads' | 'leadCapture' | 'account' | 'settings';

export default defineContentScript({
  matches: ['<all_urls>'],
  cssInjectionMode: 'manual', // Manual mode - don't let WXT auto-inject CSS
  runAt: 'document_idle',

  async main(ctx) {
    console.log('[EvenLeads] 🔍 STEP 0: Content script main() executed');
    console.log('[EvenLeads] 🔍 STEP 0: Timestamp:', new Date().toISOString());
    console.log('[EvenLeads] 🔍 STEP 0: Performance now:', performance.now().toFixed(2), 'ms');
    console.log('[EvenLeads] 🔍 STEP 1: main() started');

    // Skip on extension pages and certain protected pages
    const url = window.location.href;
    console.log('[EvenLeads] 🔍 STEP 2: URL checked:', url);

    if (url.startsWith('chrome://') ||
        url.startsWith('chrome-extension://') ||
        url.startsWith('about:') ||
        url.startsWith('edge://') ||
        url.startsWith('view-source:')) {
      return;
    }

    // LinkedIn detection
    const isLinkedIn = window.location.hostname.includes('linkedin.com');
    console.log('[EvenLeads] 🔍 STEP 3: LinkedIn check:', isLinkedIn);

    console.log('[EvenLeads] 🔍 STEP 4: Checking if document.body exists');
    // Wait for body to be ready
    if (!document.body) {
      console.log('[EvenLeads] 🔍 STEP 4a: Waiting for body...');
      await new Promise(resolve => {
        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', resolve);
        } else {
          resolve(null);
        }
      });
      console.log('[EvenLeads] 🔍 STEP 4b: Body ready');
    }

    console.log('[EvenLeads] 🔍 STEP 5: SKIPPING extension context check on LinkedIn');
    // Check if extension context is still valid (SKIP ON LINKEDIN - this might trigger detection)
    if (!isLinkedIn) {
      try {
        console.log('[EvenLeads] 🔍 STEP 5a: About to access chrome.runtime.id');
        const extensionId = chrome.runtime.id;
        console.log('[EvenLeads] 🔍 STEP 5b: Extension ID obtained:', extensionId);
        if (!extensionId) {
          throw new Error('Extension context invalid');
        }
        console.log('[EvenLeads] 🔍 STEP 5c: Extension context is valid');
      } catch (contextError) {
        console.log('[EvenLeads] 🔍 STEP 5d: Extension context invalid, showing notice');
      // Extension context is invalid - show reload notice and exit
      console.warn('[EvenLeads] Extension context invalidated. Page reload required.');

      // Create a small, unobtrusive reload notice
      const notice = document.createElement('div');
      notice.id = 'evenleads-reload-notice';
      notice.innerHTML = `
        <div style="position: fixed; top: 20px; right: 20px; z-index: 999999; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 16px 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 320px; cursor: pointer;" onclick="this.remove()">
          <div style="font-weight: 600; font-size: 14px; margin-bottom: 6px;">🔄 EvenLeads Extension Updated</div>
          <div style="font-size: 12px; opacity: 0.95; line-height: 1.4;">Please reload this page to use the latest version.</div>
          <div style="font-size: 11px; opacity: 0.8; margin-top: 8px; font-style: italic;">Click to dismiss</div>
        </div>
      `;
      document.body.appendChild(notice);

      // Auto-remove after 10 seconds
      setTimeout(() => notice.remove(), 10000);

      return; // Exit early - don't try to initialize
      }
    } else {
      console.log('[EvenLeads] 🔍 STEP 5: LinkedIn detected - skipping context validation');
    }

    console.log('[EvenLeads] 🔍 STEP 6: Setting up error handlers');
    try {
      // Debug stats tracking (for both LinkedIn and non-LinkedIn)
      const debugStats = {
        invalidRequestCount: 0,
        errorEventCount: 0,
        requestPatterns: new Map<string, number>(),
        lastErrors: [] as any[],
      };

      // ALWAYS log chrome-extension://invalid/ errors (even on LinkedIn) for debugging
      console.log('[EvenLeads] 🔍 STEP 6A: Adding error listener (works on all sites including LinkedIn)');
      window.addEventListener('error', (event) => {
        // Log chrome-extension://invalid/ errors
        if (event.message?.includes('chrome-extension://invalid') ||
            event.filename?.includes('chrome-extension://invalid')) {

          // Check if this is from OUR extension
          const ourExtensionId = chrome.runtime.id;
          const isOurExtension = event.filename?.includes(ourExtensionId) ||
                                 event.message?.includes(ourExtensionId);

          debugStats.invalidRequestCount++;
          debugStats.errorEventCount++;

          // Store last 10 errors for analysis
          debugStats.lastErrors.push({
            timestamp: new Date().toISOString(),
            message: event.message,
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno,
            target: event.target ? {
              tagName: (event.target as any).tagName,
              src: (event.target as any).src,
              href: (event.target as any).href,
            } : null,
          });
          if (debugStats.lastErrors.length > 10) {
            debugStats.lastErrors.shift();
          }

          // Log every 10th error (or first 3) to avoid spam
          if (debugStats.invalidRequestCount <= 3 || debugStats.invalidRequestCount % 10 === 0) {
            console.group(`%c[EvenLeads] 🚫 Invalid Extension Request #${debugStats.invalidRequestCount}`, 'color: #ff6b6b; font-weight: bold');
            console.log('Timestamp:', new Date().toISOString());
            console.log('Message:', event.message);
            console.log('Filename:', event.filename);
            console.log('Line:', event.lineno, 'Column:', event.colno);
            console.log('Total Count:', debugStats.invalidRequestCount);
            console.log('Is from OUR extension:', isOurExtension ? 'YES ✓' : 'NO ✗');
            console.log('Our Extension ID:', ourExtensionId);

            // Try to identify the initiator
            if (event.target) {
              const target = event.target as any;
              if (target.tagName) {
                console.log('Target Tag:', target.tagName);
                console.log('Target Src/Href:', target.src || target.href || 'N/A');
              }
            }

            // Track the pattern
            if (event.filename) {
              const pattern = event.filename.replace(/chrome-extension:\/\/invalid\//, '');
              debugStats.requestPatterns.set(pattern, (debugStats.requestPatterns.get(pattern) || 0) + 1);
              console.log('Pattern:', pattern);
              console.log('All patterns so far:', Array.from(debugStats.requestPatterns.entries()));
            }

            console.groupEnd();
          }

          // Make stats available globally for debugging
          (window as any).__evenleadsDebugStats = debugStats;
        }
      }, true); // Use capture phase

      // SKIP OTHER DEBUGGING CODE ON LINKEDIN - just load the UI
      if (isLinkedIn) {
        console.log('[EvenLeads] 🔍 LinkedIn detected - skipping other debug/event listeners');
      } else {
        console.log('[EvenLeads] 🔍 Not LinkedIn - setting up additional error handlers');
        // Add global error handlers to suppress known harmless errors
        let contextErrorCount = 0;
        const MAX_CONTEXT_ERRORS = 5; // Only log first 5 errors

        console.log('[EvenLeads] 🔍 STEP 8A: Not LinkedIn - adding context error listener');
        window.addEventListener('error', (event) => {
        // Handle extension context errors
        if (event.error?.message?.includes('Extension context invalidated') ||
            event.error?.message?.includes('message port closed') ||
            event.error?.message?.includes('Could not establish connection')) {

          contextErrorCount++;
          if (contextErrorCount <= MAX_CONTEXT_ERRORS) {
            console.warn('[EvenLeads] Suppressed context invalidation error:', event.error.message);
          } else if (contextErrorCount === MAX_CONTEXT_ERRORS + 1) {
            console.warn('[EvenLeads] Multiple context errors detected. Further errors will be silently suppressed. Please reload the page.');
          }

          event.preventDefault();
          return true;
        }
      }, true); // Use capture phase to catch early

      // Track clicked elements to help correlate with invalid requests (NOT on LinkedIn)
      if (!isLinkedIn) {
        document.addEventListener('click', (e) => {
          (window as any).__lastClickedElement = {
            tag: (e.target as Element)?.tagName,
            className: (e.target as Element)?.className,
            id: (e.target as Element)?.id,
            timestamp: new Date().toISOString()
          };
        }, true);
      }

      window.addEventListener('unhandledrejection', (event) => {
        if (event.reason?.message?.includes('Extension context invalidated') ||
            event.reason?.message?.includes('message port closed') ||
            event.reason?.message?.includes('Could not establish connection')) {

          contextErrorCount++;
          if (contextErrorCount <= MAX_CONTEXT_ERRORS) {
            console.warn('[EvenLeads] Suppressed unhandled rejection:', event.reason.message);
          }

          event.preventDefault(); // Prevent error from appearing in extension errors page
        }
      });
      } // Close else block (not LinkedIn)
      console.log('[EvenLeads] 🔍 STEP 8B: Event listeners setup complete (or skipped on LinkedIn)');

      // DON'T inject styles to document.head - this triggers LinkedIn detection!
      // Styles will be injected into Shadow DOM by WXT automatically

      // Dashboard Bridge: Listen for messages from EvenLeads dashboard
      if (url.includes('evenleads.com') || url.includes('localhost')) {
        console.log('[EvenLeads Extension] Dashboard bridge active');

        document.addEventListener('evenleads-dashboard-message', async (event: any) => {
          const { type, campaignId, autoSync } = event.detail || {};
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

            case 'GET_AUTH_STATE':
              // Respond with current authentication state
              (async () => {
                try {
                  const authState = await authStorage.get();
                  document.dispatchEvent(new CustomEvent('evenleads-extension-response', {
                    detail: {
                      type: 'AUTH_STATE',
                      authState: authState || { isAuthenticated: false },
                      timestamp: Date.now(),
                    },
                  }));
                } catch (error) {
                  console.error('[EvenLeads Extension] Error getting auth state:', error);
                  document.dispatchEvent(new CustomEvent('evenleads-extension-response', {
                    detail: {
                      type: 'AUTH_STATE',
                      authState: { isAuthenticated: false },
                      timestamp: Date.now(),
                    },
                  }));
                }
              })();
              break;

            case 'OPEN_SIDEBAR':
              console.log('[EvenLeads Extension] Processing OPEN_SIDEBAR from dashboard, campaignId:', campaignId);
              try {
                // Send message to background script to open sidebar
                console.log('[EvenLeads Extension] Sending message to background script...');
                const response = await browser.runtime.sendMessage({
                  type: 'OPEN_SIDEBAR',
                  campaignId,
                  autoSync,
                });
                console.log('[EvenLeads Extension] Background script responded:', response);

                // Confirm sidebar opened
                document.dispatchEvent(new CustomEvent('evenleads-extension-response', {
                  detail: {
                    type: 'SIDEBAR_OPENED',
                    campaignId,
                    timestamp: Date.now(),
                  },
                }));
                console.log('[EvenLeads Extension] Dispatched SIDEBAR_OPENED response');
              } catch (error) {
                console.error('[EvenLeads Extension] Error opening sidebar:', error);
                console.error('[EvenLeads Extension] Error details:', error.message, error.stack);
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
          }
        });
      }

      console.log('[EvenLeads] 🔍 STEP 9: REVERTING TO OLD SIMPLE APPROACH (NO SHADOW DOM)');

      console.log('[EvenLeads] 🔍 STEP 10A: About to createElement("div")');
      const appRoot = document.createElement('div');
      console.log('[EvenLeads] 🔍 STEP 10B: createElement done');

      console.log('[EvenLeads] 🔍 STEP 10C: Setting appRoot.id');
      appRoot.id = 'evenleads-app-root';
      console.log('[EvenLeads] 🔍 STEP 10D: appRoot.id set');

      console.log('[EvenLeads] 🔍 STEP 10E: ABOUT TO appendChild to document.body');
      console.log('[EvenLeads] 🔍 STEP 10E-0: appRoot innerHTML before append:', appRoot.innerHTML);
      console.log('[EvenLeads] 🔍 STEP 10E-0: appRoot children count:', appRoot.children.length);
      await new Promise(resolve => setTimeout(resolve, 100));
      console.log('[EvenLeads] 🔍 STEP 10E-1: promise finished, executing appendChild NOW...');

      document.body.appendChild(appRoot);

      console.log('[EvenLeads] 🔍 STEP 10F: appendChild DONE - monitoring for errors...');
      await new Promise(resolve => setTimeout(resolve, 500)); // Longer wait to see if errors appear
      console.log('[EvenLeads] 🔍 STEP 10F-1: 500ms after appendChild - any errors yet?');

      console.log('[EvenLeads] 🔍 STEP 11A: ABOUT TO ReactDOM.createRoot');
      await new Promise(resolve => setTimeout(resolve, 100));
      console.log('[EvenLeads] 🔍 STEP 11A-1: Executing ReactDOM.createRoot NOW...');

      const reactRoot = ReactDOM.createRoot(appRoot);

      console.log('[EvenLeads] 🔍 STEP 11B: ReactDOM.createRoot DONE - monitoring for errors...');
      await new Promise(resolve => setTimeout(resolve, 500)); // Longer wait to see if errors appear
      console.log('[EvenLeads] 🔍 STEP 11B-1: 500ms after createRoot - any errors yet?');

      console.log('[EvenLeads] 🔍 STEP 12A: ABOUT TO reactRoot.render(<EvenLeadsApp />)');
      console.log('[EvenLeads] 🔍 STEP 12A-0: This will mount the entire React component tree');
      await new Promise(resolve => setTimeout(resolve, 100));
      console.log('[EvenLeads] 🔍 STEP 12A-1: Executing reactRoot.render NOW...');

      reactRoot.render(<EvenLeadsApp />);

      console.log('[EvenLeads] 🔍 STEP 12B: reactRoot.render DONE - monitoring for errors...');
      await new Promise(resolve => setTimeout(resolve, 1000)); // Even longer wait
      console.log('[EvenLeads] 🔍 STEP 12B-1: 1000ms after render - any errors yet?');

      console.log('[EvenLeads] ✅ Extension loaded successfully');
    } catch (error) {
      console.error('[EvenLeads] Failed to initialize:', error);
    }
  },
});

function EvenLeadsApp() {
  const [isOpen, setIsOpen] = useState(false);
  const [devModeEnabled, setDevModeEnabled] = useState(false);
  const [showDevPanel, setShowDevPanel] = useState(false);

  // Check if we're on LinkedIn (DevMode causes invalid URL loop on LinkedIn)
  const isLinkedIn = window.location.hostname.includes('linkedin.com');

  // Debug logging
  useEffect(() => {
    console.log('[DevMode] isLinkedIn:', isLinkedIn);
    console.log('[DevMode] hostname:', window.location.hostname);
    console.log('[DevMode] devModeEnabled:', devModeEnabled);
    console.log('[DevMode] showDevPanel:', showDevPanel);
  }, [isLinkedIn, devModeEnabled, showDevPanel]);

  // Use functional setState to avoid closure issues
  const toggleSidebar = useCallback(() => {
    setIsOpen(prev => !prev);
  }, []);

  // Load DevMode state on mount (don't show panel yet)
  useEffect(() => {
    const loadDevModeState = async () => {
      const enabled = await devModeStorage.isEnabled();
      setDevModeEnabled(enabled);
      // Don't show panel on mount, only when sidebar opens
    };
    loadDevModeState();
  }, []);

  // Show dev panel when sidebar opens IF dev mode is enabled
  // Note: We allow it on LinkedIn but with a warning (user can decide)
  useEffect(() => {
    if (isOpen && devModeEnabled) {
      setShowDevPanel(true);
    }
  }, [isOpen, devModeEnabled]);

  // Removed complex test orchestration - now using simple manual testing

  // Check for pending sync on mount - open sidebar immediately if found
  useEffect(() => {
    const checkPendingSync = async () => {
      const result = await browser.storage.local.get('pending_sync');
      const pendingSync = result.pending_sync;

      if (pendingSync && (Date.now() - pendingSync.timestamp < 30000)) {
        console.log('[EvenLeadsApp] Pending sync detected, opening sidebar immediately');
        setIsOpen(true); // Open sidebar immediately for smooth UX
      }
    };

    checkPendingSync();
  }, []);

  useEffect(() => {
    // Listen for keyboard shortcut (Ctrl+Shift+L)
    const handleKeyboard = (e: KeyboardEvent) => {
      if (e.ctrlKey && e.shiftKey && e.key === 'L') {
        e.preventDefault();
        toggleSidebar();
      }
    };
    document.addEventListener('keydown', handleKeyboard);

    // Listen for messages from background script
    const handleMessage = async (message: any, sender: any, sendResponse: any) => {
      console.log('[Sidebar] chrome.runtime.onMessage fired, type:', message.type, message);

      if (message.type === 'TOGGLE_SIDEBAR') {
        console.log('[Sidebar] TOGGLE_SIDEBAR received');
        toggleSidebar();
        sendResponse({ success: true });
        return true;
      }

      if (message.type === 'OPEN_SIDEBAR') {
        console.log('[Sidebar] OPEN_SIDEBAR received from background:', message);
        console.log('[Sidebar] Current isOpen state:', isOpen);

        // Open sidebar if it's not already open
        if (!isOpen) {
          console.log('[Sidebar] Opening sidebar...');
          setIsOpen(true);
        } else {
          console.log('[Sidebar] Sidebar already open');
        }

        // Pre-select campaign if provided
        if (message.campaignId) {
          try {
            await campaignStorage.setSelected(message.campaignId);
            console.log('[Sidebar] Campaign pre-selected:', message.campaignId);
          } catch (error) {
            console.error('[Sidebar] Error pre-selecting campaign:', error);
          }
        }

        // Handle autoSync - automatically start syncing the campaign
        if (message.autoSync && message.campaignId) {
          console.log('[Sidebar] Auto-sync requested for campaign:', message.campaignId);

          try {
            // Fetch full campaign details from API
            const response: any = await api.getCampaign(message.campaignId);
            console.log('[Sidebar] API response:', response);

            // Extract campaign from response (API returns { success: true, data: campaign })
            const campaign = response.data || response;
            console.log('[Sidebar] Campaign data:', campaign);

            // Parse keywords - they come as JSON string from backend
            let keywords = [];
            if (campaign.keywords) {
              try {
                keywords = typeof campaign.keywords === 'string'
                  ? JSON.parse(campaign.keywords)
                  : campaign.keywords;
                keywords = Array.isArray(keywords) ? keywords : [];
              } catch (e) {
                console.warn('[Sidebar] Failed to parse keywords:', e);
                keywords = [];
              }
            }

            // Parse platforms
            let platforms = [];
            if (campaign.platforms) {
              try {
                platforms = typeof campaign.platforms === 'string'
                  ? JSON.parse(campaign.platforms)
                  : campaign.platforms;
                platforms = Array.isArray(platforms) ? platforms : [];
              } catch (e) {
                console.warn('[Sidebar] Failed to parse platforms:', e);
                platforms = [];
              }
            }

            // Parse subreddits if Reddit campaign
            let subreddits = [];
            if (campaign.reddit_subreddits) {
              try {
                subreddits = typeof campaign.reddit_subreddits === 'string'
                  ? JSON.parse(campaign.reddit_subreddits)
                  : campaign.reddit_subreddits;
                subreddits = Array.isArray(subreddits) ? subreddits : [];
              } catch (e) {
                console.warn('[Sidebar] Failed to parse subreddits:', e);
                subreddits = [];
              }
            }

            // Build sync configuration from campaign settings
            const syncConfig: SyncConfig = {
              campaignId: campaign.id,
              platform: platforms[0] as Platform,
              keywords: keywords,
              offering: campaign.offering || '',
              intelligentMode: false, // Will be passed from dashboard later
              maxLeadsPerKeyword: 10,
              subreddits: subreddits,
            };

            console.log('[Sidebar] Parsed campaign data:', {
              keywords,
              platforms,
              offering: campaign.offering,
              hasOffering: !!campaign.offering,
              offeringType: typeof campaign.offering,
            });
            console.log('[Sidebar] Starting auto-sync with config:', syncConfig);

            // Trigger sync via background script (proper message flow)
            await browser.runtime.sendMessage({
              type: 'RUN_SYNC',
              payload: syncConfig,
            });

            console.log('[Sidebar] Auto-sync message sent to background');
          } catch (error) {
            console.error('[Sidebar] Failed to start auto-sync:', error);
          }
        }

        sendResponse({ success: true });
        return true;
      }
    };

    console.log('[Sidebar] Registering chrome.runtime.onMessage listener');
    chrome.runtime.onMessage.addListener(handleMessage);
    console.log('[Sidebar] chrome.runtime.onMessage listener registered');

    // Listen for window messages (for OPEN_DEV_MODE from linkedin.ts)
    const handleWindowMessage = (event: MessageEvent) => {
      if (event.data && event.data.type === 'OPEN_DEV_MODE') {
        console.log('[Sidebar] OPEN_DEV_MODE received via window.postMessage');
        setDevModeEnabled(true);
        setShowDevPanel(true);
        setIsOpen(true); // Also open sidebar for better visibility
      }
    };
    window.addEventListener('message', handleWindowMessage);

    return () => {
      console.log('[Sidebar] Cleaning up listeners');
      document.removeEventListener('keydown', handleKeyboard);
      chrome.runtime.onMessage.removeListener(handleMessage);
      window.removeEventListener('message', handleWindowMessage);
    };
  }, [toggleSidebar]);

  const handleDevModeToggle = useCallback((enabled: boolean) => {
    setDevModeEnabled(enabled);
    // When dev mode is toggled on and sidebar is open, show panel immediately
    if (enabled && isOpen) {
      setShowDevPanel(true);
    }
    // When dev mode is toggled off, hide panel
    if (!enabled) {
      setShowDevPanel(false);
    }
  }, [isOpen]);

  return (
    <>
      <PlatformDetector onPlatformDetected={(platform) => {
        setIsOpen(true);
      }} />
      <FloatingIcon onClick={toggleSidebar} isVisible={true} />
      <SidebarApp
        isOpen={isOpen}
        onClose={toggleSidebar}
        onDevModeToggle={handleDevModeToggle}
        isLinkedIn={isLinkedIn}
      />
      {/* DevMode Panel - rendered at app level, independent of sidebar */}
      <DevModePanel
        isOpen={showDevPanel}
        isLinkedIn={isLinkedIn}
        onClose={() => {
          setShowDevPanel(false);
          setDevModeEnabled(false);
          devModeStorage.setEnabled(false);
        }}
      />
    </>
  );
}

function SidebarApp({ isOpen, onClose, onDevModeToggle, isLinkedIn }: { isOpen: boolean; onClose: () => void; onDevModeToggle: (enabled: boolean) => void; isLinkedIn: boolean }) {
  const [authState, setAuthState] = useState<AuthState | null>(null);
  const [campaigns, setCampaigns] = useState<Campaign[]>([]);
  const [currentView, setCurrentView] = useState<View>('oauth');
  const [loading, setLoading] = useState(true);
  const [syncing, setSyncing] = useState(false);
  const [oauthLoading, setOauthLoading] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
  const [welcomeMessage, setWelcomeMessage] = useState<string>('');
  const [baseUrl, setBaseUrl] = useState<string>('https://evenleads.com');
  const [pageInfo, setPageInfo] = useState<{ platform: string; type: string | null; url: string } | null>(null);
  const [syncProgress, setSyncProgress] = useState<SyncProgressData | null>(null);

  // Use local SVG logo
  const siteLogo = chrome.runtime.getURL('/evenleads-logo-dark.svg');

  // Debug: Log when isOpen changes
  useEffect(() => {
    console.log('[EvenLeads Sidebar] isOpen changed to:', isOpen);
  }, [isOpen]);

  // Check pending sync on mount
  useEffect(() => {
    checkPendingSync();
  }, []);

  // Check if there's a pending sync from another tab (called from SidebarApp)
  async function checkPendingSync() {
    const result = await browser.storage.local.get('pending_sync');
    const pendingSync = result.pending_sync;

    if (pendingSync && (Date.now() - pendingSync.timestamp < 30000)) {
      console.log('[SidebarApp] Pending sync detected, starting sync process:', pendingSync);

      // Clear the pending sync
      await browser.storage.local.remove('pending_sync');

      // Switch to dashboard view
      setCurrentView('dashboard');

      // Start sync with stored config
      setTimeout(() => {
        console.log('[SidebarApp] Starting pending sync...');
        handleStartSync({
          campaignId: pendingSync.campaignId,
          platform: pendingSync.platform,
          keywords: pendingSync.keywords,
          offering: pendingSync.offering,
          intelligentMode: false,
          maxLeadsPerKeyword: 10,
        });
      }, 800); // Wait for dashboard to render
    }
  }

  // Detect current page for "Get Lead" button visibility
  useEffect(() => {
    const detectCurrentPage = () => {
      const info = detectPageType();
      setPageInfo(info);
      console.log('[Sidebar] Page detected:', info);
    };

    // Detect on mount
    detectCurrentPage();

    // Re-detect on URL changes (for SPAs)
    const intervalId = setInterval(detectCurrentPage, 2000);

    return () => clearInterval(intervalId);
  }, []);

  useEffect(() => {
    loadAuthState();
    // loadSiteLogo(); // Using local logo now
    loadBaseUrl();

    // Listen for OAuth success and sync messages
    const handleMessage = (message: any, sender: any, sendResponse: any) => {
      if (message.type === 'OAUTH_SUCCESS') {
        console.log('[EvenLeads] OAuth success received');
        handleOAuthSuccess(message.payload);
        sendResponse({ success: true});
        return true;
      }

      if (message.type === 'START_SYNC') {
        console.log('[Sidebar] START_SYNC received:', message.payload);
        handleStartSync(message.payload);
        sendResponse({ success: true });
        return true;
      }
    };

    chrome.runtime.onMessage.addListener(handleMessage);

    return () => {
      chrome.runtime.onMessage.removeListener(handleMessage);
    };
  }, []);

  useEffect(() => {
    if (authState?.isAuthenticated) {
      loadCampaigns();
      if (currentView === 'oauth') {
        setCurrentView('dashboard');
      }
    } else {
      setCurrentView('oauth');
    }
  }, [authState]);

  // Validate token when sidebar opens
  useEffect(() => {
    if (isOpen && authState?.isAuthenticated) {
      validateTokenOnOpen();
    }
  }, [isOpen]);

  async function validateTokenOnOpen() {
    try {
      console.log('[Sidebar] Validating extension token...');
      const response = await api.validateExtensionToken();

      if (!response.valid) {
        console.warn('[Sidebar] Token is no longer valid, logging out...');
        await handleLogout();
        showMessage('error', 'Your extension access has been revoked. Please log in again.');
      } else {
        console.log('[Sidebar] Token is valid');

        // Update user data if it changed (e.g., roles updated)
        const state = await authStorage.get();
        if (state && response.user) {
          state.user = response.user;
          await authStorage.set(state);
          setAuthState(state);
        }
      }
    } catch (error: any) {
      console.error('[Sidebar] Token validation failed:', error);

      // If 401/403, token was revoked - log out
      if (error.message?.includes('401') || error.message?.includes('403')) {
        await handleLogout();
        showMessage('error', 'Your extension access has been revoked. Please log in again.');
      }
    }
  }

  async function loadAuthState() {
    try {
      const state = await authStorage.get();

      // If user exists but roles is missing, fetch fresh user data
      if (state?.isAuthenticated && state.user && !state.user.roles) {
        console.log('[Sidebar] roles array missing, fetching fresh user data...');
        try {
          const freshUser = await api.getUser();
          state.user = freshUser;
          await authStorage.set(state);
          console.log('[Sidebar] User data refreshed with roles:', freshUser.roles);
        } catch (error) {
          console.error('[Sidebar] Failed to refresh user data:', error);
        }
      }

      // If authenticated but subscription is missing or lacks usage data, fetch fresh subscription
      if (state?.isAuthenticated && (!state.subscription || state.subscription.used_campaigns === undefined || !state.subscription.plan?.campaigns_limit)) {
        try {
          const freshSubscription = await api.getSubscription();
          state.subscription = freshSubscription;
          await authStorage.set(state);
        } catch (error) {
          console.error('[Sidebar] Failed to refresh subscription data:', error);
        }
      }

      setAuthState(state);
    } catch (error) {
      console.error('Failed to load auth state:', error);
    } finally {
      setLoading(false);
    }
  }

  async function loadBaseUrl() {
    try {
      setBaseUrl(getApiBaseUrl());
    } catch (error) {
      console.error('Failed to load base URL:', error);
    }
  }

  async function loadCampaigns() {
    try {
      console.log('[Sidebar] Loading campaigns from storage...');
      let camps = await campaignStorage.get();

      // If storage is empty, try fetching from API
      if (!camps || camps.length === 0) {
        console.log('[Sidebar] No campaigns in storage, fetching from API...');
        try {
          camps = await api.getCampaigns(true); // Use cache if available
          if (camps && camps.length > 0) {
            await campaignStorage.set(camps);
            console.log('[Sidebar] Campaigns fetched and stored:', camps.length);
          }
        } catch (apiError) {
          console.error('[Sidebar] Failed to fetch campaigns from API:', apiError);
          camps = []; // Fallback to empty array
        }
      } else {
        console.log('[Sidebar] Loaded campaigns from storage:', camps.length);
      }

      setCampaigns(camps);
    } catch (error) {
      console.error('[Sidebar] Failed to load campaigns:', error);
      setCampaigns([]);
    }
  }


  async function handleOAuthLogin() {
    try {
      setOauthLoading(true);

      // Get API URL from config
      const baseUrl = getApiBaseUrl();

      // Generate a random state for CSRF protection
      const state = Math.random().toString(36).substring(7);

      // Build OAuth authorization URL with parameters
      const params = new URLSearchParams({
        client_id: 'browser-extension',
        scope: 'read write campaigns leads',
        state: state,
      });

      const authUrl = `${baseUrl}/oauth/authorize?${params.toString()}`;
      const authWindow = window.open(authUrl, '_blank', 'width=600,height=800');

      // Listen for postMessage from OAuth callback window
      const handleMessage = (event: MessageEvent) => {
        console.log('Received postMessage:', event.data);

        if (event.data.type === 'EVENLEADS_OAUTH_SUCCESS') {
          console.log('[OAuth] Success postMessage received');
          clearInterval(pollInterval);
          window.removeEventListener('message', handleMessage);

          // Close the auth window if it's still open
          if (authWindow && !authWindow.closed) {
            authWindow.close();
          }

          // DIRECT STORAGE APPROACH - No dependency on background script
          (async () => {
            try {
              console.log('[OAuth] ========== STORING AUTH DIRECTLY ==========');
              console.log('[OAuth] Timestamp:', new Date().toISOString());

              // Step 1: Store auth DIRECTLY in content script (don't wait for background)
              console.log('[OAuth] Storing auth data directly...');
              const storeStart = performance.now();

              await authStorage.set({
                isAuthenticated: true,
                user: event.data.user,
                token: event.data.token,
                subscription: null, // Will be fetched async
              });

              const storeEnd = performance.now();
              console.log(`[OAuth] Auth stored successfully in ${(storeEnd - storeStart).toFixed(0)}ms`);

              // Step 2: Notify background script (fire-and-forget, non-blocking)
              console.log('[OAuth] Notifying background script (non-blocking)...');
              try {
                chrome.runtime.sendMessage({
                  type: 'OAUTH_COMPLETE',
                  payload: {
                    userId: event.data.user.id,
                    isNewUser: event.data.isNewUser
                  }
                }).catch((error) => {
                  // Silently handle extension context invalidation errors
                  if (!error.message?.includes('Extension context invalidated') &&
                      !error.message?.includes('message port closed')) {
                    console.log('[OAuth] Background notification failed (non-critical):', error.message);
                  }
                  // Continue anyway - auth is already stored!
                });
              } catch (error: any) {
                // Silently handle synchronous errors (context invalidation)
                if (!error.message?.includes('Extension context invalidated') &&
                    !error.message?.includes('message port closed')) {
                  console.log('[OAuth] Background notification failed (non-critical):', error.message);
                }
              }

              // Step 3: Continue with OAuth success immediately
              console.log('[OAuth] Calling handleOAuthSuccess');
              await handleOAuthSuccess({
                isNewUser: event.data.isNewUser,
                user: event.data.user
              });

              // Step 4: Fetch subscription in parallel (non-blocking)
              console.log('[OAuth] Fetching subscription in background...');
              (async () => {
                try {
                  const subscription = await api.getSubscription();
                  const auth = await authStorage.get();
                  if (auth) {
                    auth.subscription = subscription;
                    await authStorage.set(auth);
                    console.log('[OAuth] Subscription fetched and stored');
                  }
                } catch (subError: any) {
                  console.log('[OAuth] Subscription fetch failed (non-critical):', subError.message);
                }
              })();

            } catch (error: any) {
              console.error('[OAuth] Auth storage failed:', error);
              setOauthLoading(false);
              showMessage('error', 'Failed to save authentication. Please try again.');
            }
          })();
        }
      };

      window.addEventListener('message', handleMessage);

      // Poll for window closed (check every 500ms)
      const pollInterval = setInterval(() => {
        try {
          // Check if window was closed
          if (authWindow && authWindow.closed) {
            clearInterval(pollInterval);
            window.removeEventListener('message', handleMessage);
            setOauthLoading(false);

            // Check if denied
            const authDenied = localStorage.getItem('evenleads_auth_denied');
            if (authDenied) {
              localStorage.removeItem('evenleads_auth_denied');
              showMessage('error', 'Authorization denied');
            }
          }
        } catch (e) {
          console.error('Error checking auth window:', e);
        }
      }, 500);

      // Stop polling after 5 minutes
      setTimeout(() => {
        clearInterval(pollInterval);
        window.removeEventListener('message', handleMessage);
        setOauthLoading(false);
      }, 300000);

    } catch (error: any) {
      setOauthLoading(false);
      showMessage('error', error.message || 'OAuth failed');
    }
  }

  async function handleOAuthSuccess(payload: any) {
    console.log('[OAuth] handleOAuthSuccess started');

    // Show welcome message
    setWelcomeMessage(payload.isNewUser ? 'Welcome to EvenLeads!' : 'Welcome back!');
    setCurrentView('welcome');

    // Keep loading state ON while we fetch auth data
    // Background script has stored it, but we need to read it with retry logic

    console.log('[OAuth] Attempting to load auth state with retry...');
    let attempts = 0;
    let state = null;
    const maxAttempts = 20; // 20 attempts × 500ms = 10 seconds max

    while (!state && attempts < maxAttempts) {
      attempts++;
      console.log(`[OAuth] Auth load attempt ${attempts}/${maxAttempts}`);

      // Wait 500ms between attempts
      await new Promise(resolve => setTimeout(resolve, 500));

      // Try to load auth state from storage
      try {
        state = await authStorage.get();
        if (state && state.isAuthenticated) {
          console.log('[OAuth] Auth state loaded successfully!', state.user?.name);
          break;
        } else {
          console.log('[OAuth] Auth state not ready yet, retrying...');
          state = null; // Reset if not authenticated
        }
      } catch (error) {
        console.error('[OAuth] Error loading auth state:', error);
      }
    }

    if (!state || !state.isAuthenticated) {
      console.error('[OAuth] Failed to load auth state after', attempts, 'attempts');
      setOauthLoading(false);
      showMessage('error', 'Failed to load authentication data. Please try logging in again.');
      setCurrentView('oauth');
      return;
    }

    // Success! Set the auth state
    console.log('[OAuth] Setting auth state');
    setAuthState(state);
    setOauthLoading(false);

    // Load campaigns
    console.log('[OAuth] Loading campaigns');
    await loadCampaigns();

    // Auto-switch to dashboard after 3 seconds
    console.log('[OAuth] Scheduling dashboard switch in 3 seconds');
    setTimeout(() => {
      console.log('[OAuth] Switching to dashboard');
      setCurrentView('dashboard');
      setWelcomeMessage('');
    }, 3000);
  }

  async function handleLogout() {
    try {
      await chrome.runtime.sendMessage({ type: 'AUTH_LOGOUT' });
      setAuthState(null);
      setCampaigns([]);
      setCurrentView('oauth');
      showMessage('success', 'Successfully logged out');
    } catch (error: any) {
      showMessage('error', error.message || 'Logout failed');
    }
  }

  async function handleSyncCampaigns() {
    console.log('[Sidebar] Starting campaign sync...');
    setSyncing(true);
    try {
      console.log('[Sidebar] Sending SYNC_CAMPAIGNS message to background...');

      // Add timeout protection (30 seconds - increased from 10s)
      const syncPromise = chrome.runtime.sendMessage({ type: 'SYNC_CAMPAIGNS' });
      const timeoutPromise = new Promise((_, reject) =>
        setTimeout(() => reject(new Error('Campaign sync timed out after 30 seconds')), 30000)
      );

      const response = await Promise.race([syncPromise, timeoutPromise]) as any;
      console.log('[Sidebar] Received response from background:', response);

      if (!response) {
        throw new Error('No response from background script');
      }

      if (response.error) {
        console.error('[Sidebar] Sync error from background:', response.error);
        throw new Error(response.error);
      }

      // Invalidate all caches after sync
      console.log('[Sidebar] Invalidating caches...');
      api.invalidateCampaignsCache();
      api.invalidateStatsCache();
      api.invalidateLeadsCache();

      console.log('[Sidebar] Loading campaigns from storage...');
      await loadCampaigns();
      console.log('[Sidebar] Campaigns loaded successfully, count:', response.count);
      showMessage('success', `Synced ${response.count} campaign(s)`);
    } catch (error: any) {
      console.error('[Sidebar] Campaign sync failed:', error);
      showMessage('error', error.message || 'Sync failed. Please try again.');
    } finally {
      setSyncing(false);
      console.log('[Sidebar] Campaign sync complete');
    }
  }

  function showMessage(type: 'success' | 'error', text: string) {
    setMessage({ type, text });
    setTimeout(() => setMessage(null), 3000);
  }

  async function handleStartSync(config: SyncConfig) {
    console.log('[Sidebar] Starting sync with config:', config);

    // Open sidebar if not already open
    if (!isOpen) {
      // Sidebar will open automatically via message
    }

    // Switch to dashboard view
    setCurrentView('dashboard');

    // Start sync
    try {
      await startSync(config, (progress) => {
        console.log('[Sidebar] Sync progress:', progress);
        setSyncProgress(progress);

        // When complete or error, clear after 5 seconds
        if (progress.status === 'complete' || progress.status === 'error') {
          setTimeout(() => {
            setSyncProgress(null);
            // Refresh stats after sync
            api.invalidateStatsCache();
            api.invalidateLeadsCache();
          }, 5000);
        }
      });
    } catch (error: any) {
      console.error('[Sidebar] Sync failed:', error);
      setSyncProgress({
        status: 'error',
        message: 'Sync failed',
        error: error.message,
        currentKeyword: '',
        currentKeywordIndex: 0,
        totalKeywords: 0,
        leadsFound: 0,
        leadsSubmitted: 0,
      });
    }
  }

  function handleCancelSync() {
    setSyncProgress(null);
    showMessage('error', 'Sync cancelled');
  }

  if (!isOpen) return null;

  console.log('[EvenLeads Sidebar] Rendering sidebar, isOpen:', isOpen);

  return (
    <div style={{
      position: 'fixed',
      top: 0,
      right: 0,
      bottom: 0,
      left: 0,
      pointerEvents: 'none',
      visibility: 'visible',
      opacity: 1,
      zIndex: 2147483646,
      animation: 'fade-in 0.3s ease-out forwards',
    }}>
      {/* Backdrop */}
      <div
        onClick={onClose}
        style={{
          position: 'absolute',
          top: 0,
          right: 0,
          bottom: 0,
          left: 0,
          backgroundColor: 'rgba(0, 0, 0, 0.5)',
          pointerEvents: 'auto',
        }}
      />

      {/* Sidebar */}
      <div
        style={{
          position: 'absolute',
          right: '4px',
          top: '4px',
          height: 'calc(100% - 8px)',
          width: 'min(400px, calc(100vw - 8px))',
          maxWidth: '400px',
          boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
          pointerEvents: 'auto',
          display: 'flex',
          flexDirection: 'column',
          backgroundColor: '#FFFFFF',
          color: '#000000',
          borderRadius: '8px',
          zIndex: 2147483646,
          animation: 'slide-in 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards',
        }}
      >
        {/* Header */}
        <div
          style={{
            padding: '1rem',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            backgroundColor: '#FFFFFF',
            borderBottom: '1px solid #E5E7EB',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
            <img
              src={siteLogo}
              alt="EvenLeads"
              style={{
                height: '2rem',
                width: 'auto',
                objectFit: 'contain',
              }}
              onError={(e) => {
                // Fallback to 1L badge if logo fails to load
                e.currentTarget.style.display = 'none';
                const fallback = e.currentTarget.nextElementSibling as HTMLElement;
                if (fallback) fallback.style.display = 'flex';
              }}
            />
            <div
              style={{
                width: '2rem',
                height: '2rem',
                borderRadius: '0.5rem',
                display: 'none',
                alignItems: 'center',
                justifyContent: 'center',
                boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                backgroundColor: '#000000',
              }}
            >
              <span style={{ color: '#FFFFFF', fontWeight: 700, fontSize: '0.875rem' }}>1L</span>
            </div>
          </div>
          <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
            {authState?.isAuthenticated && currentView !== 'oauth' && currentView !== 'welcome' && currentView !== 'leadCapture' && pageInfo && pageInfo.platform && pageInfo.type && (
              <button
                onClick={() => setCurrentView('leadCapture')}
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '0.5rem',
                  padding: '0.5rem 1rem',
                  borderRadius: '0.5rem',
                  backgroundColor: '#000000',
                  color: '#FFFFFF',
                  border: 'none',
                  cursor: 'pointer',
                  fontSize: '0.875rem',
                  fontWeight: 600,
                  transition: 'all 0.2s',
                  boxShadow: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.backgroundColor = '#1F2937';
                  e.currentTarget.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.backgroundColor = '#000000';
                  e.currentTarget.style.boxShadow = '0 1px 2px 0 rgba(0, 0, 0, 0.05)';
                }}
              >
                <Target style={{ width: '1rem', height: '1rem' }} />
                Get Lead
              </button>
            )}
            <button
              onClick={onClose}
              style={{
                padding: '0.5rem',
                borderRadius: '0.5rem',
                backgroundColor: 'transparent',
                border: 'none',
                cursor: 'pointer',
                transition: 'all 0.2s',
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.backgroundColor = '#F3F4F6';
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.backgroundColor = 'transparent';
              }}
            >
              <X style={{ width: '1.25rem', height: '1.25rem', color: '#6B7280' }} />
            </button>
          </div>
        </div>

        {/* Message Banner */}
        {message && (
          <div
            style={{
              paddingLeft: '1rem',
              paddingRight: '1rem',
              paddingTop: '0.75rem',
              paddingBottom: '0.75rem',
              fontSize: '0.875rem',
              lineHeight: '1.25rem',
              backgroundColor: message.type === 'success' ? '#D1FAE5' : '#FEE2E2',
              color: message.type === 'success' ? '#065F46' : '#991B1B',
              borderBottom: `1px solid ${message.type === 'success' ? '#A7F3D0' : '#FECACA'}`,
              animation: 'slide-down 0.3s ease-out forwards',
            }}
          >
            {message.text}
          </div>
        )}

        {/* Content */}
        <div style={{ flex: 1, overflowY: 'auto', backgroundColor: '#FFFFFF' }}>
          {loading ? (
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: '100%' }}>
              <Loader2 style={{ width: '2rem', height: '2rem', color: '#000000', animation: 'spin 1s linear infinite' }} />
            </div>
          ) : currentView === 'oauth' ? (
            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '100%', paddingLeft: '2rem', paddingRight: '2rem', textAlign: 'center', backgroundColor: '#FFFFFF' }}>
              {siteLogo ? (
                <img
                  src={siteLogo}
                  alt="EvenLeads"
                  style={{
                    height: '5rem',
                    width: 'auto',
                    objectFit: 'contain',
                    marginBottom: '1.5rem',
                  }}
                  onError={(e) => {
                    e.currentTarget.style.display = 'none';
                    const fallback = e.currentTarget.nextElementSibling as HTMLElement;
                    if (fallback) fallback.style.display = 'flex';
                  }}
                />
              ) : null}
              <div
                style={{
                  width: '5rem',
                  height: '5rem',
                  borderRadius: '1rem',
                  display: siteLogo ? 'none' : 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  marginBottom: '1.5rem',
                  boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                  backgroundColor: '#000000',
                }}
              >
                <span style={{ color: '#FFFFFF', fontWeight: 700, fontSize: '1.875rem', lineHeight: '2.25rem' }}>1L</span>
              </div>
              <h2 style={{ fontSize: '1.5rem', lineHeight: '2rem', fontWeight: 700, marginBottom: '0.75rem', color: '#000000' }}>Welcome to EvenLeads</h2>
              <p style={{ marginBottom: '1rem', maxWidth: '24rem', color: '#6B7280' }}>
                Sign in to start collecting and managing leads from across the web
              </p>
              <p style={{ marginBottom: '2rem', maxWidth: '24rem', fontSize: '0.875rem', color: '#9CA3AF' }}>
                Don't have an account? <a href="https://evenleads.com/register" target="_blank" rel="noopener noreferrer" style={{ color: '#000000', fontWeight: 600, textDecoration: 'underline' }}>Register here</a>
              </p>
              <button
                onClick={handleOAuthLogin}
                disabled={oauthLoading}
                style={{
                  width: '100%',
                  maxWidth: '24rem',
                  paddingLeft: '1.5rem',
                  paddingRight: '1.5rem',
                  paddingTop: '0.75rem',
                  paddingBottom: '0.75rem',
                  borderRadius: '0.5rem',
                  fontWeight: 500,
                  transition: 'all 0.2s',
                  boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                  backgroundColor: '#000000',
                  color: '#FFFFFF',
                  border: 'none',
                  cursor: oauthLoading ? 'not-allowed' : 'pointer',
                  opacity: oauthLoading ? 0.5 : 1,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '0.5rem',
                  outline: 'none',
                }}
                onMouseEnter={(e) => {
                  if (!oauthLoading) {
                    e.currentTarget.style.backgroundColor = '#1F2937';
                    e.currentTarget.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)';
                  }
                }}
                onMouseLeave={(e) => {
                  if (!oauthLoading) {
                    e.currentTarget.style.backgroundColor = '#000000';
                    e.currentTarget.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
                  }
                }}
              >
                {oauthLoading && <Loader2 style={{ width: '1.25rem', height: '1.25rem', animation: 'spin 1s linear infinite' }} />}
                {oauthLoading ? 'Opening...' : 'Sign in with EvenLeads'}
              </button>
            </div>
          ) : currentView === 'welcome' ? (
            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '100%', paddingLeft: '2rem', paddingRight: '2rem', textAlign: 'center', backgroundColor: '#FFFFFF' }}>
              {oauthLoading ? (
                // Show loading state while fetching auth data
                <>
                  <div
                    style={{
                      width: '5rem',
                      height: '5rem',
                      borderRadius: '9999px',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      marginBottom: '1.5rem',
                      boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                      backgroundColor: '#000000',
                    }}
                  >
                    <Loader2 style={{ width: '2.5rem', height: '2.5rem', color: '#FFFFFF', animation: 'spin 1s linear infinite' }} />
                  </div>
                  <h2 style={{ fontSize: '1.5rem', lineHeight: '2rem', fontWeight: 700, marginBottom: '0.75rem', color: '#000000' }}>{welcomeMessage}</h2>
                  <p style={{ color: '#6B7280', marginBottom: '0.5rem' }}>
                    Loading your data...
                  </p>
                  <p style={{ color: '#9CA3AF', fontSize: '0.875rem' }}>
                    This may take a few seconds
                  </p>
                </>
              ) : (
                // Show success checkmark when loading complete
                <>
                  <div
                    style={{
                      width: '5rem',
                      height: '5rem',
                      borderRadius: '9999px',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      marginBottom: '1.5rem',
                      boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                      backgroundColor: '#10B981',
                      animation: 'bounce-slow 2s ease-in-out infinite',
                    }}
                  >
                    <svg style={{ width: '2.5rem', height: '2.5rem' }} fill="none" viewBox="0 0 24 24" stroke="white">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <h2 style={{ fontSize: '1.5rem', lineHeight: '2rem', fontWeight: 700, marginBottom: '0.75rem', color: '#000000' }}>{welcomeMessage}</h2>
                  <p style={{ color: '#6B7280' }}>
                    You're now ready to collect leads
                  </p>
                </>
              )}
            </div>
          ) : currentView === 'dashboard' ? (
            authState ? (
              <Dashboard authState={authState} syncProgress={syncProgress} onCancelSync={handleCancelSync} />
            ) : (
              <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '100%', gap: '1rem' }}>
                <Loader2 style={{ width: '2rem', height: '2rem', color: '#000000', animation: 'spin 1s linear infinite' }} />
                <p style={{ color: '#6B7280', fontSize: '0.875rem' }}>Loading dashboard...</p>
              </div>
            )
          ) : currentView === 'campaigns' ? (
            <CampaignList
              campaigns={campaigns}
              onRefresh={handleSyncCampaigns}
              refreshing={syncing}
            />
          ) : currentView === 'leads' ? (
            <LeadsView />
          ) : currentView === 'leadCapture' ? (
            <LeadCaptureView
              onClose={() => setCurrentView('dashboard')}
              onSuccess={() => {
                api.invalidateLeadsCache();
                api.invalidateStatsCache();
                setCurrentView('leads');
              }}
            />
          ) : currentView === 'account' ? (
            authState ? (
              <AccountInfo authState={authState} onLogout={handleLogout} baseUrl={baseUrl} />
            ) : (
              <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '100%', gap: '1rem' }}>
                <Loader2 style={{ width: '2rem', height: '2rem', color: '#000000', animation: 'spin 1s linear infinite' }} />
                <p style={{ color: '#6B7280', fontSize: '0.875rem' }}>Loading account...</p>
              </div>
            )
          ) : currentView === 'settings' ? (
            <SettingsView
              onClose={() => setCurrentView('dashboard')}
              onDevModeToggle={onDevModeToggle}
              user={authState?.user}
              isLinkedIn={isLinkedIn}
            />
          ) : null}
        </div>

        {/* Footer Navigation */}
        {authState?.isAuthenticated && currentView !== 'oauth' && currentView !== 'welcome' && (
          <div
            style={{
              padding: '0.75rem',
              display: 'grid',
              gridTemplateColumns: 'repeat(5, minmax(0, 1fr))',
              gap: '0.5rem',
              backgroundColor: '#FFFFFF',
              borderTop: '1px solid #E5E7EB',
            }}
          >
            <button
              onClick={() => setCurrentView('dashboard')}
              style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '0.25rem',
                paddingLeft: '0.5rem',
                paddingRight: '0.5rem',
                paddingTop: '0.5rem',
                paddingBottom: '0.5rem',
                borderRadius: '0.5rem',
                fontWeight: 500,
                fontSize: '0.75rem',
                lineHeight: '1rem',
                transition: 'all 0.2s',
                backgroundColor: currentView === 'dashboard' ? '#000000' : '#F9FAFB',
                color: currentView === 'dashboard' ? '#FFFFFF' : '#6B7280',
                border: 'none',
                cursor: 'pointer',
              }}
              onMouseEnter={(e) => {
                if (currentView !== 'dashboard') {
                  e.currentTarget.style.backgroundColor = '#F3F4F6';
                }
              }}
              onMouseLeave={(e) => {
                if (currentView !== 'dashboard') {
                  e.currentTarget.style.backgroundColor = '#F9FAFB';
                }
              }}
            >
              <LayoutDashboard style={{ width: '1.25rem', height: '1.25rem' }} />
              <span>Dashboard</span>
            </button>
            <button
              onClick={() => setCurrentView('campaigns')}
              style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '0.25rem',
                paddingLeft: '0.5rem',
                paddingRight: '0.5rem',
                paddingTop: '0.5rem',
                paddingBottom: '0.5rem',
                borderRadius: '0.5rem',
                fontWeight: 500,
                fontSize: '0.75rem',
                lineHeight: '1rem',
                transition: 'all 0.2s',
                backgroundColor: currentView === 'campaigns' ? '#000000' : '#F9FAFB',
                color: currentView === 'campaigns' ? '#FFFFFF' : '#6B7280',
                border: 'none',
                cursor: 'pointer',
              }}
              onMouseEnter={(e) => {
                if (currentView !== 'campaigns') {
                  e.currentTarget.style.backgroundColor = '#F3F4F6';
                }
              }}
              onMouseLeave={(e) => {
                if (currentView !== 'campaigns') {
                  e.currentTarget.style.backgroundColor = '#F9FAFB';
                }
              }}
            >
              <Target style={{ width: '1.25rem', height: '1.25rem' }} />
              <span>Campaigns</span>
            </button>
            <button
              onClick={() => setCurrentView('leads')}
              style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '0.25rem',
                paddingLeft: '0.5rem',
                paddingRight: '0.5rem',
                paddingTop: '0.5rem',
                paddingBottom: '0.5rem',
                borderRadius: '0.5rem',
                fontWeight: 500,
                fontSize: '0.75rem',
                lineHeight: '1rem',
                transition: 'all 0.2s',
                backgroundColor: currentView === 'leads' ? '#000000' : '#F9FAFB',
                color: currentView === 'leads' ? '#FFFFFF' : '#6B7280',
                border: 'none',
                cursor: 'pointer',
              }}
              onMouseEnter={(e) => {
                if (currentView !== 'leads') {
                  e.currentTarget.style.backgroundColor = '#F3F4F6';
                }
              }}
              onMouseLeave={(e) => {
                if (currentView !== 'leads') {
                  e.currentTarget.style.backgroundColor = '#F9FAFB';
                }
              }}
            >
              <ListChecks style={{ width: '1.25rem', height: '1.25rem' }} />
              <span>Leads</span>
            </button>
            <button
              onClick={() => setCurrentView('account')}
              style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '0.25rem',
                paddingLeft: '0.5rem',
                paddingRight: '0.5rem',
                paddingTop: '0.5rem',
                paddingBottom: '0.5rem',
                borderRadius: '0.5rem',
                fontWeight: 500,
                fontSize: '0.75rem',
                lineHeight: '1rem',
                transition: 'all 0.2s',
                backgroundColor: currentView === 'account' ? '#000000' : '#F9FAFB',
                color: currentView === 'account' ? '#FFFFFF' : '#6B7280',
                border: 'none',
                cursor: 'pointer',
              }}
              onMouseEnter={(e) => {
                if (currentView !== 'account') {
                  e.currentTarget.style.backgroundColor = '#F3F4F6';
                }
              }}
              onMouseLeave={(e) => {
                if (currentView !== 'account') {
                  e.currentTarget.style.backgroundColor = '#F9FAFB';
                }
              }}
            >
              <User style={{ width: '1.25rem', height: '1.25rem' }} />
              <span>Account</span>
            </button>
            <button
              onClick={() => setCurrentView('settings')}
              style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '0.25rem',
                paddingLeft: '0.5rem',
                paddingRight: '0.5rem',
                paddingTop: '0.5rem',
                paddingBottom: '0.5rem',
                borderRadius: '0.5rem',
                fontWeight: 500,
                fontSize: '0.75rem',
                lineHeight: '1rem',
                transition: 'all 0.2s',
                backgroundColor: currentView === 'settings' ? '#000000' : '#F9FAFB',
                color: currentView === 'settings' ? '#FFFFFF' : '#6B7280',
                border: 'none',
                cursor: 'pointer',
              }}
              onMouseEnter={(e) => {
                if (currentView !== 'settings') {
                  e.currentTarget.style.backgroundColor = '#F3F4F6';
                }
              }}
              onMouseLeave={(e) => {
                if (currentView !== 'settings') {
                  e.currentTarget.style.backgroundColor = '#F9FAFB';
                }
              }}
            >
              <Settings style={{ width: '1.25rem', height: '1.25rem' }} />
              <span>Settings</span>
            </button>
          </div>
        )}

        {/* Keyboard Shortcut Hint */}
        <div
          style={{
            paddingLeft: '1rem',
            paddingRight: '1rem',
            paddingTop: '0.5rem',
            paddingBottom: '0.5rem',
            fontSize: '0.75rem',
            lineHeight: '1rem',
            textAlign: 'center',
            backgroundColor: '#F9FAFB',
            color: '#9CA3AF',
            borderTop: '1px solid #E5E7EB',
          }}
        >
          Press <kbd style={{ paddingLeft: '0.375rem', paddingRight: '0.375rem', paddingTop: '0.125rem', paddingBottom: '0.125rem', borderRadius: '0.25rem', backgroundColor: '#E5E7EB', color: '#374151', border: '1px solid #D1D5DB' }}>Ctrl</kbd> + <kbd style={{ paddingLeft: '0.375rem', paddingRight: '0.375rem', paddingTop: '0.125rem', paddingBottom: '0.125rem', borderRadius: '0.25rem', backgroundColor: '#E5E7EB', color: '#374151', border: '1px solid #D1D5DB' }}>Shift</kbd> + <kbd style={{ paddingLeft: '0.375rem', paddingRight: '0.375rem', paddingTop: '0.125rem', paddingBottom: '0.125rem', borderRadius: '0.25rem', backgroundColor: '#E5E7EB', color: '#374151', border: '1px solid #D1D5DB' }}>L</kbd> to toggle
        </div>
      </div>
    </div>
  );
}

function FloatingIcon({ onClick, isVisible }: { onClick: () => void; isVisible: boolean }) {
  if (!isVisible) {
    return null;
  }

  return (
    <button
      onClick={onClick}
      style={{
        pointerEvents: 'auto',
        position: 'fixed',
        top: '50%',
        right: '4px',
        transform: 'translateY(-50%)',
        zIndex: 2147483646,
        padding: '8px 12px',
        backgroundColor: '#000000',
        color: '#ffffff',
        borderRadius: '8px',
        boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
        border: 'none',
        cursor: 'pointer',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
        fontSize: '14px',
        fontWeight: '700',
        transition: 'all 0.3s ease',
        outline: 'none',
        visibility: 'visible',
        opacity: 1,
      }}
      onMouseEnter={(e) => {
        e.currentTarget.style.paddingRight = '20px';
        e.currentTarget.style.boxShadow = '0 6px 16px rgba(0, 0, 0, 0.2)';
      }}
      onMouseLeave={(e) => {
        e.currentTarget.style.paddingRight = '12px';
        e.currentTarget.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
      }}
      title="Open EvenLeads (Ctrl+Shift+L)"
    >
      <img
        src={chrome.runtime.getURL('/favicon-inverted.png')}
        alt="EvenLeads"
        style={{
          width: '24px',
          height: '24px',
          objectFit: 'contain',
        }}
      />
    </button>
  );
}
