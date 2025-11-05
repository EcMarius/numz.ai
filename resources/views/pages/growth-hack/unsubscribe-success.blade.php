<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-zinc-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full">
        <div class="bg-white border border-zinc-200 rounded-lg p-8 text-center">
            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-zinc-900 mb-2">You've Been Unsubscribed</h1>
            <p class="text-zinc-600 mb-6">
                You will no longer receive emails from us. Your feedback helps us improve.
            </p>

            <a
                href="/"
                class="inline-block bg-zinc-900 text-white font-medium py-3 px-6 rounded-lg hover:bg-zinc-800 transition-colors"
            >
                Return to Homepage
            </a>
        </div>
    </div>
</body>
</html>
