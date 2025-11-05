import React, { useState, useEffect } from 'react';
import { TrendingUp, Users, Target, Activity, BarChart3, Loader2 } from 'lucide-react';
import { api } from '../utils/api';
import SyncProgressBanner from './SyncProgress';
import type { AuthState } from '../types';
import type { SyncProgress as SyncProgressData } from '../utils/runSync';

interface DashboardProps {
  authState: AuthState;
  syncProgress?: SyncProgressData | null;
  onCancelSync?: () => void;
}

interface Stats {
  totalLeads: number;
  leadsByPlatform: Record<string, number>;
  activeCampaigns: number;
  recentActivity: {
    data: Array<{
      id: number;
      type: string;
      message: string;
      timestamp: string;
    }>;
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

const PLATFORM_COLORS: Record<string, { bg: string; text: string; border: string }> = {
  facebook: { bg: '#1877F2', text: '#FFFFFF', border: '#1877F2' },
  linkedin: { bg: '#0A66C2', text: '#FFFFFF', border: '#0A66C2' },
  reddit: { bg: '#FF4500', text: '#FFFFFF', border: '#FF4500' },
  fiverr: { bg: '#1DBF73', text: '#FFFFFF', border: '#1DBF73' },
  upwork: { bg: '#14A800', text: '#FFFFFF', border: '#14A800' },
  x: { bg: '#000000', text: '#FFFFFF', border: '#000000' },
};

export default function Dashboard({ authState, syncProgress, onCancelSync }: DashboardProps) {
  const [stats, setStats] = useState<Stats | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadingMoreActivity, setLoadingMoreActivity] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!authState || !authState.isAuthenticated) {
      console.log('[Dashboard] Auth state not ready yet');
      setLoading(false);
      return;
    }
    loadStats();
  }, [authState]);

  async function loadStats() {
    try {
      setLoading(true);
      setError(null);

      if (!authState?.token) {
        console.log('[Dashboard] No token available, cannot load stats');
        setStats({
          totalLeads: 0,
          leadsByPlatform: {},
          activeCampaigns: 0,
          recentActivity: { data: [], current_page: 1, per_page: 5, total: 0, last_page: 1 },
        });
        setLoading(false);
        return;
      }

      console.log('[Dashboard] Fetching stats (with caching)...');

      // Use API client with caching support
      const data = await api.getStats();
      console.log('[Dashboard] Stats loaded:', data);
      setStats(data);
    } catch (error) {
      console.error('[Dashboard] Failed to load stats:', error);
      setStats({
        totalLeads: 0,
        leadsByPlatform: {},
        activeCampaigns: 0,
        recentActivity: { data: [], current_page: 1, per_page: 5, total: 0, last_page: 1 },
      });
    } finally {
      setLoading(false);
    }
  }

  async function loadMoreActivity() {
    if (!stats || loadingMoreActivity || stats.recentActivity.current_page >= stats.recentActivity.last_page) return;

    try {
      setLoadingMoreActivity(true);

      const nextPage = stats.recentActivity.current_page + 1;
      console.log('[Dashboard] Loading more activity, page', nextPage);

      const data = await api.getStats(nextPage, 5, false); // Don't use cache

      // Append new activities to existing ones
      setStats({
        ...stats,
        recentActivity: {
          ...data.recentActivity,
          data: [...stats.recentActivity.data, ...data.recentActivity.data],
        },
      });
    } catch (error) {
      console.error('[Dashboard] Failed to load more activity:', error);
    } finally {
      setLoadingMoreActivity(false);
    }
  }

  if (!authState || !authState.user) {
    console.log('[Dashboard] Auth state or user is null');
    return (
      <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '100%', paddingLeft: '2rem', paddingRight: '2rem' }}>
        <Loader2 style={{ width: '2rem', height: '2rem', color: '#000000', animation: 'spin 1s linear infinite', marginBottom: '1rem' }} />
        <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#6B7280' }}>Loading your data...</p>
      </div>
    );
  }

  if (loading) {
    return (
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: '100%' }}>
        <Loader2 style={{ width: '2rem', height: '2rem', color: '#000000', animation: 'spin 1s linear infinite' }} />
      </div>
    );
  }

  const { user } = authState;
  const totalLeads = stats?.totalLeads || 0;
  const leadsByPlatform = stats?.leadsByPlatform || {};
  const activeCampaigns = stats?.activeCampaigns || 0;

  return (
    <div style={{ overflowY: 'auto', height: '100%', backgroundColor: '#FFFFFF' }}>
      <div style={{ padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
        {/* Sync Progress Banner */}
        {syncProgress && (
          <SyncProgressBanner progress={syncProgress} onCancel={onCancelSync || (() => {})} />
        )}

        {/* User Profile Section */}
        <div style={{ display: 'flex', alignItems: 'center', gap: '1rem', padding: '1.25rem', backgroundColor: '#F9FAFB', borderRadius: '0.75rem', border: '1px solid #E5E7EB', transition: 'box-shadow 0.2s' }}>
          {user?.avatar && user.avatar.startsWith('http') ? (
            <img
              src={user.avatar}
              alt={user.name}
              style={{ width: '4rem', height: '4rem', borderRadius: '9999px', border: '3px solid #000000', objectFit: 'cover' }}
              onError={(e) => {
                // Hide image and show fallback
                e.currentTarget.style.display = 'none';
                const fallback = e.currentTarget.nextElementSibling as HTMLElement;
                if (fallback) fallback.style.display = 'flex';
              }}
            />
          ) : null}
          <div style={{ width: '4rem', height: '4rem', borderRadius: '9999px', backgroundColor: '#000000', display: (user?.avatar && user.avatar.startsWith('http')) ? 'none' : 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <Users style={{ width: '2rem', height: '2rem', color: '#FFFFFF' }} />
          </div>
          <div style={{ flex: '1 1 0%', minWidth: 0 }}>
            <h2 style={{ fontSize: '1.25rem', lineHeight: '1.75rem', fontWeight: 700, color: '#000000', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
              {user?.name || 'User'}
            </h2>
            <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#6B7280', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
              {user?.email || ''}
            </p>
          </div>
        </div>

        {/* Stats Cards */}
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', gap: '1rem' }}>
          {/* Total Leads */}
          <div style={{ background: 'linear-gradient(to bottom right, #F9FAFB, #F3F4F6)', borderRadius: '0.75rem', border: '1px solid #E5E7EB', padding: '1.25rem', transition: 'all 0.2s', cursor: 'pointer' }}
               onMouseEnter={(e) => {
                 e.currentTarget.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
                 e.currentTarget.style.transform = 'scale(1.05)';
               }}
               onMouseLeave={(e) => {
                 e.currentTarget.style.boxShadow = '';
                 e.currentTarget.style.transform = 'scale(1)';
               }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '0.75rem' }}>
              <div style={{ width: '2.5rem', height: '2.5rem', borderRadius: '9999px', backgroundColor: '#000000', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <TrendingUp style={{ width: '1.25rem', height: '1.25rem', color: '#FFFFFF' }} />
              </div>
              <span style={{ fontSize: '0.75rem', lineHeight: '1rem', fontWeight: 700, color: '#6B7280', textTransform: 'uppercase', letterSpacing: '0.05em' }}>Total Leads</span>
            </div>
            <p style={{ fontSize: '2.25rem', lineHeight: '2.5rem', fontWeight: 800, color: '#000000' }}>{totalLeads}</p>
          </div>

          {/* Active Campaigns */}
          <div style={{ background: 'linear-gradient(to bottom right, #F9FAFB, #F3F4F6)', borderRadius: '0.75rem', border: '1px solid #E5E7EB', padding: '1.25rem', transition: 'all 0.2s', cursor: 'pointer' }}
               onMouseEnter={(e) => {
                 e.currentTarget.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
                 e.currentTarget.style.transform = 'scale(1.05)';
               }}
               onMouseLeave={(e) => {
                 e.currentTarget.style.boxShadow = '';
                 e.currentTarget.style.transform = 'scale(1)';
               }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '0.75rem' }}>
              <div style={{ width: '2.5rem', height: '2.5rem', borderRadius: '9999px', backgroundColor: '#000000', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <Target style={{ width: '1.25rem', height: '1.25rem', color: '#FFFFFF' }} />
              </div>
              <span style={{ fontSize: '0.75rem', lineHeight: '1rem', fontWeight: 700, color: '#6B7280', textTransform: 'uppercase', letterSpacing: '0.05em' }}>Campaigns</span>
            </div>
            <p style={{ fontSize: '2.25rem', lineHeight: '2.5rem', fontWeight: 800, color: '#000000' }}>{activeCampaigns}</p>
          </div>
        </div>

        {/* Leads by Platform */}
        <div style={{ backgroundColor: '#FFFFFF', borderRadius: '0.75rem', border: '1px solid #E5E7EB', padding: '1.5rem', boxShadow: '0 1px 2px 0 rgba(0, 0, 0, 0.05)' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '1.25rem' }}>
            <BarChart3 style={{ width: '1.25rem', height: '1.25rem', color: '#000000' }} />
            <h3 style={{ fontSize: '1.125rem', lineHeight: '1.75rem', fontWeight: 700, color: '#000000' }}>Leads by Platform</h3>
          </div>

          {Object.keys(leadsByPlatform).length > 0 ? (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
              {Object.entries(leadsByPlatform).map(([platform, count]) => {
                const platformConfig = PLATFORM_COLORS[platform.toLowerCase()] || {
                  bg: '#6B7280',
                  text: '#FFFFFF',
                  border: '#6B7280',
                };
                const percentage = totalLeads > 0 ? ((count as number) / totalLeads) * 100 : 0;

                return (
                  <div key={platform} style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                        <div
                          style={{ width: '1rem', height: '1rem', borderRadius: '9999px', boxShadow: '0 1px 2px 0 rgba(0, 0, 0, 0.05)', backgroundColor: platformConfig.bg }}
                        />
                        <span style={{ fontSize: '0.875rem', lineHeight: '1.25rem', fontWeight: 600, color: '#000000', textTransform: 'capitalize' }}>
                          {platform}
                        </span>
                      </div>
                      <span style={{ fontSize: '0.875rem', lineHeight: '1.25rem', fontWeight: 700, color: '#000000', fontVariantNumeric: 'tabular-nums' }}>
                        {count}
                      </span>
                    </div>
                    <div style={{ width: '100%', height: '0.625rem', borderRadius: '9999px', backgroundColor: '#F3F4F6', overflow: 'hidden' }}>
                      <div
                        style={{
                          height: '100%',
                          borderRadius: '9999px',
                          transition: 'all 0.7s ease-out',
                          width: `${percentage}%`,
                          backgroundColor: platformConfig.bg,
                        }}
                      />
                    </div>
                  </div>
                );
              })}
            </div>
          ) : (
            <div style={{ textAlign: 'center', paddingTop: '3rem', paddingBottom: '3rem' }}>
              <BarChart3 style={{ width: '4rem', height: '4rem', marginLeft: 'auto', marginRight: 'auto', marginBottom: '1rem', color: '#D1D5DB' }} />
              <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#6B7280', fontWeight: 500 }}>
                No leads collected yet
              </p>
              <p style={{ fontSize: '0.75rem', lineHeight: '1rem', color: '#9CA3AF', marginTop: '0.5rem' }}>
                Visit supported platforms to start collecting leads
              </p>
            </div>
          )}
        </div>

        {/* Recent Activity */}
        <div style={{ backgroundColor: '#FFFFFF', borderRadius: '0.75rem', border: '1px solid #E5E7EB', padding: '1.5rem', boxShadow: '0 1px 2px 0 rgba(0, 0, 0, 0.05)' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '1.25rem' }}>
            <Activity style={{ width: '1.25rem', height: '1.25rem', color: '#000000' }} />
            <h3 style={{ fontSize: '1.125rem', lineHeight: '1.75rem', fontWeight: 700, color: '#000000' }}>Recent Activity</h3>
          </div>

          {stats?.recentActivity && stats.recentActivity.data.length > 0 ? (
            <>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                {stats.recentActivity.data.map((activity) => (
                  <div
                    key={activity.id}
                    style={{ padding: '1rem', borderRadius: '0.5rem', backgroundColor: '#F9FAFB', border: '1px solid #E5E7EB', transition: 'background-color 0.2s', cursor: 'pointer' }}
                    onMouseEnter={(e) => {
                      e.currentTarget.style.backgroundColor = '#F3F4F6';
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.backgroundColor = '#F9FAFB';
                    }}
                  >
                    <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#000000', fontWeight: 500 }}>{activity.message}</p>
                  <p style={{ fontSize: '0.75rem', lineHeight: '1rem', color: '#9CA3AF', marginTop: '0.375rem', fontVariantNumeric: 'tabular-nums' }}>
                    {new Date(activity.timestamp).toLocaleString()}
                  </p>
                </div>
              ))}
              </div>

              {/* Show More Button */}
              {stats.recentActivity.current_page < stats.recentActivity.last_page && (
                <div style={{ textAlign: 'center', marginTop: '1rem' }}>
                  <button
                    onClick={loadMoreActivity}
                    disabled={loadingMoreActivity}
                    style={{
                      padding: '0.5rem 1.5rem',
                      fontSize: '0.875rem',
                      fontWeight: 600,
                      color: loadingMoreActivity ? '#9CA3AF' : '#000000',
                      backgroundColor: 'transparent',
                      border: 'none',
                      cursor: loadingMoreActivity ? 'not-allowed' : 'pointer',
                      transition: 'color 0.2s',
                      textDecoration: 'underline',
                    }}
                    onMouseEnter={(e) => {
                      if (!loadingMoreActivity) e.currentTarget.style.color = '#374151';
                    }}
                    onMouseLeave={(e) => {
                      if (!loadingMoreActivity) e.currentTarget.style.color = '#000000';
                    }}
                  >
                    {loadingMoreActivity ? 'Loading more...' : 'Show more'}
                  </button>
                </div>
              )}
            </>
          ) : (
            <div style={{ textAlign: 'center', paddingTop: '3rem', paddingBottom: '3rem' }}>
              <Activity style={{ width: '4rem', height: '4rem', marginLeft: 'auto', marginRight: 'auto', marginBottom: '1rem', color: '#D1D5DB' }} />
              <p style={{ fontSize: '0.875rem', lineHeight: '1.25rem', color: '#6B7280', fontWeight: 500 }}>
                No recent activity
              </p>
              <p style={{ fontSize: '0.75rem', lineHeight: '1rem', color: '#9CA3AF', marginTop: '0.5rem' }}>
                Activity will appear here as you collect leads
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
