import { defineBackground } from 'wxt/sandbox';
import { api } from '../utils/api';
import { authStorage, campaignStorage, messagingTestStorage } from '../utils/storage';

export default defineBackground(() => {
  console.log('EvenLeads background script loaded');

  // Global error handlers
  self.addEventListener('error', (event) => {
    console.error('[BG ERROR]', event.error);
  });

  self.addEventListener('unhandledrejection', (event) => {
    console.error('[BG UNHANDLED REJECTION]', event.reason);
  });

  // Handle extension icon click - toggle sidebar
  chrome.action.onClicked.addListener(async (tab) => {
    console.log('[EvenLeads BG] Extension icon clicked, tab:', tab.id);
    if (tab.id) {
      try {
        const response = await chrome.tabs.sendMessage(tab.id, { type: 'TOGGLE_SIDEBAR' });
        console.log('[EvenLeads BG] Message sent, response:', response);
      } catch (error) {
        console.error('[EvenLeads BG] Error sending message:', error);
      }
    }
  });

  // Handle keyboard shortcut command
  chrome.commands.onCommand.addListener(async (command) => {
    if (command === 'toggle-sidebar') {
      const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
      if (tab.id) {
        chrome.tabs.sendMessage(tab.id, { type: 'TOGGLE_SIDEBAR' });
      }
    }
  });

  // Listen for auth state changes
  chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    console.log('[BG] Message received, type:', message.type);
    console.log('[BG] Sender tab:', sender.tab?.id);
    console.log('[BG] Timestamp:', new Date().toISOString());

    // Health check ping
    if (message.type === 'PING') {
      console.log('[BG] PING received, sending PONG');
      sendResponse({ pong: true, timestamp: Date.now() });
      return true;
    }

    if (message.type === 'AUTH_LOGIN') {
      handleLogin(message.payload)
        .then(sendResponse)
        .catch((error) => sendResponse({ error: error.message }));
      return true; // Keep channel open for async response
    }

    if (message.type === 'AUTH_LOGOUT') {
      handleLogout()
        .then(sendResponse)
        .catch((error) => sendResponse({ error: error.message }));
      return true;
    }

    if (message.type === 'SYNC_CAMPAIGNS') {
      syncCampaigns()
        .then(sendResponse)
        .catch((error) => sendResponse({ error: error.message }));
      return true;
    }

    if (message.type === 'VALIDATE_PLAN') {
      validatePlan()
        .then(sendResponse)
        .catch((error) => sendResponse({ error: error.message }));
      return true;
    }

    if (message.type === 'SUBMIT_LEAD' || message.type === 'SUBMIT_LEAD_MANUAL') {
      submitLead(message.payload)
        .then(sendResponse)
        .catch((error) => sendResponse({ error: error.message }));
      return true;
    }

    if (message.type === 'SET_SELECTED_CAMPAIGN') {
      setSelectedCampaign(message.payload.campaignId)
        .then(sendResponse)
        .catch((error) => sendResponse({ error: error.message }));
      return true;
    }

    if (message.type === 'GET_SELECTED_CAMPAIGN') {
      getSelectedCampaign()
        .then(sendResponse)
        .catch((error) => sendResponse({ error: error.message }));
      return true;
    }

    if (message.type === 'TOGGLE_SIDEBAR') {
      // Broadcast to all tabs to toggle sidebar
      chrome.tabs.query({}, (tabs) => {
        tabs.forEach((tab) => {
          if (tab.id) {
            chrome.tabs.sendMessage(tab.id, { type: 'TOGGLE_SIDEBAR' });
          }
        });
      });
      sendResponse({ success: true });
      return true;
    }

    // Simple test: Open URL in new tab for manual testing
    if (message.type === 'OPEN_TEST_TAB') {
      (async () => {
        try {
          const tab = await chrome.tabs.create({
            url: message.payload.url,
            active: true
          });
          sendResponse({ success: true, tabId: tab.id });
        } catch (error: any) {
          sendResponse({ error: error.message });
        }
      })();
      return true;
    }

    // Messaging test: Store state and open profile URL
    if (message.type === 'START_MESSAGING_TEST') {
      (async () => {
        try {
          const { profileUrl, testMessage, platform, openDevMode } = message.payload;

          // Generate unique test ID
          const testId = `test_${Date.now()}`;

          // Store test state
          const testState = {
            active: true,
            profileUrl,
            testMessage,
            openDevMode: openDevMode ?? true,
            testId,
            timestamp: Date.now(),
            platform,
          };

          await messagingTestStorage.setTestState(testState);
          console.log('[BG] ✓ Test state stored:', testState);

          // IMPORTANT: Wait for storage to sync before opening tab
          // This prevents race condition where tab loads before storage is ready
          await new Promise(resolve => setTimeout(resolve, 200));
          console.log('[BG] ✓ Storage sync delay complete, opening tab...');

          // Verify state was stored
          const verifyState = await messagingTestStorage.getTestState();
          if (!verifyState || !verifyState.active) {
            throw new Error('Failed to store test state - verification failed');
          }
          console.log('[BG] ✓ Test state verified:', verifyState);

          // Open profile URL in new tab
          const tab = await chrome.tabs.create({
            url: profileUrl,
            active: true
          });

          console.log('[BG] ✓ Messaging test started successfully:', { testId, profileUrl, tabId: tab.id });
          sendResponse({ success: true, testId, tabId: tab.id });
        } catch (error: any) {
          console.error('[BG] Error starting messaging test:', error);
          sendResponse({ error: error.message });
        }
      })();
      return true;
    }

    // Clear messaging test state
    if (message.type === 'CLEAR_MESSAGING_TEST') {
      (async () => {
        try {
          await messagingTestStorage.clearTestState();
          sendResponse({ success: true });
        } catch (error: any) {
          sendResponse({ error: error.message });
        }
      })();
      return true;
    }

    // OAUTH_COMPLETE: Fire-and-forget notification from sidebar (auth already stored)
    if (message.type === 'OAUTH_COMPLETE') {
      console.log('[BG] ========== OAUTH_COMPLETE (non-blocking) ==========');
      console.log('[BG] User ID:', message.payload?.userId);

      // Respond immediately (don't block sidebar)
      sendResponse({ success: true });

      // Do async work in background (non-blocking)
      (async () => {
        try {
          console.log('[BG] Starting background sync...');

          // Fetch subscription and update storage
          try {
            const subscription = await api.getSubscription();
            const auth = await authStorage.get();
            if (auth) {
              auth.subscription = subscription;
              await authStorage.set(auth);
              console.log('[BG] Subscription synced');
            }
          } catch (subError) {
            console.error('[BG] Subscription sync failed:', subError);
          }

          // Sync campaigns
          try {
            await syncCampaigns();
            console.log('[BG] Campaigns synced');
          } catch (campError) {
            console.error('[BG] Campaign sync failed:', campError);
          }

          console.log('[BG] Background sync complete');
        } catch (error) {
          console.error('[BG] Background sync error:', error);
        }
      })();

      return true;
    }

    // Legacy OAUTH_CALLBACK support (in case it's still called)
    if (message.type === 'OAUTH_CALLBACK') {
      console.log('[BG] OAUTH_CALLBACK (deprecated - use OAUTH_COMPLETE instead)');
      handleOAuthCallback(message.payload)
        .then((result) => {
          console.log('[BG] Sending response:', result);
          sendResponse(result);
        })
        .catch((error) => {
          console.error('[BG] Error in handleOAuthCallback:', error);
          sendResponse({ success: false, error: error.message });
        });
      return true;
    }

    // OPEN_SIDEBAR: Open extension sidebar and optionally trigger sync
    if (message.type === 'OPEN_SIDEBAR') {
      console.log('[BG] OPEN_SIDEBAR received:', message);
      console.log('[BG] Sender tab:', sender.tab?.id, sender.tab?.url);

      // Handle asynchronously
      (async () => {
        try {
          // Set selected campaign first
          if (message.campaignId) {
            await setSelectedCampaign(message.campaignId);
            console.log('[BG] Campaign pre-selected:', message.campaignId);
          }

          // Open sidebar on the active tab
          const tabs = await chrome.tabs.query({ active: true, currentWindow: true });
          console.log('[BG] Active tabs found:', tabs.length);

          if (tabs[0]?.id) {
            console.log('[BG] Sending OPEN_SIDEBAR to tab:', tabs[0].id, tabs[0].url);
            const response = await chrome.tabs.sendMessage(tabs[0].id, {
              type: 'OPEN_SIDEBAR',
              campaignId: message.campaignId,
              autoSync: message.autoSync,
            });
            console.log('[BG] Tab responded:', response);
            sendResponse({ success: true, message: 'Sidebar opened', response });
          } else {
            console.error('[BG] No active tab found!');
            sendResponse({ success: false, error: 'No active tab found' });
          }
        } catch (error) {
          console.error('[BG] Error in OPEN_SIDEBAR handler:', error);
          sendResponse({ success: false, error: error.message });
        }
      })();

      return true; // Keep message channel open for async response
    }

    // CREATE_TAB: Create a new tab (called from content script)
    if (message.type === 'CREATE_TAB') {
      console.log('[BG] CREATE_TAB received, URL:', message.url);
      chrome.tabs.create({ url: message.url, active: true });
      sendResponse({ success: true });
      return true;
    }

    // RUN_SYNC: Trigger extension-based sync
    if (message.type === 'RUN_SYNC') {
      console.log('[BG] RUN_SYNC received:', message.payload || message);

      // Set selected campaign if provided
      const campaignId = message.payload?.campaignId || message.campaignId;
      if (campaignId) {
        setSelectedCampaign(campaignId)
          .then(() => {
            console.log('[BG] Campaign selected for sync:', campaignId);
          })
          .catch(console.error);
      }

      sendResponse({ success: true, message: 'Sync started' });

      // Forward to active tab to execute sync
      chrome.tabs.query({ active: true, currentWindow: true }, (tabs) => {
        if (tabs[0]?.id) {
          chrome.tabs.sendMessage(tabs[0].id, {
            type: 'START_SYNC',
            payload: message.payload || { campaignId },
          });
        }
      });

      return true;
    }
  });

  // Set up periodic plan validation (every 10 minutes)
  if (chrome.alarms) {
    chrome.alarms.create('validatePlan', { periodInMinutes: 10 });
    chrome.alarms.onAlarm.addListener((alarm) => {
      if (alarm.name === 'validatePlan') {
        validatePlan().catch(console.error);
      }
    });
  } else {
    console.warn('[BG] chrome.alarms API not available');
  }
});

/**
 * Helper Functions
 */

async function handleLogin(credentials: { email: string; password: string }) {
  try {
    const { token, user } = await api.login(credentials.email, credentials.password);
    const subscription = await api.getSubscription();

    await authStorage.set({
      isAuthenticated: true,
      user,
      token,
      subscription,
    });

    // Sync campaigns after login
    await syncCampaigns();

    return { success: true, user };
  } catch (error: any) {
    console.error('Login failed:', error);
    throw error;
  }
}

async function handleLogout() {
  try {
    await api.logout();
    await authStorage.clear();
    await campaignStorage.clear();
    return { success: true };
  } catch (error: any) {
    console.error('Logout failed:', error);
    throw error;
  }
}

async function syncCampaigns() {
  try {
    const isAuth = await authStorage.isAuthenticated();
    if (!isAuth) {
      console.log('Not authenticated, skipping campaign sync');
      return { success: false, error: 'Not authenticated' };
    }

    console.log('[BG] Fetching campaigns from API (bypassing cache)...');
    const campaigns = await api.getCampaigns(false); // Force fresh data from API
    await campaignStorage.set(campaigns);
    console.log(`[BG] Synced ${campaigns.length} campaigns to storage`);
    return { success: true, count: campaigns.length };
  } catch (error: any) {
    console.error('[BG] Campaign sync failed:', error);
    throw error;
  }
}

async function validatePlan() {
  try {
    const isAuth = await authStorage.isAuthenticated();
    if (!isAuth) {
      return { valid: false, error: 'Not authenticated' };
    }

    const validation = await api.validatePlan();

    // Update subscription in storage
    const authState = await authStorage.get();
    if (authState && validation.subscription) {
      authState.subscription = validation.subscription;
      await authStorage.set(authState);
    }

    if (!validation.valid) {
      // Show notification if plan is not valid
      chrome.notifications.create({
        type: 'basic',
        iconUrl: 'icon/128.png',
        title: 'EvenLeads: Plan Issue',
        message: 'Your subscription has expired or is inactive. Please update your plan to continue.',
        priority: 2,
      });
    }

    return validation;
  } catch (error: any) {
    console.error('[BG] validatePlan error:', error);
    throw error;
  }
}

async function submitLead(payload: { campaignId: number; lead: any }) {
  try {
    await api.submitLead(payload.campaignId, payload.lead);

    // Show success notification
    chrome.notifications.create({
      type: 'basic',
      iconUrl: 'icon/128.png',
      title: 'EvenLeads',
      message: 'Lead submitted successfully!',
      priority: 1,
    });

    return { success: true };
  } catch (error: any) {
    console.error('Lead submission failed:', error);

    // Show error notification
    chrome.notifications.create({
      type: 'basic',
      iconUrl: 'icon/128.png',
      title: 'EvenLeads: Submission Failed',
      message: error.message || 'Failed to submit lead',
      priority: 2,
    });

    throw error;
  }
}

async function setSelectedCampaign(campaignId: number) {
  try {
    await campaignStorage.setSelected(campaignId);
    console.log('Selected campaign set to:', campaignId);
    return { success: true, campaignId };
  } catch (error: any) {
    console.error('Failed to set selected campaign:', error);
    throw error;
  }
}

async function getSelectedCampaign() {
  try {
    const campaignId = await campaignStorage.getSelected();
    const campaign = await campaignStorage.getSelectedCampaign();
    return { success: true, campaignId, campaign };
  } catch (error: any) {
    console.error('Failed to get selected campaign:', error);
    throw error;
  }
}

async function handleOAuthCallback(payload: { token: string; user: any; isNewUser: boolean }) {
  const startTime = performance.now();
  let keepAliveInterval: NodeJS.Timeout | null = null;

  try {
    console.log('[BG OAuth] Callback started for user:', payload.user?.email);
    console.log('[BG OAuth] Start time:', new Date().toISOString());

    // Keep service worker alive during OAuth process
    keepAliveInterval = setInterval(() => {
      console.log('[BG OAuth] ❤️ Keep-alive ping');
    }, 4000);

    // STEP 1: Store token FIRST before making any authenticated API calls
    console.log('[BG OAuth] STEP 1: Storing auth data...');
    const storeStart = performance.now();

    await authStorage.set({
      isAuthenticated: true,
      user: payload.user,
      token: payload.token,
      subscription: null, // Will fetch next
    });

    const storeEnd = performance.now();
    console.log(`[BG OAuth] Token stored successfully in ${(storeEnd - storeStart).toFixed(0)}ms`);

    // Verify storage write
    const verification = await authStorage.get();
    if (!verification || verification.token !== payload.token) {
      throw new Error('Storage verification failed - token mismatch');
    }
    console.log('[BG OAuth] Storage verified successfully');

    // STEP 2: Fetch subscription with timeout protection
    let subscription = null;
    try {
      console.log('[BG OAuth] Fetching subscription...');

      // Race between subscription fetch and 3-second timeout
      const subscriptionPromise = api.getSubscription();
      const timeoutPromise = new Promise((_, reject) =>
        setTimeout(() => reject(new Error('Subscription fetch timeout')), 3000)
      );

      subscription = await Promise.race([subscriptionPromise, timeoutPromise]) as any;
      console.log('[BG OAuth] Subscription fetched successfully');

      // Update with real subscription data
      await authStorage.set({
        isAuthenticated: true,
        user: payload.user,
        token: payload.token,
        subscription,
      });
      console.log('[BG OAuth] Auth data updated with subscription');
    } catch (subError: any) {
      console.error('[BG OAuth] Failed to fetch subscription:', subError.message);
      console.log('[BG OAuth] Continuing without subscription data');
      // Continue anyway - auth is stored, user can still use the extension without subscription
    }

    // STEP 3: Sync campaigns with timeout protection
    try {
      console.log('[BG OAuth] Syncing campaigns...');

      // Race between campaign sync and 3-second timeout
      const campaignPromise = syncCampaigns();
      const timeoutPromise = new Promise((_, reject) =>
        setTimeout(() => reject(new Error('Campaign sync timeout')), 3000)
      );

      await Promise.race([campaignPromise, timeoutPromise]);
      console.log('[BG OAuth] Campaigns synced successfully');
    } catch (campaignError: any) {
      console.error('[BG OAuth] Failed to sync campaigns:', campaignError.message);
      console.log('[BG OAuth] Continuing without campaign data');
      // Continue anyway
    }

    const endTime = performance.now();
    const totalTime = (endTime - startTime).toFixed(0);
    console.log(`[BG OAuth] ========== CALLBACK COMPLETED in ${totalTime}ms ==========`);

    return { success: true };

  } catch (error: any) {
    console.error('[BG OAuth] ========== CALLBACK FAILED ==========');
    console.error('[BG OAuth] Error:', error);
    console.error('[BG OAuth] Stack:', error.stack);
    return { success: false, error: error.message || 'OAuth callback failed' };
  } finally {
    // Always clear keep-alive interval
    if (keepAliveInterval) {
      clearInterval(keepAliveInterval);
      console.log('[BG OAuth] Keep-alive cleared');
    }
  }
}

/**
 * Platform Engine Test Handlers
 */

async function handlePlatformEngineTest(payload: any, tabId?: number) {
  console.log('[BG] Starting platform engine test:', payload);

  const { platform, mode, profileUrls, testMessage } = payload;

  // Save test state
  await platformEngineTestStorage.setTestState({
    active: true,
    platform,
    mode,
    profileUrls,
    currentIndex: 0,
    testMessage,
    results: [],
    startedAt: Date.now(),
    tabId,
  });

  console.log('[BG] Test state saved, starting execution...');

  if (mode === 'openAndMessage' && profileUrls.length > 0) {
    // Start with first profile
    console.log('[BG] Navigating to first profile:', profileUrls[0]);
    await navigateToProfile(profileUrls[0], tabId);
  } else if (mode === 'sendMessage') {
    // Send message to current tab to execute
    if (tabId) {
      await chrome.tabs.sendMessage(tabId, {
        type: 'EXECUTE_PLATFORM_ENGINE_STEP',
      });
    }
  }

  return { success: true, message: 'Test started' };
}

async function navigateToProfile(profileUrl: string, tabId?: number) {
  console.log('[BG] Navigating to profile:', profileUrl, 'in tab:', tabId);

  if (tabId) {
    await chrome.tabs.update(tabId, { url: profileUrl });
  } else {
    const [currentTab] = await chrome.tabs.query({ active: true, currentWindow: true });
    if (currentTab.id) {
      await chrome.tabs.update(currentTab.id, { url: profileUrl });
    }
  }
}

async function handleEngineStepComplete(payload: any) {
  console.log('[BG] Step complete:', payload);

  const state = await platformEngineTestStorage.getTestState();
  if (!state || !state.active) {
    console.error('[BG] No active test found');
    return { success: false, error: 'No active test' };
  }

  // Add result
  await platformEngineTestStorage.addResult({
    ...payload.result,
    profileUrl: payload.profileUrl,
    index: payload.index,
    timestamp: Date.now(),
  });

  console.log('[BG] Result saved. Current index:', state.currentIndex, 'Total profiles:', state.profileUrls.length);

  // Move to next profile
  const nextIndex = state.currentIndex + 1;

  // Only navigate if message was sent successfully
  if (!payload.result.success || !payload.result.messageSent) {
    console.log('[BG] Message not sent, stopping test');
    state.active = false;
    await platformEngineTestStorage.setTestState(state);
    return { success: false, error: 'Message not sent', completed: true };
  }

  if (nextIndex < state.profileUrls.length) {
    // More profiles to process
    console.log('[BG] Message sent successfully! Moving to next profile, index:', nextIndex);

    // Update state with new index and clear processing flag BEFORE navigation
    const updatedState = await platformEngineTestStorage.getTestState();
    if (updatedState) {
      await platformEngineTestStorage.setTestState({
        ...updatedState,
        currentIndex: nextIndex,
        isProcessing: false // Clear processing flag before navigation
      });
      console.log('[BG] State updated: currentIndex =', nextIndex, ', isProcessing = false');
    }

    // Wait before navigating
    console.log('[BG] Waiting 3 seconds before navigation...');
    await new Promise(resolve => setTimeout(resolve, 3000));

    console.log('[BG] Navigating to next profile:', state.profileUrls[nextIndex]);
    await navigateToProfile(state.profileUrls[nextIndex], state.tabId);

    return { success: true, hasMore: true, nextIndex };
  } else {
    // Test complete
    console.log('[BG] Test complete! Total profiles processed:', state.profileUrls.length);

    // Keep state but mark as inactive so results can be viewed
    state.active = false;
    await platformEngineTestStorage.setTestState(state);

    return { success: true, hasMore: false, completed: true, results: state.results };
  }
}

async function handleStopEngineTest() {
  console.log('[BG] Stopping platform engine test');
  await platformEngineTestStorage.clearTestState();
  return { success: true };
}

async function getPlatformEngineTestState() {
  const state = await platformEngineTestStorage.getTestState();
  return { success: true, state };
}
