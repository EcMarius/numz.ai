(async function() {
  const contentEl = document.querySelector('.popup-content');
  if (!contentEl) return;

  try {
    // Get auth state from storage
    const authData = await chrome.storage.local.get('auth:state');
    const authState = authData['auth:state'];

    if (!authState || !authState.isAuthenticated) {
      // Not authenticated
      contentEl.innerHTML = `
        <div class="popup-unauthenticated">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 auto 1rem; color: #D1D5DB;">
            <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <h2>Welcome!</h2>
          <p>Sign in to start collecting leads</p>
          <button class="popup-btn" id="openSidebarBtn">Open Extension</button>
        </div>
      `;

      document.getElementById('openSidebarBtn').addEventListener('click', async () => {
        const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
        if (tab.id) {
          await chrome.tabs.sendMessage(tab.id, { type: 'TOGGLE_SIDEBAR' });
          window.close();
        }
      });

      return;
    }

    // Fetch stats
    const apiUrlData = await chrome.storage.local.get('api:url');
    const baseUrl = apiUrlData['api:url'] || 'https://evenleads.com';
    const token = authState.token;

    const response = await fetch(`${baseUrl}/api/stats`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    });

    const stats = await response.json();

    // Render authenticated view
    contentEl.innerHTML = `
      ${authState.user ? `
        <div class="popup-user-info">
          ${authState.user.avatar
            ? `<img src="${authState.user.avatar}" class="popup-user-avatar" alt="${authState.user.name}" />`
            : `<div class="popup-user-avatar-fallback">${authState.user.name.charAt(0).toUpperCase()}</div>`
          }
          <div class="popup-user-details">
            <div class="popup-user-name">${authState.user.name}</div>
            <div class="popup-user-email">${authState.user.email}</div>
          </div>
        </div>
      ` : ''}

      <div class="popup-status">
        <div class="popup-status-dot"></div>
        <div class="popup-status-text">Extension is active</div>
      </div>

      <div class="popup-stats-grid">
        <div class="popup-stat-card">
          <div class="popup-stat-label">TOTAL LEADS</div>
          <div class="popup-stat-value">${stats.totalLeads || 0}</div>
        </div>
        <div class="popup-stat-card">
          <div class="popup-stat-label">CAMPAIGNS</div>
          <div class="popup-stat-value">${stats.activeCampaigns || 0}</div>
        </div>
      </div>

      <button class="popup-btn" id="openSidebarBtn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
          <line x1="9" y1="3" x2="9" y2="21"/>
        </svg>
        Open Full Dashboard
      </button>

      <button class="popup-btn popup-btn-secondary" id="openWebBtn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
          <polyline points="15 3 21 3 21 9"/>
          <line x1="10" y1="14" x2="21" y2="3"/>
        </svg>
        Open Web Dashboard
      </button>
    `;

    // Add event listeners
    document.getElementById('openSidebarBtn').addEventListener('click', async () => {
      const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
      if (tab.id) {
        await chrome.tabs.sendMessage(tab.id, { type: 'TOGGLE_SIDEBAR' });
        window.close();
      }
    });

    document.getElementById('openWebBtn').addEventListener('click', () => {
      window.open(`${baseUrl}/dashboard`, '_blank');
    });

  } catch (error) {
    console.error('Failed to load popup:', error);
    contentEl.innerHTML = `
      <div class="popup-error">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 auto 1rem; color: #EF4444;">
          <circle cx="12" cy="12" r="10"/>
          <line x1="15" y1="9" x2="9" y2="15"/>
          <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
        <h2>Error</h2>
        <p>Failed to load extension data</p>
        <button class="popup-btn" onclick="location.reload()">Retry</button>
      </div>
    `;
  }
})();
