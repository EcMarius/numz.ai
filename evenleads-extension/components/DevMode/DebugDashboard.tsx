import React, { useState, useEffect } from 'react';
import { Activity, AlertCircle, BarChart3, RefreshCw, Download } from 'lucide-react';

interface DebugStats {
  totalInvalidRequests: number;
  blockedFetchCount: number;
  errorEventCount: number;
  interceptedClicks?: number;
  staticResourceAccesses?: number;
  sessionDuration: number;
  requestPatterns: Record<string, number>;
}

export default function DebugDashboard() {
  const [stats, setStats] = useState<DebugStats | null>(null);
  const [isLinkedIn, setIsLinkedIn] = useState(false);
  const [autoRefresh, setAutoRefresh] = useState(true);

  useEffect(() => {
    setIsLinkedIn(window.location.hostname.includes('linkedin.com'));
    refreshStats();

    if (autoRefresh) {
      const interval = setInterval(refreshStats, 5000); // Refresh every 5 seconds
      return () => clearInterval(interval);
    }
  }, [autoRefresh]);

  const refreshStats = () => {
    // Debugger removed - just show placeholder
    setStats({
      totalInvalidRequests: 0,
      blockedFetchCount: 0,
      errorEventCount: 0,
      interceptedClicks: 0,
      staticResourceAccesses: 0,
      sessionDuration: 0,
      requestPatterns: {}
    });
  };

  const exportDebugLog = () => {
    console.log('Debug logging disabled');
  };

  const clearLogs = () => {
    console.clear();
  };

  if (!stats) {
    return (
      <div className="flex items-center justify-center h-full text-gray-400">
        <div className="text-center">
          <Activity className="w-12 h-12 mx-auto mb-2 animate-pulse" />
          <p>Loading debug stats...</p>
        </div>
      </div>
    );
  }

  const hasIssues = stats.totalInvalidRequests > 0;

  return (
    <div className="h-full overflow-y-auto p-4 bg-gray-900">
      {/* Header */}
      <div className="mb-6">
        <div className="flex items-center justify-between mb-2">
          <h2 className="text-xl font-bold text-white flex items-center gap-2">
            <BarChart3 className="w-6 h-6" />
            Debug Dashboard
          </h2>
          <div className="flex items-center gap-2">
            <button
              onClick={() => setAutoRefresh(!autoRefresh)}
              className={`px-3 py-1.5 rounded-lg text-sm flex items-center gap-1.5 transition-colors ${
                autoRefresh
                  ? 'bg-green-600 hover:bg-green-700 text-white'
                  : 'bg-gray-700 hover:bg-gray-600 text-gray-300'
              }`}
            >
              <RefreshCw className={`w-3.5 h-3.5 ${autoRefresh ? 'animate-spin' : ''}`} />
              {autoRefresh ? 'Live' : 'Paused'}
            </button>
            <button
              onClick={refreshStats}
              className="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm"
            >
              Refresh
            </button>
          </div>
        </div>

        {isLinkedIn && (
          <div className="flex items-center gap-2 text-blue-400 text-sm bg-blue-900/30 px-3 py-2 rounded-lg">
            <AlertCircle className="w-4 h-4" />
            LinkedIn detected - monitoring for invalid extension requests
          </div>
        )}
      </div>

      {/* Status Overview */}
      <div className="grid grid-cols-2 gap-4 mb-6">
        <div className={`p-4 rounded-lg ${hasIssues ? 'bg-red-900/30 border border-red-700' : 'bg-green-900/30 border border-green-700'}`}>
          <div className="text-sm text-gray-400 mb-1">Status</div>
          <div className={`text-2xl font-bold ${hasIssues ? 'text-red-400' : 'text-green-400'}`}>
            {hasIssues ? '⚠️ Issues Detected' : '✅ All Clear'}
          </div>
        </div>

        <div className="p-4 bg-gray-800 rounded-lg border border-gray-700">
          <div className="text-sm text-gray-400 mb-1">Session Duration</div>
          <div className="text-2xl font-bold text-white">
            {Math.floor(stats.sessionDuration / 60)}m {Math.floor(stats.sessionDuration % 60)}s
          </div>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-2 gap-4 mb-6">
        <StatCard
          label="Invalid Requests"
          value={stats.totalInvalidRequests}
          color={stats.totalInvalidRequests > 0 ? 'red' : 'gray'}
        />
        <StatCard
          label="Blocked Fetches"
          value={stats.blockedFetchCount}
          color={stats.blockedFetchCount > 0 ? 'yellow' : 'gray'}
        />
        <StatCard
          label="Error Events"
          value={stats.errorEventCount}
          color={stats.errorEventCount > 0 ? 'orange' : 'gray'}
        />
        <StatCard
          label="Intercepted Clicks"
          value={stats.interceptedClicks || 0}
          color={stats.interceptedClicks && stats.interceptedClicks > 0 ? 'blue' : 'gray'}
        />
      </div>

      {isLinkedIn && (
        <div className="mb-6">
          <StatCard
            label="Static Resource Accesses"
            value={stats.staticResourceAccesses || 0}
            color="purple"
            fullWidth
          />
        </div>
      )}

      {/* Request Patterns */}
      {Object.keys(stats.requestPatterns).length > 0 && (
        <div className="mb-6">
          <h3 className="text-lg font-semibold text-white mb-3">Request Patterns</h3>
          <div className="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <table className="w-full">
              <thead className="bg-gray-700/50">
                <tr>
                  <th className="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Pattern</th>
                  <th className="px-4 py-2 text-right text-xs font-medium text-gray-400 uppercase">Count</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-700">
                {Object.entries(stats.requestPatterns).map(([pattern, count]) => (
                  <tr key={pattern}>
                    <td className="px-4 py-2 text-sm text-gray-300 font-mono">{pattern}</td>
                    <td className="px-4 py-2 text-sm text-gray-300 text-right font-bold">{count}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Rate Information */}
      {stats.totalInvalidRequests > 0 && stats.sessionDuration > 0 && (
        <div className="mb-6 p-4 bg-yellow-900/20 border border-yellow-700 rounded-lg">
          <h3 className="text-sm font-semibold text-yellow-400 mb-2">⚠️ Request Rate</h3>
          <p className="text-white text-2xl font-bold">
            {(stats.totalInvalidRequests / (stats.sessionDuration / 60)).toFixed(1)} requests/min
          </p>
          <p className="text-yellow-300 text-sm mt-1">
            At this rate, you'll see {Math.round((stats.totalInvalidRequests / stats.sessionDuration) * 3600)} requests per hour
          </p>
        </div>
      )}

      {/* Actions */}
      <div className="flex gap-3">
        <button
          onClick={exportDebugLog}
          className="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg flex items-center justify-center gap-2 transition-colors"
        >
          <Download className="w-4 h-4" />
          Export Debug Log
        </button>
        <button
          onClick={() => console.clear()}
          className="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors"
        >
          Clear Console
        </button>
      </div>

      {/* Console Commands */}
      <div className="mt-6 p-4 bg-gray-800 rounded-lg border border-gray-700">
        <h3 className="text-sm font-semibold text-gray-400 mb-2">Console Commands</h3>
        <div className="space-y-1 text-xs font-mono">
          <div className="text-gray-300">
            <span className="text-blue-400">window.getLinkedInDebugStats()</span> - Get current stats
          </div>
          <div className="text-gray-300">
            <span className="text-blue-400">window.exportLinkedInDebug()</span> - Export full report
          </div>
        </div>
      </div>
    </div>
  );
}

interface StatCardProps {
  label: string;
  value: number;
  color: 'red' | 'yellow' | 'orange' | 'blue' | 'purple' | 'gray' | 'green';
  fullWidth?: boolean;
}

function StatCard({ label, value, color, fullWidth }: StatCardProps) {
  const colorClasses = {
    red: 'bg-red-900/30 border-red-700 text-red-400',
    yellow: 'bg-yellow-900/30 border-yellow-700 text-yellow-400',
    orange: 'bg-orange-900/30 border-orange-700 text-orange-400',
    blue: 'bg-blue-900/30 border-blue-700 text-blue-400',
    purple: 'bg-purple-900/30 border-purple-700 text-purple-400',
    green: 'bg-green-900/30 border-green-700 text-green-400',
    gray: 'bg-gray-800 border-gray-700 text-gray-400'
  };

  return (
    <div className={`p-4 rounded-lg border ${colorClasses[color]} ${fullWidth ? 'col-span-2' : ''}`}>
      <div className="text-sm text-gray-400 mb-1">{label}</div>
      <div className={`text-3xl font-bold ${color === 'gray' ? 'text-white' : ''}`}>
        {value.toLocaleString()}
      </div>
    </div>
  );
}
