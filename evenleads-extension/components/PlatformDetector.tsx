import React, { useState, useEffect } from 'react';
import { ArrowRight, Zap } from 'lucide-react';
import { PLATFORMS, type Platform } from '../types';
import { detectPageType, type PageType } from '../utils/pageDetection';

interface PlatformDetectorProps {
  onPlatformDetected?: (platform: Platform) => void;
}

export default function PlatformDetector({ onPlatformDetected }: PlatformDetectorProps) {
  const [detectedPlatform, setDetectedPlatform] = useState<Platform | null>(null);
  const [detectedPageType, setDetectedPageType] = useState<PageType | null>(null);
  const [isVisible, setIsVisible] = useState(false);
  const [dismissed, setDismissed] = useState(false);

  useEffect(() => {
    detectPlatformAndType();
  }, []);

  useEffect(() => {
    if (detectedPlatform && detectedPageType && !dismissed) {
      // Show the notification after 2 seconds
      const timer = setTimeout(() => {
        setIsVisible(true);
      }, 2000);

      return () => clearTimeout(timer);
    }
  }, [detectedPlatform, detectedPageType, dismissed]);

  function detectPlatformAndType() {
    const pageInfo = detectPageType();

    // Only show notification for actionable page types
    const actionableTypes: PageType[] = ['profile', 'post', 'group', 'community', 'gig', 'job'];

    if (pageInfo.platform && pageInfo.type && actionableTypes.includes(pageInfo.type)) {
      setDetectedPlatform(pageInfo.platform as Platform);
      setDetectedPageType(pageInfo.type);
      console.log('[PlatformDetector] Detected:', pageInfo.platform, pageInfo.type);
    } else {
      setDetectedPlatform(null);
      setDetectedPageType(null);
      console.log('[PlatformDetector] Not an actionable page type:', pageInfo);
    }
  }

  function handleSwitch() {
    if (detectedPlatform && onPlatformDetected) {
      onPlatformDetected(detectedPlatform);
    }
    setIsVisible(false);
    setDismissed(true);
  }

  function handleDismiss() {
    setIsVisible(false);
    setDismissed(true);
  }

  if (!detectedPlatform || !detectedPageType || !isVisible) {
    return null;
  }

  const platformConfig = PLATFORMS[detectedPlatform];

  // Get page type display name
  const pageTypeNames: Record<string, string> = {
    profile: 'Profile',
    post: 'Post',
    gig: 'Gig',
    job: 'Job',
    group: 'Group',
    community: 'Community',
  };

  const pageTypeName = pageTypeNames[detectedPageType] || 'Page';

  return (
    <div
      style={{
        position: 'fixed',
        top: '1rem',
        right: '1rem',
        zIndex: 999997,
        pointerEvents: 'auto',
        animation: 'slide-down 0.3s ease-out forwards',
      }}
    >
      <div
        style={{
          borderRadius: '0.75rem',
          boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
          padding: '1rem',
          maxWidth: '24rem',
          backgroundColor: '#FFFFFF',
          border: `2px solid ${platformConfig.color}`,
        }}
      >
        <div style={{ display: 'flex', alignItems: 'flex-start', gap: '0.75rem' }}>
          <div
            style={{
              padding: '0.5rem',
              borderRadius: '0.5rem',
              backgroundColor: platformConfig.color,
            }}
          >
            <Zap style={{ width: '1.25rem', height: '1.25rem', color: '#FFFFFF' }} />
          </div>

          <div style={{ flex: 1 }}>
            <h3 style={{ fontWeight: 700, fontSize: '0.875rem', lineHeight: '1.25rem', marginBottom: '0.25rem', color: '#000000' }}>
              {platformConfig.displayName} {pageTypeName} Detected
            </h3>
            <p style={{ fontSize: '0.75rem', lineHeight: '1rem', marginBottom: '0.75rem', color: '#6B7280' }}>
              {detectedPageType === 'profile' && 'Track this profile for potential leads'}
              {detectedPageType === 'post' && 'Save this post as a lead'}
              {detectedPageType === 'group' && 'Monitor this group for leads'}
              {detectedPageType === 'community' && 'Monitor this community for leads'}
              {detectedPageType === 'gig' && 'Save this gig as a lead opportunity'}
              {detectedPageType === 'job' && 'Save this job as a lead opportunity'}
            </p>

            <div style={{ display: 'flex', gap: '0.5rem' }}>
              <button
                onClick={handleSwitch}
                style={{
                  flex: 1,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '0.5rem',
                  paddingLeft: '0.75rem',
                  paddingRight: '0.75rem',
                  paddingTop: '0.5rem',
                  paddingBottom: '0.5rem',
                  borderRadius: '0.5rem',
                  fontSize: '0.875rem',
                  lineHeight: '1.25rem',
                  fontWeight: 500,
                  transition: 'transform 0.2s',
                  backgroundColor: platformConfig.color,
                  color: '#FFFFFF',
                  border: 'none',
                  cursor: 'pointer',
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.transform = 'scale(1.05)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.transform = 'scale(1)';
                }}
              >
                Activate
                <ArrowRight style={{ width: '1rem', height: '1rem' }} />
              </button>
              <button
                onClick={handleDismiss}
                style={{
                  paddingLeft: '0.75rem',
                  paddingRight: '0.75rem',
                  paddingTop: '0.5rem',
                  paddingBottom: '0.5rem',
                  borderRadius: '0.5rem',
                  fontSize: '0.875rem',
                  lineHeight: '1.25rem',
                  fontWeight: 500,
                  backgroundColor: '#F3F4F6',
                  color: '#6B7280',
                  border: 'none',
                  cursor: 'pointer',
                }}
              >
                Dismiss
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
