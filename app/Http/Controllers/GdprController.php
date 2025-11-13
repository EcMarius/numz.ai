<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\GdprService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GdprController extends Controller
{
    protected GdprService $gdprService;
    protected ActivityLogger $activityLogger;

    public function __construct(GdprService $gdprService, ActivityLogger $activityLogger)
    {
        $this->middleware('auth');
        $this->gdprService = $gdprService;
        $this->activityLogger = $activityLogger;
    }

    /**
     * Show GDPR privacy page
     */
    public function index()
    {
        $user = Auth::user();
        $hasPrivacyConsent = $this->gdprService->hasConsent($user, 'privacy_policy');
        $hasMarketingConsent = $this->gdprService->hasConsent($user, 'marketing');
        $pendingDeletionRequest = $user->pendingDeletionRequest();

        return view('gdpr.index', compact('hasPrivacyConsent', 'hasMarketingConsent', 'pendingDeletionRequest'));
    }

    /**
     * Export user data
     */
    public function export()
    {
        $user = Auth::user();

        try {
            $zipPath = $this->gdprService->exportUserDataAsZip($user);

            return response()->download($zipPath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Data export failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to export your data. Please try again or contact support.');
        }
    }

    /**
     * Request data deletion
     */
    public function requestDeletion(Request $request)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
            'confirm' => 'required|accepted',
        ]);

        $user = Auth::user();

        if ($user->hasPendingDeletionRequest()) {
            return back()->with('info', 'You already have a pending deletion request.');
        }

        $deletionRequest = $this->gdprService->requestDataDeletion($user, $request->reason);

        return back()->with('success', 'Your data deletion request has been submitted. We will process it within 30 days.');
    }

    /**
     * Cancel data deletion request
     */
    public function cancelDeletion()
    {
        $user = Auth::user();
        $deletionRequest = $user->pendingDeletionRequest();

        if (!$deletionRequest) {
            return back()->with('error', 'No pending deletion request found.');
        }

        $deletionRequest->update(['status' => 'cancelled']);

        $this->activityLogger->log(
            'deletion_cancelled',
            'Data deletion request cancelled',
            $user
        );

        return back()->with('success', 'Your data deletion request has been cancelled.');
    }

    /**
     * Update consent preferences
     */
    public function updateConsent(Request $request)
    {
        $request->validate([
            'marketing' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        if ($request->boolean('marketing')) {
            $this->gdprService->logConsent($user, 'marketing', 'I consent to marketing communications');
        } else {
            $this->gdprService->withdrawConsent($user, 'marketing');
        }

        return back()->with('success', 'Your consent preferences have been updated.');
    }

    /**
     * Accept privacy policy
     */
    public function acceptPrivacyPolicy()
    {
        $user = Auth::user();
        $this->gdprService->acceptPrivacyPolicy($user);

        return back()->with('success', 'Privacy policy accepted.');
    }

    /**
     * Accept terms of service
     */
    public function acceptTerms()
    {
        $user = Auth::user();
        $this->gdprService->acceptTermsOfService($user);

        return back()->with('success', 'Terms of service accepted.');
    }

    /**
     * Show consent history
     */
    public function consentHistory()
    {
        $user = Auth::user();
        $consents = \App\Models\ConsentLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('gdpr.consent-history', compact('consents'));
    }
}
