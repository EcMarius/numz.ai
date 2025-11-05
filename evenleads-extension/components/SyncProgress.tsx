import React from 'react';
import { Loader2, CheckCircle, XCircle, X as XIcon, Zap } from 'lucide-react';
import type { SyncProgress as SyncProgressType } from '../utils/runSync';

interface SyncProgressProps {
  progress: SyncProgressType;
  onCancel: () => void;
}

const PLATFORM_COLORS: Record<string, string> = {
  facebook: '#1877F2',
  linkedin: '#0A66C2',
  reddit: '#FF4500',
  fiverr: '#1DBF73',
  upwork: '#14A800',
  x: '#000000',
};

export default function SyncProgress({ progress, onCancel }: SyncProgressProps) {
  const platformColor = PLATFORM_COLORS[progress.currentKeyword?.toLowerCase()] || '#000000';

  return (
    <div
      style={{
        padding: '1rem',
        backgroundColor: progress.status === 'complete' ? '#D1FAE5' : progress.status === 'error' ? '#FEE2E2' : '#DBEAFE',
        border: `1px solid ${progress.status === 'complete' ? '#A7F3D0' : progress.status === 'error' ? '#FECACA' : '#93C5FD'}`,
        borderRadius: '0.75rem',
        marginBottom: '1rem',
        boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
      }}
    >
      <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: '0.75rem' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', flex: 1 }}>
          {/* Status Icon */}
          {progress.status === 'complete' ? (
            <CheckCircle style={{ width: '1.5rem', height: '1.5rem', color: '#065F46', flexShrink: 0 }} />
          ) : progress.status === 'error' ? (
            <XCircle style={{ width: '1.5rem', height: '1.5rem', color: '#991B1B', flexShrink: 0 }} />
          ) : (
            <Loader2 style={{ width: '1.5rem', height: '1.5rem', color: '#1E40AF', flexShrink: 0, animation: 'spin 1s linear infinite' }} />
          )}

          {/* Progress Text */}
          <div style={{ flex: 1 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.25rem' }}>
              <h4 style={{ fontSize: '0.875rem', fontWeight: 700, color: progress.status === 'complete' ? '#065F46' : progress.status === 'error' ? '#991B1B' : '#1E40AF' }}>
                {progress.status === 'complete' ? 'Sync Complete' : progress.status === 'error' ? 'Sync Failed' : 'Syncing Campaign'}
              </h4>
            </div>
            <p style={{ fontSize: '0.75rem', color: progress.status === 'complete' ? '#047857' : progress.status === 'error' ? '#991B1B' : '#374151' }}>
              {progress.message}
            </p>
            {progress.error && (
              <p style={{ fontSize: '0.75rem', color: '#991B1B', marginTop: '0.25rem', fontStyle: 'italic' }}>
                Error: {progress.error}
              </p>
            )}
          </div>
        </div>

        {/* Cancel Button */}
        {progress.status !== 'complete' && progress.status !== 'error' && (
          <button
            onClick={onCancel}
            style={{
              padding: '0.375rem',
              borderRadius: '0.375rem',
              backgroundColor: '#F3F4F6',
              border: 'none',
              cursor: 'pointer',
              flexShrink: 0,
              marginLeft: '0.5rem',
              transition: 'background-color 0.2s',
            }}
            onMouseEnter={(e) => {
              e.currentTarget.style.backgroundColor = '#E5E7EB';
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.backgroundColor = '#F3F4F6';
            }}
          >
            <XIcon style={{ width: '1rem', height: '1rem', color: '#6B7280' }} />
          </button>
        )}
      </div>

      {/* Stats Row */}
      {(progress.leadsFound > 0 || progress.leadsSubmitted > 0) && (
        <div style={{ display: 'flex', gap: '1rem', paddingTop: '0.75rem', borderTop: '1px solid rgba(0, 0, 0, 0.05)' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.375rem' }}>
            <Zap style={{ width: '1rem', height: '1rem', color: '#EAB308' }} />
            <span style={{ fontSize: '0.75rem', fontWeight: 600, color: '#374151' }}>
              Found: <span style={{ color: '#000000' }}>{progress.leadsFound}</span>
            </span>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.375rem' }}>
            <CheckCircle style={{ width: '1rem', height: '1rem', color: '#22C55E' }} />
            <span style={{ fontSize: '0.75rem', fontWeight: 600, color: '#374151' }}>
              Submitted: <span style={{ color: '#000000' }}>{progress.leadsSubmitted}</span>
            </span>
          </div>
        </div>
      )}

      {/* Progress Bar */}
      {progress.status !== 'complete' && progress.status !== 'error' && progress.totalKeywords > 0 && (
        <div style={{ marginTop: '0.75rem' }}>
          <div style={{ width: '100%', height: '0.375rem', backgroundColor: 'rgba(0, 0, 0, 0.1)', borderRadius: '9999px', overflow: 'hidden' }}>
            <div
              style={{
                height: '100%',
                backgroundColor: '#1E40AF',
                borderRadius: '9999px',
                width: `${((progress.currentKeywordIndex + 1) / progress.totalKeywords) * 100}%`,
                transition: 'width 0.3s ease-out',
              }}
            />
          </div>
        </div>
      )}

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
