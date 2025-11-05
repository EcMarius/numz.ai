<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EvenLeads - Authentication</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .success-icon {
            animation: scaleIn 0.5s ease-out;
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
    </style>
</head>
<body class="bg-white dark:bg-black min-h-screen flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <!-- Logo -->
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 rounded-2xl mb-6 shadow-lg">
            <span class="text-white dark:text-black font-bold text-3xl">1L</span>
        </div>

        <!-- Status Message -->
        <div id="loading-state">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Completing Sign In...</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Please wait while we connect your extension</p>
            <div class="spinner mx-auto text-gray-900 dark:text-white"></div>
        </div>

        <div id="success-state" class="hidden">
            <div class="success-icon inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-full mb-6">
                <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-green-600 dark:text-green-400 mb-3">Success!</h1>
            <p class="text-gray-600 dark:text-gray-400" id="success-message"></p>
        </div>

        <div id="error-state" class="hidden">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-red-500 to-red-600 rounded-full mb-6">
                <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-red-600 dark:text-red-400 mb-3">Authentication Failed</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Something went wrong during sign in.</p>
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-sm text-red-800 dark:text-red-200" id="error-message"></p>
            </div>
        </div>
    </div>

    <script>
        (async function() {
            const loadingState = document.getElementById('loading-state');
            const successState = document.getElementById('success-state');
            const errorState = document.getElementById('error-state');
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');

            try {
                // Get data from URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                const token = urlParams.get('token');
                const userJson = urlParams.get('user');
                const isNewUser = urlParams.get('new') === '1';

                if (!token || !userJson) {
                    throw new Error('Missing authentication data. Please try again.');
                }

                const user = JSON.parse(decodeURIComponent(userJson));

                // Store auth data in localStorage (accessible by extension)
                const authData = {
                    token,
                    user,
                    isNewUser,
                    timestamp: Date.now()
                };

                // Use a special key that the extension will look for
                localStorage.setItem('evenleads_extension_auth', JSON.stringify(authData));

                // Also try to post message to opener (if extension opened this window)
                if (window.opener) {
                    window.opener.postMessage({
                        type: 'EVENLEADS_AUTH_SUCCESS',
                        payload: authData
                    }, '*');
                }

                // Success!
                loadingState.classList.add('hidden');
                successState.classList.remove('hidden');
                successMessage.textContent = isNewUser
                    ? 'Welcome to EvenLeads! This window will close automatically.'
                    : 'Welcome back! This window will close automatically.';

                // Auto-close after 1 second
                setTimeout(() => {
                    window.close();
                }, 1000);

            } catch (error) {
                console.error('OAuth callback error:', error);
                loadingState.classList.add('hidden');
                errorState.classList.remove('hidden');
                errorMessage.textContent = error.message;
            }
        })();
    </script>

    @vite(['resources/js/app.js'])
</body>
</html>
