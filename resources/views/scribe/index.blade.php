<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>EvenLeads API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
                    body .content .php-example code { display: none; }
                    body .content .python-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost:8000";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.3.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.3.0.js") }}"></script>

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

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;,&quot;php&quot;,&quot;python&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                                            <button type="button" class="lang-button" data-language-name="php">php</button>
                                            <button type="button" class="lang-button" data-language-name="python">python</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-account" class="tocify-header">
                <li class="tocify-item level-1" data-unique="account">
                    <a href="#account">Account</a>
                </li>
                                    <ul id="tocify-subheader-account" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="account-GETapi-v1-account-usage">
                                <a href="#account-GETapi-v1-account-usage">Get account usage and limits</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-authentication" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authentication">
                    <a href="#authentication">Authentication</a>
                </li>
                                    <ul id="tocify-subheader-authentication" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="authentication-GETapi-v1-auth-validate">
                                <a href="#authentication-GETapi-v1-auth-validate">Validate API Key</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-campaigns" class="tocify-header">
                <li class="tocify-item level-1" data-unique="campaigns">
                    <a href="#campaigns">Campaigns</a>
                </li>
                                    <ul id="tocify-subheader-campaigns" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="campaigns-GETapi-v1-campaigns">
                                <a href="#campaigns-GETapi-v1-campaigns">List all campaigns</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="campaigns-GETapi-v1-campaigns--id-">
                                <a href="#campaigns-GETapi-v1-campaigns--id-">Get a single campaign</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="campaigns-POSTapi-v1-campaigns">
                                <a href="#campaigns-POSTapi-v1-campaigns">Create a new campaign</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="campaigns-PUTapi-v1-campaigns--id-">
                                <a href="#campaigns-PUTapi-v1-campaigns--id-">Update a campaign</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="campaigns-DELETEapi-v1-campaigns--id-">
                                <a href="#campaigns-DELETEapi-v1-campaigns--id-">Delete a campaign</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-leads" class="tocify-header">
                <li class="tocify-item level-1" data-unique="leads">
                    <a href="#leads">Leads</a>
                </li>
                                    <ul id="tocify-subheader-leads" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="leads-GETapi-v1-leads">
                                <a href="#leads-GETapi-v1-leads">List all leads</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="leads-GETapi-v1-leads--id-">
                                <a href="#leads-GETapi-v1-leads--id-">Get a single lead</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="leads-DELETEapi-v1-leads--id-">
                                <a href="#leads-DELETEapi-v1-leads--id-">Delete a lead</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="leads-POSTapi-v1-leads-bulk-delete">
                                <a href="#leads-POSTapi-v1-leads-bulk-delete">Bulk delete leads</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="leads-PATCHapi-v1-leads--id--status">
                                <a href="#leads-PATCHapi-v1-leads--id--status">Update lead status</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-sync" class="tocify-header">
                <li class="tocify-item level-1" data-unique="sync">
                    <a href="#sync">Sync</a>
                </li>
                                    <ul id="tocify-subheader-sync" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="sync-POSTapi-v1-sync-campaign--id-">
                                <a href="#sync-POSTapi-v1-sync-campaign--id-">Trigger manual sync for a campaign</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sync-POSTapi-v1-sync-all">
                                <a href="#sync-POSTapi-v1-sync-all">Sync all active campaigns</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="sync-GETapi-v1-sync-history--id-">
                                <a href="#sync-GETapi-v1-sync-history--id-">Get sync history for a campaign</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: October 12, 2025</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<p>Comprehensive API for managing campaigns, leads, and sync operations in EvenLeads.</p>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>
<pre><code>Welcome to the EvenLeads API documentation. This API allows you to programmatically manage your campaigns, leads, and sync operations.

## Getting Started

To use this API, you'll need an API key. You can create one from your [API Settings](/settings/api) page.

## Authentication

All API requests must include your API key in the **X-API-Key** header:

```
X-API-Key: your_api_key_here
```

Alternatively, you can pass it as a query parameter:

```
?api_key=your_api_key_here
```

## Base URL

All API requests should be made to:
```
http://localhost:8000/api/v1
```

Replace `localhost:8000` with your actual domain in production.

## Rate Limiting

- Manual sync operations are limited to once every 15 minutes per campaign
- Standard rate limiting of 60 requests per minute applies to all endpoints

## Code Examples

Throughout this documentation, you'll find code examples in multiple programming languages (Bash, JavaScript, PHP, Python). Use the language selector at the top to choose your preferred language.</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>To authenticate requests, include a <strong><code>X-API-Key</code></strong> header with the value <strong><code>"your_api_key_here"</code></strong>.</p>
<p>All authenticated endpoints are marked with a <code>requires authentication</code> badge in the documentation below.</p>
<p>You can create API keys by visiting your <a href="/settings/api">API Settings</a> page in your dashboard. Include your API key in the <code>X-API-Key</code> header or as an <code>api_key</code> query parameter.</p>

        <h1 id="account">Account</h1>

    <p>API endpoints for account information and usage</p>

                                <h2 id="account-GETapi-v1-account-usage">Get account usage and limits</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Retrieve current usage statistics and plan limits for EvenLeads features.</p>

<span id="example-requests-GETapi-v1-account-usage">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/account/usage" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/account/usage"
);

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/account/usage';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/account/usage'
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-account-usage">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;user&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;John Doe&quot;,
            &quot;email&quot;: &quot;john@example.com&quot;
        },
        &quot;plan&quot;: {
            &quot;name&quot;: &quot;Professional&quot;,
            &quot;interval&quot;: &quot;monthly&quot;
        },
        &quot;limits&quot;: {
            &quot;campaigns&quot;: {
                &quot;current&quot;: 3,
                &quot;max&quot;: 5,
                &quot;percentage&quot;: 60
            },
            &quot;leads_storage&quot;: {
                &quot;current&quot;: 1250,
                &quot;max&quot;: 5000,
                &quot;percentage&quot;: 25
            },
            &quot;leads_per_sync&quot;: {
                &quot;max&quot;: 100
            },
            &quot;ai_replies_per_month&quot;: {
                &quot;current&quot;: 45,
                &quot;max&quot;: 500,
                &quot;percentage&quot;: 9,
                &quot;resets_at&quot;: &quot;2025-02-01T00:00:00.000000Z&quot;
            },
            &quot;manual_syncs_per_month&quot;: {
                &quot;current&quot;: 12,
                &quot;max&quot;: 100,
                &quot;percentage&quot;: 12,
                &quot;resets_at&quot;: &quot;2025-02-01T00:00:00.000000Z&quot;
            },
            &quot;keywords_per_campaign&quot;: {
                &quot;max&quot;: 20
            },
            &quot;automated_sync_interval_minutes&quot;: {
                &quot;value&quot;: 60
            }
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-account-usage" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-account-usage"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-account-usage"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-account-usage" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-account-usage">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-account-usage" data-method="GET"
      data-path="api/v1/account/usage"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-account-usage', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-account-usage"
                    onclick="tryItOut('GETapi-v1-account-usage');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-account-usage"
                    onclick="cancelTryOut('GETapi-v1-account-usage');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-account-usage"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/account/usage</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="GETapi-v1-account-usage"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-account-usage"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-account-usage"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="authentication">Authentication</h1>

    <p>API endpoints for authentication</p>

                                <h2 id="authentication-GETapi-v1-auth-validate">Validate API Key</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Validates if an API key is valid and returns associated user information.</p>

<span id="example-requests-GETapi-v1-auth-validate">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/auth/validate" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/auth/validate"
);

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/auth/validate';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/auth/validate'
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-auth-validate">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;API key is valid&quot;,
    &quot;data&quot;: {
        &quot;user&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;John Doe&quot;,
            &quot;email&quot;: &quot;john@example.com&quot;
        },
        &quot;api_key&quot;: {
            &quot;name&quot;: &quot;My API Key&quot;,
            &quot;created_at&quot;: &quot;2025-01-01T00:00:00.000000Z&quot;,
            &quot;last_used_at&quot;: &quot;2025-01-15T12:30:00.000000Z&quot;
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Invalid API key.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-auth-validate" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-auth-validate"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-auth-validate"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-auth-validate" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-auth-validate">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-auth-validate" data-method="GET"
      data-path="api/v1/auth/validate"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-auth-validate', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-auth-validate"
                    onclick="tryItOut('GETapi-v1-auth-validate');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-auth-validate"
                    onclick="cancelTryOut('GETapi-v1-auth-validate');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-auth-validate"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/auth/validate</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="GETapi-v1-auth-validate"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-auth-validate"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-auth-validate"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="campaigns">Campaigns</h1>

    <p>API endpoints for managing campaigns</p>

                                <h2 id="campaigns-GETapi-v1-campaigns">List all campaigns</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get a paginated list of all campaigns for the authenticated user.</p>

<span id="example-requests-GETapi-v1-campaigns">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/campaigns?page=1&amp;per_page=15&amp;status=active&amp;platform=reddit" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/campaigns"
);

const params = {
    "page": "1",
    "per_page": "15",
    "status": "active",
    "platform": "reddit",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/campaigns';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'page' =&gt; '1',
            'per_page' =&gt; '15',
            'status' =&gt; 'active',
            'platform' =&gt; 'reddit',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/campaigns'
params = {
  'page': '1',
  'per_page': '15',
  'status': 'active',
  'platform': 'reddit',
}
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-campaigns">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;campaigns&quot;: [
            {
                &quot;id&quot;: 1,
                &quot;name&quot;: &quot;Web Development Services&quot;,
                &quot;offering&quot;: &quot;Custom web development&quot;,
                &quot;website_url&quot;: &quot;https://example.com&quot;,
                &quot;platforms&quot;: [
                    &quot;reddit&quot;,
                    &quot;facebook&quot;
                ],
                &quot;status&quot;: &quot;active&quot;,
                &quot;keywords&quot;: [
                    &quot;web development&quot;,
                    &quot;website&quot;
                ],
                &quot;strong_matches_count&quot;: 15,
                &quot;partial_matches_count&quot;: 8,
                &quot;new_leads_count&quot;: 5,
                &quot;last_sync_at&quot;: &quot;2025-01-15T10:30:00.000000Z&quot;,
                &quot;next_sync_at&quot;: &quot;2025-01-15T11:30:00.000000Z&quot;,
                &quot;created_at&quot;: &quot;2025-01-01T00:00:00.000000Z&quot;,
                &quot;updated_at&quot;: &quot;2025-01-15T10:30:00.000000Z&quot;
            }
        ],
        &quot;pagination&quot;: {
            &quot;current_page&quot;: 1,
            &quot;per_page&quot;: 15,
            &quot;total&quot;: 25,
            &quot;last_page&quot;: 2
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-campaigns" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-campaigns"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-campaigns"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-campaigns" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-campaigns">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-campaigns" data-method="GET"
      data-path="api/v1/campaigns"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-campaigns', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-campaigns"
                    onclick="tryItOut('GETapi-v1-campaigns');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-campaigns"
                    onclick="cancelTryOut('GETapi-v1-campaigns');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-campaigns"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/campaigns</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="GETapi-v1-campaigns"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-campaigns"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-campaigns"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-campaigns"
               value="1"
               data-component="query">
    <br>
<p>Page number for pagination. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-campaigns"
               value="15"
               data-component="query">
    <br>
<p>Number of items per page (max 100). Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-v1-campaigns"
               value="active"
               data-component="query">
    <br>
<p>Filter by status (active, paused, completed). Example: <code>active</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>platform</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="platform"                data-endpoint="GETapi-v1-campaigns"
               value="reddit"
               data-component="query">
    <br>
<p>Filter by platform (reddit, facebook, etc). Example: <code>reddit</code></p>
            </div>
                </form>

                    <h2 id="campaigns-GETapi-v1-campaigns--id-">Get a single campaign</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Retrieve detailed information about a specific campaign including all settings, matches, and sync history.</p>

<span id="example-requests-GETapi-v1-campaigns--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/campaigns/1" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/campaigns/1"
);

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/campaigns/1';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/campaigns/1'
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-campaigns--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Web Development Services&quot;,
        &quot;offering&quot;: &quot;Custom web development&quot;,
        &quot;website_url&quot;: &quot;https://example.com&quot;,
        &quot;portfolio_path&quot;: &quot;/portfolio&quot;,
        &quot;platforms&quot;: [
            &quot;reddit&quot;,
            &quot;facebook&quot;
        ],
        &quot;facebook_groups&quot;: [
            &quot;group1&quot;,
            &quot;group2&quot;
        ],
        &quot;keywords&quot;: [
            &quot;web development&quot;,
            &quot;website&quot;
        ],
        &quot;include_keywords&quot;: [
            &quot;need&quot;,
            &quot;looking for&quot;
        ],
        &quot;ai_settings&quot;: {
            &quot;tone&quot;: &quot;professional&quot;,
            &quot;length&quot;: &quot;medium&quot;
        },
        &quot;include_call_to_action&quot;: true,
        &quot;status&quot;: &quot;active&quot;,
        &quot;strong_matches_count&quot;: 15,
        &quot;partial_matches_count&quot;: 8,
        &quot;new_leads_count&quot;: 5,
        &quot;last_sync_at&quot;: &quot;2025-01-15T10:30:00.000000Z&quot;,
        &quot;next_sync_at&quot;: &quot;2025-01-15T11:30:00.000000Z&quot;,
        &quot;created_at&quot;: &quot;2025-01-01T00:00:00.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2025-01-15T10:30:00.000000Z&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Campaign not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-campaigns--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-campaigns--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-campaigns--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-campaigns--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-campaigns--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-campaigns--id-" data-method="GET"
      data-path="api/v1/campaigns/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-campaigns--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-campaigns--id-"
                    onclick="tryItOut('GETapi-v1-campaigns--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-campaigns--id-"
                    onclick="cancelTryOut('GETapi-v1-campaigns--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-campaigns--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/campaigns/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="GETapi-v1-campaigns--id-"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-campaigns--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-campaigns--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1-campaigns--id-"
               value="1"
               data-component="url">
    <br>
<p>The campaign ID. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="campaigns-POSTapi-v1-campaigns">Create a new campaign</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Create a new campaign. You can only create campaigns for social platforms your account is connected with.</p>

<span id="example-requests-POSTapi-v1-campaigns">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/campaigns" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"Web Development Services\",
    \"offering\": \"Custom web development and design services\",
    \"website_url\": \"https:\\/\\/example.com\",
    \"portfolio_path\": \"\\/portfolio\",
    \"platforms\": [
        \"reddit\",
        \"facebook\"
    ],
    \"facebook_groups\": [
        \"group1\",
        \"group2\"
    ],
    \"keywords\": [
        \"web development\",
        \"website\"
    ],
    \"include_keywords\": [
        \"need\",
        \"looking for\"
    ],
    \"ai_settings\": {
        \"tone\": \"professional\",
        \"length\": \"medium\"
    },
    \"include_call_to_action\": true,
    \"status\": \"active\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/campaigns"
);

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "Web Development Services",
    "offering": "Custom web development and design services",
    "website_url": "https:\/\/example.com",
    "portfolio_path": "\/portfolio",
    "platforms": [
        "reddit",
        "facebook"
    ],
    "facebook_groups": [
        "group1",
        "group2"
    ],
    "keywords": [
        "web development",
        "website"
    ],
    "include_keywords": [
        "need",
        "looking for"
    ],
    "ai_settings": {
        "tone": "professional",
        "length": "medium"
    },
    "include_call_to_action": true,
    "status": "active"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/campaigns';
$response = $client-&gt;post(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'json' =&gt; [
            'name' =&gt; 'Web Development Services',
            'offering' =&gt; 'Custom web development and design services',
            'website_url' =&gt; 'https://example.com',
            'portfolio_path' =&gt; '/portfolio',
            'platforms' =&gt; [
                'reddit',
                'facebook',
            ],
            'facebook_groups' =&gt; [
                'group1',
                'group2',
            ],
            'keywords' =&gt; [
                'web development',
                'website',
            ],
            'include_keywords' =&gt; [
                'need',
                'looking for',
            ],
            'ai_settings' =&gt; [
                'tone' =&gt; 'professional',
                'length' =&gt; 'medium',
            ],
            'include_call_to_action' =&gt; true,
            'status' =&gt; 'active',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/campaigns'
payload = {
    "name": "Web Development Services",
    "offering": "Custom web development and design services",
    "website_url": "https:\/\/example.com",
    "portfolio_path": "\/portfolio",
    "platforms": [
        "reddit",
        "facebook"
    ],
    "facebook_groups": [
        "group1",
        "group2"
    ],
    "keywords": [
        "web development",
        "website"
    ],
    "include_keywords": [
        "need",
        "looking for"
    ],
    "ai_settings": {
        "tone": "professional",
        "length": "medium"
    },
    "include_call_to_action": true,
    "status": "active"
}
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('POST', url, headers=headers, json=payload)
response.json()</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-campaigns">
            <blockquote>
            <p>Example response (201):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Campaign created successfully&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Web Development Services&quot;,
        &quot;status&quot;: &quot;active&quot;,
        &quot;created_at&quot;: &quot;2025-01-15T12:00:00.000000Z&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Validation failed&quot;,
    &quot;errors&quot;: {
        &quot;platforms&quot;: [
            &quot;You are not connected to facebook. Please connect your account first.&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-campaigns" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-campaigns"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-campaigns"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-campaigns" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-campaigns">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-campaigns" data-method="POST"
      data-path="api/v1/campaigns"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-campaigns', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-campaigns"
                    onclick="tryItOut('POSTapi-v1-campaigns');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-campaigns"
                    onclick="cancelTryOut('POSTapi-v1-campaigns');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-campaigns"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/campaigns</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="POSTapi-v1-campaigns"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-campaigns"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-campaigns"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-campaigns"
               value="Web Development Services"
               data-component="body">
    <br>
<p>The campaign name. Example: <code>Web Development Services</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>offering</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="offering"                data-endpoint="POSTapi-v1-campaigns"
               value="Custom web development and design services"
               data-component="body">
    <br>
<p>Description of what you're offering. Example: <code>Custom web development and design services</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>website_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="website_url"                data-endpoint="POSTapi-v1-campaigns"
               value="https://example.com"
               data-component="body">
    <br>
<p>The website URL. Example: <code>https://example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>portfolio_path</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="portfolio_path"                data-endpoint="POSTapi-v1-campaigns"
               value="/portfolio"
               data-component="body">
    <br>
<p>Portfolio path. Example: <code>/portfolio</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>platforms</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="platforms[0]"                data-endpoint="POSTapi-v1-campaigns"
               data-component="body">
        <input type="text" style="display: none"
               name="platforms[1]"                data-endpoint="POSTapi-v1-campaigns"
               data-component="body">
    <br>
<p>Array of platforms (must be connected).</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>facebook_groups</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="facebook_groups[0]"                data-endpoint="POSTapi-v1-campaigns"
               data-component="body">
        <input type="text" style="display: none"
               name="facebook_groups[1]"                data-endpoint="POSTapi-v1-campaigns"
               data-component="body">
    <br>
<p>Facebook groups to monitor (required if facebook in platforms).</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>keywords</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="keywords[0]"                data-endpoint="POSTapi-v1-campaigns"
               data-component="body">
        <input type="text" style="display: none"
               name="keywords[1]"                data-endpoint="POSTapi-v1-campaigns"
               data-component="body">
    <br>
<p>Keywords to search for.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>include_keywords</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="include_keywords[0]"                data-endpoint="POSTapi-v1-campaigns"
               data-component="body">
        <input type="text" style="display: none"
               name="include_keywords[1]"                data-endpoint="POSTapi-v1-campaigns"
               data-component="body">
    <br>
<p>Additional filter keywords.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>ai_settings</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="ai_settings"                data-endpoint="POSTapi-v1-campaigns"
               value=""
               data-component="body">
    <br>
<p>AI generation settings.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>include_call_to_action</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
                <label data-endpoint="POSTapi-v1-campaigns" style="display: none">
            <input type="radio" name="include_call_to_action"
                   value="true"
                   data-endpoint="POSTapi-v1-campaigns"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-campaigns" style="display: none">
            <input type="radio" name="include_call_to_action"
                   value="false"
                   data-endpoint="POSTapi-v1-campaigns"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Include call to action. Example: <code>true</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="POSTapi-v1-campaigns"
               value="active"
               data-component="body">
    <br>
<p>Campaign status (active, paused). Example: <code>active</code></p>
        </div>
        </form>

                    <h2 id="campaigns-PUTapi-v1-campaigns--id-">Update a campaign</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Update an existing campaign. Same validation rules as creation apply.</p>

<span id="example-requests-PUTapi-v1-campaigns--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/v1/campaigns/1" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"Updated Campaign Name\",
    \"offering\": \"Updated offering description\",
    \"website_url\": \"https:\\/\\/example.com\",
    \"portfolio_path\": \"\\/portfolio\",
    \"platforms\": [
        \"reddit\"
    ],
    \"facebook_groups\": [
        \"group1\"
    ],
    \"keywords\": [
        \"web dev\",
        \"website\"
    ],
    \"include_keywords\": [
        \"need\"
    ],
    \"ai_settings\": {
        \"tone\": \"casual\"
    },
    \"include_call_to_action\": false,
    \"status\": \"paused\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/campaigns/1"
);

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "Updated Campaign Name",
    "offering": "Updated offering description",
    "website_url": "https:\/\/example.com",
    "portfolio_path": "\/portfolio",
    "platforms": [
        "reddit"
    ],
    "facebook_groups": [
        "group1"
    ],
    "keywords": [
        "web dev",
        "website"
    ],
    "include_keywords": [
        "need"
    ],
    "ai_settings": {
        "tone": "casual"
    },
    "include_call_to_action": false,
    "status": "paused"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/campaigns/1';
$response = $client-&gt;put(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'json' =&gt; [
            'name' =&gt; 'Updated Campaign Name',
            'offering' =&gt; 'Updated offering description',
            'website_url' =&gt; 'https://example.com',
            'portfolio_path' =&gt; '/portfolio',
            'platforms' =&gt; [
                'reddit',
            ],
            'facebook_groups' =&gt; [
                'group1',
            ],
            'keywords' =&gt; [
                'web dev',
                'website',
            ],
            'include_keywords' =&gt; [
                'need',
            ],
            'ai_settings' =&gt; [
                'tone' =&gt; 'casual',
            ],
            'include_call_to_action' =&gt; false,
            'status' =&gt; 'paused',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/campaigns/1'
payload = {
    "name": "Updated Campaign Name",
    "offering": "Updated offering description",
    "website_url": "https:\/\/example.com",
    "portfolio_path": "\/portfolio",
    "platforms": [
        "reddit"
    ],
    "facebook_groups": [
        "group1"
    ],
    "keywords": [
        "web dev",
        "website"
    ],
    "include_keywords": [
        "need"
    ],
    "ai_settings": {
        "tone": "casual"
    },
    "include_call_to_action": false,
    "status": "paused"
}
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('PUT', url, headers=headers, json=payload)
response.json()</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-campaigns--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Campaign updated successfully&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Updated Campaign Name&quot;,
        &quot;updated_at&quot;: &quot;2025-01-15T12:30:00.000000Z&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Campaign not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-PUTapi-v1-campaigns--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-campaigns--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-campaigns--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-campaigns--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-campaigns--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-campaigns--id-" data-method="PUT"
      data-path="api/v1/campaigns/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-campaigns--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-campaigns--id-"
                    onclick="tryItOut('PUTapi-v1-campaigns--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-campaigns--id-"
                    onclick="cancelTryOut('PUTapi-v1-campaigns--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-campaigns--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/campaigns/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="PUTapi-v1-campaigns--id-"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-campaigns--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-campaigns--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PUTapi-v1-campaigns--id-"
               value="1"
               data-component="url">
    <br>
<p>The campaign ID. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-v1-campaigns--id-"
               value="Updated Campaign Name"
               data-component="body">
    <br>
<p>The campaign name. Example: <code>Updated Campaign Name</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>offering</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="offering"                data-endpoint="PUTapi-v1-campaigns--id-"
               value="Updated offering description"
               data-component="body">
    <br>
<p>Description of what you're offering. Example: <code>Updated offering description</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>website_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="website_url"                data-endpoint="PUTapi-v1-campaigns--id-"
               value="https://example.com"
               data-component="body">
    <br>
<p>The website URL. Example: <code>https://example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>portfolio_path</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="portfolio_path"                data-endpoint="PUTapi-v1-campaigns--id-"
               value="/portfolio"
               data-component="body">
    <br>
<p>Portfolio path. Example: <code>/portfolio</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>platforms</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="platforms[0]"                data-endpoint="PUTapi-v1-campaigns--id-"
               data-component="body">
        <input type="text" style="display: none"
               name="platforms[1]"                data-endpoint="PUTapi-v1-campaigns--id-"
               data-component="body">
    <br>
<p>Array of platforms (must be connected).</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>facebook_groups</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="facebook_groups[0]"                data-endpoint="PUTapi-v1-campaigns--id-"
               data-component="body">
        <input type="text" style="display: none"
               name="facebook_groups[1]"                data-endpoint="PUTapi-v1-campaigns--id-"
               data-component="body">
    <br>
<p>Facebook groups to monitor.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>keywords</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="keywords[0]"                data-endpoint="PUTapi-v1-campaigns--id-"
               data-component="body">
        <input type="text" style="display: none"
               name="keywords[1]"                data-endpoint="PUTapi-v1-campaigns--id-"
               data-component="body">
    <br>
<p>Keywords to search for.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>include_keywords</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="include_keywords[0]"                data-endpoint="PUTapi-v1-campaigns--id-"
               data-component="body">
        <input type="text" style="display: none"
               name="include_keywords[1]"                data-endpoint="PUTapi-v1-campaigns--id-"
               data-component="body">
    <br>
<p>Additional filter keywords.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>ai_settings</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="ai_settings"                data-endpoint="PUTapi-v1-campaigns--id-"
               value=""
               data-component="body">
    <br>
<p>AI generation settings.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>include_call_to_action</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
                <label data-endpoint="PUTapi-v1-campaigns--id-" style="display: none">
            <input type="radio" name="include_call_to_action"
                   value="true"
                   data-endpoint="PUTapi-v1-campaigns--id-"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-campaigns--id-" style="display: none">
            <input type="radio" name="include_call_to_action"
                   value="false"
                   data-endpoint="PUTapi-v1-campaigns--id-"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Include call to action. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PUTapi-v1-campaigns--id-"
               value="paused"
               data-component="body">
    <br>
<p>Campaign status. Example: <code>paused</code></p>
        </div>
        </form>

                    <h2 id="campaigns-DELETEapi-v1-campaigns--id-">Delete a campaign</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Soft delete a campaign and all its associated data.</p>

<span id="example-requests-DELETEapi-v1-campaigns--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/campaigns/1" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/campaigns/1"
);

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/campaigns/1';
$response = $client-&gt;delete(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/campaigns/1'
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('DELETE', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-campaigns--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Campaign deleted successfully&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Campaign not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-DELETEapi-v1-campaigns--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-campaigns--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-campaigns--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-campaigns--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-campaigns--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-campaigns--id-" data-method="DELETE"
      data-path="api/v1/campaigns/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-campaigns--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-campaigns--id-"
                    onclick="tryItOut('DELETEapi-v1-campaigns--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-campaigns--id-"
                    onclick="cancelTryOut('DELETEapi-v1-campaigns--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-campaigns--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/campaigns/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="DELETEapi-v1-campaigns--id-"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-campaigns--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-campaigns--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-v1-campaigns--id-"
               value="1"
               data-component="url">
    <br>
<p>The campaign ID. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="leads">Leads</h1>

    <p>API endpoints for managing leads</p>

                                <h2 id="leads-GETapi-v1-leads">List all leads</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Get a paginated list of all leads with comprehensive filtering options.</p>

<span id="example-requests-GETapi-v1-leads">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/leads?page=1&amp;per_page=15&amp;campaign_id=1&amp;status=new&amp;match_type=strong&amp;platform=reddit&amp;min_confidence=7&amp;max_confidence=10&amp;search=website&amp;sort_by=created_at&amp;sort_order=desc" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/leads"
);

const params = {
    "page": "1",
    "per_page": "15",
    "campaign_id": "1",
    "status": "new",
    "match_type": "strong",
    "platform": "reddit",
    "min_confidence": "7",
    "max_confidence": "10",
    "search": "website",
    "sort_by": "created_at",
    "sort_order": "desc",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/leads';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'page' =&gt; '1',
            'per_page' =&gt; '15',
            'campaign_id' =&gt; '1',
            'status' =&gt; 'new',
            'match_type' =&gt; 'strong',
            'platform' =&gt; 'reddit',
            'min_confidence' =&gt; '7',
            'max_confidence' =&gt; '10',
            'search' =&gt; 'website',
            'sort_by' =&gt; 'created_at',
            'sort_order' =&gt; 'desc',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/leads'
params = {
  'page': '1',
  'per_page': '15',
  'campaign_id': '1',
  'status': 'new',
  'match_type': 'strong',
  'platform': 'reddit',
  'min_confidence': '7',
  'max_confidence': '10',
  'search': 'website',
  'sort_by': 'created_at',
  'sort_order': 'desc',
}
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-leads">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;leads&quot;: [
            {
                &quot;id&quot;: 1,
                &quot;campaign_id&quot;: 1,
                &quot;campaign_name&quot;: &quot;Web Development Services&quot;,
                &quot;platform&quot;: &quot;reddit&quot;,
                &quot;platform_id&quot;: &quot;abc123&quot;,
                &quot;title&quot;: &quot;Looking for web developer&quot;,
                &quot;description&quot;: &quot;Need someone to build a website&quot;,
                &quot;url&quot;: &quot;https://reddit.com/r/example/comments/abc123&quot;,
                &quot;author&quot;: &quot;john_doe&quot;,
                &quot;subreddit&quot;: &quot;webdev&quot;,
                &quot;facebook_group&quot;: null,
                &quot;comments_count&quot;: 5,
                &quot;confidence_score&quot;: 8,
                &quot;match_type&quot;: &quot;strong&quot;,
                &quot;status&quot;: &quot;new&quot;,
                &quot;matched_keywords&quot;: [
                    &quot;website&quot;,
                    &quot;web developer&quot;
                ],
                &quot;synced_at&quot;: &quot;2025-01-15T10:30:00.000000Z&quot;,
                &quot;contacted_at&quot;: null,
                &quot;created_at&quot;: &quot;2025-01-15T10:30:00.000000Z&quot;,
                &quot;updated_at&quot;: &quot;2025-01-15T10:30:00.000000Z&quot;
            }
        ],
        &quot;pagination&quot;: {
            &quot;current_page&quot;: 1,
            &quot;per_page&quot;: 15,
            &quot;total&quot;: 150,
            &quot;last_page&quot;: 10
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-leads" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-leads"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-leads"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-leads" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-leads">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-leads" data-method="GET"
      data-path="api/v1/leads"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-leads', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-leads"
                    onclick="tryItOut('GETapi-v1-leads');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-leads"
                    onclick="cancelTryOut('GETapi-v1-leads');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-leads"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/leads</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="GETapi-v1-leads"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-leads"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-leads"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-leads"
               value="1"
               data-component="query">
    <br>
<p>Page number for pagination. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-leads"
               value="15"
               data-component="query">
    <br>
<p>Number of items per page (max 100). Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>campaign_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="campaign_id"                data-endpoint="GETapi-v1-leads"
               value="1"
               data-component="query">
    <br>
<p>Filter by campaign ID. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-v1-leads"
               value="new"
               data-component="query">
    <br>
<p>Filter by status (new, contacted, closed). Example: <code>new</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>match_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="match_type"                data-endpoint="GETapi-v1-leads"
               value="strong"
               data-component="query">
    <br>
<p>Filter by match type (strong, partial). Example: <code>strong</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>platform</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="platform"                data-endpoint="GETapi-v1-leads"
               value="reddit"
               data-component="query">
    <br>
<p>Filter by platform (reddit, facebook, etc). Example: <code>reddit</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>min_confidence</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="min_confidence"                data-endpoint="GETapi-v1-leads"
               value="7"
               data-component="query">
    <br>
<p>Minimum confidence score (0-10). Example: <code>7</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>max_confidence</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="max_confidence"                data-endpoint="GETapi-v1-leads"
               value="10"
               data-component="query">
    <br>
<p>Maximum confidence score (0-10). Example: <code>10</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>search</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="search"                data-endpoint="GETapi-v1-leads"
               value="website"
               data-component="query">
    <br>
<p>Search in title and description. Example: <code>website</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>sort_by</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="sort_by"                data-endpoint="GETapi-v1-leads"
               value="created_at"
               data-component="query">
    <br>
<p>Sort by field (created_at, confidence_score, synced_at). Example: <code>created_at</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>sort_order</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="sort_order"                data-endpoint="GETapi-v1-leads"
               value="desc"
               data-component="query">
    <br>
<p>Sort order (asc, desc). Example: <code>desc</code></p>
            </div>
                </form>

                    <h2 id="leads-GETapi-v1-leads--id-">Get a single lead</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Retrieve detailed information about a specific lead including AI generations and campaign details.</p>

<span id="example-requests-GETapi-v1-leads--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/leads/1" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/leads/1"
);

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/leads/1';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/leads/1'
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-leads--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;campaign&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Web Development Services&quot;,
            &quot;offering&quot;: &quot;Custom web development&quot;
        },
        &quot;platform&quot;: &quot;reddit&quot;,
        &quot;platform_id&quot;: &quot;abc123&quot;,
        &quot;title&quot;: &quot;Looking for web developer&quot;,
        &quot;description&quot;: &quot;Need someone to build a website for my startup&quot;,
        &quot;url&quot;: &quot;https://reddit.com/r/example/comments/abc123&quot;,
        &quot;author&quot;: &quot;john_doe&quot;,
        &quot;subreddit&quot;: &quot;webdev&quot;,
        &quot;facebook_group&quot;: null,
        &quot;comments_count&quot;: 5,
        &quot;confidence_score&quot;: 8,
        &quot;match_type&quot;: &quot;strong&quot;,
        &quot;status&quot;: &quot;new&quot;,
        &quot;matched_keywords&quot;: [
            &quot;website&quot;,
            &quot;web developer&quot;
        ],
        &quot;ai_generations&quot;: [
            {
                &quot;id&quot;: 1,
                &quot;generated_message&quot;: &quot;Hi! I&#039;d love to help with your project...&quot;,
                &quot;created_at&quot;: &quot;2025-01-15T10:35:00.000000Z&quot;
            }
        ],
        &quot;synced_at&quot;: &quot;2025-01-15T10:30:00.000000Z&quot;,
        &quot;contacted_at&quot;: null,
        &quot;created_at&quot;: &quot;2025-01-15T10:30:00.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2025-01-15T10:30:00.000000Z&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Lead not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-leads--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-leads--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-leads--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-leads--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-leads--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-leads--id-" data-method="GET"
      data-path="api/v1/leads/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-leads--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-leads--id-"
                    onclick="tryItOut('GETapi-v1-leads--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-leads--id-"
                    onclick="cancelTryOut('GETapi-v1-leads--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-leads--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/leads/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="GETapi-v1-leads--id-"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-leads--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-leads--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1-leads--id-"
               value="1"
               data-component="url">
    <br>
<p>The lead ID. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="leads-DELETEapi-v1-leads--id-">Delete a lead</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Permanently delete a lead from your account.</p>

<span id="example-requests-DELETEapi-v1-leads--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/leads/1" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/leads/1"
);

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/leads/1';
$response = $client-&gt;delete(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/leads/1'
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('DELETE', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-leads--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Lead deleted successfully&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Lead not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-DELETEapi-v1-leads--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-leads--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-leads--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-leads--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-leads--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-leads--id-" data-method="DELETE"
      data-path="api/v1/leads/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-leads--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-leads--id-"
                    onclick="tryItOut('DELETEapi-v1-leads--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-leads--id-"
                    onclick="cancelTryOut('DELETEapi-v1-leads--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-leads--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/leads/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="DELETEapi-v1-leads--id-"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-leads--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-leads--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-v1-leads--id-"
               value="1"
               data-component="url">
    <br>
<p>The lead ID. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="leads-POSTapi-v1-leads-bulk-delete">Bulk delete leads</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Delete multiple leads at once by providing an array of lead IDs.</p>

<span id="example-requests-POSTapi-v1-leads-bulk-delete">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/leads/bulk-delete" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"lead_ids\": [
        1,
        2,
        3
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/leads/bulk-delete"
);

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "lead_ids": [
        1,
        2,
        3
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/leads/bulk-delete';
$response = $client-&gt;post(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'json' =&gt; [
            'lead_ids' =&gt; [
                1,
                2,
                3,
            ],
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/leads/bulk-delete'
payload = {
    "lead_ids": [
        1,
        2,
        3
    ]
}
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('POST', url, headers=headers, json=payload)
response.json()</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-leads-bulk-delete">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Successfully deleted 3 leads&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (422):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Validation failed&quot;,
    &quot;errors&quot;: {
        &quot;lead_ids&quot;: [
            &quot;The lead ids field is required.&quot;
        ]
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-leads-bulk-delete" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-leads-bulk-delete"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-leads-bulk-delete"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-leads-bulk-delete" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-leads-bulk-delete">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-leads-bulk-delete" data-method="POST"
      data-path="api/v1/leads/bulk-delete"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-leads-bulk-delete', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-leads-bulk-delete"
                    onclick="tryItOut('POSTapi-v1-leads-bulk-delete');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-leads-bulk-delete"
                    onclick="cancelTryOut('POSTapi-v1-leads-bulk-delete');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-leads-bulk-delete"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/leads/bulk-delete</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="POSTapi-v1-leads-bulk-delete"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-leads-bulk-delete"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-leads-bulk-delete"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>lead_ids</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lead_ids[0]"                data-endpoint="POSTapi-v1-leads-bulk-delete"
               data-component="body">
        <input type="text" style="display: none"
               name="lead_ids[1]"                data-endpoint="POSTapi-v1-leads-bulk-delete"
               data-component="body">
    <br>
<p>Array of lead IDs to delete.</p>
        </div>
        </form>

                    <h2 id="leads-PATCHapi-v1-leads--id--status">Update lead status</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Update the status of a lead (new, contacted, closed).</p>

<span id="example-requests-PATCHapi-v1-leads--id--status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://localhost:8000/api/v1/leads/1/status" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"status\": \"contacted\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/leads/1/status"
);

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "status": "contacted"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/leads/1/status';
$response = $client-&gt;patch(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'json' =&gt; [
            'status' =&gt; 'contacted',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/leads/1/status'
payload = {
    "status": "contacted"
}
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('PATCH', url, headers=headers, json=payload)
response.json()</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-leads--id--status">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Lead status updated successfully&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;status&quot;: &quot;contacted&quot;,
        &quot;contacted_at&quot;: &quot;2025-01-15T12:00:00.000000Z&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Lead not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-PATCHapi-v1-leads--id--status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-leads--id--status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-leads--id--status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-leads--id--status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-leads--id--status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-leads--id--status" data-method="PATCH"
      data-path="api/v1/leads/{id}/status"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-leads--id--status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-leads--id--status"
                    onclick="tryItOut('PATCHapi-v1-leads--id--status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-leads--id--status"
                    onclick="cancelTryOut('PATCHapi-v1-leads--id--status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-leads--id--status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/leads/{id}/status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="PATCHapi-v1-leads--id--status"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-leads--id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-leads--id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PATCHapi-v1-leads--id--status"
               value="1"
               data-component="url">
    <br>
<p>The lead ID. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PATCHapi-v1-leads--id--status"
               value="contacted"
               data-component="body">
    <br>
<p>The new status. Example: <code>contacted</code></p>
        </div>
        </form>

                <h1 id="sync">Sync</h1>

    <p>API endpoints for syncing campaigns</p>

                                <h2 id="sync-POSTapi-v1-sync-campaign--id-">Trigger manual sync for a campaign</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Manually trigger a sync operation for a specific campaign to fetch new leads immediately.</p>

<span id="example-requests-POSTapi-v1-sync-campaign--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/sync/campaign/1" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/sync/campaign/1"
);

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/sync/campaign/1';
$response = $client-&gt;post(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/sync/campaign/1'
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('POST', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-sync-campaign--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Sync started successfully&quot;,
    &quot;data&quot;: {
        &quot;campaign_id&quot;: 1,
        &quot;campaign_name&quot;: &quot;Web Development Services&quot;,
        &quot;status&quot;: &quot;queued&quot;,
        &quot;queued_at&quot;: &quot;2025-01-15T12:00:00.000000Z&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Campaign not found&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (429):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Campaign was synced recently. Please wait before syncing again.&quot;,
    &quot;data&quot;: {
        &quot;last_sync_at&quot;: &quot;2025-01-15T11:55:00.000000Z&quot;,
        &quot;next_available_sync&quot;: &quot;2025-01-15T12:10:00.000000Z&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-sync-campaign--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-sync-campaign--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-sync-campaign--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-sync-campaign--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-sync-campaign--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-sync-campaign--id-" data-method="POST"
      data-path="api/v1/sync/campaign/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-sync-campaign--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-sync-campaign--id-"
                    onclick="tryItOut('POSTapi-v1-sync-campaign--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-sync-campaign--id-"
                    onclick="cancelTryOut('POSTapi-v1-sync-campaign--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-sync-campaign--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/sync/campaign/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="POSTapi-v1-sync-campaign--id-"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-sync-campaign--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-sync-campaign--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="POSTapi-v1-sync-campaign--id-"
               value="1"
               data-component="url">
    <br>
<p>The campaign ID to sync. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="sync-POSTapi-v1-sync-all">Sync all active campaigns</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Trigger a sync operation for all active campaigns belonging to the authenticated user.</p>

<span id="example-requests-POSTapi-v1-sync-all">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/sync/all" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/sync/all"
);

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/sync/all';
$response = $client-&gt;post(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/sync/all'
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('POST', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-sync-all">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;message&quot;: &quot;Sync started for 3 campaigns&quot;,
    &quot;data&quot;: {
        &quot;total_campaigns&quot;: 3,
        &quot;queued_campaigns&quot;: [
            {
                &quot;id&quot;: 1,
                &quot;name&quot;: &quot;Web Development Services&quot;
            },
            {
                &quot;id&quot;: 2,
                &quot;name&quot;: &quot;Design Services&quot;
            },
            {
                &quot;id&quot;: 3,
                &quot;name&quot;: &quot;SEO Consulting&quot;
            }
        ],
        &quot;queued_at&quot;: &quot;2025-01-15T12:00:00.000000Z&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;No active campaigns found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-sync-all" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-sync-all"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-sync-all"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-sync-all" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-sync-all">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-sync-all" data-method="POST"
      data-path="api/v1/sync/all"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-sync-all', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-sync-all"
                    onclick="tryItOut('POSTapi-v1-sync-all');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-sync-all"
                    onclick="cancelTryOut('POSTapi-v1-sync-all');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-sync-all"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/sync/all</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="POSTapi-v1-sync-all"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-sync-all"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-sync-all"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="sync-GETapi-v1-sync-history--id-">Get sync history for a campaign</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Retrieve the sync history for a specific campaign with details about each sync operation.</p>

<span id="example-requests-GETapi-v1-sync-history--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/sync/history/1?page=1&amp;per_page=15" \
    --header "X-API-Key: your_api_key_here" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/sync/history/1"
);

const params = {
    "page": "1",
    "per_page": "15",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "X-API-Key": "your_api_key_here",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="php-example">
    <pre><code class="language-php">$client = new \GuzzleHttp\Client();
$url = 'http://localhost:8000/api/v1/sync/history/1';
$response = $client-&gt;get(
    $url,
    [
        'headers' =&gt; [
            'X-API-Key' =&gt; 'your_api_key_here',
            'Content-Type' =&gt; 'application/json',
            'Accept' =&gt; 'application/json',
        ],
        'query' =&gt; [
            'page' =&gt; '1',
            'per_page' =&gt; '15',
        ],
    ]
);
$body = $response-&gt;getBody();
print_r(json_decode((string) $body));</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/sync/history/1'
params = {
  'page': '1',
  'per_page': '15',
}
headers = {
  'X-API-Key': 'your_api_key_here',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-sync-history--id-">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;campaign_id&quot;: 1,
        &quot;campaign_name&quot;: &quot;Web Development Services&quot;,
        &quot;sync_history&quot;: [
            {
                &quot;id&quot;: 1,
                &quot;started_at&quot;: &quot;2025-01-15T10:00:00.000000Z&quot;,
                &quot;completed_at&quot;: &quot;2025-01-15T10:05:00.000000Z&quot;,
                &quot;status&quot;: &quot;completed&quot;,
                &quot;leads_found&quot;: 5,
                &quot;errors&quot;: null,
                &quot;duration_seconds&quot;: 300
            }
        ],
        &quot;pagination&quot;: {
            &quot;current_page&quot;: 1,
            &quot;per_page&quot;: 15,
            &quot;total&quot;: 50,
            &quot;last_page&quot;: 4
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;message&quot;: &quot;Campaign not found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-sync-history--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-sync-history--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-sync-history--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-sync-history--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-sync-history--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-sync-history--id-" data-method="GET"
      data-path="api/v1/sync/history/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-sync-history--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-sync-history--id-"
                    onclick="tryItOut('GETapi-v1-sync-history--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-sync-history--id-"
                    onclick="cancelTryOut('GETapi-v1-sync-history--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-sync-history--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/sync/history/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-Key</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-Key" class="auth-value"               data-endpoint="GETapi-v1-sync-history--id-"
               value="your_api_key_here"
               data-component="header">
    <br>
<p>Example: <code>your_api_key_here</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-sync-history--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-sync-history--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1-sync-history--id-"
               value="1"
               data-component="url">
    <br>
<p>The campaign ID. Example: <code>1</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-sync-history--id-"
               value="1"
               data-component="query">
    <br>
<p>Page number for pagination. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-sync-history--id-"
               value="15"
               data-component="query">
    <br>
<p>Number of items per page (max 100). Example: <code>15</code></p>
            </div>
                </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                                                        <button type="button" class="lang-button" data-language-name="php">php</button>
                                                        <button type="button" class="lang-button" data-language-name="python">python</button>
                            </div>
            </div>
</div>
</body>
</html>
