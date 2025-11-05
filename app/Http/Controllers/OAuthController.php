<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /**
     * Start OAuth authorization flow
     */
    public function authorize(Request $request)
    {
        $clientId = $request->query('client_id', 'browser-extension');
        $redirectUri = $request->query('redirect_uri');
        $scope = $request->query('scope', 'read write');
        $state = $request->query('state');

        // Store OAuth request data in session
        session([
            'oauth_request' => [
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'scope' => $scope,
                'state' => $state,
            ]
        ]);

        // Check if user is logged in
        if (!Auth::check()) {
            // Redirect to login with return URL
            return redirect()->route('auth.login')->with('intended', route('oauth.consent'));
        }

        // User is logged in, show consent screen
        return redirect()->route('oauth.consent');
    }

    /**
     * Show OAuth consent screen
     */
    public function showConsent(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('oauth.authorize', $request->all());
        }

        $oauthRequest = session('oauth_request', []);

        if (empty($oauthRequest)) {
            return redirect()->route('home')->with('error', 'Invalid OAuth request');
        }

        // Define client applications and their permissions
        $clients = [
            'browser-extension' => [
                'name' => 'EvenLeads Browser Extension',
                'description' => 'Collect and manage leads from across the web',
                'icon' => '/images/extension-icon.png',
            ],
            'mobile-app' => [
                'name' => 'EvenLeads Mobile App',
                'description' => 'Manage your leads on the go',
                'icon' => '/images/mobile-icon.png',
            ],
        ];

        $client = $clients[$oauthRequest['client_id']] ?? [
            'name' => $oauthRequest['client_id'],
            'description' => 'Third-party application',
            'icon' => null,
        ];

        // Parse requested scopes
        $scopes = explode(' ', $oauthRequest['scope']);
        $permissions = $this->getScopePermissions($scopes);

        return view('pages.auth.oauth-app-consent', [
            'client' => $client,
            'permissions' => $permissions,
            'user' => Auth::user(),
        ]);
    }

    /**
     * Handle consent approval/denial
     */
    public function handleConsent(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('auth.login');
        }

        $oauthRequest = session('oauth_request');

        if (empty($oauthRequest)) {
            return redirect()->route('home')->with('error', 'Invalid OAuth request');
        }

        // Check if user denied
        if ($request->input('action') === 'deny') {
            session()->forget('oauth_request');

            if ($oauthRequest['redirect_uri']) {
                return redirect($oauthRequest['redirect_uri'] . '?error=access_denied&state=' . ($oauthRequest['state'] ?? ''));
            }

            return redirect()->route('home')->with('message', 'Authorization denied');
        }

        // User approved - generate token
        $user = Auth::user();
        $token = $user->createToken($oauthRequest['client_id'], explode(' ', $oauthRequest['scope']))->plainTextToken;

        // Check if this is a new authorization
        $isNewUser = $user->created_at->diffInMinutes(now()) < 60;

        // Clear OAuth session
        session()->forget('oauth_request');

        // Prepare user data
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar(), // Use avatar() method which includes default fallback
            'role_id' => $user->role_id,
            'roles' => $user->getRoleNames()->toArray(), // Array of role names (e.g., ['admin', 'editor'])
        ];

        // Build callback URL
        $params = http_build_query([
            'token' => $token,
            'user' => json_encode($userData),
            'new' => $isNewUser ? '1' : '0',
            'state' => $oauthRequest['state'] ?? '',
        ]);

        $callbackUrl = route('oauth.callback') . '?' . $params;

        return redirect($callbackUrl);
    }

    /**
     * OAuth callback page
     */
    public function callback(Request $request)
    {
        return view('pages.auth.oauth-callback', [
            'token' => $request->query('token'),
            'user' => $request->query('user'),
            'isNewUser' => $request->query('new') === '1',
            'state' => $request->query('state'),
        ]);
    }

    /**
     * Get human-readable permission descriptions for scopes
     */
    private function getScopePermissions(array $scopes): array
    {
        $scopeMap = [
            'read' => [
                'title' => 'View your account information',
                'description' => 'Access your name, email, and profile details',
                'icon' => '👤',
            ],
            'write' => [
                'title' => 'Manage your data',
                'description' => 'Create, update, and delete your campaigns and leads',
                'icon' => '✏️',
            ],
            'campaigns' => [
                'title' => 'Access your campaigns',
                'description' => 'View and manage your lead generation campaigns',
                'icon' => '📊',
            ],
            'leads' => [
                'title' => 'Access your leads',
                'description' => 'View, create, and manage leads from various platforms',
                'icon' => '🎯',
            ],
            'api' => [
                'title' => 'Make API requests',
                'description' => 'Perform actions on your behalf through the API',
                'icon' => '🔌',
            ],
        ];

        $permissions = [];
        foreach ($scopes as $scope) {
            if (isset($scopeMap[$scope])) {
                $permissions[] = $scopeMap[$scope];
            }
        }

        return $permissions;
    }
}
