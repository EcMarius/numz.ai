<?php

namespace App\Http\Middleware;

use App\Models\SystemInstallation;
use Closure;
use Illuminate\Http\Request;

class CheckInstallation
{
    public function handle(Request $request, Closure $next)
    {
        // Skip check for installer routes
        if ($request->is('install/*') || $request->is('install')) {
            // If already installed, redirect to home
            if (SystemInstallation::isInstalled()) {
                return redirect('/');
            }
            return $next($request);
        }

        // Check if installed
        if (!SystemInstallation::isInstalled()) {
            return redirect('/install');
        }

        // Check license validity
        if (!SystemInstallation::isLicenseValid()) {
            return redirect('/install/license')->with('error', 'Your license has expired or is invalid');
        }

        return $next($request);
    }
}
