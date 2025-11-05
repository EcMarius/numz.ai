import { User, CreditCard, ExternalLink, Shield, RefreshCw } from 'lucide-react';
import type { AuthState } from '../types';
import { useState, useEffect } from 'react';
import { getApiBaseUrl } from '../config';

interface AccountInfoProps {
  authState: AuthState;
  onLogout: () => void;
  baseUrl?: string;
}

export default function AccountInfo({ authState, onLogout, baseUrl }: AccountInfoProps) {
  const [currentAuthState, setCurrentAuthState] = useState(authState);
  const { user, subscription } = currentAuthState;
  const apiBaseUrl = baseUrl || getApiBaseUrl();
  const [dashboardUrl, setDashboardUrl] = useState(`${apiBaseUrl}/dashboard`);
  const [refreshing, setRefreshing] = useState(false);
  const [refreshMessage, setRefreshMessage] = useState<string | null>(null);

  useEffect(() => {
    setDashboardUrl(`${apiBaseUrl}/dashboard`);
  }, [apiBaseUrl]);

  useEffect(() => {
    setCurrentAuthState(authState);
  }, [authState]);

  // Listen for subscription updates
  useEffect(() => {
    const handleSubscriptionUpdate = async () => {
      const { authStorage } = await import('../utils/storage');
      const freshAuthState = await authStorage.get();
      if (freshAuthState) {
        setCurrentAuthState(freshAuthState);
      }
    };

    window.addEventListener('subscription-updated', handleSubscriptionUpdate);
    return () => window.removeEventListener('subscription-updated', handleSubscriptionUpdate);
  }, []);

  // Format numbers over 999 with dot separator (e.g., 1.000, 10.000)
  const formatNumber = (num: number | undefined | null): string => {
    if (num === undefined || num === null) return '0';
    if (num > 999) {
      return num.toLocaleString('de-DE'); // German locale uses dot as thousand separator
    }
    return num.toString();
  };

  async function handleRefreshSubscription() {
    setRefreshing(true);
    setRefreshMessage(null);

    try {
      const response = await chrome.runtime.sendMessage({ type: 'VALIDATE_PLAN' });

      if (response && response.error) {
        setRefreshMessage(`Failed to refresh: ${response.error}`);
      } else if (response && response.valid && response.subscription) {
        // Update the displayed subscription data without reloading
        const { authStorage } = await import('../utils/storage');
        const authState = await authStorage.get();
        if (authState) {
          authState.subscription = response.subscription;
          await authStorage.set(authState);
          // Trigger a re-render by updating the parent component
          window.dispatchEvent(new CustomEvent('subscription-updated'));
        }
        setRefreshMessage('Subscription updated!');
        setTimeout(() => setRefreshMessage(null), 3000);
      } else {
        setRefreshMessage('No active subscription found');
      }
    } catch (error: any) {
      console.error('[AccountInfo] Failed to refresh subscription:', error);
      setRefreshMessage(`Error: ${error.message || 'Unknown error'}`);
    } finally {
      setRefreshing(false);
    }
  }

  return (
    <div style={{ backgroundColor: '#FFFFFF', padding: '1.25rem', display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
      {/* User Info */}
      <div style={{ backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', padding: '1.25rem', borderRadius: '0.5rem' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '1rem', marginBottom: '1rem' }}>
          {user?.avatar && user.avatar.startsWith('http') ? (
            <img
              src={user.avatar}
              alt={user.name}
              style={{ width: '3rem', height: '3rem', borderRadius: '9999px', border: '2px solid #000000' }}
              onError={(e) => {
                e.currentTarget.style.display = 'none';
                const fallback = e.currentTarget.nextElementSibling as HTMLElement;
                if (fallback) fallback.style.display = 'flex';
              }}
            />
          ) : null}
          <div style={{ width: '3rem', height: '3rem', borderRadius: '9999px', display: (user?.avatar && user.avatar.startsWith('http')) ? 'none' : 'flex', alignItems: 'center', justifyContent: 'center', backgroundColor: '#E5E7EB' }}>
            <User style={{ width: '1.5rem', height: '1.5rem', color: '#6B7280' }} />
          </div>
          <div style={{ flex: 1 }}>
            <h3 style={{ fontWeight: 600, color: '#000000' }}>{user?.name}</h3>
            <p style={{ fontSize: '0.875rem', color: '#6B7280' }}>{user?.email}</p>
          </div>
        </div>

        <div style={{ paddingLeft: '1rem', paddingRight: '1rem', paddingBottom: '0.75rem' }}>
          <a
            href={dashboardUrl}
            target="_blank"
            rel="noopener noreferrer"
            style={{
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '0.5rem',
              width: '100%',
              paddingTop: '0.5rem',
              paddingBottom: '0.5rem',
              paddingLeft: '1rem',
              paddingRight: '1rem',
              borderRadius: '0.5rem',
              fontSize: '0.875rem',
              fontWeight: 500,
              transition: 'background-color 0.2s',
              backgroundColor: 'transparent',
              border: '1px solid #D1D5DB',
              color: '#000000',
              textDecoration: 'none',
            }}
            onMouseEnter={(e) => {
              e.currentTarget.style.backgroundColor = '#F3F4F6';
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.backgroundColor = 'transparent';
            }}
          >
            <ExternalLink style={{ width: '1rem', height: '1rem' }} />
            Open Dashboard
          </a>
        </div>
      </div>

      {/* Subscription Info */}
      {subscription && (
        <div style={{ backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', borderRadius: '0.5rem', padding: '1rem' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.75rem' }}>
            <CreditCard style={{ width: '1.25rem', height: '1.25rem', color: '#000000' }} />
            <h3 style={{ fontWeight: 600, color: '#000000' }}>Subscription</h3>
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem', fontSize: '0.875rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span style={{ color: '#6B7280' }}>Plan</span>
              <span style={{ fontWeight: 500, color: '#000000' }}>{subscription.plan?.name || 'Unknown'}</span>
            </div>

            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span style={{ color: '#6B7280' }}>Status</span>
              <span style={{
                paddingLeft: '0.5rem',
                paddingRight: '0.5rem',
                paddingTop: '0.125rem',
                paddingBottom: '0.125rem',
                borderRadius: '9999px',
                fontSize: '0.75rem',
                fontWeight: 500,
                backgroundColor: subscription.status === 'active' ? '#D1FAE5' : subscription.status === 'trialing' ? '#DBEAFE' : '#F3F4F6',
                color: subscription.status === 'active' ? '#065F46' : subscription.status === 'trialing' ? '#1E40AF' : '#374151',
              }}>
                {subscription.status}
              </span>
            </div>

            {subscription.trial_ends_at && (
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span style={{ color: '#6B7280' }}>Trial Ends</span>
                <span style={{ fontWeight: 500, color: '#000000' }}>
                  {new Date(subscription.trial_ends_at).toLocaleDateString()}
                </span>
              </div>
            )}

            {subscription.ends_at && (
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span style={{ color: '#6B7280' }}>Ends At</span>
                <span style={{ fontWeight: 500, color: '#000000' }}>
                  {new Date(subscription.ends_at).toLocaleDateString()}
                </span>
              </div>
            )}
          </div>

          {subscription.plan && (
            <div style={{ marginTop: '1rem', paddingTop: '1rem', borderTop: '1px solid #E5E7EB' }}>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem', fontSize: '0.875rem' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span style={{ color: '#6B7280' }}>Campaigns</span>
                  <span style={{ fontWeight: 500, color: '#000000' }}>
                    {formatNumber(subscription.used_campaigns)}/{formatNumber(subscription.plan.campaigns_limit)}
                  </span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span style={{ color: '#6B7280' }}>Manual Syncs</span>
                  <span style={{ fontWeight: 500, color: '#000000' }}>
                    {formatNumber(subscription.used_manual_syncs)}/{formatNumber(subscription.plan.manual_syncs_limit)} month
                  </span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span style={{ color: '#6B7280' }}>AI Replies</span>
                  <span style={{ fontWeight: 500, color: '#000000' }}>
                    {formatNumber(subscription.used_ai_replies)}/{formatNumber(subscription.plan.ai_replies_limit)} month
                  </span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span style={{ color: '#6B7280' }}>Leads</span>
                  <span style={{ fontWeight: 500, color: '#000000' }}>
                    {formatNumber(subscription.used_leads)}/{subscription.plan.leads_limit ? formatNumber(subscription.plan.leads_limit) : 'unlimited'}
                  </span>
                </div>
              </div>
            </div>
          )}

          {/* Refresh Message */}
          {refreshMessage && (
            <div style={{
              marginTop: '0.75rem',
              padding: '0.5rem',
              borderRadius: '0.5rem',
              fontSize: '0.75rem',
              fontWeight: 500,
              textAlign: 'center',
              backgroundColor: refreshMessage.includes('Error') || refreshMessage.includes('Failed') ? '#FEE2E2' : '#D1FAE5',
              color: refreshMessage.includes('Error') || refreshMessage.includes('Failed') ? '#991B1B' : '#065F46',
            }}>
              {refreshMessage}
            </div>
          )}

          {/* Action Buttons */}
          <div style={{ marginTop: '1rem', display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
            <button
              onClick={handleRefreshSubscription}
              disabled={refreshing}
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '0.5rem',
                width: '100%',
                padding: '0.5rem 1rem',
                backgroundColor: '#FFFFFF',
                border: '1px solid #D1D5DB',
                color: '#000000',
                borderRadius: '0.5rem',
                fontSize: '0.875rem',
                fontWeight: 500,
                transition: 'background-color 0.2s',
                cursor: refreshing ? 'not-allowed' : 'pointer',
                opacity: refreshing ? 0.5 : 1,
              }}
              onMouseEnter={(e) => {
                if (!refreshing) e.currentTarget.style.backgroundColor = '#F9FAFB';
              }}
              onMouseLeave={(e) => {
                if (!refreshing) e.currentTarget.style.backgroundColor = '#FFFFFF';
              }}
            >
              <RefreshCw style={{ width: '1rem', height: '1rem', animation: refreshing ? 'spin 1s linear infinite' : 'none' }} />
              {refreshing ? 'Refreshing...' : 'Refresh Subscription'}
            </button>

            <a
              href="https://evenleads.com/settings/subscription"
              target="_blank"
              rel="noopener noreferrer"
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '0.5rem',
                width: '100%',
                padding: '0.5rem 1rem',
                backgroundColor: '#000000',
                color: '#FFFFFF',
                borderRadius: '0.5rem',
                fontSize: '0.875rem',
                fontWeight: 500,
                transition: 'background-color 0.2s',
                textDecoration: 'none',
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.backgroundColor = '#1F2937';
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.backgroundColor = '#000000';
              }}
            >
              Manage Subscription
            </a>

            <p style={{ fontSize: '0.75rem', color: '#6B7280', textAlign: 'center', marginTop: '0.5rem' }}>
              Just purchased a plan? Click "Refresh Subscription" to update.
            </p>
          </div>
        </div>
      )}

      {/* Security & Privacy */}
      <div style={{ border: '1px solid #E5E7EB', borderRadius: '0.5rem', padding: '1rem' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.75rem' }}>
          <Shield style={{ width: '1.25rem', height: '1.25rem', color: '#000000' }} />
          <h3 style={{ fontWeight: 600, color: '#000000' }}>Security & Privacy</h3>
        </div>

        <p style={{ fontSize: '0.875rem', color: '#6B7280', marginBottom: '1rem' }}>
          This extension collects leads from the platforms you visit based on your campaign settings.
          All data is securely transmitted to your EvenLeads account.
        </p>

        <a
          href="https://evenleads.com/privacy"
          target="_blank"
          rel="noopener noreferrer"
          style={{ fontSize: '0.875rem', color: '#000000', textDecoration: 'none' }}
          onMouseEnter={(e) => {
            e.currentTarget.style.textDecoration = 'underline';
          }}
          onMouseLeave={(e) => {
            e.currentTarget.style.textDecoration = 'none';
          }}
        >
          Privacy Policy
        </a>
      </div>

      {/* Logout Button */}
      <button
        onClick={onLogout}
        style={{
          width: '100%',
          padding: '0.625rem 1rem',
          borderRadius: '0.5rem',
          fontWeight: 500,
          transition: 'background-color 0.2s',
          backgroundColor: '#000000',
          color: '#FFFFFF',
          border: 'none',
          cursor: 'pointer',
        }}
        onMouseEnter={(e) => {
          e.currentTarget.style.backgroundColor = '#1F2937';
        }}
        onMouseLeave={(e) => {
          e.currentTarget.style.backgroundColor = '#000000';
        }}
      >
        Sign Out
      </button>

      {/* Version */}
      <div style={{ textAlign: 'center', fontSize: '0.75rem', color: '#9CA3AF' }}>
        Version 1.0.0
      </div>
    </div>
  );
}
