<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-zinc-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full">
        <div class="bg-white border border-zinc-200 rounded-lg p-8">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-zinc-900 mb-2">Unsubscribe</h1>
                <p class="text-zinc-600">
                    We're sorry to see you go. You will no longer receive emails from us.
                </p>
            </div>

            <form method="POST" action="{{ route('growth-hack.unsubscribe.process') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="reason" class="block text-sm font-medium text-zinc-900 mb-2">
                        Why are you unsubscribing? (Optional)
                    </label>
                    <select
                        name="reason"
                        id="reason"
                        class="w-full px-4 py-3 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-transparent"
                    >
                        <option value="">Select a reason...</option>
                        <option value="not_interested">Not interested</option>
                        <option value="too_many_emails">Too many emails</option>
                        <option value="not_relevant">Content not relevant</option>
                        <option value="never_signed_up">I never signed up</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <button
                    type="submit"
                    class="w-full bg-zinc-900 text-white font-medium py-3 px-6 rounded-lg hover:bg-zinc-800 transition-colors"
                >
                    Confirm Unsubscribe
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="/" class="text-sm text-zinc-600 hover:text-zinc-900">
                    Return to homepage
                </a>
            </div>
        </div>
    </div>
</body>
</html>
