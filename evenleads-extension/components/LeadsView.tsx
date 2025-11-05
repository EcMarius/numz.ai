import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Search, Filter, Trash2, Eye, CheckCircle, Loader2, ExternalLink, Archive, X, Star } from 'lucide-react';
import { api } from '../utils/api';

interface Lead {
  id: number;
  platform: string;
  platform_id: string;
  title: string;
  description: string;
  url: string;
  author: string;
  status: string;
  created_at: string;
  campaign?: {
    id: number;
    name: string;
  };
}

interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

const PLATFORM_COLORS: Record<string, string> = {
  facebook: '#1877F2',
  linkedin: '#0A66C2',
  reddit: '#FF4500',
  fiverr: '#1DBF73',
  upwork: '#14A800',
  x: '#000000',
};

const STATUS_CONFIG: Record<string, { bg: string; text: string; label: string }> = {
  new: { bg: '#DBEAFE', text: '#1E40AF', label: 'New' },
  contacted: { bg: '#D1FAE5', text: '#065F46', label: 'Contacted' },
  qualified: { bg: '#FEF3C7', text: '#92400E', label: 'Qualified' },
  converted: { bg: '#D1FAE5', text: '#065F46', label: 'Converted' },
  archived: { bg: '#F3F4F6', text: '#4B5563', label: 'Archived' },
};

function LeadsView() {
  const [leads, setLeads] = useState<Lead[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [pagination, setPagination] = useState<Pagination | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [debouncedSearchQuery, setDebouncedSearchQuery] = useState('');
  const [selectedPlatform, setSelectedPlatform] = useState<string>('all');
  const [selectedStatus, setSelectedStatus] = useState<string>('all');
  const [selectedLead, setSelectedLead] = useState<Lead | null>(null);

  // Ref for intersection observer
  const observerTarget = useRef<HTMLDivElement>(null);

  // Debounce search query (500ms delay)
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearchQuery(searchQuery);
    }, 500);

    return () => clearTimeout(timer);
  }, [searchQuery]);

  // Load leads on mount and when filters change
  useEffect(() => {
    loadLeads(true);
  }, [debouncedSearchQuery, selectedPlatform, selectedStatus]);

  // Infinite scroll observer
  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting && !loadingMore && pagination && pagination.current_page < pagination.last_page) {
          loadMoreLeads();
        }
      },
      { threshold: 0.1 }
    );

    const currentTarget = observerTarget.current;
    if (currentTarget) {
      observer.observe(currentTarget);
    }

    return () => {
      if (currentTarget) {
        observer.unobserve(currentTarget);
      }
    };
  }, [loadingMore, pagination]);

  async function loadLeads(resetPagination: boolean = true) {
    try {
      if (resetPagination) {
        setLoading(true);
      }

      console.log('[LeadsView] Loading leads with filters:', { search: debouncedSearchQuery, platform: selectedPlatform, status: selectedStatus });

      // Use API client with filters
      const response = await api.getLeads(20, 1, {
        search: debouncedSearchQuery || undefined,
        platform: selectedPlatform,
        status: selectedStatus,
      });

      console.log('[LeadsView] Loaded', response.leads.length, 'leads out of', response.pagination.total);

      if (resetPagination) {
        setLeads(response.leads);
      } else {
        setLeads((prev) => [...prev, ...response.leads]);
      }
      setPagination(response.pagination);
    } catch (error) {
      console.error('[LeadsView] Failed to load leads:', error);
      setLeads([]);
      setPagination(null);
    } finally {
      setLoading(false);
    }
  }

  async function loadMoreLeads() {
    if (!pagination || loadingMore || pagination.current_page >= pagination.last_page) return;

    try {
      setLoadingMore(true);

      const nextPage = pagination.current_page + 1;
      console.log('[LeadsView] Loading page', nextPage, 'of', pagination.last_page);

      const response = await api.getLeads(20, nextPage, {
        search: debouncedSearchQuery || undefined,
        platform: selectedPlatform,
        status: selectedStatus,
      }, false); // Don't use cache for pagination

      console.log('[LeadsView] Loaded', response.leads.length, 'more leads');

      setLeads((prev) => [...prev, ...response.leads]);
      setPagination(response.pagination);
    } catch (error) {
      console.error('[LeadsView] Failed to load more leads:', error);
    } finally {
      setLoadingMore(false);
    }
  }

  async function updateLeadStatus(leadId: number, status: string) {
    try {
      const baseUrl = getApiBaseUrl();

      const authData = await chrome.storage.local.get('auth:state');
      const token = authData['auth:state']?.token;

      const response = await fetch(`${baseUrl}/api/leads/${leadId}/status`, {
        method: 'PATCH',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ status }),
      });

      if (response.ok) {
        await loadLeads();
      }
    } catch (error) {
      console.error('Failed to update lead status:', error);
    }
  }

  async function deleteLead(leadId: number) {
    if (!confirm('Are you sure you want to delete this lead?')) {
      return;
    }

    try {
      const baseUrl = getApiBaseUrl();

      const authData = await chrome.storage.local.get('auth:state');
      const token = authData['auth:state']?.token;

      const response = await fetch(`${baseUrl}/api/leads/${leadId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        await loadLeads();
        setSelectedLead(null);
      }
    } catch (error) {
      console.error('Failed to delete lead:', error);
    }
  }

  // Get unique platforms and statuses from loaded leads
  const platforms = Array.from(new Set(leads.map((l) => l.platform)));
  const statuses = ['new', 'contacted', 'qualified', 'converted', 'archived']; // All possible statuses

  if (loading) {
    return (
      <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '100%', backgroundColor: '#FFFFFF' }}>
        <Loader2 style={{ width: '2.5rem', height: '2.5rem', color: '#000000', animation: 'spin 1s linear infinite', marginBottom: '1rem' }} />
        <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#6B7280' }}>Loading leads...</p>
        <style>
          {`
            @keyframes spin {
              from { transform: rotate(0deg); }
              to { transform: rotate(360deg); }
            }
            @keyframes pulse {
              0%, 100% { opacity: 1; }
              50% { opacity: 0.5; }
            }
          `}
        </style>
      </div>
    );
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', height: '100%', backgroundColor: '#FFFFFF' }}>
      <style>
        {`
          @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
          }
        `}
      </style>
      {/* Header */}
      <div style={{ borderBottom: '1px solid #E5E7EB', padding: '1.5rem', backgroundColor: '#FFFFFF' }}>
        <h2 style={{ fontSize: '1.5rem', lineHeight: '2rem', fontWeight: 800, color: '#000000', marginBottom: '1.25rem' }}>Leads Management</h2>

        {/* Search Bar */}
        <div style={{ position: 'relative', marginBottom: '1rem' }}>
          <Search style={{ position: 'absolute', left: '1rem', top: '50%', transform: 'translateY(-50%)', width: '1rem', height: '1rem', color: '#9CA3AF', pointerEvents: 'none' }} />
          <input
            type="text"
            placeholder="Search leads by title, description, or author..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            style={{ width: '100%', paddingLeft: '3rem', paddingRight: '1rem', paddingTop: '0.75rem', paddingBottom: '0.75rem', backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', borderRadius: '0.5rem', fontSize: '0.875rem', lineHeight: '1.25rem', color: '#000000', outline: 'none', transition: 'all 0.2s' }}
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

        {/* Filter Pills */}
        <div style={{ display: 'flex', gap: '0.75rem', flexWrap: 'wrap' }}>
          <div style={{ flex: '1 1 0%', minWidth: 0 }}>
            <select
              value={selectedPlatform}
              onChange={(e) => setSelectedPlatform(e.target.value)}
              style={{ width: '100%', paddingLeft: '1rem', paddingRight: '1rem', paddingTop: '0.625rem', paddingBottom: '0.625rem', backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', borderRadius: '0.5rem', fontSize: '0.875rem', lineHeight: '1.25rem', color: '#000000', outline: 'none', cursor: 'pointer', transition: 'all 0.2s' }}
              onFocus={(e) => {
                e.currentTarget.style.borderColor = '#000000';
                e.currentTarget.style.boxShadow = '0 0 0 2px rgba(0, 0, 0, 0.1)';
              }}
              onBlur={(e) => {
                e.currentTarget.style.borderColor = '#E5E7EB';
                e.currentTarget.style.boxShadow = 'none';
              }}
            >
              <option value="all">All Platforms</option>
              {platforms.map((platform) => (
                <option key={platform} value={platform}>
                  {platform.charAt(0).toUpperCase() + platform.slice(1)}
                </option>
              ))}
            </select>
          </div>

          <div style={{ flex: '1 1 0%', minWidth: 0 }}>
            <select
              value={selectedStatus}
              onChange={(e) => setSelectedStatus(e.target.value)}
              style={{ width: '100%', paddingLeft: '1rem', paddingRight: '1rem', paddingTop: '0.625rem', paddingBottom: '0.625rem', backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', borderRadius: '0.5rem', fontSize: '0.875rem', lineHeight: '1.25rem', color: '#000000', outline: 'none', cursor: 'pointer', transition: 'all 0.2s' }}
              onFocus={(e) => {
                e.currentTarget.style.borderColor = '#000000';
                e.currentTarget.style.boxShadow = '0 0 0 2px rgba(0, 0, 0, 0.1)';
              }}
              onBlur={(e) => {
                e.currentTarget.style.borderColor = '#E5E7EB';
                e.currentTarget.style.boxShadow = 'none';
              }}
            >
              <option value="all">All Statuses</option>
              {statuses.map((status) => (
                <option key={status} value={status}>
                  {STATUS_CONFIG[status]?.label || status.charAt(0).toUpperCase() + status.slice(1)}
                </option>
              ))}
            </select>
          </div>
        </div>

        <p style={{ marginTop: '1rem', fontSize: '0.875rem', lineHeight: '1.25rem', color: '#6B7280', fontWeight: 500 }}>
          Showing <span style={{ color: '#000000', fontWeight: 700 }}>{leads.length}</span> of{' '}
          <span style={{ color: '#000000', fontWeight: 700 }}>{pagination?.total || 0}</span> leads
          {(debouncedSearchQuery || selectedPlatform !== 'all' || selectedStatus !== 'all') && pagination && pagination.total < 1000 ? ` (${pagination.total} matches)` : ''}
        </p>
      </div>

      {/* Leads List */}
      <div style={{ flex: '1 1 0%', overflowY: 'auto', padding: '1.5rem' }}>
        {leads.length === 0 && !loading ? (
          <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '100%', textAlign: 'center', paddingTop: '3rem', paddingBottom: '3rem' }}>
            <div style={{ width: '5rem', height: '5rem', borderRadius: '9999px', backgroundColor: '#F3F4F6', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '1.5rem' }}>
              <Archive style={{ width: '2.5rem', height: '2.5rem', color: '#9CA3AF' }} />
            </div>
            <h3 style={{ fontSize: '1.25rem', lineHeight: '1.75rem', fontWeight: 700, color: '#000000', marginBottom: '0.75rem' }}>No leads found</h3>
            <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#6B7280', maxWidth: '28rem' }}>
              {debouncedSearchQuery || selectedPlatform !== 'all' || selectedStatus !== 'all'
                ? 'Try adjusting your search or filters'
                : 'Start collecting leads from supported platforms'}
            </p>
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
            {leads.map((lead) => {
              const statusConfig = STATUS_CONFIG[lead.status] || STATUS_CONFIG.new;
              const platformColor = PLATFORM_COLORS[lead.platform.toLowerCase()] || '#6B7280';
              const isSelected = selectedLead?.id === lead.id;

              return (
                <div
                  key={lead.id}
                  onClick={() => setSelectedLead(lead)}
                  style={{
                    padding: '1.25rem',
                    borderRadius: '0.75rem',
                    cursor: 'pointer',
                    transition: 'all 0.2s',
                    border: isSelected ? '2px solid #000000' : '2px solid #E5E7EB',
                    backgroundColor: isSelected ? '#F9FAFB' : '#FFFFFF',
                    boxShadow: isSelected ? '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)' : 'none',
                  }}
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
                  <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: '0.75rem' }}>
                    <h3 style={{ fontSize: '1rem', lineHeight: '1.5rem', fontWeight: 700, color: '#000000', flex: '1 1 0%', paddingRight: '1rem' }}>
                      {lead.title}
                    </h3>
                  </div>

                  <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#374151', marginBottom: '1rem', display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                    {lead.description}
                  </p>

                  <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', flexWrap: 'wrap' }}>
                    <span
                      style={{ display: 'inline-flex', alignItems: 'center', paddingLeft: '0.75rem', paddingRight: '0.75rem', paddingTop: '0.375rem', paddingBottom: '0.375rem', borderRadius: '0.5rem', fontSize: '0.75rem', lineHeight: '1rem', fontWeight: 700, color: '#FFFFFF', backgroundColor: platformColor, boxShadow: '0 1px 2px 0 rgba(0, 0, 0, 0.05)' }}
                    >
                      {lead.platform.charAt(0).toUpperCase() + lead.platform.slice(1)}
                    </span>

                    <span
                      style={{ display: 'inline-flex', alignItems: 'center', paddingLeft: '0.75rem', paddingRight: '0.75rem', paddingTop: '0.375rem', paddingBottom: '0.375rem', borderRadius: '0.5rem', fontSize: '0.75rem', lineHeight: '1rem', fontWeight: 700, backgroundColor: statusConfig.bg, color: statusConfig.text }}
                    >
                      {statusConfig.label}
                    </span>

                    <span style={{ fontSize: '0.75rem', lineHeight: '1rem', color: '#6B7280', fontWeight: 500 }}>
                      by <span style={{ color: '#000000', fontWeight: 600 }}>{lead.author}</span>
                    </span>
                  </div>
                </div>
              );
            })}

            {/* Intersection observer target for infinite scroll */}
            <div ref={observerTarget} style={{ height: '1px' }} />

            {/* Loading skeleton when fetching more leads */}
            {loadingMore && (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', marginTop: '0.75rem' }}>
                {[1, 2, 3].map((i) => (
                  <div
                    key={i}
                    style={{
                      padding: '1.25rem',
                      borderRadius: '0.75rem',
                      border: '2px solid #E5E7EB',
                      backgroundColor: '#FFFFFF',
                      animation: 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }}
                  >
                    <div style={{ height: '1.5rem', backgroundColor: '#E5E7EB', borderRadius: '0.375rem', marginBottom: '0.75rem', width: '70%' }} />
                    <div style={{ height: '1rem', backgroundColor: '#F3F4F6', borderRadius: '0.375rem', marginBottom: '0.5rem' }} />
                    <div style={{ height: '1rem', backgroundColor: '#F3F4F6', borderRadius: '0.375rem', width: '60%', marginBottom: '1rem' }} />
                    <div style={{ display: 'flex', gap: '0.5rem' }}>
                      <div style={{ height: '1.75rem', width: '4rem', backgroundColor: '#E5E7EB', borderRadius: '0.5rem' }} />
                      <div style={{ height: '1.75rem', width: '3.5rem', backgroundColor: '#E5E7EB', borderRadius: '0.5rem' }} />
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}
      </div>

      {/* Lead Detail Modal */}
      {selectedLead && (
        <div
          style={{ position: 'fixed', top: 0, right: 0, bottom: 0, left: 0, backgroundColor: 'rgba(0, 0, 0, 0.5)', display: 'flex', alignItems: 'flex-end', zIndex: 50 }}
          onClick={() => setSelectedLead(null)}
        >
          <div
            style={{ width: '100%', maxHeight: '85%', overflowY: 'auto', backgroundColor: '#FFFFFF', borderTopLeftRadius: '1.5rem', borderTopRightRadius: '1.5rem', boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)' }}
            onClick={(e) => e.stopPropagation()}
          >
            <div style={{ position: 'sticky', top: 0, backgroundColor: '#FFFFFF', borderBottom: '1px solid #E5E7EB', paddingLeft: '1.5rem', paddingRight: '1.5rem', paddingTop: '1rem', paddingBottom: '1rem', display: 'flex', alignItems: 'center', justifyContent: 'space-between', zIndex: 10 }}>
              <h3 style={{ fontSize: '1.125rem', lineHeight: '1.75rem', fontWeight: 700, color: '#000000', flex: '1 1 0%', paddingRight: '1rem' }}>
                {selectedLead.title}
              </h3>
              <button
                onClick={() => setSelectedLead(null)}
                style={{ padding: '0.5rem', borderRadius: '0.5rem', backgroundColor: '#F3F4F6', border: 'none', cursor: 'pointer', flexShrink: 0, transition: 'background-color 0.2s' }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.backgroundColor = '#E5E7EB';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.backgroundColor = '#F3F4F6';
                }}
              >
                <X style={{ width: '1.25rem', height: '1.25rem', color: '#374151' }} />
              </button>
            </div>

            <div style={{ padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
              {/* Description */}
              <div>
                <label style={{ fontSize: '0.75rem', lineHeight: '1rem', fontWeight: 700, color: '#9CA3AF', textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: '0.5rem', display: 'block' }}>
                  Description
                </label>
                <p style={{ fontSize: '0.875rem', lineHeight: '1.625rem', color: '#000000' }}>
                  {selectedLead.description}
                </p>
              </div>

              {/* Author */}
              <div>
                <label style={{ fontSize: '0.75rem', lineHeight: '1rem', fontWeight: 700, color: '#9CA3AF', textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: '0.5rem', display: 'block' }}>
                  Author
                </label>
                <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#000000', fontWeight: 600 }}>
                  {selectedLead.author}
                </p>
              </div>

              {/* Campaign */}
              {selectedLead.campaign && (
                <div>
                  <label style={{ fontSize: '0.75rem', lineHeight: '1rem', fontWeight: 700, color: '#9CA3AF', textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: '0.5rem', display: 'block' }}>
                    Campaign
                  </label>
                  <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#000000', fontWeight: 600 }}>
                    {selectedLead.campaign.name}
                  </p>
                </div>
              )}

              {/* View Original Button */}
              <a
                href={selectedLead.url}
                target="_blank"
                rel="noopener noreferrer"
                style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.5rem', width: '100%', paddingLeft: '1.5rem', paddingRight: '1.5rem', paddingTop: '0.875rem', paddingBottom: '0.875rem', backgroundColor: '#000000', color: '#FFFFFF', borderRadius: '0.75rem', fontWeight: 700, textDecoration: 'none', transition: 'all 0.2s', boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)' }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.backgroundColor = '#1F2937';
                  e.currentTarget.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.backgroundColor = '#000000';
                  e.currentTarget.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';
                }}
              >
                <ExternalLink style={{ width: '1rem', height: '1rem' }} />
                View Original Post
              </a>

              {/* Quick Actions */}
              <div style={{ paddingTop: '1rem', borderTop: '1px solid #E5E7EB' }}>
                <label style={{ fontSize: '0.75rem', lineHeight: '1rem', fontWeight: 700, color: '#9CA3AF', textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: '0.75rem', display: 'block' }}>
                  Quick Actions
                </label>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', gap: '0.75rem' }}>
                  <button
                    onClick={() => updateLeadStatus(selectedLead.id, 'contacted')}
                    style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.5rem', padding: '0.75rem', backgroundColor: '#D1FAE5', color: '#065F46', borderRadius: '0.5rem', fontSize: '0.875rem', lineHeight: '1.25rem', fontWeight: 700, border: 'none', cursor: 'pointer', transition: 'background-color 0.2s' }}
                    onMouseEnter={(e) => {
                      e.currentTarget.style.backgroundColor = '#A7F3D0';
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.backgroundColor = '#D1FAE5';
                    }}
                  >
                    <CheckCircle style={{ width: '1rem', height: '1rem' }} />
                    Mark Contacted
                  </button>
                  <button
                    onClick={() => updateLeadStatus(selectedLead.id, 'archived')}
                    style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.5rem', padding: '0.75rem', backgroundColor: '#F3F4F6', color: '#374151', borderRadius: '0.5rem', fontSize: '0.875rem', lineHeight: '1.25rem', fontWeight: 700, border: 'none', cursor: 'pointer', transition: 'background-color 0.2s' }}
                    onMouseEnter={(e) => {
                      e.currentTarget.style.backgroundColor = '#E5E7EB';
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.backgroundColor = '#F3F4F6';
                    }}
                  >
                    <Archive style={{ width: '1rem', height: '1rem' }} />
                    Archive
                  </button>
                </div>
              </div>

              {/* Delete Button */}
              <button
                onClick={() => deleteLead(selectedLead.id)}
                style={{ width: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.5rem', paddingLeft: '1.5rem', paddingRight: '1.5rem', paddingTop: '0.875rem', paddingBottom: '0.875rem', backgroundColor: '#FEF2F2', color: '#991B1B', borderRadius: '0.75rem', fontWeight: 700, border: '2px solid #FECACA', cursor: 'pointer', transition: 'background-color 0.2s' }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.backgroundColor = '#FEE2E2';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.backgroundColor = '#FEF2F2';
                }}
              >
                <Trash2 style={{ width: '1rem', height: '1rem' }} />
                Delete Lead
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default React.memo(LeadsView);
