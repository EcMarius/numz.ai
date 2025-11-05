@extends('installer.layout')

@section('title', 'Database Configuration')

@section('content')
<div class="fade-in">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-database text-blue-500 mr-2"></i>Database Configuration
    </h2>

    <p class="text-gray-600 mb-6">
        Configure your database connection. Make sure the database exists before proceeding.
    </p>

    <form id="databaseForm" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Database Host <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       name="db_host"
                       id="db_host"
                       value="localhost"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Database Port <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       name="db_port"
                       id="db_port"
                       value="3306"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all"
                       required>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Database Name <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   name="db_name"
                   id="db_name"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all"
                   placeholder="numzai"
                   required>
            <p class="text-xs text-gray-500 mt-1">Database must already exist</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Database Username <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   name="db_username"
                   id="db_username"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all"
                   required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Database Password
            </label>
            <input type="password"
                   name="db_password"
                   id="db_password"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all">
            <p class="text-xs text-gray-500 mt-1">Leave blank if no password is set</p>
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
                <p class="text-sm text-green-700">Database connection successful!</p>
            </div>
        </div>

        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
            <div class="flex">
                <i class="fas fa-exclamation-triangle text-yellow-400 mt-1 mr-3"></i>
                <div>
                    <p class="text-sm text-yellow-700">
                        <strong>Important:</strong> Ensure your database is empty or ready for NUMZ.AI installation.
                    </p>
                </div>
            </div>
        </div>
    </form>

    <div class="flex justify-between mt-8">
        <a href="{{ route('installer.license') }}"
           class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back
        </a>

        <button type="submit"
                form="databaseForm"
                id="testBtn"
                class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:shadow-lg transition-all">
            <span id="testBtnText">Test & Continue</span>
            <i class="fas fa-arrow-right ml-2" id="testBtnIcon"></i>
            <i class="fas fa-spinner spinner ml-2 hidden" id="testBtnSpinner"></i>
        </button>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#databaseForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#testBtn');
        const $btnText = $('#testBtnText');
        const $btnIcon = $('#testBtnIcon');
        const $btnSpinner = $('#testBtnSpinner');
        const $errorMsg = $('#error-message');
        const $successMsg = $('#success-message');

        // Disable button and show spinner
        $btn.prop('disabled', true);
        $btnText.text('Testing Connection...');
        $btnIcon.addClass('hidden');
        $btnSpinner.removeClass('hidden');
        $errorMsg.addClass('hidden');
        $successMsg.addClass('hidden');

        $.ajax({
            url: '{{ route('installer.database.test') }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $successMsg.removeClass('hidden');
                    setTimeout(function() {
                        window.location.href = '{{ route('installer.admin') }}';
                    }, 1000);
                }
            },
            error: function(xhr) {
                let errorText = 'Database connection failed. Please check your credentials.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorText = xhr.responseJSON.message;
                }

                $('#error-text').text(errorText);
                $errorMsg.removeClass('hidden');

                // Re-enable button
                $btn.prop('disabled', false);
                $btnText.text('Test & Continue');
                $btnIcon.removeClass('hidden');
                $btnSpinner.addClass('hidden');
            }
        });
    });
});
</script>
@endpush
@endsection
