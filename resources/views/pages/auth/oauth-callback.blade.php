<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EvenLeads - Authentication Success</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            text-align: center;
            max-width: 400px;
            width: 100%;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ffffff 0%, #d1d1d1 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .logo span {
            font-size: 36px;
            font-weight: bold;
            color: #0a0a0a;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 16px;
            font-weight: 700;
        }

        p {
            font-size: 16px;
            color: #a0a0a0;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #4ade80;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.3s ease-out;
        }

        .success-icon svg {
            width: 48px;
            height: 48px;
            color: white;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success {
            color: #4ade80;
        }

        .error {
            color: #ef4444;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            margin-top: 30px;
            text-align: left;
        }

        .user-info-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffffff 0%, #d1d1d1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #0a0a0a;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .user-email {
            font-size: 14px;
            color: #a0a0a0;
        }

        .token-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 12px;
            color: #a0a0a0;
        }

        .close-info {
            margin-top: 20px;
            padding: 15px;
            background: rgba(74, 222, 128, 0.1);
            border: 1px solid rgba(74, 222, 128, 0.3);
            border-radius: 8px;
            font-size: 14px;
            color: #4ade80;
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="loading-state">
            <div class="logo">
                <span>1L</span>
            </div>
            <h1 id="title">Completing Authentication...</h1>
            <p id="message">Sending data to extension...</p>
            <div class="spinner" id="spinner"></div>
        </div>

        <div id="success-state" style="display: none;">
            <div class="success-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="success">Authentication Successful!</h1>
            <p>You can now use EvenLeads extension to collect and manage leads.</p>

            @if(isset($user))
            <div class="user-info">
                <div class="user-info-header">
                    <div class="user-avatar">
                        {{ strtoupper(substr(json_decode($user)->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="user-details">
                        <div class="user-name">{{ json_decode($user)->name ?? 'User' }}</div>
                        <div class="user-email">{{ json_decode($user)->email ?? '' }}</div>
                    </div>
                </div>
                <div class="token-info">
                    ✓ Access token generated<br>
                    ✓ Extension authorized<br>
                    @if($isNewUser)
                    ✓ New account created
                    @else
                    ✓ Welcome back!
                    @endif
                </div>
            </div>
            @endif

            <div class="close-info">
                This window will close automatically in <span id="countdown">3</span> seconds...
            </div>
        </div>

        <div id="error-state" style="display: none;">
            <div class="logo">
                <span>1L</span>
            </div>
            <h1 class="error">Authentication Failed</h1>
            <p>We couldn't complete your authentication.</p>
            <div class="error-message" id="error-message"></div>
        </div>
    </div>

    <script>
        (async function() {
            try {
                // Get URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                const token = urlParams.get('token');
                const userJson = urlParams.get('user');
                const isNewUser = urlParams.get('new') === '1';
                const state = urlParams.get('state');

                if (!token || !userJson) {
                    throw new Error('Missing authentication data. Please try signing in again.');
                }

                const user = JSON.parse(decodeURIComponent(userJson));

                // Store authentication data in localStorage for the extension to pick up
                const authData = {
                    token: token,
                    user: user,
                    isNewUser: isNewUser,
                    timestamp: Date.now()
                };

                localStorage.setItem('evenleads_extension_auth', JSON.stringify(authData));

                // Send message to opener window (the extension opened this popup)
                if (window.opener && !window.opener.closed) {
                    console.log('Sending auth data to opener window via postMessage');
                    window.opener.postMessage({
                        type: 'EVENLEADS_OAUTH_SUCCESS',
                        token: token,
                        user: user,
                        isNewUser: isNewUser
                    }, '*');
                }

                // Also try to send to extension background if available
                if (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.id) {
                    console.log('Sending OAUTH_CALLBACK message to extension');
                    try {
                        chrome.runtime.sendMessage({
                            type: 'OAUTH_CALLBACK',
                            payload: {
                                token: token,
                                user: user,
                                isNewUser: isNewUser
                            }
                        });
                        console.log('Message sent to extension successfully');
                    } catch (err) {
                        console.log('Not running in extension context:', err);
                    }
                }

                // Wait a moment to ensure postMessage is sent and received
                console.log('Waiting 1 second to ensure message delivery...');
                await new Promise(resolve => setTimeout(resolve, 1000));

                // Show success state
                document.getElementById('loading-state').style.display = 'none';
                document.getElementById('success-state').style.display = 'block';

                // Countdown and auto-close
                let countdown = 3;
                const countdownEl = document.getElementById('countdown');

                const countdownInterval = setInterval(() => {
                    countdown--;
                    if (countdownEl) {
                        countdownEl.textContent = countdown;
                    }

                    if (countdown <= 0) {
                        clearInterval(countdownInterval);

                        // Try to close window
                        try {
                            window.close();
                        } catch (e) {
                            console.log('Could not close window:', e);
                        }

                        // Fallback if window.close() doesn't work
                        setTimeout(() => {
                            if (!window.closed) {
                                window.location.href = '{{ url('/dashboard') }}';
                            }
                        }, 500);
                    }
                }, 1000);

            } catch (error) {
                console.error('OAuth callback error:', error);

                // Show error state
                document.getElementById('loading-state').style.display = 'none';
                document.getElementById('error-state').style.display = 'block';
                document.getElementById('error-message').textContent = error.message;
            }
        })();

        // Handle beforeunload to notify extension
        window.addEventListener('beforeunload', function() {
            localStorage.setItem('evenleads_auth_closed', Date.now().toString());
        });
    </script>
</body>
</html>
