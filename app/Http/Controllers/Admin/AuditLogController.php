<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->middleware(['auth', 'role:super-admin|admin']);
        $this->auditService = $auditService;
    }

    /**
     * Display audit logs
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by event
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filter by model
        if ($request->filled('model_type')) {
            $query->where('auditable_type', $request->model_type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $audits = $query->paginate(50);

        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $events = AuditLog::distinct('event')->pluck('event');
        $models = AuditLog::distinct('auditable_type')->pluck('auditable_type');

        return view('admin.audit-logs.index', compact('audits', 'users', 'events', 'models'));
    }

    /**
     * Show audit log details
     */
    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user', 'auditable');

        return view('admin.audit-logs.show', compact('auditLog'));
    }

    /**
     * Export audit logs
     */
    public function export(Request $request)
    {
        $filters = $request->only(['user_id', 'event', 'model_type', 'date_from', 'date_to']);
        $data = $this->auditService->export($filters);

        $filename = 'audit_logs_' . date('Y-m-d_His') . '.json';

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Show audit statistics
     */
    public function statistics()
    {
        $stats = $this->auditService->getStatistics(30);

        return view('admin.audit-logs.statistics', compact('stats'));
    }

    /**
     * Clean old audits
     */
    public function clean(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:90',
        ]);

        $deleted = $this->auditService->cleanOldAudits($request->days);

        return back()->with('success', "Cleaned {$deleted} old audit logs.");
    }
}
