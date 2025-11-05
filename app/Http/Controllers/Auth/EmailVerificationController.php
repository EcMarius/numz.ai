<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EmailVerificationController
{
    /**
     * Handle email verification
     * Works regardless of login state
     */
    public function verify(Request $request, $id, $hash)
    {
        Log::info('Email verification link clicked', [
            'user_id' => $id,
            'hash_provided' => substr($hash, 0, 10) . '...',
            'request_url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'is_logged_in' => Auth::check(),
            'current_user_id' => Auth::id(),
        ]);

        // Find the user
        try {
            $user = User::findOrFail($id);
        } catch (\Exception $e) {
            Log::error('User not found for verification', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()
                ->view('errors.404', ['message' => 'User not found.'], 404)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache');
        }

        // Verify the hash matches the user's email
        $expectedHash = sha1($user->getEmailForVerification());
        $hashMatches = hash_equals($hash, $expectedHash);

        Log::info('Hash verification check', [
            'user_id' => $user->id,
            'email' => $user->email,
            'hash_matches' => $hashMatches,
            'provided_hash' => substr($hash, 0, 10) . '...',
            'expected_hash' => substr($expectedHash, 0, 10) . '...',
        ]);

        if (!$hashMatches) {
            Log::warning('Invalid verification link - hash mismatch', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            return redirect()->route('login')
                ->with('error', 'Invalid verification link. Please request a new verification email.')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            Log::info('Email already verified', [
                'user_id' => $user->id,
                'email' => $user->email,
                'verified_at' => $user->email_verified_at,
            ]);

            if (Auth::check()) {
                return redirect()->route('dashboard')
                    ->with('success', 'Email already verified!')
                    ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            }
            return redirect()->route('login')
                ->with('success', 'Email already verified! Please log in.')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        // Mark email as verified
        $user->markEmailAsVerified();

        // Refresh user to get updated data
        $user->refresh();

        Log::info('Email verified successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
        ]);

        // Clear the verification email cache to allow resending if needed later
        Cache::forget('verification_email_sent_' . $user->id);

        // If user is not logged in, log them in automatically
        if (!Auth::check()) {
            Auth::login($user, true);
            Log::info('User auto-logged in after verification', [
                'user_id' => $user->id,
                'auth_check' => Auth::check(),
            ]);
        }

        // Fire the verified event
        event(new \Illuminate\Auth\Events\Verified($user));

        Log::info('Verified event fired', [
            'user_id' => $user->id,
        ]);

        // Redirect to onboarding if user hasn't completed it yet
        $redirectRoute = $user->onboarding_completed ? 'dashboard' : config('devdojo.auth.settings.redirect_after_auth', '/onboarding');

        Log::info('Redirecting after verification', [
            'user_id' => $user->id,
            'onboarding_completed' => $user->onboarding_completed,
            'redirect_to' => $redirectRoute,
        ]);

        return redirect($redirectRoute)
            ->with('success', 'Email verified successfully! Welcome to EvenLeads.')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
