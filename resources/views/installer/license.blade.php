@extends('installer.layout')

@section('title', 'License Verification')

@section('content')
<div class="fade-in">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-key text-purple-500 mr-2"></i>License Verification
    </h2>

    <p class="text-gray-600 mb-6">
        Please enter your NUMZ.AI license key and email to continue with the installation.
    </p>

    <form id="licenseForm" class="space-y-6">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                License Key <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   name="license_key"
                   id="license_key"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all"
                   placeholder="XXXX-XXXX-XXXX-XXXX-XXXX"
                   required>
            <p class="text-xs text-gray-500 mt-1">Enter your 20+ character license key</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                License Email <span class="text-red-500">*</span>
            </label>
            <input type="email"
                   name="email"
                   id="email"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all"
                   placeholder="your@email.com"
                   required>
            <p class="text-xs text-gray-500 mt-1">Email associated with your license</p>
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
                <p class="text-sm text-green-700">License verified successfully!</p>
            </div>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
            <div class="flex">
                <i class="fas fa-info-circle text-blue-400 mt-1 mr-3"></i>
                <div>
                    <p class="text-sm text-blue-700">
                        <strong>Don't have a license?</strong><br>
                        Purchase NUMZ.AI at <a href="https://numz.ai" target="_blank" class="underline font-semibold">numz.ai</a>
                    </p>
                </div>
            </div>
        </div>
    </form>

    <div class="flex justify-between mt-8">
        <a href="{{ route('installer.requirements') }}"
           class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back
        </a>

        <button type="submit"
                form="licenseForm"
                id="verifyBtn"
                class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:shadow-lg transition-all">
            <span id="verifyBtnText">Verify License</span>
            <i class="fas fa-arrow-right ml-2" id="verifyBtnIcon"></i>
            <i class="fas fa-spinner spinner ml-2 hidden" id="verifyBtnSpinner"></i>
        </button>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#licenseForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#verifyBtn');
        const $btnText = $('#verifyBtnText');
        const $btnIcon = $('#verifyBtnIcon');
        const $btnSpinner = $('#verifyBtnSpinner');
        const $errorMsg = $('#error-message');
        const $successMsg = $('#success-message');

        // Disable button and show spinner
        $btn.prop('disabled', true);
        $btnText.text('Verifying...');
        $btnIcon.addClass('hidden');
        $btnSpinner.removeClass('hidden');
        $errorMsg.addClass('hidden');
        $successMsg.addClass('hidden');

        $.ajax({
            url: '{{ route('installer.license.verify') }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $successMsg.removeClass('hidden');
                    setTimeout(function() {
                        window.location.href = '{{ route('installer.database') }}';
                    }, 1000);
                }
            },
            error: function(xhr) {
                let errorText = 'License verification failed. Please check your credentials.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorText = xhr.responseJSON.message;
                }

                $('#error-text').text(errorText);
                $errorMsg.removeClass('hidden');

                // Re-enable button
                $btn.prop('disabled', false);
                $btnText.text('Verify License');
                $btnIcon.removeClass('hidden');
                $btnSpinner.addClass('hidden');
            }
        });
    });
});
</script>
@endpush
@endsection
