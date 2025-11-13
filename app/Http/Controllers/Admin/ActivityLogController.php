<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    protected ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->middleware(['auth', 'role:super-admin|admin|support']);
        $this->activityLogger = $activityLogger;
    }

    /**
     * Display activity logs
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by IP
        if ($request->filled('ip_address')) {
            $query->where('ip_address', $request->ip_address);
        }

        $activities = $query->paginate(50);

        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $types = ActivityLog::distinct('type')->pluck('type');

        return view('admin.activity-logs.index', compact('activities', 'users', 'types'));
    }

    /**
     * Show activity log details
     */
    public function show(ActivityLog $activityLog)
    {
        return view('admin.activity-logs.show', compact('activityLog'));
    }

    /**
     * Export activity logs
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:json,csv',
        ]);

        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->get();

        $data = $activities->map(function ($activity) {
            return [
                'date' => $activity->created_at->toDateTimeString(),
                'user' => $activity->user?->name ?? 'System',
                'type' => $activity->type,
                'description' => $activity->description,
                'ip_address' => $activity->ip_address,
                'user_agent' => $activity->user_agent,
            ];
        });

        if ($request->format === 'json') {
            return response()->json($data);
        }

        // CSV export
        $filename = 'activity_logs_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, ['Date', 'User', 'Type', 'Description', 'IP Address', 'User Agent']);

            // Data rows
            foreach ($data as $row) {
                fputcsv($file, [
                    $row['date'],
                    $row['user'],
                    $row['type'],
                    $row['description'],
                    $row['ip_address'],
                    $row['user_agent'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show suspicious activities
     */
    public function suspicious()
    {
        $activities = ActivityLog::suspicious()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.activity-logs.suspicious', compact('activities'));
    }

    /**
     * Clean old logs
     */
    public function clean(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:30',
        ]);

        $deleted = $this->activityLogger->cleanOldActivities($request->days);

        return back()->with('success', "Cleaned {$deleted} old activity logs.");
    }
}
