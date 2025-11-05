# Platform Engine Test - Cross-Page Persistence Implementation

## Current Status

✅ **Completed**:
- Storage methods added to `utils/storage.ts`
- `PlatformEngineTestState` interface created
- `platformEngineTestStorage` methods created
- `sidebarStorage` for sidebar state

## What Needs to Be Implemented

### 1. Background Script Message Handlers

**File**: `entrypoints/background.ts`

**Add after line 113** (after TOGGLE_SIDEBAR handler):

```typescript
// Platform Engine Test Orchestration
if (message.type === 'START_PLATFORM_ENGINE_TEST') {
  handlePlatformEngineTest(message.payload, sender.tab?.id)
    .then(sendResponse)
    .catch((error) => sendResponse({ error: error.message }));
  return true;
}

if (message.type === 'PLATFORM_ENGINE_STEP_COMPLETE') {
  handleEngineStepComplete(message.payload)
    .then(sendResponse)
    .catch((error) => sendResponse({ error: error.message }));
  return true;
}

if (message.type === 'STOP_PLATFORM_ENGINE_TEST') {
  handleStopEngineTest()
    .then(sendResponse)
    .catch((error) => sendResponse({ error: error.message }));
  return true;
}
```

**Add before `export default defineBackground`** (end of file):

```typescript
import { platformEngineTestStorage } from '../utils/storage';

async function handlePlatformEngineTest(payload: any, tabId?: number) {
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

  if (mode === 'openAndMessage' && profileUrls.length > 0) {
    // Start with first profile
    await navigateToProfile(profileUrls[0], tabId);
  }

  return { success: true, message: 'Test started' };
}

async function navigateToProfile(profileUrl: string, tabId?: number) {
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
  const state = await platformEngineTestStorage.getTestState();
  if (!state || !state.active) {
    return { success: false, error: 'No active test' };
  }

  // Add result
  await platformEngineTestStorage.addResult(payload.result);

  // Move to next profile
  const nextIndex = state.currentIndex + 1;

  if (nextIndex < state.profileUrls.length) {
    // More profiles to process
    await platformEngineTestStorage.updateCurrentIndex(nextIndex);
    await navigateToProfile(state.profileUrls[nextIndex], state.tabId);

    return { success: true, hasMore: true, nextIndex };
  } else {
    // Test complete
    await platformEngineTestStorage.clearTestState();

    return { success: true, hasMore: false, completed: true };
  }
}

async function handleStopEngineTest() {
  await platformEngineTestStorage.clearTestState();
  return { success: true };
}
```

---

### 2. DevMode Panel - Send to Background Script

**File**: `components/DevMode/DevModePanel.tsx`

**Replace `handleRunPlatformEngineTest` function**:

```typescript
const handleRunPlatformEngineTest = async () => {
  if (engineRunning) {
    alert('⚠️ Test already running! Please wait for it to complete.');
    return;
  }

  if (engineTestMode === 'openAndMessage' && !engineProfileUrls.trim()) {
    alert('⚠️ Please enter at least one profile URL!');
    return;
  }

  if (engineTestMode === 'sendMessage' && !engineTestMessage.trim()) {
    alert('⚠️ Please enter a test message!');
    return;
  }

  const confirmMessage = engineTestMode === 'openAndMessage'
    ? `🧪 Platform Engine Test\n\nMode: Open Profile & Click Message\nPlatform: ${engineTestPlatform}\n\nProfile URLs:\n${engineProfileUrls}\n\nThis will:\n1. Navigate to each profile\n2. Click the "Message" button\n3. Wait for messaging interface\n4. State will persist across page navigations\n\nContinue?`
    : `🧪 Platform Engine Test\n\nMode: Send Message\nPlatform: ${engineTestPlatform}\n\nMessage:\n"${engineTestMessage}"\n\nThis will send the message in the current messaging interface.\n\nContinue?`;

  if (!confirm(confirmMessage)) {
    return;
  }

  setEngineRunning(true);
  setEngineResults([]);

  try {
    const urls = engineProfileUrls.split('\n').map(url => url.trim()).filter(url => url);

    // Send to background script for orchestration
    const response = await chrome.runtime.sendMessage({
      type: 'START_PLATFORM_ENGINE_TEST',
      payload: {
        platform: engineTestPlatform,
        mode: engineTestMode,
        profileUrls: urls,
        testMessage: engineTestMessage,
      },
    });

    if (response.success) {
      console.log('[DevMode] Test started successfully');
      // The background script will handle navigation
      // Sidebar will auto-resume on each page load
    } else {
      alert(`❌ Failed to start test: ${response.error}`);
      setEngineRunning(false);
    }
  } catch (error) {
    console.error('[DevMode] Error starting platform engine test:', error);
    alert(`❌ Error!\n\n${error instanceof Error ? error.message : 'Unknown error'}`);
    setEngineRunning(false);
  }
};
```

---

### 3. Sidebar - Auto-Resume Test on Page Load

**File**: `entrypoints/sidebar.content.tsx`

**Add after sidebar initialization** (around line 150-200, inside the main App component):

```typescript
// Inside the App component, add useEffect for auto-resume

useEffect(() => {
  // Check if there's an active platform engine test
  const checkAndResumeTest = async () => {
    const testState = await platformEngineTestStorage.getTestState();

    if (testState && testState.active) {
      console.log('[Sidebar] Active platform engine test detected, resuming...');

      // Auto-open sidebar if needed
      if (!isSidebarOpen) {
        setIsSidebarOpen(true);
      }

      // Auto-open DevMode if needed
      const devModeEnabled = await devModeStorage.isDevModeEnabled();
      if (devModeEnabled && !isDevModeOpen) {
        setIsDevModeOpen(true);
      }

      // Execute current step
      await executeEnginestep(testState);
    }
  };

  checkAndResumeTest();
}, []); // Run once on mount

async function executeEngineStep(testState: PlatformEngineTestState) {
  const { createPlatformEngine } = await import('../utils/engines');
  const engine = createPlatformEngine(testState.platform as any);

  await engine.loadSchemas(['profile', 'messaging']);

  const currentUrl = window.location.href;
  const currentProfileUrl = testState.profileUrls[testState.currentIndex];

  // Check if we're on the right profile
  if (!currentUrl.includes(currentProfileUrl.split('/').pop() || '')) {
    console.warn('[Sidebar] Not on expected profile URL, waiting...');
    return;
  }

  // Execute the action
  const result = await engine.openProfileAndMessage(currentProfileUrl);

  // Notify background script that step is complete
  await chrome.runtime.sendMessage({
    type: 'PLATFORM_ENGINE_STEP_COMPLETE',
    payload: {
      result,
      profileUrl: currentProfileUrl,
      index: testState.currentIndex,
    },
  });
}
```

---

### 4. Auto-Open Sidebar on Test Resume

**File**: `entrypoints/sidebar.content.tsx`

**Modify sidebar state initialization**:

```typescript
// Check if sidebar should be open (from storage or active test)
const [isSidebarOpen, setIsSidebarOpen] = useState(false);

useEffect(() => {
  const initSidebarState = async () => {
    // Check if test is active
    const testActive = await platformEngineTestStorage.isTestActive();

    // Check stored sidebar state
    const wasOpen = await sidebarStorage.isOpen();

    // Open if test is active OR was previously open
    if (testActive || wasOpen) {
      setIsSidebarOpen(true);
    }
  };

  initSidebarState();
}, []);

// Save sidebar state when it changes
useEffect(() => {
  sidebarStorage.setOpen(isSidebarOpen);
}, [isSidebarOpen]);
```

---

## Implementation Order

1. ✅ Storage methods (DONE)
2. ⚠️ Background script handlers (TODO)
3. ⚠️ DevMode panel update (TODO)
4. ⚠️ Sidebar auto-resume (TODO)
5. ⚠️ Testing (TODO)

---

## Expected Flow After Implementation

```
1. User opens DevMode → Enters profile URLs → Clicks "Run Test"
   ↓
2. DevMode sends START_PLATFORM_ENGINE_TEST to background script
   ↓
3. Background script:
   - Saves test state to chrome.storage
   - Navigates to first profile URL
   ↓
4. Page loads → Sidebar auto-initializes:
   - Checks chrome.storage for active test
   - Finds active test
   - Auto-opens sidebar
   - Auto-opens DevMode (if was open)
   - Executes current step (click Message button)
   ↓
5. Step completes → Sends PLATFORM_ENGINE_STEP_COMPLETE to background
   ↓
6. Background script:
   - Saves result
   - Moves to next profile
   - Navigates to next URL
   ↓
7. Repeat steps 4-6 for each profile
   ↓
8. Last profile completes:
   - Background clears test state
   - Sidebar shows "Test Complete!" message
   - Results displayed in DevMode panel
```

---

## Files to Modify (Summary)

1. ✅ `utils/storage.ts` - Add storage (DONE)
2. ⚠️ `entrypoints/background.ts` - Add handlers (~100 lines)
3. ⚠️ `components/DevMode/DevModePanel.tsx` - Update test handler (~50 lines)
4. ⚠️ `entrypoints/sidebar.content.tsx` - Add auto-resume (~80 lines)

**Total**: ~230 lines of code to add

---

## Testing Checklist

After implementation:
- [ ] Test state persists across page navigation
- [ ] Sidebar auto-opens on resume
- [ ] DevMode auto-opens on resume
- [ ] Each profile is processed sequentially
- [ ] Results are collected
- [ ] Test completes successfully
- [ ] State is cleared after completion
- [ ] Can stop test mid-way
- [ ] Error handling works

---

## Next Session Recommendation

This is a significant architectural change that requires careful implementation. I recommend:

1. **Session 1** (Next): Implement background orchestration
2. **Session 2**: Implement sidebar auto-resume
3. **Session 3**: Testing and refinement

OR complete it all in one long session (estimated 2-3 hours).
