@extends('installer.layout')

@section('title', 'Create Admin Account')

@section('content')
<div class="fade-in">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-user-shield text-green-500 mr-2"></i>Create Admin Account
    </h2>

    <p class="text-gray-600 mb-6">
        Create your administrator account to manage NUMZ.AI. You'll use these credentials to log in.
    </p>

    <form id="adminForm" class="space-y-6">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Full Name <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   name="name"
                   id="name"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all"
                   placeholder="John Doe"
                   required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Email Address <span class="text-red-500">*</span>
            </label>
            <input type="email"
                   name="email"
                   id="email"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all"
                   placeholder="admin@example.com"
                   required>
            <p class="text-xs text-gray-500 mt-1">This will be your login email</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Password <span class="text-red-500">*</span>
            </label>
            <input type="password"
                   name="password"
                   id="password"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all"
                   placeholder="••••••••"
                   minlength="8"
                   required>
            <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Confirm Password <span class="text-red-500">*</span>
            </label>
            <input type="password"
                   name="password_confirmation"
                   id="password_confirmation"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all"
                   placeholder="••••••••"
                   minlength="8"
                   required>
        </div>

        <div id="error-message" class="hidden bg-red-50 border-l-4 border-red-400 p-4 rounded">
            <div class="flex">
                <i class="fas fa-exclamation-circle text-red-400 mt-1 mr-3"></i>
                <p class="text-sm text-red-700" id="error-text"></p>
            </div>
        </div>

        <div id="success-message" class="hidden bg-green-50 border-l-4 border-green-400 p-4 rounded">
            <div class="flex">
                <i class="fas fa-check-circle text-green-400 mt-1 mr-3"></i>
                <p class="text-sm text-green-700">Installation complete! Redirecting to dashboard...</p>
            </div>
        </div>

        <div class="bg-purple-50 border-l-4 border-purple-400 p-4 rounded">
            <div class="flex">
                <i class="fas fa-info-circle text-purple-400 mt-1 mr-3"></i>
                <div>
                    <p class="text-sm text-purple-700">
                        <strong>Final Step!</strong> After creating your account, NUMZ.AI will run migrations and set up the system.
                    </p>
                </div>
            </div>
        </div>
    </form>

    <div class="flex justify-between mt-8">
        <a href="{{ route('installer.database') }}"
           class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back
        </a>

        <button type="submit"
                form="adminForm"
                id="installBtn"
                class="px-6 py-3 bg-gradient-to-r from-green-600 to-blue-600 text-white rounded-lg hover:shadow-lg transition-all">
            <span id="installBtnText">Complete Installation</span>
            <i class="fas fa-rocket ml-2" id="installBtnIcon"></i>
            <i class="fas fa-spinner spinner ml-2 hidden" id="installBtnSpinner"></i>
        </button>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#adminForm').on('submit', function(e) {
        e.preventDefault();

        // Check password match
        const password = $('#password').val();
        const confirmPassword = $('#password_confirmation').val();

        if (password !== confirmPassword) {
            $('#error-text').text('Passwords do not match!');
            $('#error-message').removeClass('hidden');
            return;
        }

        const $btn = $('#installBtn');
        const $btnText = $('#installBtnText');
        const $btnIcon = $('#installBtnIcon');
        const $btnSpinner = $('#installBtnSpinner');
        const $errorMsg = $('#error-message');
        const $successMsg = $('#success-message');

        // Disable button and show spinner
        $btn.prop('disabled', true);
        $btnText.text('Installing NUMZ.AI...');
        $btnIcon.addClass('hidden');
        $btnSpinner.removeClass('hidden');
        $errorMsg.addClass('hidden');
        $successMsg.addClass('hidden');

        $.ajax({
            url: '{{ route('installer.install') }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $successMsg.removeClass('hidden');
                    setTimeout(function() {
                        window.location.href = '/';
                    }, 2000);
                }
            },
            error: function(xhr) {
                let errorText = 'Installation failed. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorText = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorText = errors.join(' ');
                }

                $('#error-text').text(errorText);
                $errorMsg.removeClass('hidden');

                // Re-enable button
                $btn.prop('disabled', false);
                $btnText.text('Complete Installation');
                $btnIcon.removeClass('hidden');
                $btnSpinner.addClass('hidden');
            }
        });
    });
});
</script>
@endpush
@endsection
