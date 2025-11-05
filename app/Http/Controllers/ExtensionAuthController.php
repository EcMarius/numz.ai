<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ExtensionAuthController extends Controller
{
    /**
     * Show the extension login page
     */
    public function showLogin(Request $request)
    {
        // Check if coming from extension
        $fromExtension = $request->query('extension') === 'true';

        if (!$fromExtension) {
            return redirect()->route('auth.login');
        }

        return view('pages.auth.extension-login');
    }

    /**
     * Handle extension login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Check if this is a new user (registered recently)
            $isNewUser = $user->created_at->diffInMinutes(now()) < 60;

            // Generate API token for the extension
            $token = $user->createToken('extension-token')->plainTextToken;

            // Build the callback URL that the extension expects
            $callbackUrl = $this->buildExtensionCallback($token, $user, $isNewUser);

            // Redirect to the extension callback page
            return redirect($callbackUrl);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->except('password'));
    }

    /**
     * Build the extension callback URL
     */
    protected function buildExtensionCallback($token, $user, $isNewUser = false)
    {
        // Get the extension ID from the manifest (you'll need to update this with actual extension ID)
        // For local development, we'll use chrome-extension:// with a generic callback

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'role_id' => $user->role_id,
            'roles' => $user->getRoleNames()->toArray(), // Array of role names (e.g., ['admin', 'editor'])
        ];

        $params = http_build_query([
            'token' => $token,
            'user' => json_encode($userData),
            'new' => $isNewUser ? '1' : '0',
        ]);

        // Return to the OAuth callback HTML page that will communicate with the extension
        return route('extension.oauth-callback') . '?' . $params;
    }

    /**
     * Show the OAuth callback page (HTML that communicates with extension)
     */
    public function showCallback(Request $request)
    {
        return view('pages.auth.extension-callback', [
            'token' => $request->query('token'),
            'user' => $request->query('user'),
            'isNewUser' => $request->query('new') === '1',
        ]);
    }
}
