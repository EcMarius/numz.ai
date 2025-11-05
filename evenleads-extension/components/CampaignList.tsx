import { useState, useEffect } from 'react';
import { RefreshCw, ExternalLink, Target, Zap, Pause, Play, Loader2 } from 'lucide-react';
import type { Campaign } from '../types';
import CampaignSelector from './CampaignSelector';
import { campaignStorage } from '../utils/storage';

interface CampaignListProps {
  campaigns: Campaign[];
  onRefresh: () => void;
  refreshing: boolean;
}

const PLATFORM_COLORS: Record<string, string> = {
  facebook: '#1877F2',
  linkedin: '#0A66C2',
  reddit: '#FF4500',
  fiverr: '#1DBF73',
  upwork: '#14A800',
  x: '#000000',
};

export default function CampaignList({ campaigns, onRefresh, refreshing }: CampaignListProps) {
  const [selectedCampaignId, setSelectedCampaignId] = useState<number | null>(null);

  useEffect(() => {
    loadSelectedCampaign();
  }, []);

  // Reload selected campaign when campaigns list changes
  useEffect(() => {
    if (campaigns.length > 0) {
      loadSelectedCampaign();
    }
  }, [campaigns]);

  async function loadSelectedCampaign() {
    const selected = await campaignStorage.getSelected();
    console.log('[CampaignList] Loaded selected campaign ID:', selected);
    setSelectedCampaignId(selected);
  }

  async function handleSelectCampaign(campaignId: number) {
    setSelectedCampaignId(campaignId);
    try {
      await chrome.runtime.sendMessage({
        type: 'SET_SELECTED_CAMPAIGN',
        payload: { campaignId },
      });
    } catch (error: any) {
      // Silently handle extension context invalidation
      if (!error.message?.includes('Extension context invalidated') &&
          !error.message?.includes('message port closed')) {
        console.error('[CampaignList] Failed to set selected campaign:', error);
      }
    }
  }

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'active':
        return <Play className="w-3 h-3" />;
      case 'paused':
        return <Pause className="w-3 h-3" />;
      case 'syncing':
        return <Loader2 className="w-3 h-3 animate-spin" />;
      default:
        return null;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'active':
        return { bg: '#D1FAE5', text: '#065F46' };
      case 'paused':
        return { bg: '#FEF3C7', text: '#92400E' };
      case 'syncing':
        return { bg: '#DBEAFE', text: '#1E40AF' };
      default:
        return { bg: '#F3F4F6', text: '#4B5563' };
    }
  };

  if (campaigns.length === 0) {
    return (
      <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '100%', padding: '0 2rem', backgroundColor: '#FFFFFF' }}>
        <div style={{ width: '5rem', height: '5rem', borderRadius: '9999px', backgroundColor: '#F3F4F6', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '1.5rem' }}>
          <Target style={{ width: '2.5rem', height: '2.5rem', color: '#9CA3AF' }} />
        </div>
        <h3 style={{ fontSize: '1.25rem', lineHeight: '1.75rem', fontWeight: 700, color: '#000000', marginBottom: '0.75rem' }}>No Campaigns Yet</h3>
        <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#6B7280', textAlign: 'center', marginBottom: '1.5rem', maxWidth: '20rem' }}>
          Create your first campaign on the EvenLeads dashboard to start collecting leads
        </p>
        <button
          onClick={onRefresh}
          disabled={refreshing}
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: '0.5rem',
            padding: '0.75rem 1.5rem',
            backgroundColor: refreshing ? '#9CA3AF' : '#000000',
            color: '#FFFFFF',
            borderRadius: '0.5rem',
            fontWeight: 600,
            border: 'none',
            cursor: refreshing ? 'not-allowed' : 'pointer',
            transition: 'all 0.2s',
            boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
            opacity: refreshing ? 0.5 : 1
          }}
          onMouseEnter={(e) => {
            if (!refreshing) {
              e.currentTarget.style.backgroundColor = '#1F2937';
              e.currentTarget.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
            }
          }}
          onMouseLeave={(e) => {
            if (!refreshing) {
              e.currentTarget.style.backgroundColor = '#000000';
              e.currentTarget.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';
            }
          }}
        >
          <RefreshCw style={{ width: '1rem', height: '1rem', animation: refreshing ? 'spin 1s linear infinite' : 'none' }} />
          {refreshing ? 'Refreshing...' : 'Refresh Campaigns'}
        </button>
        <style>
          {`
            @keyframes spin {
              from { transform: rotate(0deg); }
              to { transform: rotate(360deg); }
            }
          `}
        </style>
      </div>
    );
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', height: '100%', backgroundColor: '#FFFFFF' }}>
      {/* Header */}
      <div style={{ borderBottom: '1px solid #E5E7EB', padding: '1.5rem', backgroundColor: '#FFFFFF' }}>
        <h2 style={{ fontSize: '1.5rem', lineHeight: '2rem', fontWeight: 800, color: '#000000', marginBottom: '0.5rem' }}>Active Campaigns</h2>
        <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#6B7280' }}>
          Monitor platforms to collect leads for these campaigns
        </p>
      </div>

      {/* Content */}
      <div style={{ flex: 1, overflowY: 'auto', padding: '1.5rem' }}>
        {/* Active Campaign Selector */}
        <div style={{ background: 'linear-gradient(to bottom right, #F9FAFB, #F3F4F6)', borderRadius: '0.75rem', border: '1px solid #E5E7EB', padding: '1.25rem', boxShadow: '0 1px 2px 0 rgba(0, 0, 0, 0.05)', marginBottom: '1.5rem' }}>
          <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', fontSize: '0.875rem', fontWeight: 700, color: '#000000', marginBottom: '0.75rem' }}>
            <Zap style={{ width: '1.25rem', height: '1.25rem', color: '#EAB308' }} />
            <span>Active Campaign</span>
          </label>
          <CampaignSelector
            campaigns={campaigns}
            selectedCampaignId={selectedCampaignId}
            onSelect={handleSelectCampaign}
          />
          <p style={{ fontSize: '0.75rem', lineHeight: '1rem', color: '#6B7280', marginTop: '0.75rem' }}>
            Leads will be automatically submitted to the selected campaign. Leave unselected to monitor all campaigns.
          </p>
        </div>

        {/* Campaigns List Header */}
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '1rem' }}>
          <h3 style={{ fontSize: '1.125rem', lineHeight: '1.75rem', fontWeight: 700, color: '#000000' }}>
            All Campaigns <span style={{ fontSize: '0.875rem', fontWeight: 400, color: '#6B7280' }}>({campaigns.length})</span>
          </h3>
          <button
            onClick={onRefresh}
            disabled={refreshing}
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '0.5rem',
              padding: '0.5rem 0.75rem',
              fontSize: '0.875rem',
              fontWeight: 600,
              color: refreshing ? '#9CA3AF' : '#374151',
              backgroundColor: '#F3F4F6',
              borderRadius: '0.5rem',
              border: 'none',
              cursor: refreshing ? 'not-allowed' : 'pointer',
              transition: 'background-color 0.2s',
              opacity: refreshing ? 0.5 : 1
            }}
            onMouseEnter={(e) => {
              if (!refreshing) e.currentTarget.style.backgroundColor = '#E5E7EB';
            }}
            onMouseLeave={(e) => {
              if (!refreshing) e.currentTarget.style.backgroundColor = '#F3F4F6';
            }}
          >
            <RefreshCw style={{ width: '0.875rem', height: '0.875rem', animation: refreshing ? 'spin 1s linear infinite' : 'none' }} />
            Refresh
          </button>
        </div>

        {/* Campaigns Cards */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
          {campaigns.map((campaign) => {
            const statusColor = getStatusColor(campaign.status);
            const isSelected = selectedCampaignId === campaign.id;

            return (
              <div
                key={campaign.id}
                style={{
                  borderRadius: '0.75rem',
                  padding: '1.25rem',
                  transition: 'all 0.2s',
                  border: isSelected ? '2px solid #000000' : '2px solid #E5E7EB',
                  backgroundColor: isSelected ? '#F9FAFB' : '#FFFFFF',
                  boxShadow: isSelected ? '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)' : 'none',
                  cursor: isSelected ? 'default' : 'pointer'
                }}
                onClick={() => !isSelected && handleSelectCampaign(campaign.id)}
                onMouseEnter={(e) => {
                  if (!isSelected) {
                    e.currentTarget.style.borderColor = '#D1D5DB';
                    e.currentTarget.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';
                  }
                }}
                onMouseLeave={(e) => {
                  if (!isSelected) {
                    e.currentTarget.style.borderColor = '#E5E7EB';
                    e.currentTarget.style.boxShadow = 'none';
                  }
                }}
              >
                {/* Title row with inline ACTIVE badge */}
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '0.75rem' }}>
                  <h3 style={{ fontSize: '1.125rem', lineHeight: '1.75rem', fontWeight: 700, color: '#000000', flex: 1 }}>
                    {campaign.name}
                  </h3>
                  {isSelected && (
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.375rem', padding: '0.25rem 0.625rem', backgroundColor: '#000000', color: '#FFFFFF', borderRadius: '9999px', fontSize: '0.625rem', fontWeight: 700, letterSpacing: '0.05em', flexShrink: 0, marginLeft: '0.75rem' }}>
                      <Zap style={{ width: '0.75rem', height: '0.75rem', color: '#FACC15' }} />
                      ACTIVE
                    </div>
                  )}
                </div>

                {/* Status Badge */}
                <div style={{ display: 'inline-flex', alignItems: 'center', gap: '0.375rem', padding: '0.25rem 0.625rem', borderRadius: '9999px', fontSize: '0.75rem', fontWeight: 700, backgroundColor: statusColor.bg, color: statusColor.text, marginBottom: '1rem' }}>
                  {getStatusIcon(campaign.status)}
                  <span style={{ textTransform: 'capitalize' }}>{campaign.status}</span>
                </div>

                {/* Platform Badges - Better Layout */}
                <div style={{ marginBottom: '1rem' }}>
                  <p style={{ fontSize: '0.75rem', fontWeight: 600, color: '#6B7280', marginBottom: '0.5rem', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                    Platforms
                  </p>
                  <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.5rem' }}>
                    {campaign.platforms.map((platform) => (
                      <span
                        key={platform}
                        style={{
                          display: 'inline-flex',
                          alignItems: 'center',
                          padding: '0.375rem 0.75rem',
                          borderRadius: '0.5rem',
                          fontSize: '0.75rem',
                          fontWeight: 700,
                          color: '#FFFFFF',
                          backgroundColor: PLATFORM_COLORS[platform.toLowerCase()] || '#6B7280',
                          boxShadow: '0 1px 2px 0 rgba(0, 0, 0, 0.05)'
                        }}
                      >
                        {platform.charAt(0).toUpperCase() + platform.slice(1)}
                      </span>
                    ))}
                  </div>
                </div>

                {/* Keywords */}
                {campaign.keywords && campaign.keywords.length > 0 && (
                  <div style={{ paddingTop: '0.75rem', borderTop: '1px solid #E5E7EB' }}>
                    <p style={{ fontSize: '0.75rem', color: '#6B7280', fontWeight: 600, marginBottom: '0.5rem', textTransform: 'uppercase', letterSpacing: '0.05em' }}>Keywords</p>
                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.375rem' }}>
                      {campaign.keywords.slice(0, 5).map((keyword, idx) => (
                        <span
                          key={idx}
                          style={{ padding: '0.25rem 0.5rem', backgroundColor: '#F3F4F6', color: '#374151', borderRadius: '0.25rem', fontSize: '0.75rem', fontWeight: 500 }}
                        >
                          {keyword}
                        </span>
                      ))}
                      {campaign.keywords.length > 5 && (
                        <span style={{ padding: '0.25rem 0.5rem', backgroundColor: '#F3F4F6', color: '#6B7280', borderRadius: '0.25rem', fontSize: '0.75rem', fontWeight: 500 }}>
                          +{campaign.keywords.length - 5} more
                        </span>
                      )}
                    </div>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </div>

      <style>
        {`
          @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
          }
        `}
      </style>
    </div>
  );
}
