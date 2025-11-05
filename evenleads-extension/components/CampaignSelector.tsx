import { Check, ChevronDown } from 'lucide-react';
import { useState } from 'react';
import type { Campaign } from '../types';

interface CampaignSelectorProps {
  campaigns: Campaign[];
  selectedCampaignId: number | null;
  onSelect: (campaignId: number) => void;
}

export default function CampaignSelector({ campaigns, selectedCampaignId, onSelect }: CampaignSelectorProps) {
  const [isOpen, setIsOpen] = useState(false);

  const selectedCampaign = campaigns.find(c => c.id === selectedCampaignId);

  const getPlatformColor = (platform: string): string => {
    switch (platform) {
      case 'facebook': return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200';
      case 'linkedin': return 'bg-sky-100 dark:bg-sky-900/30 text-sky-800 dark:text-sky-200';
      case 'reddit': return 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-200';
      case 'fiverr': return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200';
      case 'upwork': return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200';
      default: return 'bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-200';
    }
  };

  return (
    <div className="relative">
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="w-full flex items-center justify-between px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900 transition"
      >
        <div className="flex-1 text-left">
          {selectedCampaign ? (
            <div>
              <div className="font-medium text-sm">{selectedCampaign.name}</div>
              <div className="flex gap-1 mt-1">
                {selectedCampaign.platforms.slice(0, 3).map((platform) => (
                  <span
                    key={platform}
                    className={`text-xs px-1.5 py-0.5 rounded ${getPlatformColor(platform)}`}
                  >
                    {platform}
                  </span>
                ))}
                {selectedCampaign.platforms.length > 3 && (
                  <span className="text-xs text-gray-500">+{selectedCampaign.platforms.length - 3}</span>
                )}
              </div>
            </div>
          ) : (
            <div className="text-gray-500 text-sm">Select a campaign...</div>
          )}
        </div>
        <ChevronDown className={`w-4 h-4 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
      </button>

      {isOpen && (
        <>
          {/* Backdrop */}
          <div
            className="fixed inset-0 z-10"
            onClick={() => setIsOpen(false)}
          />

          {/* Dropdown */}
          <div className="absolute z-20 w-full mt-1 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg shadow-lg max-h-64 overflow-y-auto">
            {campaigns.length === 0 ? (
              <div className="px-4 py-3 text-sm text-gray-500 text-center">
                No campaigns available
              </div>
            ) : (
              campaigns.map((campaign) => (
                <button
                  key={campaign.id}
                  onClick={() => {
                    onSelect(campaign.id);
                    setIsOpen(false);
                  }}
                  className="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900 transition text-left"
                >
                  <div className="flex-1">
                    <div className="font-medium text-sm">{campaign.name}</div>
                    <div className="flex gap-1 mt-1">
                      {campaign.platforms.map((platform) => (
                        <span
                          key={platform}
                          className={`text-xs px-1.5 py-0.5 rounded ${getPlatformColor(platform)}`}
                        >
                          {platform}
                        </span>
                      ))}
                    </div>
                  </div>
                  {campaign.id === selectedCampaignId && (
                    <Check className="w-4 h-4 text-gray-900 dark:text-white flex-shrink-0 ml-2" />
                  )}
                </button>
              ))
            )}
          </div>
        </>
      )}
    </div>
  );
}
