<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .endpoint-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
        .method-badge {
            @apply px-3 py-1 rounded-full text-xs font-bold uppercase;
        }
        .method-get { @apply bg-blue-100 text-blue-800; }
        .method-post { @apply bg-green-100 text-green-800; }
        .method-put { @apply bg-yellow-100 text-yellow-800; }
        .method-delete { @apply bg-red-100 text-red-800; }
        pre { @apply bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto; }
        code { @apply text-sm font-mono; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <h1 class="text-4xl font-bold mb-4">REST API Documentation</h1>
            <p class="text-xl text-blue-100">Complete API reference for {{ config('app.name') }}</p>
            <div class="mt-6 flex gap-4">
                <span class="bg-white/20 backdrop-blur-sm px-4 py-2 rounded-lg">
                    <i class="fas fa-code mr-2"></i>Version: v1
                </span>
                <span class="bg-white/20 backdrop-blur-sm px-4 py-2 rounded-lg">
                    <i class="fas fa-bolt mr-2"></i>Rate Limit: 60 requests/minute
                </span>
                <span class="bg-white/20 backdrop-blur-sm px-4 py-2 rounded-lg">
                    <i class="fas fa-shield-alt mr-2"></i>Authentication: API Key
                </span>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Quick Navigation -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-2xl font-bold mb-4">Quick Navigation</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="#authentication" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    <i class="fas fa-key text-blue-600 mr-3"></i>
                    <span class="font-semibold">Authentication</span>
                </a>
                <a href="#clients" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    <i class="fas fa-users text-green-600 mr-3"></i>
                    <span class="font-semibold">Clients</span>
                </a>
                <a href="#services" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                    <i class="fas fa-server text-purple-600 mr-3"></i>
                    <span class="font-semibold">Services</span>
                </a>
                <a href="#invoices" class="flex items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                    <i class="fas fa-file-invoice text-yellow-600 mr-3"></i>
                    <span class="font-semibold">Invoices</span>
                </a>
                <a href="#domains" class="flex items-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                    <i class="fas fa-globe text-indigo-600 mr-3"></i>
                    <span class="font-semibold">Domains</span>
                </a>
                <a href="#tickets" class="flex items-center p-4 bg-red-50 rounded-lg hover:bg-red-100 transition">
                    <i class="fas fa-ticket-alt text-red-600 mr-3"></i>
                    <span class="font-semibold">Tickets</span>
                </a>
                <a href="#products" class="flex items-center p-4 bg-pink-50 rounded-lg hover:bg-pink-100 transition">
                    <i class="fas fa-box text-pink-600 mr-3"></i>
                    <span class="font-semibold">Products</span>
                </a>
                <a href="#webhooks" class="flex items-center p-4 bg-teal-50 rounded-lg hover:bg-teal-100 transition">
                    <i class="fas fa-webhook text-teal-600 mr-3"></i>
                    <span class="font-semibold">Webhooks</span>
                </a>
            </div>
        </div>

        <!-- Getting Started -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-2xl font-bold mb-4">Getting Started</h2>
            <div class="prose max-w-none">
                <p class="text-gray-600 mb-4">The API is RESTful and returns JSON responses. All requests must be made over HTTPS.</p>

                <h3 class="text-xl font-semibold mb-3">Base URL</h3>
                <pre><code>{{ $apiUrl }}</code></pre>

                <h3 class="text-xl font-semibold mb-3 mt-6">Authentication</h3>
                <p class="text-gray-600 mb-4">Include your API key in the request header:</p>
                <pre><code>X-API-Key: your_api_key_here</code></pre>

                <h3 class="text-xl font-semibold mb-3 mt-6">Rate Limiting</h3>
                <p class="text-gray-600 mb-4">The API allows 60 requests per minute. Response headers include:</p>
                <pre><code>X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1638360000</code></pre>

                <h3 class="text-xl font-semibold mb-3 mt-6">Response Format</h3>
                <p class="text-gray-600 mb-4">All responses follow this structure:</p>
                <pre><code>{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}</code></pre>

                <h3 class="text-xl font-semibold mb-3 mt-6">Error Codes</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr><td class="px-6 py-4 whitespace-nowrap text-sm font-medium">200</td><td class="px-6 py-4 text-sm text-gray-500">Success</td></tr>
                            <tr><td class="px-6 py-4 whitespace-nowrap text-sm font-medium">201</td><td class="px-6 py-4 text-sm text-gray-500">Created</td></tr>
                            <tr><td class="px-6 py-4 whitespace-nowrap text-sm font-medium">400</td><td class="px-6 py-4 text-sm text-gray-500">Bad Request</td></tr>
                            <tr><td class="px-6 py-4 whitespace-nowrap text-sm font-medium">401</td><td class="px-6 py-4 text-sm text-gray-500">Unauthorized</td></tr>
                            <tr><td class="px-6 py-4 whitespace-nowrap text-sm font-medium">404</td><td class="px-6 py-4 text-sm text-gray-500">Not Found</td></tr>
                            <tr><td class="px-6 py-4 whitespace-nowrap text-sm font-medium">429</td><td class="px-6 py-4 text-sm text-gray-500">Too Many Requests</td></tr>
                            <tr><td class="px-6 py-4 whitespace-nowrap text-sm font-medium">500</td><td class="px-6 py-4 text-sm text-gray-500">Internal Server Error</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Authentication Endpoints -->
        <div id="authentication" class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-key text-blue-600 mr-3"></i>
                Authentication
            </h2>

            <!-- Login -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Login</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-post">POST</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/auth/login</code>
                    </div>
                </div>
                <p class="text-gray-600 mb-4">Authenticate and receive an API token.</p>
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">Request Body:</h4>
                    <pre><code>{
  "email": "user@example.com",
  "password": "your_password"
}</code></pre>
                </div>
                <div>
                    <h4 class="font-semibold mb-2">Response:</h4>
                    <pre><code>{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com"
    },
    "token": "your_bearer_token",
    "token_type": "Bearer"
  }
}</code></pre>
                </div>
            </div>

            <!-- Create API Key -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Create API Key</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-post">POST</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/auth/keys</code>
                    </div>
                </div>
                <p class="text-gray-600 mb-4">Generate a new API key for authentication.</p>
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">Request Body:</h4>
                    <pre><code>{
  "name": "Production API Key"
}</code></pre>
                </div>
                <div>
                    <h4 class="font-semibold mb-2">Response:</h4>
                    <pre><code>{
  "success": true,
  "message": "API key created successfully",
  "data": {
    "id": 1,
    "name": "Production API Key",
    "key": "sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}</code></pre>
                </div>
            </div>
        </div>

        <!-- Client Endpoints -->
        <div id="clients" class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-users text-green-600 mr-3"></i>
                Client Management
            </h2>

            <!-- List Clients -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">List Clients</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/clients</code>
                    </div>
                </div>
                <p class="text-gray-600 mb-4">Retrieve a paginated list of all clients.</p>
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">Query Parameters:</h4>
                    <ul class="list-disc list-inside text-gray-600 space-y-1">
                        <li><code>per_page</code> - Items per page (default: 15)</li>
                        <li><code>search</code> - Search by name, email, or company</li>
                        <li><code>status</code> - Filter by status (active/inactive)</li>
                    </ul>
                </div>
            </div>

            <!-- Create Client -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Create Client</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-post">POST</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/clients</code>
                    </div>
                </div>
                <p class="text-gray-600 mb-4">Create a new client account.</p>
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">Request Body:</h4>
                    <pre><code>{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secure_password",
  "company_name": "Acme Inc",
  "country": "US"
}</code></pre>
                </div>
            </div>

            <!-- Get Client -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Get Client</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/clients/{id}</code>
                    </div>
                </div>
                <p class="text-gray-600">Retrieve details of a specific client.</p>
            </div>

            <!-- Update Client -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Update Client</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-put">PUT</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/clients/{id}</code>
                    </div>
                </div>
                <p class="text-gray-600">Update client information.</p>
            </div>

            <!-- Delete Client -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Delete Client</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-delete">DELETE</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/clients/{id}</code>
                    </div>
                </div>
                <p class="text-gray-600">Delete a client account.</p>
            </div>
        </div>

        <!-- Service Endpoints -->
        <div id="services" class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-server text-purple-600 mr-3"></i>
                Service Management
            </h2>

            <!-- List Services -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">List Services</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/services</code>
                    </div>
                </div>
                <p class="text-gray-600 mb-4">Retrieve all services.</p>
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">Query Parameters:</h4>
                    <ul class="list-disc list-inside text-gray-600 space-y-1">
                        <li><code>user_id</code> - Filter by client ID</li>
                        <li><code>status</code> - Filter by status (active/pending/suspended/terminated)</li>
                        <li><code>per_page</code> - Items per page (default: 15)</li>
                    </ul>
                </div>
            </div>

            <!-- Create Service -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Create Service</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-post">POST</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/services</code>
                    </div>
                </div>
                <p class="text-gray-600 mb-4">Create a new hosting service.</p>
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">Request Body:</h4>
                    <pre><code>{
  "user_id": 1,
  "hosting_product_id": 1,
  "domain": "example.com",
  "billing_cycle": "monthly",
  "price": 9.99
}</code></pre>
                </div>
            </div>

            <!-- Service Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="endpoint-card border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold">Activate Service</h3>
                        <span class="method-badge method-post">POST</span>
                    </div>
                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">/services/{id}/activate</code>
                </div>
                <div class="endpoint-card border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold">Suspend Service</h3>
                        <span class="method-badge method-post">POST</span>
                    </div>
                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">/services/{id}/suspend</code>
                </div>
                <div class="endpoint-card border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold">Terminate Service</h3>
                        <span class="method-badge method-post">POST</span>
                    </div>
                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">/services/{id}/terminate</code>
                </div>
                <div class="endpoint-card border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold">Upgrade Service</h3>
                        <span class="method-badge method-post">POST</span>
                    </div>
                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">/services/{id}/upgrade</code>
                </div>
            </div>
        </div>

        <!-- Invoice Endpoints -->
        <div id="invoices" class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-file-invoice text-yellow-600 mr-3"></i>
                Invoice Management
            </h2>

            <!-- List Invoices -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">List Invoices</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/invoices</code>
                    </div>
                </div>
                <p class="text-gray-600 mb-4">Retrieve all invoices.</p>
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">Query Parameters:</h4>
                    <ul class="list-disc list-inside text-gray-600 space-y-1">
                        <li><code>user_id</code> - Filter by client ID</li>
                        <li><code>status</code> - Filter by status (paid/unpaid/cancelled)</li>
                        <li><code>per_page</code> - Items per page (default: 15)</li>
                    </ul>
                </div>
            </div>

            <!-- Create Invoice -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Create Invoice</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-post">POST</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/invoices</code>
                    </div>
                </div>
                <p class="text-gray-600 mb-4">Create a new invoice.</p>
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">Request Body:</h4>
                    <pre><code>{
  "user_id": 1,
  "due_date": "2024-12-31",
  "currency": "USD",
  "discount": 0,
  "items": [
    {
      "description": "Hosting - January 2024",
      "quantity": 1,
      "unit_price": 9.99
    }
  ]
}</code></pre>
                </div>
            </div>

            <!-- Invoice Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="endpoint-card border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold">Mark as Paid</h3>
                        <span class="method-badge method-post">POST</span>
                    </div>
                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">/invoices/{id}/pay</code>
                </div>
                <div class="endpoint-card border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold">Cancel Invoice</h3>
                        <span class="method-badge method-post">POST</span>
                    </div>
                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">/invoices/{id}/cancel</code>
                </div>
                <div class="endpoint-card border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold">Download PDF</h3>
                        <span class="method-badge method-get">GET</span>
                    </div>
                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">/invoices/{id}/download</code>
                </div>
            </div>
        </div>

        <!-- Domain Endpoints -->
        <div id="domains" class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-globe text-indigo-600 mr-3"></i>
                Domain Management
            </h2>

            <!-- List Domains -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">List Domains</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/domains</code>
                    </div>
                </div>
                <p class="text-gray-600">Retrieve all registered domains.</p>
            </div>

            <!-- Register Domain -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Register Domain</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-post">POST</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/domains/register</code>
                    </div>
                </div>
                <p class="text-gray-600 mb-4">Register a new domain.</p>
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">Request Body:</h4>
                    <pre><code>{
  "user_id": 1,
  "domain_name": "example.com",
  "years": 1,
  "auto_renew": true,
  "privacy_protection": true
}</code></pre>
                </div>
            </div>

            <!-- Domain Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="endpoint-card border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold">Renew Domain</h3>
                        <span class="method-badge method-post">POST</span>
                    </div>
                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">/domains/{id}/renew</code>
                </div>
                <div class="endpoint-card border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold">Transfer Domain</h3>
                        <span class="method-badge method-post">POST</span>
                    </div>
                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">/domains/{id}/transfer</code>
                </div>
            </div>
        </div>

        <!-- Ticket Endpoints -->
        <div id="tickets" class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-ticket-alt text-red-600 mr-3"></i>
                Support Tickets
            </h2>

            <!-- List Tickets -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">List Tickets</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/tickets</code>
                    </div>
                </div>
                <p class="text-gray-600">Retrieve all support tickets.</p>
            </div>

            <!-- Create Ticket -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Create Ticket</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-post">POST</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/tickets</code>
                    </div>
                </div>
                <p class="text-gray-600 mb-4">Create a new support ticket.</p>
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">Request Body:</h4>
                    <pre><code>{
  "user_id": 1,
  "subject": "Account Access Issue",
  "message": "I cannot log into my account...",
  "department": "support",
  "priority": "normal"
}</code></pre>
                </div>
            </div>

            <!-- Ticket Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="endpoint-card border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold">Reply to Ticket</h3>
                        <span class="method-badge method-post">POST</span>
                    </div>
                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">/tickets/{id}/reply</code>
                </div>
                <div class="endpoint-card border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold">Close Ticket</h3>
                        <span class="method-badge method-post">POST</span>
                    </div>
                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">/tickets/{id}/close</code>
                </div>
            </div>
        </div>

        <!-- Product Endpoints -->
        <div id="products" class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-box text-pink-600 mr-3"></i>
                Product Catalog
            </h2>

            <!-- List Products -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">List Products</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/products</code>
                    </div>
                </div>
                <p class="text-gray-600">Retrieve all available products.</p>
            </div>

            <!-- Get Product -->
            <div class="endpoint-card border border-gray-200 rounded-lg p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Get Product</h3>
                    <div class="flex items-center gap-2">
                        <span class="method-badge method-get">GET</span>
                        <code class="text-sm bg-gray-100 px-3 py-1 rounded">/products/{id}</code>
                    </div>
                </div>
                <p class="text-gray-600">Retrieve details of a specific product.</p>
            </div>
        </div>

        <!-- Webhooks -->
        <div id="webhooks" class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-webhook text-teal-600 mr-3"></i>
                Webhooks
            </h2>

            <p class="text-gray-600 mb-6">Webhooks allow you to receive real-time notifications when events occur in your account.</p>

            <h3 class="text-xl font-semibold mb-4">Available Events</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="border border-gray-200 rounded-lg p-4">
                    <h4 class="font-semibold mb-2">Invoice Events</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• invoice.created</li>
                        <li>• invoice.paid</li>
                        <li>• invoice.overdue</li>
                        <li>• invoice.cancelled</li>
                    </ul>
                </div>
                <div class="border border-gray-200 rounded-lg p-4">
                    <h4 class="font-semibold mb-2">Service Events</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• service.created</li>
                        <li>• service.activated</li>
                        <li>• service.suspended</li>
                        <li>• service.terminated</li>
                    </ul>
                </div>
                <div class="border border-gray-200 rounded-lg p-4">
                    <h4 class="font-semibold mb-2">Domain Events</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• domain.registered</li>
                        <li>• domain.renewed</li>
                        <li>• domain.transferred</li>
                    </ul>
                </div>
                <div class="border border-gray-200 rounded-lg p-4">
                    <h4 class="font-semibold mb-2">Ticket Events</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• ticket.created</li>
                        <li>• ticket.replied</li>
                        <li>• ticket.closed</li>
                    </ul>
                </div>
            </div>

            <h3 class="text-xl font-semibold mb-4">Webhook Payload</h3>
            <pre><code>{
  "event": "invoice.paid",
  "timestamp": "2024-01-01T00:00:00.000000Z",
  "data": {
    "invoice_id": 1,
    "invoice_number": "INV-202401-0001",
    "user_id": 1,
    "amount": 9.99,
    "payment_method": "stripe"
  }
}</code></pre>

            <h3 class="text-xl font-semibold mb-4 mt-6">Signature Verification</h3>
            <p class="text-gray-600 mb-4">Webhooks are signed using HMAC SHA256. Verify the signature using the <code class="bg-gray-100 px-2 py-1 rounded">X-Webhook-Signature</code> header:</p>
            <pre><code>$signature = hash_hmac('sha256', $payload, $webhook_secret);
if (!hash_equals($signature, $receivedSignature)) {
    // Invalid signature
    http_response_code(401);
    exit;
}</code></pre>
        </div>

        <!-- Code Examples -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6">Code Examples</h2>

            <div class="space-y-6">
                <!-- PHP Example -->
                <div>
                    <h3 class="text-xl font-semibold mb-3 flex items-center">
                        <i class="fab fa-php text-purple-600 mr-2"></i>
                        PHP
                    </h3>
                    <pre><code>&lt;?php
$apiKey = 'your_api_key_here';
$apiUrl = '{{ $apiUrl }}';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl . '/clients');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
print_r($data);</code></pre>
                </div>

                <!-- Python Example -->
                <div>
                    <h3 class="text-xl font-semibold mb-3 flex items-center">
                        <i class="fab fa-python text-blue-600 mr-2"></i>
                        Python
                    </h3>
                    <pre><code>import requests

api_key = 'your_api_key_here'
api_url = '{{ $apiUrl }}'

headers = {
    'X-API-Key': api_key,
    'Accept': 'application/json'
}

response = requests.get(f'{api_url}/clients', headers=headers)
data = response.json()
print(data)</code></pre>
                </div>

                <!-- JavaScript Example -->
                <div>
                    <h3 class="text-xl font-semibold mb-3 flex items-center">
                        <i class="fab fa-js text-yellow-600 mr-2"></i>
                        JavaScript (Node.js)
                    </h3>
                    <pre><code>const fetch = require('node-fetch');

const apiKey = 'your_api_key_here';
const apiUrl = '{{ $apiUrl }}';

fetch(`${apiUrl}/clients`, {
  headers: {
    'X-API-Key': apiKey,
    'Accept': 'application/json'
  }
})
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));</code></pre>
                </div>

                <!-- cURL Example -->
                <div>
                    <h3 class="text-xl font-semibold mb-3 flex items-center">
                        <i class="fas fa-terminal text-green-600 mr-2"></i>
                        cURL
                    </h3>
                    <pre><code>curl -X GET '{{ $apiUrl }}/clients' \
  -H 'X-API-Key: your_api_key_here' \
  -H 'Accept: application/json'</code></pre>
                </div>
            </div>
        </div>

        <!-- SDKs and Tools -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 mb-8">
            <h2 class="text-2xl font-bold mb-4">SDKs & Tools</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg p-4 shadow-sm">
                    <i class="fas fa-file-code text-blue-600 text-2xl mb-2"></i>
                    <h3 class="font-semibold mb-2">Postman Collection</h3>
                    <p class="text-sm text-gray-600 mb-3">Import our Postman collection to test all endpoints.</p>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">Download</button>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm">
                    <i class="fas fa-book text-green-600 text-2xl mb-2"></i>
                    <h3 class="font-semibold mb-2">OpenAPI Spec</h3>
                    <p class="text-sm text-gray-600 mb-3">Download our OpenAPI 3.0 specification file.</p>
                    <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">Download</button>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm">
                    <i class="fas fa-code text-purple-600 text-2xl mb-2"></i>
                    <h3 class="font-semibold mb-2">Client Libraries</h3>
                    <p class="text-sm text-gray-600 mb-3">Official SDKs for PHP, Python, and JavaScript.</p>
                    <button class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 text-sm">Browse</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="bg-gray-800 text-white mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center">
                <p class="text-gray-400">Need help? Contact our support team at <a href="mailto:support@example.com" class="text-blue-400 hover:text-blue-300">support@example.com</a></p>
                <p class="text-gray-500 mt-2">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        // Smooth scroll to sections
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
