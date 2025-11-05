import React, { useState, useEffect } from 'react';
import { X, Send, Loader2, CheckCircle, ExternalLink, Plus, Target, RefreshCw } from 'lucide-react';
import { campaignStorage } from '../utils/storage';
import { api } from '../utils/api';
import type { Campaign } from '../types';
import type { Platform, PageType } from '../utils/pageDetection';
import { detectPageType, extractPlatformId, getPlatformColor } from '../utils/pageDetection';

interface LeadCaptureViewProps {
  onClose: () => void;
  onSuccess?: () => void;
}

export default function LeadCaptureView({ onClose, onSuccess }: LeadCaptureViewProps) {
  const [pageInfo, setPageInfo] = useState<{ platform: Platform; type: PageType; url: string } | null>(null);
  const [campaigns, setCampaigns] = useState<Campaign[]>([]);
  const [allCampaigns, setAllCampaigns] = useState<Campaign[]>([]);
  const [selectedCampaignId, setSelectedCampaignId] = useState<number | null>(null);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [author, setAuthor] = useState('');
  const [url, setUrl] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [extracting, setExtracting] = useState(false);

  useEffect(() => {
    detectCurrentPage();
    loadCampaigns();
  }, []);

  function detectCurrentPage() {
    const info = detectPageType();
    setPageInfo(info);
    setUrl(info.url);

    // Only auto-extract if we're on a valid page type
    if (info.platform && info.type) {
      extractPageData();
    }
  }

  async function loadCampaigns() {
    try {
      let camps = await campaignStorage.get();

      // If storage is empty, try API
      if (!camps || camps.length === 0) {
        camps = await api.getCampaigns(true);
      }

      setAllCampaigns(camps);

      // Filter by current platform
      if (pageInfo?.platform) {
        const platformCampaigns = camps.filter((c) =>
          c.platforms.includes(pageInfo.platform)
        );
        setCampaigns(platformCampaigns);

        if (platformCampaigns.length === 1) {
          setSelectedCampaignId(platformCampaigns[0].id);
        }
      } else {
        setCampaigns(camps);
      }
    } catch (err) {
      console.error('Failed to load campaigns:', err);
      setCampaigns([]);
    }
  }

  async function extractPageData() {
    if (!pageInfo || !pageInfo.platform) return;

    setExtracting(true);
    try {
      console.log('[LeadCapture] Extracting data for', pageInfo.platform, pageInfo.type);

      // Send message to content script to extract data
      const response = await chrome.tabs.query({ active: true, currentWindow: true });
      if (response[0]?.id) {
        const extractedData = await chrome.tabs.sendMessage(response[0].id, {
          type: 'EXTRACT_LEAD_DATA',
          payload: { platform: pageInfo.platform, pageType: pageInfo.type },
        });

        if (extractedData) {
          setTitle(extractedData.title || '');
          setDescription(extractedData.description || '');
          setAuthor(extractedData.author || '');
        }
      }
    } catch (error) {
      console.error('[LeadCapture] Failed to extract data:', error);
    } finally {
      setExtracting(false);
    }
  }

  async function handleSubmit() {
    if (!selectedCampaignId) {
      setError('Please select a campaign');
      return;
    }

    if (!title.trim()) {
      setError('Title is required');
      return;
    }

    setSubmitting(true);
    setError(null);

    try {
      const platformId = extractPlatformId(url, pageInfo?.platform || '');

      try {
        await chrome.runtime.sendMessage({
          type: 'SUBMIT_LEAD_MANUAL',
          payload: {
            campaignId: selectedCampaignId,
            lead: {
              platform: pageInfo?.platform || 'unknown',
              platform_id: platformId,
              title: title.trim(),
              description: description.trim(),
              url,
              author: author.trim(),
              confidence: 10,
            },
          },
        });
      } catch (sendError: any) {
        // If context invalidated, throw a user-friendly error
        if (sendError.message?.includes('Extension context invalidated') ||
            sendError.message?.includes('message port closed')) {
          throw new Error('Extension was reloaded. Please try again.');
        }
        throw sendError;
      }

      setSuccess(true);
      api.invalidateLeadsCache();
      api.invalidateStatsCache();

      setTimeout(() => {
        onSuccess?.();
      }, 2000);
    } catch (err: any) {
      console.error('Lead submission failed:', err);
      setError(err.message || 'Failed to submit lead');
      setSubmitting(false);
    }
  }

  function handleCreateCampaign() {
    // Open campaigns page with parameters to auto-open create modal
    const baseUrl = 'https://evenleads.com';
    const platform = pageInfo?.platform || '';
    window.open(`${baseUrl}/campaigns?action=create&platform=${platform}`, '_blank');
  }

  if (!pageInfo || !pageInfo.platform || !pageInfo.type) {
    return (
      <div style={{ display: 'flex', flexDirection: 'column', height: '100%', backgroundColor: '#FFFFFF' }}>
        <div style={{ borderBottom: '1px solid #E5E7EB', padding: '1.5rem', backgroundColor: '#FFFFFF', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <h2 style={{ fontSize: '1.5rem', lineHeight: '2rem', fontWeight: 800, color: '#000000' }}>Get Lead</h2>
          <button onClick={onClose} style={{ padding: '0.5rem', borderRadius: '0.5rem', backgroundColor: '#F3F4F6', border: 'none', cursor: 'pointer' }}>
            <X style={{ width: '1.25rem', height: '1.25rem', color: '#374151' }} />
          </button>
        </div>

        <div style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', padding: '2rem', textAlign: 'center' }}>
          <div style={{ width: '5rem', height: '5rem', borderRadius: '9999px', backgroundColor: '#F3F4F6', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '1.5rem' }}>
            <Target style={{ width: '2.5rem', height: '2.5rem', color: '#9CA3AF' }} />
          </div>
          <h3 style={{ fontSize: '1.25rem', lineHeight: '1.75rem', fontWeight: 700, color: '#000000', marginBottom: '0.75rem' }}>
            No Lead Detected
          </h3>
          <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#6B7280', maxWidth: '28rem' }}>
            Navigate to a profile, post, group, gig, or job page on a supported platform to capture leads.
          </p>
          <p style={{ fontSize: '0.75rem', lineHeight: '1rem', color: '#9CA3AF', marginTop: '1rem' }}>
            Supported: LinkedIn, Reddit, Facebook, X, Fiverr, Upwork
          </p>
        </div>
      </div>
    );
  }

  if (success) {
    return (
      <div style={{ display: 'flex', flexDirection: 'column', height: '100%', backgroundColor: '#FFFFFF' }}>
        <div style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', padding: '2rem', textAlign: 'center' }}>
          <div style={{ width: '5rem', height: '5rem', borderRadius: '9999px', backgroundColor: '#D1FAE5', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '1.5rem' }}>
            <CheckCircle style={{ width: '2.5rem', height: '2.5rem', color: '#065F46' }} />
          </div>
          <h3 style={{ fontSize: '1.25rem', lineHeight: '1.75rem', fontWeight: 700, color: '#000000', marginBottom: '0.75rem' }}>
            Lead Submitted!
          </h3>
          <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#6B7280' }}>
            Your lead has been successfully added to the campaign.
          </p>
        </div>
      </div>
    );
  }

  const platformColor = getPlatformColor(pageInfo.platform);

  return (
    <div style={{ display: 'flex', flexDirection: 'column', height: '100%', backgroundColor: '#FFFFFF' }}>
      {/* Header */}
      <div style={{ borderBottom: '1px solid #E5E7EB', padding: '1.5rem', backgroundColor: '#FFFFFF' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
          <h2 style={{ fontSize: '1.5rem', lineHeight: '2rem', fontWeight: 800, color: '#000000' }}>Get Lead</h2>
          <button
            onClick={onClose}
            style={{ padding: '0.5rem', borderRadius: '0.5rem', backgroundColor: '#F3F4F6', border: 'none', cursor: 'pointer', transition: 'background-color 0.2s' }}
            onMouseEnter={(e) => { e.currentTarget.style.backgroundColor = '#E5E7EB'; }}
            onMouseLeave={(e) => { e.currentTarget.style.backgroundColor = '#F3F4F6'; }}
          >
            <X style={{ width: '1.25rem', height: '1.25rem', color: '#374151' }} />
          </button>
        </div>

        {/* Platform & Page Type Badge */}
        <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
          <span style={{ display: 'inline-flex', alignItems: 'center', padding: '0.375rem 0.75rem', borderRadius: '0.5rem', fontSize: '0.75rem', fontWeight: 700, color: '#FFFFFF', backgroundColor: platformColor }}>
            {pageInfo.platform.charAt(0).toUpperCase() + pageInfo.platform.slice(1)}
          </span>
          <span style={{ display: 'inline-flex', alignItems: 'center', padding: '0.375rem 0.75rem', borderRadius: '0.5rem', fontSize: '0.75rem', fontWeight: 700, color: '#374151', backgroundColor: '#F3F4F6' }}>
            {pageInfo.type?.charAt(0).toUpperCase() + pageInfo.type?.slice(1)}
          </span>
          {extracting && (
            <Loader2 style={{ width: '1rem', height: '1rem', color: '#9CA3AF', animation: 'spin 1s linear infinite' }} />
          )}
        </div>
      </div>

      {/* Form */}
      <div style={{ flex: 1, overflowY: 'auto', padding: '1.5rem' }}>
        {error && (
          <div style={{ padding: '1rem', backgroundColor: '#FEF2F2', border: '1px solid #FECACA', borderRadius: '0.5rem', marginBottom: '1rem' }}>
            <p style={{ fontSize: '0.875rem', color: '#991B1B' }}>{error}</p>
          </div>
        )}

        {/* Campaign Selector */}
        <div style={{ marginBottom: '1.5rem' }}>
          <label style={{ fontSize: '0.875rem', fontWeight: 700, color: '#374151', marginBottom: '0.5rem', display: 'block' }}>
            Campaign *
          </label>

          {campaigns.length === 0 ? (
            <div style={{ padding: '1.5rem', backgroundColor: '#FEF3C7', border: '1px solid #FDE047', borderRadius: '0.75rem', textAlign: 'center' }}>
              <p style={{ fontSize: '0.875rem', color: '#92400E', marginBottom: '1rem' }}>
                No campaigns found for {pageInfo.platform.charAt(0).toUpperCase() + pageInfo.platform.slice(1)}.
              </p>
              <button
                onClick={handleCreateCampaign}
                style={{ display: 'inline-flex', alignItems: 'center', gap: '0.5rem', padding: '0.75rem 1.5rem', backgroundColor: '#000000', color: '#FFFFFF', borderRadius: '0.5rem', fontSize: '0.875rem', fontWeight: 700, border: 'none', cursor: 'pointer', transition: 'background-color 0.2s' }}
                onMouseEnter={(e) => { e.currentTarget.style.backgroundColor = '#1F2937'; }}
                onMouseLeave={(e) => { e.currentTarget.style.backgroundColor = '#000000'; }}
              >
                <Plus style={{ width: '1rem', height: '1rem' }} />
                Create Campaign
              </button>
              <p style={{ fontSize: '0.75rem', color: '#92400E', marginTop: '0.75rem' }}>
                Make sure to select {pageInfo.platform.charAt(0).toUpperCase() + pageInfo.platform.slice(1)} as a platform
              </p>
            </div>
          ) : (
            <>
              <select
                value={selectedCampaignId || ''}
                onChange={(e) => setSelectedCampaignId(Number(e.target.value))}
                style={{ width: '100%', padding: '0.75rem', backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', borderRadius: '0.5rem', fontSize: '0.875rem', color: '#000000', outline: 'none', transition: 'all 0.2s', cursor: 'pointer' }}
                onFocus={(e) => {
                  e.currentTarget.style.borderColor = '#000000';
                  e.currentTarget.style.boxShadow = '0 0 0 2px rgba(0, 0, 0, 0.1)';
                }}
                onBlur={(e) => {
                  e.currentTarget.style.borderColor = '#E5E7EB';
                  e.currentTarget.style.boxShadow = 'none';
                }}
              >
                <option value="">Select a campaign...</option>
                {campaigns.map((campaign) => (
                  <option key={campaign.id} value={campaign.id}>
                    {campaign.name}
                  </option>
                ))}
              </select>

              <button
                onClick={() => {
                  loadCampaigns();
                }}
                style={{ display: 'inline-flex', alignItems: 'center', gap: '0.5rem', marginTop: '0.5rem', padding: '0.5rem 0.75rem', backgroundColor: '#F3F4F6', color: '#374151', borderRadius: '0.5rem', fontSize: '0.75rem', fontWeight: 600, border: 'none', cursor: 'pointer', transition: 'background-color 0.2s' }}
                onMouseEnter={(e) => { e.currentTarget.style.backgroundColor = '#E5E7EB'; }}
                onMouseLeave={(e) => { e.currentTarget.style.backgroundColor = '#F3F4F6'; }}
              >
                <RefreshCw style={{ width: '0.75rem', height: '0.75rem' }} />
                Refresh Campaigns
              </button>
            </>
          )}
        </div>

        {/* Title */}
        <div style={{ marginBottom: '1.5rem' }}>
          <label style={{ fontSize: '0.875rem', fontWeight: 700, color: '#374151', marginBottom: '0.5rem', display: 'block' }}>
            Title *
          </label>
          <input
            type="text"
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            placeholder="Lead title..."
            style={{ width: '100%', padding: '0.75rem', backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', borderRadius: '0.5rem', fontSize: '0.875rem', color: '#000000', outline: 'none', transition: 'all 0.2s' }}
            onFocus={(e) => {
              e.currentTarget.style.borderColor = '#000000';
              e.currentTarget.style.boxShadow = '0 0 0 2px rgba(0, 0, 0, 0.1)';
            }}
            onBlur={(e) => {
              e.currentTarget.style.borderColor = '#E5E7EB';
              e.currentTarget.style.boxShadow = 'none';
            }}
          />
        </div>

        {/* Description */}
        <div style={{ marginBottom: '1.5rem' }}>
          <label style={{ fontSize: '0.875rem', fontWeight: 700, color: '#374151', marginBottom: '0.5rem', display: 'block' }}>
            Description
          </label>
          <textarea
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            placeholder="Lead description..."
            rows={4}
            style={{ width: '100%', padding: '0.75rem', backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', borderRadius: '0.5rem', fontSize: '0.875rem', color: '#000000', outline: 'none', transition: 'all 0.2s', resize: 'vertical' }}
            onFocus={(e) => {
              e.currentTarget.style.borderColor = '#000000';
              e.currentTarget.style.boxShadow = '0 0 0 2px rgba(0, 0, 0, 0.1)';
            }}
            onBlur={(e) => {
              e.currentTarget.style.borderColor = '#E5E7EB';
              e.currentTarget.style.boxShadow = 'none';
            }}
          />
        </div>

        {/* Author */}
        <div style={{ marginBottom: '1.5rem' }}>
          <label style={{ fontSize: '0.875rem', fontWeight: 700, color: '#374151', marginBottom: '0.5rem', display: 'block' }}>
            Author
          </label>
          <input
            type="text"
            value={author}
            onChange={(e) => setAuthor(e.target.value)}
            placeholder="Author name..."
            style={{ width: '100%', padding: '0.75rem', backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', borderRadius: '0.5rem', fontSize: '0.875rem', color: '#000000', outline: 'none', transition: 'all 0.2s' }}
            onFocus={(e) => {
              e.currentTarget.style.borderColor = '#000000';
              e.currentTarget.style.boxShadow = '0 0 0 2px rgba(0, 0, 0, 0.1)';
            }}
            onBlur={(e) => {
              e.currentTarget.style.borderColor = '#E5E7EB';
              e.currentTarget.style.boxShadow = 'none';
            }}
          />
        </div>

        {/* URL */}
        <div style={{ marginBottom: '1.5rem' }}>
          <label style={{ fontSize: '0.875rem', fontWeight: 700, color: '#374151', marginBottom: '0.5rem', display: 'block' }}>
            URL
          </label>
          <input
            type="text"
            value={url}
            onChange={(e) => setUrl(e.target.value)}
            placeholder="Lead URL..."
            style={{ width: '100%', padding: '0.75rem', backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', borderRadius: '0.5rem', fontSize: '0.875rem', color: '#000000', outline: 'none', transition: 'all 0.2s' }}
            onFocus={(e) => {
              e.currentTarget.style.borderColor = '#000000';
              e.currentTarget.style.boxShadow = '0 0 0 2px rgba(0, 0, 0, 0.1)';
            }}
            onBlur={(e) => {
              e.currentTarget.style.borderColor = '#E5E7EB';
              e.currentTarget.style.boxShadow = 'none';
            }}
          />
        </div>

        {/* Submit Button */}
        <button
          onClick={handleSubmit}
          disabled={submitting || !selectedCampaignId || !title.trim()}
          style={{
            width: '100%',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '0.5rem',
            padding: '0.875rem',
            backgroundColor: submitting || !selectedCampaignId || !title.trim() ? '#9CA3AF' : '#000000',
            color: '#FFFFFF',
            borderRadius: '0.75rem',
            fontSize: '0.875rem',
            fontWeight: 700,
            border: 'none',
            cursor: submitting || !selectedCampaignId || !title.trim() ? 'not-allowed' : 'pointer',
            transition: 'all 0.2s',
            boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
          }}
          onMouseEnter={(e) => {
            if (!submitting && selectedCampaignId && title.trim()) {
              e.currentTarget.style.backgroundColor = '#1F2937';
              e.currentTarget.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
            }
          }}
          onMouseLeave={(e) => {
            if (!submitting && selectedCampaignId && title.trim()) {
              e.currentTarget.style.backgroundColor = '#000000';
              e.currentTarget.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';
            }
          }}
        >
          {submitting ? (
            <>
              <Loader2 style={{ width: '1rem', height: '1rem', animation: 'spin 1s linear infinite' }} />
              Submitting...
            </>
          ) : (
            <>
              <Send style={{ width: '1rem', height: '1rem' }} />
              Submit Lead
            </>
          )}
        </button>
      </div>
    </div>
  );
}
