<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    protected TwoFactorService $twoFactorService;
    protected ActivityLogger $activityLogger;

    public function __construct(TwoFactorService $twoFactorService, ActivityLogger $activityLogger)
    {
        $this->middleware('auth');
        $this->twoFactorService = $twoFactorService;
        $this->activityLogger = $activityLogger;
    }

    /**
     * Show 2FA setup page
     */
    public function setup()
    {
        $user = Auth::user();

        if ($this->twoFactorService->isEnabled($user)) {
            return redirect()->route('2fa.settings')->with('info', '2FA is already enabled.');
        }

        $secret = $this->twoFactorService->generateSecret();
        $qrCode = $this->twoFactorService->generateQrCode($user, $secret);

        session(['2fa_secret' => $secret]);

        return view('security.2fa.setup', compact('qrCode', 'secret'));
    }

    /**
     * Enable 2FA
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        $secret = session('2fa_secret');

        if (!$secret) {
            return back()->withErrors(['code' => 'Setup session expired. Please start again.']);
        }

        // Verify code
        if (!$this->twoFactorService->verifyCode($secret, $request->code)) {
            return back()->withErrors(['code' => 'Invalid verification code.']);
        }

        // Generate recovery codes
        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        // Enable 2FA
        $this->twoFactorService->enable($user, $secret, $recoveryCodes);

        // Log activity
        $this->activityLogger->log2FAEnable($user);

        session()->forget('2fa_secret');

        return redirect()->route('2fa.recovery-codes')
            ->with('recovery_codes', $recoveryCodes)
            ->with('success', '2FA has been enabled successfully!');
    }

    /**
     * Show recovery codes
     */
    public function recoveryCodes()
    {
        $user = Auth::user();

        if (!$this->twoFactorService->isEnabled($user)) {
            return redirect()->route('2fa.setup');
        }

        $recoveryCodes = session('recovery_codes');

        if (!$recoveryCodes) {
            return redirect()->route('2fa.settings');
        }

        return view('security.2fa.recovery-codes', compact('recoveryCodes'));
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $user = Auth::user();

        if (!$this->twoFactorService->isEnabled($user)) {
            return back()->withErrors(['error' => '2FA is not enabled.']);
        }

        $recoveryCodes = $this->twoFactorService->regenerateRecoveryCodes($user);

        $this->activityLogger->log(
            'recovery_codes_regenerate',
            'Recovery codes regenerated',
            $user
        );

        return redirect()->route('2fa.recovery-codes')
            ->with('recovery_codes', $recoveryCodes)
            ->with('success', 'Recovery codes have been regenerated.');
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        // Verify password
        if (!\Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $this->twoFactorService->disable($user);

        // Log activity
        $this->activityLogger->log2FADisable($user);

        return redirect()->route('2fa.settings')
            ->with('success', '2FA has been disabled.');
    }

    /**
     * Show 2FA challenge
     */
    public function challenge()
    {
        return view('security.2fa.challenge');
    }

    /**
     * Verify 2FA challenge
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();
        $secret = $this->twoFactorService->getSecret($user);

        // Try TOTP code first
        if ($this->twoFactorService->verifyCode($secret, $request->code)) {
            session(['2fa_verified' => true]);
            return redirect()->intended(route('dashboard'));
        }

        // Try recovery code
        if ($this->twoFactorService->verifyRecoveryCode($user, $request->code)) {
            session(['2fa_verified' => true]);

            $this->activityLogger->log(
                'recovery_code_used',
                'Recovery code used for 2FA',
                $user
            );

            return redirect()->intended(route('dashboard'))
                ->with('warning', 'You used a recovery code. Please regenerate your recovery codes.');
        }

        return back()->withErrors(['code' => 'Invalid verification code.']);
    }

    /**
     * Show 2FA settings
     */
    public function settings()
    {
        $user = Auth::user();
        $isEnabled = $this->twoFactorService->isEnabled($user);
        $remainingCodes = $isEnabled ? $this->twoFactorService->getRemainingRecoveryCodesCount($user) : 0;

        return view('security.2fa.settings', compact('isEnabled', 'remainingCodes'));
    }
}
