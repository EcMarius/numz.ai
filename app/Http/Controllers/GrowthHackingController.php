<?php

namespace App\Http\Controllers;

use App\Models\GrowthHackingProspect;
use App\Models\GrowthHackingEmail;
use App\Models\UnsubscribeRequest;
use App\Services\GrowthHacking\ProspectAccountService;
use App\Services\GrowthHacking\EmailTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class GrowthHackingController extends Controller
{
    /**
     * Show welcome/password setup page
     */
    public function welcome(string $token)
    {
        $prospect = GrowthHackingProspect::where('secure_token', $token)->first();

        if (!$prospect) {
            abort(404, 'Invalid or expired link');
        }

        if (!$prospect->isTokenValid()) {
            abort(410, 'This link has expired');
        }

        // Get sample leads
        $sampleLeads = $prospect->leads()->orderBy('confidence_score', 'desc')->take(3)->get();

        return view('pages.growth-hack.welcome', [
            'prospect' => $prospect,
            'sampleLeads' => $sampleLeads,
            'token' => $token,
        ]);
    }

    /**
     * Set password and login
     */
    public function setPassword(Request $request, ProspectAccountService $accountService)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $result = $accountService->setPasswordViaToken($request->token, $request->password);

        if (!$result['success']) {
            return back()->withErrors(['password' => $result['error']]);
        }

        // Log the user in
        Auth::login($result['user']);

        // Redirect to dashboard
        return redirect('/dashboard')->with('success', 'Welcome! Your leads are ready.');
    }

    /**
     * Show unsubscribe page
     */
    public function unsubscribe(string $token)
    {
        $email = GrowthHackingEmail::where('unsubscribe_token', $token)->first();

        if (!$email) {
            abort(404, 'Invalid unsubscribe link');
        }

        return view('pages.growth-hack.unsubscribe', [
            'email' => $email,
            'token' => $token,
        ]);
    }

    /**
     * Process unsubscribe
     */
    public function processUnsubscribe(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        $email = GrowthHackingEmail::where('unsubscribe_token', $request->token)->first();

        if ($email) {
            // Mark email as unsubscribed
            $email->markAsUnsubscribed();

            // Add to global unsubscribe list
            UnsubscribeRequest::createUnsubscribe(
                $email->email_address,
                $request->token,
                $request->reason
            );

            Log::info("User unsubscribed from growth hacking emails", [
                'email' => $email->email_address,
                'reason' => $request->reason,
            ]);
        }

        return view('pages.growth-hack.unsubscribe-success');
    }

    /**
     * Track email open (1x1 pixel)
     */
    public function trackOpen(string $token, EmailTrackingService $tracking)
    {
        $tracking->trackOpen($token);

        // Return 1x1 transparent pixel
        return response()->file(public_path('images/pixel.gif'), [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Track email click
     */
    public function trackClick(Request $request, EmailTrackingService $tracking)
    {
        $token = $request->query('token');
        $url = $request->query('url');

        if ($token && $url) {
            $redirectUrl = $tracking->trackClick($token, urldecode($url));
            return redirect($redirectUrl);
        }

        abort(404);
    }
}
