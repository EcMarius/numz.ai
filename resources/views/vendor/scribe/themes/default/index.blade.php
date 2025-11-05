@php
    use Knuckles\Scribe\Tools\WritingUtils as u;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{!! $metadata['title'] !!}</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{!! $assetPathPrefix !!}css/theme-default.style.css" media="screen">
    <link rel="stylesheet" href="{!! $assetPathPrefix !!}css/theme-default.print.css" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

@if(isset($metadata['example_languages']))
    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
        @foreach($metadata['example_languages'] as $lang)
            body .content .{{ $lang }}-example code { display: none; }
        @endforeach
    </style>
@endif

@if($tryItOut['enabled'] ?? true)
    <script>
        var tryItOutBaseUrl = "{!! $tryItOut['base_url'] ?? $baseUrl !!}";
        var useCsrf = Boolean({!! $tryItOut['use_csrf'] ?? null !!});
        var csrfUrl = "{!! $tryItOut['csrf_url'] ?? null !!}";
    </script>
    <script src="{{ u::getVersionedAsset($assetPathPrefix.'js/tryitout.js') }}"></script>
@endif

    <script src="{{ u::getVersionedAsset($assetPathPrefix.'js/theme-default.js') }}"></script>

    <!-- Custom API Key Selector -->
    <style>
        .api-key-selector-container {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }
        .api-key-selector-container h4 {
            margin: 0 0 12px 0;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
        .api-key-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .api-key-selector-container select,
        .api-key-selector-container input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 13px;
            font-family: monospace;
        }
        .api-key-selector-container button {
            padding: 8px 16px;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
        }
        .api-key-selector-container button:hover {
            background: #333;
        }
        .api-key-selector-container .or-divider {
            text-align: center;
            margin: 10px 0;
            color: #6c757d;
            font-size: 12px;
        }
        .api-key-selector-container p {
            margin: 8px 0 0 0;
            font-size: 12px;
            color: #6c757d;
        }
        .api-key-selector-container a {
            color: #000;
            text-decoration: underline;
        }
        .api-key-message {
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 4px;
            font-size: 13px;
            display: none;
            animation: fadeIn 0.3s;
        }
        .api-key-message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .api-key-message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <script>
        // Custom API Key Selector
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for page to fully render
            setTimeout(function() {
                // Fetch user's API keys if authenticated
                fetch('/api/user/api-keys')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            addApiKeySelector(data.data);
                        }
                    })
                    .catch(err => {
                        console.log('Not authenticated or error fetching API keys');
                    });
            }, 500);

            function addApiKeySelector(apiKeys) {
                // Find the auth info section
                const authSection = document.querySelector('#authenticating-requests');
                if (!authSection) return;

                // Create selector container
                const selectorHTML = `
                    <div class="api-key-selector-container">
                        <h4>🔑 Select or Enter API Key</h4>

                        <div class="api-key-input-group">
                            <select id="api-key-select" style="flex: 1;">
                                <option value="">-- Select an existing API key --</option>
                                ${apiKeys.map(key => `<option value="${key.key}">${key.name}</option>`).join('')}
                            </select>
                            <button onclick="applySelectedApiKey()">Use Selected Key</button>
                        </div>

                        <div class="or-divider">OR</div>

                        <div class="api-key-input-group">
                            <input type="text" id="api-key-manual" placeholder="Paste your API key here..." />
                            <button onclick="applyManualApiKey()">Use This Key</button>
                        </div>

                        <div id="api-key-message" class="api-key-message"></div>

                        <p>Don't have an API key? <a href="/settings/api" target="_blank">Create one here</a></p>
                    </div>
                `;

                // Insert after the auth section
                const container = document.createElement('div');
                container.innerHTML = selectorHTML;
                authSection.parentNode.insertBefore(container.firstElementChild, authSection.nextSibling);
            }
        });

        function showMessage(message, type = 'success') {
            const messageDiv = document.getElementById('api-key-message');
            messageDiv.textContent = message;
            messageDiv.className = 'api-key-message ' + type;
            messageDiv.style.display = 'block';

            // Auto-hide after 5 seconds
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }

        function applySelectedApiKey() {
            const select = document.getElementById('api-key-select');
            const apiKey = select.value;

            if (!apiKey) {
                showMessage('Please select an API key from the dropdown', 'error');
                return;
            }

            const count = applyApiKeyToAll(apiKey);
            showMessage(`✓ API key applied successfully! Ready to use on all ${count} endpoints.`, 'success');
        }

        function applyManualApiKey() {
            const input = document.getElementById('api-key-manual');
            const apiKey = input.value.trim();

            if (!apiKey) {
                showMessage('Please enter an API key', 'error');
                return;
            }

            const count = applyApiKeyToAll(apiKey);
            showMessage(`✓ API key applied successfully! Ready to use on all ${count} endpoints.`, 'success');
        }

        function applyApiKeyToAll(apiKey) {
            // Store in global window for Scribe to use
            window.lastAuthValue = apiKey;

            // Find all Scribe auth input fields (Scribe uses .auth-value class)
            const authInputs = document.querySelectorAll('.auth-value');

            authInputs.forEach(input => {
                input.value = apiKey;
                // Trigger multiple events to ensure Scribe picks it up
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.dispatchEvent(new Event('keyup', { bubbles: true }));
            });

            console.log(`Applied API key to ${authInputs.length} input fields`);
            return authInputs.length;
        }
    </script>

</head>

<body data-languages="{{ json_encode($metadata['example_languages'] ?? []) }}">

@include("scribe::themes.default.sidebar")

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        {!! $intro !!}

        {!! $auth !!}

        @include("scribe::themes.default.groups")

        {!! $append !!}
    </div>
    <div class="dark-box">
        @if(isset($metadata['example_languages']))
            <div class="lang-selector">
                @foreach($metadata['example_languages'] as $name => $lang)
                    @php if (is_numeric($name)) $name = $lang; @endphp
                    <button type="button" class="lang-button" data-language-name="{{$lang}}">{{$name}}</button>
                @endforeach
            </div>
        @endif
    </div>
</div>
</body>
</html>
