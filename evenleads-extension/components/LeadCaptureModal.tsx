import React, { useState, useEffect } from 'react';
import { X, Send, Loader2, CheckCircle, ExternalLink } from 'lucide-react';
import { campaignStorage } from '../utils/storage';
import type { Campaign } from '../types';
import type { Platform, PageType } from '../utils/pageDetection';
import { extractPlatformId, getPlatformColor } from '../utils/pageDetection';

interface LeadCaptureModalProps {
  platform: Platform;
  pageType: PageType;
  onClose: () => void;
  onSuccess?: (leadId?: number) => void;
}

export default function LeadCaptureModal({
  platform,
  pageType,
  onClose,
  onSuccess,
}: LeadCaptureModalProps) {
  const [campaigns, setCampaigns] = useState<Campaign[]>([]);
  const [selectedCampaignId, setSelectedCampaignId] = useState<number | null>(null);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [author, setAuthor] = useState('');
  const [url, setUrl] = useState(window.location.href);
  const [submitting, setSubmitting] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [createdLeadId, setCreatedLeadId] = useState<number | null>(null);

  useEffect(() => {
    loadCampaigns();
    extractPageData();
  }, []);

  async function loadCampaigns() {
    try {
      const allCampaigns = await campaignStorage.get();
      const platformCampaigns = allCampaigns.filter((c) =>
        c.platforms.includes(platform)
      );
      setCampaigns(platformCampaigns);

      if (platformCampaigns.length === 1) {
        setSelectedCampaignId(platformCampaigns[0].id);
      }
    } catch (err) {
      console.error('Failed to load campaigns:', err);
    }
  }

  function extractPageData() {
    try {
      // Try to extract title
      const titleEl = document.querySelector('h1');
      if (titleEl && titleEl.textContent) {
        setTitle(titleEl.textContent.trim().substring(0, 255));
      }

      // Try to extract author/profile name
      if (pageType === 'profile') {
        const nameEl =
          document.querySelector('[data-test-id="profile-name"]') ||
          document.querySelector('.profile-name') ||
          document.querySelector('[data-testid="UserName"]') ||
          titleEl;
        if (nameEl && nameEl.textContent) {
          setAuthor(nameEl.textContent.trim());
        }
      }

      // Try to extract description/content
      const contentSelectors = [
        '[data-test-id="post-content"]',
        '.post-content',
        '[data-testid="tweetText"]',
        'article p',
        '.description',
      ];

      for (const selector of contentSelectors) {
        const contentEl = document.querySelector(selector);
        if (contentEl && contentEl.textContent) {
          setDescription(contentEl.textContent.trim().substring(0, 1000));
          break;
        }
      }
    } catch (err) {
      console.error('Failed to extract page data:', err);
    }
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();

    if (!selectedCampaignId || !title.trim()) {
      setError('Please select a campaign and enter a title');
      return;
    }

    setSubmitting(true);
    setError(null);

    try {
      const platformId = extractPlatformId(url, platform, pageType);

      let response;
      try {
        response = await chrome.runtime.sendMessage({
          type: 'SUBMIT_LEAD_MANUAL',
          payload: {
            campaignId: selectedCampaignId,
            lead: {
              platform,
              platform_id: platformId,
              title: title.trim(),
              description: description.trim(),
              url: url.trim(),
              author: author.trim() || 'Unknown',
              matched_keywords: [],
              confidence_score: 7,
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

      if (response?.error) {
        throw new Error(response.error);
      }

      setSuccess(true);
      setCreatedLeadId(response?.leadId || null);

      setTimeout(() => {
        if (onSuccess) {
          onSuccess(response?.leadId);
        } else {
          onClose();
        }
      }, 2000);
    } catch (err: any) {
      setError(err.message || 'Failed to submit lead');
    } finally {
      setSubmitting(false);
    }
  }

  const platformColor = getPlatformColor(platform);

  return (
    <div
      style={{
        position: 'fixed',
        inset: 0,
        zIndex: 9999,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '1rem',
        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
      }}
    >
      {/* Backdrop */}
      <div
        onClick={onClose}
        style={{
          position: 'absolute',
          inset: 0,
          backgroundColor: 'rgba(0, 0, 0, 0.5)',
        }}
      />

      {/* Modal Content */}
      <div
        style={{
          position: 'relative',
          backgroundColor: '#FFFFFF',
          borderRadius: '0.75rem',
          boxShadow: '0 20px 50px rgba(0, 0, 0, 0.3)',
          maxWidth: '32rem',
          width: '100%',
          maxHeight: '90vh',
          overflow: 'auto',
        }}
      >
        {/* Header */}
        <div
          style={{
            position: 'sticky',
            top: 0,
            backgroundColor: '#FFFFFF',
            borderBottom: '1px solid #E5E7EB',
            padding: '1.25rem 1.5rem',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            zIndex: 1,
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
            <div
              style={{
                width: '2rem',
                height: '2rem',
                borderRadius: '50%',
                backgroundColor: platformColor,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color: '#FFFFFF',
                fontSize: '0.75rem',
                fontWeight: 700,
              }}
            >
              {platform.substring(0, 2).toUpperCase()}
            </div>
            <h3 style={{ fontSize: '1.125rem', fontWeight: 700, color: '#000000' }}>
              Add to EvenLeads
            </h3>
          </div>
          <button
            onClick={onClose}
            style={{
              background: 'none',
              border: 'none',
              cursor: 'pointer',
              color: '#6B7280',
              padding: '0.25rem',
            }}
          >
            <X style={{ width: '1.25rem', height: '1.25rem' }} />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} style={{ padding: '1.5rem' }}>
          {success ? (
            <div
              style={{
                textAlign: 'center',
                padding: '2rem 1rem',
              }}
            >
              <CheckCircle
                style={{
                  width: '3rem',
                  height: '3rem',
                  color: '#10B981',
                  margin: '0 auto 1rem',
                }}
              />
              <h4 style={{ fontSize: '1.125rem', fontWeight: 700, marginBottom: '0.5rem' }}>
                Lead Added Successfully!
              </h4>
              <p style={{ color: '#6B7280', fontSize: '0.875rem', marginBottom: '1.5rem' }}>
                The lead has been added to your campaign.
              </p>
              {createdLeadId && (
                <button
                  type="button"
                  onClick={() => {
                    // Open lead detail view - will implement later
                    onClose();
                  }}
                  style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '0.5rem',
                    padding: '0.625rem 1.25rem',
                    backgroundColor: '#000000',
                    color: '#FFFFFF',
                    border: 'none',
                    borderRadius: '0.5rem',
                    fontSize: '0.875rem',
                    fontWeight: 500,
                    cursor: 'pointer',
                  }}
                >
                  <ExternalLink style={{ width: '1rem', height: '1rem' }} />
                  View Lead
                </button>
              )}
            </div>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
              {/* Campaign Selector */}
              <div>
                <label
                  style={{
                    display: 'block',
                    fontSize: '0.875rem',
                    fontWeight: 600,
                    color: '#000000',
                    marginBottom: '0.5rem',
                  }}
                >
                  Campaign <span style={{ color: '#EF4444' }}>*</span>
                </label>
                <select
                  value={selectedCampaignId || ''}
                  onChange={(e) => setSelectedCampaignId(Number(e.target.value))}
                  required
                  style={{
                    width: '100%',
                    padding: '0.625rem 0.875rem',
                    border: '1px solid #D1D5DB',
                    borderRadius: '0.5rem',
                    fontSize: '0.875rem',
                    outline: 'none',
                  }}
                >
                  <option value="">Select a campaign...</option>
                  {campaigns.map((campaign) => (
                    <option key={campaign.id} value={campaign.id}>
                      {campaign.name}
                    </option>
                  ))}
                </select>
              </div>

              {/* Title */}
              <div>
                <label
                  style={{
                    display: 'block',
                    fontSize: '0.875rem',
                    fontWeight: 600,
                    color: '#000000',
                    marginBottom: '0.5rem',
                  }}
                >
                  Title <span style={{ color: '#EF4444' }}>*</span>
                </label>
                <input
                  type="text"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  placeholder="Enter title or headline..."
                  required
                  maxLength={255}
                  style={{
                    width: '100%',
                    padding: '0.625rem 0.875rem',
                    border: '1px solid #D1D5DB',
                    borderRadius: '0.5rem',
                    fontSize: '0.875rem',
                    outline: 'none',
                  }}
                />
              </div>

              {/* Author */}
              <div>
                <label
                  style={{
                    display: 'block',
                    fontSize: '0.875rem',
                    fontWeight: 600,
                    color: '#000000',
                    marginBottom: '0.5rem',
                  }}
                >
                  Author
                </label>
                <input
                  type="text"
                  value={author}
                  onChange={(e) => setAuthor(e.target.value)}
                  placeholder="Author name..."
                  style={{
                    width: '100%',
                    padding: '0.625rem 0.875rem',
                    border: '1px solid #D1D5DB',
                    borderRadius: '0.5rem',
                    fontSize: '0.875rem',
                    outline: 'none',
                  }}
                />
              </div>

              {/* Description */}
              <div>
                <label
                  style={{
                    display: 'block',
                    fontSize: '0.875rem',
                    fontWeight: 600,
                    color: '#000000',
                    marginBottom: '0.5rem',
                  }}
                >
                  Description
                </label>
                <textarea
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  placeholder="Enter description or content..."
                  rows={4}
                  maxLength={1000}
                  style={{
                    width: '100%',
                    padding: '0.625rem 0.875rem',
                    border: '1px solid #D1D5DB',
                    borderRadius: '0.5rem',
                    fontSize: '0.875rem',
                    outline: 'none',
                    resize: 'vertical',
                  }}
                />
              </div>

              {/* URL */}
              <div>
                <label
                  style={{
                    display: 'block',
                    fontSize: '0.875rem',
                    fontWeight: 600,
                    color: '#000000',
                    marginBottom: '0.5rem',
                  }}
                >
                  URL
                </label>
                <input
                  type="url"
                  value={url}
                  onChange={(e) => setUrl(e.target.value)}
                  placeholder="https://..."
                  style={{
                    width: '100%',
                    padding: '0.625rem 0.875rem',
                    border: '1px solid #D1D5DB',
                    borderRadius: '0.5rem',
                    fontSize: '0.875rem',
                    outline: 'none',
                  }}
                />
              </div>

              {/* Error Message */}
              {error && (
                <div
                  style={{
                    padding: '0.75rem 1rem',
                    backgroundColor: '#FEE2E2',
                    border: '1px solid #FCA5A5',
                    borderRadius: '0.5rem',
                    color: '#991B1B',
                    fontSize: '0.875rem',
                  }}
                >
                  {error}
                </div>
              )}

              {/* Submit Button */}
              <button
                type="submit"
                disabled={submitting}
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '0.5rem',
                  width: '100%',
                  padding: '0.75rem 1rem',
                  backgroundColor: submitting ? '#9CA3AF' : '#000000',
                  color: '#FFFFFF',
                  border: 'none',
                  borderRadius: '0.5rem',
                  fontSize: '0.875rem',
                  fontWeight: 600,
                  cursor: submitting ? 'not-allowed' : 'pointer',
                }}
              >
                {submitting ? (
                  <>
                    <Loader2
                      style={{
                        width: '1rem',
                        height: '1rem',
                        animation: 'spin 1s linear infinite',
                      }}
                    />
                    <span>Submitting...</span>
                  </>
                ) : (
                  <>
                    <Send style={{ width: '1rem', height: '1rem' }} />
                    <span>Add to Campaign</span>
                  </>
                )}
              </button>
            </div>
          )}
        </form>
      </div>
    </div>
  );
}
