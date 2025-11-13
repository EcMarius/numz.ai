<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ApiSecurityService;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityController extends Controller
{
    protected ApiSecurityService $apiSecurityService;
    protected TwoFactorService $twoFactorService;

    public function __construct(
        ApiSecurityService $apiSecurityService,
        TwoFactorService $twoFactorService
    ) {
        $this->middleware('auth:sanctum');
        $this->apiSecurityService = $apiSecurityService;
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Get 2FA status
     */
    public function twoFactorStatus(): JsonResponse
    {
        $user = Auth::user();
        $isEnabled = $this->twoFactorService->isEnabled($user);

        return response()->json([
            'enabled' => $isEnabled,
            'method' => $isEnabled ? ($user->two_factor_method ?? 'app') : null,
            'remaining_recovery_codes' => $isEnabled ? $this->twoFactorService->getRemainingRecoveryCodesCount($user) : 0,
        ]);
    }

    /**
     * Generate 2FA secret
     */
    public function generateTwoFactorSecret(): JsonResponse
    {
        $user = Auth::user();

        if ($this->twoFactorService->isEnabled($user)) {
            return response()->json([
                'error' => '2FA is already enabled',
            ], 400);
        }

        $secret = $this->twoFactorService->generateSecret();
        $qrCode = $this->twoFactorService->generateQrCode($user, $secret);

        return response()->json([
            'secret' => $secret,
            'qr_code_svg' => $qrCode,
        ]);
    }

    /**
     * Enable 2FA
     */
    public function enableTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'secret' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();

        // Verify code
        if (!$this->twoFactorService->verifyCode($request->secret, $request->code)) {
            return response()->json([
                'error' => 'Invalid verification code',
            ], 422);
        }

        // Generate recovery codes
        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        // Enable 2FA
        $this->twoFactorService->enable($user, $request->secret, $recoveryCodes);

        return response()->json([
            'message' => '2FA enabled successfully',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Disable 2FA
     */
    public function disableTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        // Verify password
        if (!\Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Invalid password',
            ], 422);
        }

        $this->twoFactorService->disable($user);

        return response()->json([
            'message' => '2FA disabled successfully',
        ]);
    }

    /**
     * Verify 2FA code
     */
    public function verifyTwoFactorCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();
        $secret = $this->twoFactorService->getSecret($user);

        if ($this->twoFactorService->verifyCode($secret, $request->code)) {
            return response()->json([
                'valid' => true,
            ]);
        }

        // Try recovery code
        if ($this->twoFactorService->verifyRecoveryCode($user, $request->code)) {
            return response()->json([
                'valid' => true,
                'recovery_code_used' => true,
                'warning' => 'Recovery code used. Please regenerate your recovery codes.',
            ]);
        }

        return response()->json([
            'valid' => false,
            'error' => 'Invalid verification code',
        ], 422);
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(): JsonResponse
    {
        $user = Auth::user();

        if (!$this->twoFactorService->isEnabled($user)) {
            return response()->json([
                'error' => '2FA is not enabled',
            ], 400);
        }

        $recoveryCodes = $this->twoFactorService->regenerateRecoveryCodes($user);

        return response()->json([
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Get active sessions
     */
    public function activeSessions(): JsonResponse
    {
        $user = Auth::user();
        $sessions = app(\App\Services\SessionManager::class)->getActiveSessions($user);

        return response()->json([
            'sessions' => $sessions,
        ]);
    }

    /**
     * Terminate session
     */
    public function terminateSession(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $success = app(\App\Services\SessionManager::class)->terminateSession($request->session_id);

        if ($success) {
            return response()->json([
                'message' => 'Session terminated successfully',
            ]);
        }

        return response()->json([
            'error' => 'Failed to terminate session',
        ], 400);
    }

    /**
     * Terminate all other sessions
     */
    public function terminateOtherSessions(): JsonResponse
    {
        $user = Auth::user();
        $count = app(\App\Services\SessionManager::class)->terminateOtherSessions($user);

        return response()->json([
            'message' => 'All other sessions terminated',
            'count' => $count,
        ]);
    }
}
