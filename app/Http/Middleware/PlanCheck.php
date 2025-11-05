<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class PlanCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Explicitly exclude checkout, plan selection, and subscription routes (MUST BE FIRST!)
        $excludedRoutes = [
            'checkout.quick',
            'plan-selection',
            'trial-ended',
            'plan-expired',
            'subscription.welcome',
            'verification.notice',
            'verification.verify',
            'verification.send',
            'login',
            'register',
            'password.request',
            'password.reset',
            'password.email',
            'password.update',
            'auth.login',
            'auth.register',
            'onboarding',
            'onboarding.*',
        ];
        if ($request->routeIs($excludedRoutes)) {
            return $next($request);
        }

        // Also exclude by path patterns (including auth paths)
        if ($request->is('checkout/*') ||
            $request->is('plan-selection') ||
            $request->is('subscription/*') ||
            $request->is('settings/subscription') ||
            $request->is('auth/*') ||
            $request->is('login') ||
            $request->is('register') ||
            $request->is('password/*') ||
            $request->is('email/*') ||
            $request->is('onboarding') ||
            $request->is('onboarding/*')) {
            return $next($request);
        }

        // Also exclude admin paths
        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }

        // Only check plan for specific routes that require an active subscription
        $protectedRoutes = [
            'dashboard',
            'leads',
            'leads.*',
            'campaigns',
            'campaigns.*',
            'user.api-keys',
            'settings.api',
        ];

        // Skip plan check for non-protected routes
        if (!$request->routeIs($protectedRoutes)) {
            return $next($request);
        }

        if (!$user) {
            return $next($request);
        }

        // Check if user's email is verified first (takes priority over plan check)
        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Cache plan check for 5 minutes to reduce DB queries
        $cacheKey = "user_plan_check_{$user->id}";
        $planStatus = \Cache::remember($cacheKey, 300, function () use ($user) {
            // Check if user has active subscription
            $subscription = \Wave\Subscription::where('billable_id', $user->id)
                ->whereIn('billable_type', ['user', 'App\\Models\\User'])
                ->whereIn('status', ['active', 'trialing'])
                ->orderBy('created_at', 'desc')
                ->first();

            if ($subscription) {
                return ['status' => 'active'];
            }

            // Check if user is on trial period
            if ($user->trial_ends_at) {
                $trialEnds = Carbon::parse($user->trial_ends_at);

                if ($trialEnds->isFuture()) {
                    return ['status' => 'trial'];
                } else {
                    return ['status' => 'trial_ended'];
                }
            }

            // Check if user had a subscription that expired
            $expiredSubscription = \Wave\Subscription::where('billable_id', $user->id)
                ->where('billable_type', 'user')
                ->whereIn('status', ['canceled', 'expired', 'past_due'])
                ->orderBy('created_at', 'desc')
                ->first();

            if ($expiredSubscription) {
                return ['status' => 'expired'];
            }

            return ['status' => 'none'];
        });

        // Handle plan status
        switch ($planStatus['status']) {
            case 'active':
            case 'trial':
                return $next($request);

            case 'trial_ended':
                return redirect()->route('trial-ended');

            case 'expired':
                return redirect()->route('plan-expired');

            default:
                return redirect()->route('plan-selection');
        }
    }
}
