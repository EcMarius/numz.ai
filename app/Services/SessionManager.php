<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SessionManager
{
    /**
     * Get all active sessions for user
     */
    public function getActiveSessions(User $user): array
    {
        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get();

        return $sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => \Carbon\Carbon::createFromTimestamp($session->last_activity),
                'is_current' => $session->id === Session::getId(),
            ];
        })->toArray();
    }

    /**
     * Terminate session
     */
    public function terminateSession(string $sessionId): bool
    {
        return DB::table('sessions')
            ->where('id', $sessionId)
            ->delete() > 0;
    }

    /**
     * Terminate all sessions except current
     */
    public function terminateOtherSessions(User $user, ?string $currentSessionId = null): int
    {
        $currentSessionId = $currentSessionId ?? Session::getId();

        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    /**
     * Terminate all sessions for user
     */
    public function terminateAllSessions(User $user): int
    {
        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Get session count for user
     */
    public function getSessionCount(User $user): int
    {
        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->count();
    }

    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions(): int
    {
        $lifetime = config('session.lifetime', 120) * 60; // Convert minutes to seconds
        $expiredTime = time() - $lifetime;

        return DB::table('sessions')
            ->where('last_activity', '<', $expiredTime)
            ->delete();
    }

    /**
     * Get session information
     */
    public function getSessionInfo(string $sessionId): ?array
    {
        $session = DB::table('sessions')
            ->where('id', $sessionId)
            ->first();

        if (!$session) {
            return null;
        }

        return [
            'id' => $session->id,
            'user_id' => $session->user_id,
            'ip_address' => $session->ip_address,
            'user_agent' => $session->user_agent,
            'last_activity' => \Carbon\Carbon::createFromTimestamp($session->last_activity),
            'payload' => $session->payload,
        ];
    }

    /**
     * Check if user has multiple active sessions
     */
    public function hasMultipleSessions(User $user): bool
    {
        return $this->getSessionCount($user) > 1;
    }

    /**
     * Enforce single session per user
     */
    public function enforceSingleSession(User $user): int
    {
        $currentSessionId = Session::getId();

        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    /**
     * Get sessions by IP address
     */
    public function getSessionsByIp(string $ip): array
    {
        $sessions = DB::table('sessions')
            ->where('ip_address', $ip)
            ->orderBy('last_activity', 'desc')
            ->get();

        return $sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'user_id' => $session->user_id,
                'user_agent' => $session->user_agent,
                'last_activity' => \Carbon\Carbon::createFromTimestamp($session->last_activity),
            ];
        })->toArray();
    }

    /**
     * Regenerate session ID for security
     */
    public function regenerateSession(): bool
    {
        Session::regenerate();
        return true;
    }

    /**
     * Get session statistics
     */
    public function getStatistics(): array
    {
        $totalSessions = DB::table('sessions')->count();
        $activeSessions = DB::table('sessions')
            ->where('last_activity', '>', time() - 900) // Active in last 15 minutes
            ->count();

        $sessionsByHour = DB::table('sessions')
            ->select(DB::raw('FROM_UNIXTIME(last_activity, "%Y-%m-%d %H:00:00") as hour, COUNT(*) as count'))
            ->where('last_activity', '>', time() - 86400) // Last 24 hours
            ->groupBy('hour')
            ->orderBy('hour', 'desc')
            ->get();

        return [
            'total' => $totalSessions,
            'active' => $activeSessions,
            'by_hour' => $sessionsByHour->toArray(),
        ];
    }
}
