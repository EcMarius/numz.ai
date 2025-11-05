import React, { useState, useEffect } from 'react';
import {
  ArrowLeft,
  ExternalLink,
  Calendar,
  User,
  Tag,
  MessageSquare,
  CheckCircle,
  Clock,
  Target,
  Loader2,
} from 'lucide-react';
import { getApiBaseUrl } from '../config';

interface Lead {
  id: number;
  platform: string;
  platform_id: string;
  title: string;
  description: string;
  url: string;
  author: string;
  status: string;
  confidence_score: number;
  matched_keywords: string[];
  created_at: string;
  synced_at?: string;
  campaign?: {
    id: number;
    name: string;
  };
  subreddit?: string;
  facebook_group?: string;
  comments_count?: number;
}

interface LeadDetailViewProps {
  leadId: number;
  onBack: () => void;
}

const PLATFORM_COLORS: Record<string, string> = {
  facebook: '#1877F2',
  linkedin: '#0A66C2',
  reddit: '#FF4500',
  fiverr: '#1DBF73',
  upwork: '#14A800',
  x: '#000000',
};

const STATUS_COLORS: Record<string, { bg: string; text: string }> = {
  new: { bg: '#DBEAFE', text: '#1E40AF' },
  contacted: { bg: '#D1FAE5', text: '#065F46' },
  qualified: { bg: '#FEF3C7', text: '#92400E' },
  converted: { bg: '#D1FAE5', text: '#065F46' },
  archived: { bg: '#F3F4F6', text: '#4B5563' },
};

export default function LeadDetailView({ leadId, onBack }: LeadDetailViewProps) {
  const [lead, setLead] = useState<Lead | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadLead();
  }, [leadId]);

  async function loadLead() {
    try {
      setLoading(true);
      const baseUrl = getApiBaseUrl();

      const authData = await chrome.storage.local.get('auth:state');
      const token = authData['auth:state']?.token;

      if (!token) {
        throw new Error('Not authenticated');
      }

      const response = await fetch(`${baseUrl}/api/leads/${leadId}`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error('Failed to load lead');
      }

      const data = await response.json();
      setLead(data.data || data);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  if (loading) {
    return (
      <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '100%', padding: '2rem' }}>
        <Loader2 style={{ width: '2rem', height: '2rem', color: '#000000', animation: 'spin 1s linear infinite', marginBottom: '1rem' }} />
        <p style={{ color: '#6B7280', fontSize: '0.875rem' }}>Loading lead details...</p>
      </div>
    );
  }

  if (error || !lead) {
    return (
      <div style={{ padding: '1.5rem' }}>
        <button
          onClick={onBack}
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '0.5rem',
            padding: '0.5rem 1rem',
            backgroundColor: 'transparent',
            border: '1px solid #E5E7EB',
            borderRadius: '0.5rem',
            cursor: 'pointer',
            marginBottom: '1.5rem',
          }}
        >
          <ArrowLeft style={{ width: '1rem', height: '1rem' }} />
          Back
        </button>
        <div style={{ textAlign: 'center', padding: '2rem' }}>
          <p style={{ color: '#EF4444' }}>{error || 'Lead not found'}</p>
        </div>
      </div>
    );
  }

  const platformColor = PLATFORM_COLORS[lead.platform] || '#6B7280';
  const statusColor = STATUS_COLORS[lead.status] || STATUS_COLORS.new;

  return (
    <div style={{ backgroundColor: '#FFFFFF', height: '100%', overflow: 'auto' }}>
      {/* Header */}
      <div style={{ padding: '1.25rem', borderBottom: '1px solid #E5E7EB', position: 'sticky', top: 0, backgroundColor: '#FFFFFF', zIndex: 1 }}>
        <button
          onClick={onBack}
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '0.5rem',
            padding: '0.5rem 1rem',
            backgroundColor: 'transparent',
            border: '1px solid #E5E7EB',
            borderRadius: '0.5rem',
            cursor: 'pointer',
            marginBottom: '1rem',
            fontSize: '0.875rem',
          }}
        >
          <ArrowLeft style={{ width: '1rem', height: '1rem' }} />
          Back to Leads
        </button>

        <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '0.75rem' }}>
          <div
            style={{
              width: '2.5rem',
              height: '2.5rem',
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
            {lead.platform.substring(0, 2).toUpperCase()}
          </div>
          <div>
            <h1 style={{ fontSize: '1.25rem', fontWeight: 700, color: '#000000', marginBottom: '0.25rem' }}>
              {lead.title}
            </h1>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
              <span
                style={{
                  padding: '0.25rem 0.625rem',
                  borderRadius: '9999px',
                  fontSize: '0.75rem',
                  fontWeight: 600,
                  backgroundColor: statusColor.bg,
                  color: statusColor.text,
                }}
              >
                {lead.status}
              </span>
              <span style={{ color: '#6B7280', fontSize: '0.875rem' }}>
                Confidence: {lead.confidence_score}/10
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Content */}
      <div style={{ padding: '1.25rem' }}>
        {/* Campaign Info */}
        {lead.campaign && (
          <div style={{ marginBottom: '1.5rem', padding: '1rem', backgroundColor: '#F9FAFB', borderRadius: '0.75rem' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.5rem' }}>
              <Target style={{ width: '1rem', height: '1rem', color: '#000000' }} />
              <span style={{ fontSize: '0.875rem', fontWeight: 600, color: '#000000' }}>Campaign</span>
            </div>
            <p style={{ fontSize: '0.875rem', color: '#6B7280' }}>{lead.campaign.name}</p>
          </div>
        )}

        {/* Author */}
        <div style={{ marginBottom: '1.5rem' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.5rem' }}>
            <User style={{ width: '1rem', height: '1rem', color: '#000000' }} />
            <span style={{ fontSize: '0.875rem', fontWeight: 600, color: '#000000' }}>Author</span>
          </div>
          <p style={{ fontSize: '0.875rem', color: '#6B7280' }}>{lead.author}</p>
        </div>

        {/* Description */}
        {lead.description && (
          <div style={{ marginBottom: '1.5rem' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.5rem' }}>
              <MessageSquare style={{ width: '1rem', height: '1rem', color: '#000000' }} />
              <span style={{ fontSize: '0.875rem', fontWeight: 600, color: '#000000' }}>Description</span>
            </div>
            <p style={{ fontSize: '0.875rem', color: '#6B7280', lineHeight: 1.6, whiteSpace: 'pre-wrap' }}>
              {lead.description}
            </p>
          </div>
        )}

        {/* Keywords */}
        {lead.matched_keywords && lead.matched_keywords.length > 0 && (
          <div style={{ marginBottom: '1.5rem' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.5rem' }}>
              <Tag style={{ width: '1rem', height: '1rem', color: '#000000' }} />
              <span style={{ fontSize: '0.875rem', fontWeight: 600, color: '#000000' }}>Matched Keywords</span>
            </div>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.5rem' }}>
              {lead.matched_keywords.map((keyword, index) => (
                <span
                  key={index}
                  style={{
                    padding: '0.25rem 0.625rem',
                    backgroundColor: '#F3F4F6',
                    border: '1px solid #E5E7EB',
                    borderRadius: '0.375rem',
                    fontSize: '0.75rem',
                    color: '#000000',
                  }}
                >
                  {keyword}
                </span>
              ))}
            </div>
          </div>
        )}

        {/* Metadata */}
        <div style={{ marginBottom: '1.5rem' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.75rem' }}>
            <Calendar style={{ width: '1rem', height: '1rem', color: '#000000' }} />
            <span style={{ fontSize: '0.875rem', fontWeight: 600, color: '#000000' }}>Details</span>
          </div>
          <div style={{ fontSize: '0.75rem', color: '#6B7280', display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
            <div>
              <strong>Created:</strong> {new Date(lead.created_at).toLocaleString()}
            </div>
            {lead.synced_at && (
              <div>
                <strong>Synced:</strong> {new Date(lead.synced_at).toLocaleString()}
              </div>
            )}
            {lead.subreddit && (
              <div>
                <strong>Subreddit:</strong> r/{lead.subreddit}
              </div>
            )}
            {lead.facebook_group && (
              <div>
                <strong>Facebook Group:</strong> {lead.facebook_group}
              </div>
            )}
            {lead.comments_count !== undefined && (
              <div>
                <strong>Comments:</strong> {lead.comments_count}
              </div>
            )}
          </div>
        </div>

        {/* Actions */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
          <a
            href={lead.url}
            target="_blank"
            rel="noopener noreferrer"
            style={{
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '0.5rem',
              padding: '0.75rem 1rem',
              backgroundColor: platformColor,
              color: '#FFFFFF',
              borderRadius: '0.5rem',
              textDecoration: 'none',
              fontSize: '0.875rem',
              fontWeight: 600,
            }}
          >
            <ExternalLink style={{ width: '1rem', height: '1rem' }} />
            View on {lead.platform.charAt(0).toUpperCase() + lead.platform.slice(1)}
          </a>

          <button
            onClick={async () => {
              const baseUrl = getApiBaseUrl();
              window.open(`${baseUrl}/dashboard/leads/${lead.id}`, '_blank');
            }}
            style={{
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '0.5rem',
              padding: '0.75rem 1rem',
              backgroundColor: 'transparent',
              border: '1px solid #E5E7EB',
              borderRadius: '0.5rem',
              cursor: 'pointer',
              fontSize: '0.875rem',
              fontWeight: 600,
              color: '#000000',
            }}
          >
            <ExternalLink style={{ width: '1rem', height: '1rem' }} />
            View on Dashboard
          </button>
        </div>
      </div>
    </div>
  );
}
