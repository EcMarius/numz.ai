/**
 * Manual Lead Submission UI
 *
 * Creates a floating button and modal for manually submitting leads from platforms
 * where automatic scraping is not possible (like Facebook)
 */

import { createRoot } from 'react-dom/client';
import { useState, useEffect } from 'react';
import { Plus, X, Send } from 'lucide-react';
import { campaignStorage } from '../../utils/storage';
import type { Campaign, Platform } from '../../types';

interface ManualSubmitUIProps {
  platform: Platform;
  onSubmit: (data: {
    campaignId: number;
    title: string;
    description: string;
    url: string;
  }) => Promise<void>;
}

function ManualSubmitUI({ platform, onSubmit }: ManualSubmitUIProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [campaigns, setCampaigns] = useState<Campaign[]>([]);
  const [selectedCampaignId, setSelectedCampaignId] = useState<number | null>(null);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [url, setUrl] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState<{type: 'success' | 'error'; text: string} | null>(null);

  useEffect(() => {
    loadCampaigns();
  }, []);

  useEffect(() => {
    if (isOpen) {
      // Pre-fill URL with current page
      setUrl(window.location.href);
    }
  }, [isOpen]);

  async function loadCampaigns() {
    const allCampaigns = await campaignStorage.get();
    const platformCampaigns = allCampaigns.filter(c => c.platforms.includes(platform));
    setCampaigns(platformCampaigns);

    // Auto-select if only one campaign
    if (platformCampaigns.length === 1) {
      setSelectedCampaignId(platformCampaigns[0].id);
    } else {
      // Try to get selected campaign
      const selected = await campaignStorage.getSelected();
      if (selected && platformCampaigns.some(c => c.id === selected)) {
        setSelectedCampaignId(selected);
      }
    }
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();

    if (!selectedCampaignId || !title.trim()) {
      showMessage('error', 'Please select a campaign and enter a title');
      return;
    }

    setSubmitting(true);
    setMessage(null);

    try {
      await onSubmit({
        campaignId: selectedCampaignId,
        title: title.trim(),
        description: description.trim(),
        url: url.trim() || window.location.href,
      });

      showMessage('success', 'Lead submitted successfully!');

      // Clear form
      setTitle('');
      setDescription('');

      // Close modal after 1.5 seconds
      setTimeout(() => {
        setIsOpen(false);
        setMessage(null);
      }, 1500);
    } catch (error: any) {
      showMessage('error', error.message || 'Failed to submit lead');
    } finally {
      setSubmitting(false);
    }
  }

  function showMessage(type: 'success' | 'error', text: string) {
    setMessage({ type, text });
    setTimeout(() => setMessage(null), 5000);
  }

  const platformColor = platform === 'facebook' ? '#1877F2' : '#0A66C2';

  return (
    <>
      {/* Floating Action Button */}
      <button
        onClick={() => setIsOpen(true)}
        style={{ background: platformColor }}
        className="fixed bottom-6 right-6 z-[9998] w-14 h-14 rounded-full shadow-lg flex items-center justify-center text-white hover:opacity-90 transition-all hover:scale-110"
        title="Submit Lead to EvenLeads"
      >
        <Plus className="w-6 h-6" />
      </button>

      {/* Modal */}
      {isOpen && (
        <div className="fixed inset-0 z-[9999] flex items-center justify-center p-4" style={{ fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' }}>
          {/* Backdrop */}
          <div
            className="absolute inset-0 bg-black/50"
            onClick={() => setIsOpen(false)}
          />

          {/* Modal Content */}
          <div className="relative bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div className="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
              <h3 className="text-lg font-semibold text-gray-900">Submit Lead</h3>
              <button
                onClick={() => setIsOpen(false)}
                className="text-gray-400 hover:text-gray-600"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              {/* Campaign Selector */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Campaign <span className="text-red-500">*</span>
                </label>
                <select
                  value={selectedCampaignId || ''}
                  onChange={(e) => setSelectedCampaignId(Number(e.target.value))}
                  required
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
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
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Title <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  placeholder="Enter the post title or summary..."
                  required
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                />
              </div>

              {/* Description */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Description
                </label>
                <textarea
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  placeholder="Enter the full post content or description..."
                  rows={4}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                />
              </div>

              {/* URL (pre-filled) */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  URL
                </label>
                <input
                  type="url"
                  value={url}
                  onChange={(e) => setUrl(e.target.value)}
                  placeholder="https://..."
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 text-sm"
                />
              </div>

              {/* Message */}
              {message && (
                <div className={`p-3 rounded-lg text-sm ${
                  message.type === 'success'
                    ? 'bg-green-50 text-green-800 border border-green-200'
                    : 'bg-red-50 text-red-800 border border-red-200'
                }`}>
                  {message.text}
                </div>
              )}

              {/* Submit Button */}
              <button
                type="submit"
                disabled={submitting}
                className="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-900 text-white rounded-lg font-medium hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition"
              >
                {submitting ? (
                  <>
                    <div className="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent" />
                    <span>Submitting...</span>
                  </>
                ) : (
                  <>
                    <Send className="w-4 h-4" />
                    <span>Submit Lead</span>
                  </>
                )}
              </button>
            </form>
          </div>
        </div>
      )}
    </>
  );
}

export function initManualSubmitUI(platform: Platform) {
  const container = document.createElement('div');
  container.id = 'evenleads-manual-submit';
  document.body.appendChild(container);

  const root = createRoot(container);

  root.render(
    <ManualSubmitUI
      platform={platform}
      onSubmit={async (data) => {
        // Submit lead via background script
        const response = await chrome.runtime.sendMessage({
          type: 'SUBMIT_LEAD_MANUAL',
          payload: {
            campaignId: data.campaignId,
            lead: {
              platform,
              platform_id: `manual_${Date.now()}`,
              title: data.title,
              description: data.description,
              url: data.url,
              author: 'manual_submission',
              matched_keywords: [],
              confidence_score: 7,
            },
          },
        });

        if (response?.error) {
          throw new Error(response.error);
        }
      }}
    />
  );
}
