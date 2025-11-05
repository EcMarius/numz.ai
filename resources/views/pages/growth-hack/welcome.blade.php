<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-zinc-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-2xl w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-zinc-900 mb-2">Welcome to {{ config('app.name') }}</h1>
            <p class="text-lg text-zinc-600">
                We found <span class="font-semibold text-zinc-900">{{ $prospect->leads_found }}</span> potential leads for {{ $prospect->business_name ?? 'your business' }}
            </p>
        </div>

        <!-- Sample Leads Preview -->
        @if($sampleLeads->count() > 0)
            <div class="bg-white border border-zinc-200 rounded-lg p-6 mb-6">
                <h3 class="text-sm font-semibold text-zinc-500 uppercase mb-4">Your Top Leads</h3>
                <div class="space-y-4">
                    @foreach($sampleLeads as $lead)
                        <div class="border-l-4 border-zinc-900 pl-4">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 text-xs font-bold rounded bg-zinc-900 text-white">
                                    {{ number_format($lead->confidence_score, 1) }}
                                </span>
                                <span class="text-xs text-zinc-500">{{ $lead->platform }}</span>
                            </div>
                            <h4 class="font-semibold text-zinc-900">{{ $lead->title }}</h4>
                            <p class="text-sm text-zinc-600 mt-1">{{ Str::limit($lead->description, 120) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Password Setup Form -->
        <div class="bg-white border border-zinc-200 rounded-lg p-8">
            <h2 class="text-xl font-semibold text-zinc-900 mb-6">Set Your Password</h2>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-red-800">{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('growth-hack.set-password') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="password" class="block text-sm font-medium text-zinc-900 mb-2">
                        Password
                    </label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        minlength="8"
                        class="w-full px-4 py-3 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-transparent"
                        placeholder="Enter your password"
                    >
                    <p class="text-xs text-zinc-500 mt-1">Minimum 8 characters</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-zinc-900 mb-2">
                        Confirm Password
                    </label>
                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        required
                        minlength="8"
                        class="w-full px-4 py-3 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-zinc-900 focus:border-transparent"
                        placeholder="Confirm your password"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full bg-zinc-900 text-white font-medium py-3 px-6 rounded-lg hover:bg-zinc-800 transition-colors"
                >
                    Set Password & View All Leads
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-sm text-zinc-500">
            <p>Link expires in {{ $prospect->token_expires_at->diffForHumans() }}</p>
        </div>
    </div>
</body>
</html>
