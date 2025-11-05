import React, { useState, useEffect } from 'react';
import { CheckCircle, Loader2, TrendingUp } from 'lucide-react';

interface CollectionIndicatorProps {
  platform?: string;
  leadsCollected?: number;
  isCollecting?: boolean;
  onClose?: () => void;
}

export default function CollectionIndicator({
  platform = 'Platform',
  leadsCollected = 0,
  isCollecting = false,
  onClose,
}: CollectionIndicatorProps) {
  const [isVisible, setIsVisible] = useState(true);

  useEffect(() => {
    // Auto-hide after 5 seconds if not collecting
    if (!isCollecting && leadsCollected > 0) {
      const timer = setTimeout(() => {
        setIsVisible(false);
        onClose?.();
      }, 5000);

      return () => clearTimeout(timer);
    }
  }, [isCollecting, leadsCollected, onClose]);

  if (!isVisible) return null;

  const platformColors: Record<string, string> = {
    facebook: '#1877F2',
    linkedin: '#0A66C2',
    reddit: '#FF4500',
    fiverr: '#1DBF73',
    upwork: '#14A800',
    x: '#000000',
  };

  const platformColor = platformColors[platform.toLowerCase()] || '#000000';

  return (
    <div
      style={{
        position: 'fixed',
        top: '1rem',
        left: '50%',
        transform: 'translateX(-50%)',
        zIndex: 999999,
        pointerEvents: 'auto',
        animation: 'slide-down 0.3s ease-out forwards',
      }}
    >
      <div
        style={{
          background: '#FFFFFF',
          borderRadius: '0.75rem',
          boxShadow: '0 10px 40px rgba(0, 0, 0, 0.15)',
          padding: '1rem 1.25rem',
          display: 'flex',
          alignItems: 'center',
          gap: '0.875rem',
          border: `2px solid ${platformColor}`,
          minWidth: '280px',
        }}
      >
        {/* Icon */}
        <div
          style={{
            width: '2.5rem',
            height: '2.5rem',
            borderRadius: '50%',
            background: platformColor,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            flexShrink: 0,
          }}
        >
          {isCollecting ? (
            <Loader2
              style={{
                width: '1.25rem',
                height: '1.25rem',
                color: '#FFFFFF',
                animation: 'spin 1s linear infinite',
              }}
            />
          ) : (
            <CheckCircle
              style={{
                width: '1.25rem',
                height: '1.25rem',
                color: '#FFFFFF',
              }}
            />
          )}
        </div>

        {/* Content */}
        <div style={{ flex: 1 }}>
          <div
            style={{
              fontSize: '0.875rem',
              fontWeight: 600,
              color: '#000000',
              marginBottom: '0.125rem',
            }}
          >
            {isCollecting ? 'Collecting Leads...' : 'Leads Collected!'}
          </div>
          <div style={{ fontSize: '0.75rem', color: '#6B7280' }}>
            {isCollecting
              ? `Scanning ${platform}...`
              : `${leadsCollected} lead${leadsCollected !== 1 ? 's' : ''} from ${platform}`}
          </div>
        </div>

        {/* Count Badge */}
        {leadsCollected > 0 && !isCollecting && (
          <div
            style={{
              background: platformColor,
              color: '#FFFFFF',
              fontSize: '0.875rem',
              fontWeight: 700,
              padding: '0.375rem 0.625rem',
              borderRadius: '9999px',
              minWidth: '2rem',
              textAlign: 'center',
            }}
          >
            {leadsCollected}
          </div>
        )}

        {/* Close Button */}
        {onClose && !isCollecting && (
          <button
            onClick={() => {
              setIsVisible(false);
              onClose();
            }}
            style={{
              background: 'none',
              border: 'none',
              color: '#9CA3AF',
              cursor: 'pointer',
              padding: '0.25rem',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              transition: 'color 0.2s',
            }}
            onMouseEnter={(e) => {
              e.currentTarget.style.color = '#000000';
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.color = '#9CA3AF';
            }}
          >
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
            >
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        )}
      </div>
    </div>
  );
}
