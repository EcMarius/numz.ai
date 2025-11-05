import React, { useState, useEffect } from 'react';
import { Settings, Bell, ExternalLink, X, Code } from 'lucide-react';
import { config } from '../config';
import { devModeStorage } from '../utils/storage';

interface SettingsViewProps {
  onClose?: () => void;
  onDevModeToggle?: (enabled: boolean) => void;
  user?: { id: number; name: string; email: string; avatar?: string; role_id?: number; roles?: string[] } | null;
  isLinkedIn?: boolean;
}

export default function SettingsView({ onClose, onDevModeToggle, user, isLinkedIn }: SettingsViewProps) {
  // SECURITY: Only check roles array (not role_id)
  // Also verify domain to prevent local spoofing
  const [isAdmin, setIsAdmin] = useState(false);
  const [isVerified, setIsVerified] = useState(false);

  useEffect(() => {
    verifyAdminAccess();
  }, [user]);

  async function verifyAdminAccess() {
    try {
      // Step 1: Check roles array for admin role
      const hasAdminRole = user?.roles?.includes('admin') || false;

      if (!hasAdminRole) {
        setIsAdmin(false);
        setIsVerified(true);
        return;
      }

      // Step 2: Verify domain (must be evenleads.com or localhost for development)
      const { getApiBaseUrl } = await import('../config');
      const baseUrl = getApiBaseUrl();
      const isProductionDomain = baseUrl.includes('evenleads.com');
      const isLocalDomain = baseUrl.includes('localhost') || baseUrl.includes('127.0.0.1');
      const isValidDomain = isProductionDomain || isLocalDomain;

      if (!isValidDomain) {
        setIsAdmin(false);
        setIsVerified(true);
        return;
      }

      // Step 3: Validate token with server
      const { api } = await import('../utils/api');
      try {
        const response = await api.validateExtensionToken();

        if (response.valid && response.user?.roles?.includes('admin')) {
          setIsAdmin(true);
        } else {
          setIsAdmin(false);
        }
      } catch (error) {
        console.error('[SettingsView] Token validation error:', error);
        setIsAdmin(false);
      }

      setIsVerified(true);
    } catch (error) {
      console.error('[SettingsView] Admin verification failed:', error);
      setIsAdmin(false);
      setIsVerified(true);
    }
  }

  // Debug logging
  useEffect(() => {
    // If roles array is missing, force refresh user data
    if (user && !user.roles) {
      console.warn('[SettingsView] roles array is MISSING! Forcing user data refresh...');
      refreshUserData();
    }
  }, [user]);

  async function refreshUserData() {
    try {
      const { api } = await import('../utils/api');
      const { authStorage } = await import('../utils/storage');

      console.log('[SettingsView] Fetching fresh user data from API...');
      const freshUser = await api.getUser();
      console.log('[SettingsView] Fresh user data:', freshUser);

      // Update auth state with fresh user data
      const authState = await authStorage.get();
      if (authState) {
        authState.user = freshUser;
        await authStorage.set(authState);
        console.log('[SettingsView] Auth state updated with roles:', freshUser.roles || freshUser.role_id);

        // Force page refresh to reload with new data
        window.location.reload();
      }
    } catch (error) {
      console.error('[SettingsView] Failed to refresh user data:', error);
    }
  }

  const [notificationSettings, setNotificationSettings] = useState({
    leadDetected: true,
    quotaWarnings: true,
    syncErrors: true,
  });

  const [devModeEnabled, setDevModeEnabled] = useState(false);

  useEffect(() => {
    loadDevModeState();
  }, []);

  async function loadDevModeState() {
    const enabled = await devModeStorage.isEnabled();
    setDevModeEnabled(enabled);
  }

  async function handleDevModeToggle(enabled: boolean) {
    setDevModeEnabled(enabled);
    await devModeStorage.setEnabled(enabled);
    if (onDevModeToggle) {
      onDevModeToggle(enabled);
    }
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', height: '100%', backgroundColor: '#FFFFFF' }}>
      {/* Header */}
      <div style={{ padding: '1.5rem 2rem', borderBottom: '1px solid #E5E7EB' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
          <div style={{ width: '2.5rem', height: '2.5rem', borderRadius: '0.5rem', backgroundColor: '#000000', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <Settings style={{ width: '1.25rem', height: '1.25rem', color: '#FFFFFF' }} />
          </div>
          <div>
            <h2 style={{ fontSize: '1.25rem', lineHeight: '1.75rem', fontWeight: 700, color: '#000000' }}>Settings</h2>
            <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#6B7280' }}>Configure your extension</p>
          </div>
        </div>
      </div>

      {/* Content */}
      <div style={{ flex: '1 1 0%', overflowY: 'auto', padding: '1.5rem 2rem' }}>
        <div style={{ maxWidth: '48rem', display: 'flex', flexDirection: 'column', gap: '2rem' }}>

          {/* Notifications Settings */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
              <Bell style={{ width: '1.25rem', height: '1.25rem', color: '#000000' }} />
              <h3 style={{ fontSize: '1.125rem', lineHeight: '1.75rem', fontWeight: 600, color: '#000000' }}>Notifications</h3>
            </div>

            <div style={{ backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', borderRadius: '0.5rem', padding: '1rem', display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
              <label style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', cursor: 'pointer' }}>
                <div>
                  <div style={{ fontSize: '0.875rem', lineHeight: '1.25rem', fontWeight: 500, color: '#000000' }}>Lead Detected</div>
                  <div style={{ fontSize: '0.75rem', lineHeight: '1rem', color: '#6B7280' }}>Show notification when a new lead is found</div>
                </div>
                <input
                  type="checkbox"
                  checked={notificationSettings.leadDetected}
                  onChange={(e) => setNotificationSettings({ ...notificationSettings, leadDetected: e.target.checked })}
                  style={{ width: '1.25rem', height: '1.25rem', accentColor: '#000000', cursor: 'pointer' }}
                />
              </label>

              <label style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', cursor: 'pointer' }}>
                <div>
                  <div style={{ fontSize: '0.875rem', lineHeight: '1.25rem', fontWeight: 500, color: '#000000' }}>Quota Warnings</div>
                  <div style={{ fontSize: '0.75rem', lineHeight: '1rem', color: '#6B7280' }}>Notify when approaching plan limits</div>
                </div>
                <input
                  type="checkbox"
                  checked={notificationSettings.quotaWarnings}
                  onChange={(e) => setNotificationSettings({ ...notificationSettings, quotaWarnings: e.target.checked })}
                  style={{ width: '1.25rem', height: '1.25rem', accentColor: '#000000', cursor: 'pointer' }}
                />
              </label>

              <label style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', cursor: 'pointer' }}>
                <div>
                  <div style={{ fontSize: '0.875rem', lineHeight: '1.25rem', fontWeight: 500, color: '#000000' }}>Sync Errors</div>
                  <div style={{ fontSize: '0.75rem', lineHeight: '1rem', color: '#6B7280' }}>Alert on synchronization failures</div>
                </div>
                <input
                  type="checkbox"
                  checked={notificationSettings.syncErrors}
                  onChange={(e) => setNotificationSettings({ ...notificationSettings, syncErrors: e.target.checked })}
                  style={{ width: '1.25rem', height: '1.25rem', accentColor: '#000000', cursor: 'pointer' }}
                />
              </label>
            </div>
          </div>

          {/* Developer Settings - Admin Only */}
          {isVerified && isAdmin && (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                <Code style={{ width: '1.25rem', height: '1.25rem', color: '#000000' }} />
                <h3 style={{ fontSize: '1.125rem', lineHeight: '1.75rem', fontWeight: 600, color: '#000000' }}>Developer</h3>
              </div>

            <div style={{ backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', borderRadius: '0.5rem', padding: '1rem', display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
              <label style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', cursor: 'pointer' }}>
                <div>
                  <div style={{ fontSize: '0.875rem', lineHeight: '1.25rem', fontWeight: 500, color: '#000000' }}>DEV Mode</div>
                  <div style={{ fontSize: '0.75rem', lineHeight: '1rem', color: '#6B7280' }}>
                    Enable element inspector for building platform schemas
                  </div>
                </div>
                <input
                  type="checkbox"
                  checked={devModeEnabled}
                  onChange={(e) => handleDevModeToggle(e.target.checked)}
                  style={{ width: '1.25rem', height: '1.25rem', accentColor: '#3B82F6', cursor: 'pointer' }}
                />
              </label>

              {devModeEnabled && !isLinkedIn && (
                <div style={{
                  marginTop: '0.5rem',
                  padding: '0.75rem',
                  backgroundColor: '#DBEAFE',
                  borderRadius: '0.375rem',
                  fontSize: '0.75rem',
                  color: '#1E40AF',
                }}>
                  <strong>DEV Mode Active:</strong> Use the floating panel to inspect elements and build schemas for platforms. Export the schema and import it in the admin panel.
                </div>
              )}

              {devModeEnabled && isLinkedIn && (
                <div style={{
                  marginTop: '0.5rem',
                  padding: '0.75rem',
                  backgroundColor: '#FEE2E2',
                  borderRadius: '0.375rem',
                  fontSize: '0.75rem',
                  color: '#991B1B',
                  lineHeight: '1.5',
                }}>
                  <strong>⚠️ DevMode Disabled on LinkedIn:</strong> DevMode causes compatibility issues with LinkedIn's JavaScript and generates thousands of invalid URL requests. Please use DevMode on other platforms (Reddit, Facebook, etc.) or disable it while on LinkedIn.
                </div>
              )}
            </div>
            </div>
          )}

          {/* Verifying admin access */}
          {!isVerified && (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                <Code style={{ width: '1.25rem', height: '1.25rem', color: '#9CA3AF' }} />
                <h3 style={{ fontSize: '1.125rem', lineHeight: '1.75rem', fontWeight: 600, color: '#9CA3AF' }}>Developer</h3>
              </div>
              <div style={{ backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', borderRadius: '0.5rem', padding: '1rem', textAlign: 'center' }}>
                <p style={{ fontSize: '0.75rem', color: '#6B7280' }}>Verifying admin access...</p>
              </div>
            </div>
          )}

          {/* About */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
            <h3 style={{ fontSize: '1.125rem', lineHeight: '1.75rem', fontWeight: 600, color: '#000000' }}>About</h3>

            <div style={{ backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', borderRadius: '0.5rem', padding: '1rem', display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <div style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#6B7280' }}>Version</div>
                <div style={{ fontSize: '0.875rem', lineHeight: '1.25rem', fontWeight: 500, color: '#000000' }}>{config.VERSION}</div>
              </div>

              <div style={{ borderTop: '1px solid #E5E7EB', paddingTop: '0.75rem' }}>
                <a
                  href={`${config.API_BASE_URL}/docs`}
                  target="_blank"
                  rel="noopener noreferrer"
                  style={{ display: 'inline-flex', alignItems: 'center', gap: '0.5rem', fontSize: '0.875rem', lineHeight: '1.25rem', color: '#000000', textDecoration: 'none', transition: 'text-decoration 0.2s' }}
                  onMouseEnter={(e) => {
                    e.currentTarget.style.textDecoration = 'underline';
                  }}
                  onMouseLeave={(e) => {
                    e.currentTarget.style.textDecoration = 'none';
                  }}
                >
                  <ExternalLink style={{ width: '1rem', height: '1rem' }} />
                  View Documentation
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  );
}
