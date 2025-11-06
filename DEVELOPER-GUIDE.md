# NUMZ.AI Developer Guide

## Module Development Documentation

Welcome to the NUMZ.AI Module Development Guide. This comprehensive documentation will help you create custom modules for payment gateways, domain registrars, and provisioning integrations.

---

## Table of Contents

1. [Module Architecture](#module-architecture)
2. [Payment Gateway Modules](#payment-gateway-modules)
3. [Domain Registrar Modules](#domain-registrar-modules)
4. [Provisioning Modules](#provisioning-modules)
5. [Module Configuration System](#module-configuration-system)
6. [Best Practices](#best-practices)
7. [Testing Your Module](#testing-your-module)
8. [Module Deployment](#module-deployment)

---

## Module Architecture

NUMZ.AI uses an **interface-based module architecture** that allows developers to extend functionality without modifying core code.

### Core Principles

1. **Interface Contracts**: All modules implement specific interfaces
2. **Database Configuration**: Settings stored in `module_settings` table
3. **Encrypted Storage**: Sensitive data (API keys) stored encrypted
4. **Namespace Convention**: `App\Numz\Modules\{Type}\{ModuleName}`
5. **Self-Contained**: Each module is independent and portable

### Module Types

| Type | Interface | Purpose |
|------|-----------|---------|
| **Payment Gateway** | `PaymentGatewayInterface` | Process payments, refunds, webhooks |
| **Domain Registrar** | `RegistrarInterface` | Register domains, manage DNS, transfers |
| **Provisioning** | `ProvisioningInterface` | Create/suspend/terminate hosting accounts |
| **Integration** | Custom | Social auth, CRM, analytics, etc. |

---

## Payment Gateway Modules

Payment gateway modules handle payment processing, refunds, and webhook notifications.

### 1. Payment Gateway Interface

All payment gateway modules must implement `App\Numz\Contracts\PaymentGatewayInterface`:

```php
<?php

namespace App\Numz\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Get the gateway name
     */
    public function getName(): string;

    /**
     * Get the gateway display name
     */
    public function getDisplayName(): string;

    /**
     * Get gateway configuration fields
     */
    public function getConfigFields(): array;

    /**
     * Initialize the gateway with settings
     */
    public function initialize(array $settings): void;

    /**
     * Process a payment
     *
     * @param array $params ['amount' => float, 'currency' => string, 'token' => string, 'description' => string]
     * @return array ['success' => bool, 'transaction_id' => string, 'message' => string]
     */
    public function charge(array $params): array;

    /**
     * Process a refund
     *
     * @param array $params ['transaction_id' => string, 'amount' => float]
     * @return array ['success' => bool, 'message' => string]
     */
    public function refund(array $params): array;

    /**
     * Handle webhook notification
     *
     * @param array $payload Raw webhook data
     * @return array ['success' => bool, 'invoice_id' => int|null, 'amount' => float|null]
     */
    public function handleWebhook(array $payload): array;

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $signature, string $payload): bool;

    /**
     * Check if gateway is active
     */
    public function isActive(): bool;
}
```

### 2. Creating a Payment Gateway Module

Let's create a **Stripe** payment gateway module as an example.

**File**: `app/Numz/Modules/PaymentGateways/StripeGateway.php`

```php
<?php

namespace App\Numz\Modules\PaymentGateways;

use App\Numz\Contracts\PaymentGatewayInterface;

class StripeGateway implements PaymentGatewayInterface
{
    protected string $apiKey;
    protected bool $testMode;
    protected bool $isActive;

    public function getName(): string
    {
        return 'stripe';
    }

    public function getDisplayName(): string
    {
        return 'Stripe';
    }

    public function getConfigFields(): array
    {
        return [
            'api_key' => [
                'label' => 'API Secret Key',
                'type' => 'password',
                'required' => true,
                'encrypted' => true,
                'help' => 'Your Stripe secret key (sk_live_... or sk_test_...)',
            ],
            'publishable_key' => [
                'label' => 'Publishable Key',
                'type' => 'text',
                'required' => true,
                'encrypted' => false,
                'help' => 'Your Stripe publishable key (pk_live_... or pk_test_...)',
            ],
            'webhook_secret' => [
                'label' => 'Webhook Secret',
                'type' => 'password',
                'required' => false,
                'encrypted' => true,
                'help' => 'Webhook signing secret from Stripe dashboard',
            ],
            'test_mode' => [
                'label' => 'Test Mode',
                'type' => 'checkbox',
                'required' => false,
                'encrypted' => false,
                'help' => 'Enable test mode for development',
            ],
        ];
    }

    public function initialize(array $settings): void
    {
        $this->apiKey = $settings['api_key'] ?? '';
        $this->testMode = (bool) ($settings['test_mode'] ?? false);
        $this->isActive = !empty($this->apiKey);
    }

    public function charge(array $params): array
    {
        try {
            // Initialize Stripe
            \Stripe\Stripe::setApiKey($this->apiKey);

            // Create charge
            $charge = \Stripe\Charge::create([
                'amount' => $params['amount'] * 100, // Convert to cents
                'currency' => $params['currency'] ?? 'usd',
                'source' => $params['token'],
                'description' => $params['description'] ?? 'Payment',
                'metadata' => [
                    'invoice_id' => $params['invoice_id'] ?? null,
                ],
            ]);

            return [
                'success' => true,
                'transaction_id' => $charge->id,
                'message' => 'Payment successful',
            ];
        } catch (\Stripe\Exception\CardException $e) {
            return [
                'success' => false,
                'transaction_id' => null,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'transaction_id' => null,
                'message' => 'Payment failed: ' . $e->getMessage(),
            ];
        }
    }

    public function refund(array $params): array
    {
        try {
            \Stripe\Stripe::setApiKey($this->apiKey);

            $refund = \Stripe\Refund::create([
                'charge' => $params['transaction_id'],
                'amount' => isset($params['amount']) ? $params['amount'] * 100 : null,
            ]);

            return [
                'success' => true,
                'message' => 'Refund processed successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Refund failed: ' . $e->getMessage(),
            ];
        }
    }

    public function handleWebhook(array $payload): array
    {
        $event = $payload['type'] ?? '';

        switch ($event) {
            case 'charge.succeeded':
                $charge = $payload['data']['object'];
                return [
                    'success' => true,
                    'invoice_id' => $charge['metadata']['invoice_id'] ?? null,
                    'amount' => $charge['amount'] / 100,
                    'transaction_id' => $charge['id'],
                ];

            case 'charge.failed':
                return [
                    'success' => false,
                    'message' => 'Payment failed',
                ];

            default:
                return ['success' => false, 'message' => 'Unhandled event'];
        }
    }

    public function verifyWebhookSignature(string $signature, string $payload): bool
    {
        try {
            $webhookSecret = $this->webhookSecret ?? '';
            if (empty($webhookSecret)) {
                return false;
            }

            \Stripe\Webhook::constructEvent($payload, $signature, $webhookSecret);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
```

### 3. Registering Your Payment Gateway

To register your payment gateway, add it to the configuration:

**File**: `config/numz.php`

```php
'payment_gateways' => [
    'stripe' => App\Numz\Modules\PaymentGateways\StripeGateway::class,
    'paypal' => App\Numz\Modules\PaymentGateways\PayPalGateway::class,
    'coinbase' => App\Numz\Modules\PaymentGateways\CoinbaseGateway::class,
    // Add your custom gateway here
    'your_gateway' => App\Numz\Modules\PaymentGateways\YourGateway::class,
],
```

### 4. Installing Composer Dependencies

If your gateway requires external libraries (like Stripe SDK), add them to `composer.json`:

```bash
composer require stripe/stripe-php
```

### 5. Configuring via Admin Panel

1. Navigate to **Admin Panel** → **Settings** → **Payment Gateways**
2. Select your gateway
3. Enter configuration values (API keys, etc.)
4. Toggle "Active" status
5. Save settings

Settings are automatically encrypted for sensitive fields marked with `'encrypted' => true`.

---

## Domain Registrar Modules

Domain registrar modules handle domain registration, DNS management, transfers, and WHOIS updates.

### 1. Registrar Interface

All registrar modules must implement `App\Numz\Contracts\RegistrarInterface`:

```php
<?php

namespace App\Numz\Contracts;

interface RegistrarInterface
{
    /**
     * Get the registrar name
     */
    public function getName(): string;

    /**
     * Get the registrar display name
     */
    public function getDisplayName(): string;

    /**
     * Get registrar configuration fields
     */
    public function getConfigFields(): array;

    /**
     * Initialize the registrar with settings
     */
    public function initialize(array $settings): void;

    /**
     * Check domain availability
     *
     * @param string $domain Fully qualified domain name
     * @return bool True if available, false if taken
     */
    public function checkAvailability(string $domain): bool;

    /**
     * Register a domain
     *
     * @param array $params ['domain' => string, 'years' => int, 'nameservers' => array, 'contacts' => array]
     * @return array ['success' => bool, 'domain_id' => string|null, 'message' => string]
     */
    public function registerDomain(array $params): array;

    /**
     * Renew a domain
     *
     * @param array $params ['domain' => string, 'years' => int]
     * @return array ['success' => bool, 'message' => string]
     */
    public function renewDomain(array $params): array;

    /**
     * Transfer a domain in
     *
     * @param array $params ['domain' => string, 'epp_code' => string]
     * @return array ['success' => bool, 'message' => string]
     */
    public function transferDomain(array $params): array;

    /**
     * Update nameservers
     *
     * @param string $domain
     * @param array $nameservers ['ns1.example.com', 'ns2.example.com']
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateNameservers(string $domain, array $nameservers): array;

    /**
     * Get domain information
     *
     * @param string $domain
     * @return array Domain details including expiry, nameservers, etc.
     */
    public function getDomainInfo(string $domain): array;

    /**
     * Enable/disable WHOIS privacy
     *
     * @param string $domain
     * @param bool $enable
     * @return array ['success' => bool, 'message' => string]
     */
    public function setWhoisPrivacy(string $domain, bool $enable): array;

    /**
     * Get EPP code for transfer out
     *
     * @param string $domain
     * @return array ['success' => bool, 'epp_code' => string|null, 'message' => string]
     */
    public function getEppCode(string $domain): array;

    /**
     * Lock/unlock domain
     *
     * @param string $domain
     * @param bool $lock
     * @return array ['success' => bool, 'message' => string]
     */
    public function setDomainLock(string $domain, bool $lock): array;
}
```

### 2. Creating a Registrar Module

Example: **DomainNameAPI** registrar module

**File**: `app/Numz/Modules/Registrars/DomainNameAPIRegistrar.php`

```php
<?php

namespace App\Numz\Modules\Registrars;

use App\Numz\Contracts\RegistrarInterface;
use Illuminate\Support\Facades\Http;

class DomainNameAPIRegistrar implements RegistrarInterface
{
    protected string $apiUrl;
    protected string $username;
    protected string $password;
    protected bool $testMode;

    public function getName(): string
    {
        return 'domainnameapi';
    }

    public function getDisplayName(): string
    {
        return 'DomainNameAPI';
    }

    public function getConfigFields(): array
    {
        return [
            'username' => [
                'label' => 'API Username',
                'type' => 'text',
                'required' => true,
                'encrypted' => false,
            ],
            'password' => [
                'label' => 'API Password',
                'type' => 'password',
                'required' => true,
                'encrypted' => true,
            ],
            'test_mode' => [
                'label' => 'Test Mode',
                'type' => 'checkbox',
                'required' => false,
                'encrypted' => false,
            ],
        ];
    }

    public function initialize(array $settings): void
    {
        $this->username = $settings['username'] ?? '';
        $this->password = $settings['password'] ?? '';
        $this->testMode = (bool) ($settings['test_mode'] ?? false);
        $this->apiUrl = $this->testMode
            ? 'https://api-ote.domainnameapi.com'
            : 'https://api.domainnameapi.com';
    }

    public function checkAvailability(string $domain): bool
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->post("{$this->apiUrl}/check", [
                'domain' => $domain,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['available'] ?? false;
        }

        return false;
    }

    public function registerDomain(array $params): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->post("{$this->apiUrl}/register", [
                    'domain' => $params['domain'],
                    'period' => $params['years'] ?? 1,
                    'nameservers' => $params['nameservers'] ?? [],
                    'contacts' => $params['contacts'] ?? [],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'domain_id' => $data['domain_id'] ?? null,
                    'message' => 'Domain registered successfully',
                ];
            }

            return [
                'success' => false,
                'domain_id' => null,
                'message' => $response->json()['message'] ?? 'Registration failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'domain_id' => null,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    public function renewDomain(array $params): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->post("{$this->apiUrl}/renew", [
                    'domain' => $params['domain'],
                    'period' => $params['years'] ?? 1,
                ]);

            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? 'Domain renewed successfully'
                    : 'Renewal failed',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function transferDomain(array $params): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->post("{$this->apiUrl}/transfer", [
                    'domain' => $params['domain'],
                    'auth_code' => $params['epp_code'],
                ]);

            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? 'Transfer initiated'
                    : 'Transfer failed',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateNameservers(string $domain, array $nameservers): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->post("{$this->apiUrl}/modify-nameservers", [
                    'domain' => $domain,
                    'nameservers' => $nameservers,
                ]);

            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? 'Nameservers updated'
                    : 'Update failed',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getDomainInfo(string $domain): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->get("{$this->apiUrl}/info", ['domain' => $domain]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function setWhoisPrivacy(string $domain, bool $enable): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->post("{$this->apiUrl}/whois-privacy", [
                    'domain' => $domain,
                    'enabled' => $enable,
                ]);

            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? 'WHOIS privacy updated'
                    : 'Update failed',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getEppCode(string $domain): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->get("{$this->apiUrl}/epp-code", ['domain' => $domain]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'epp_code' => $data['epp_code'] ?? null,
                    'message' => 'EPP code retrieved',
                ];
            }

            return [
                'success' => false,
                'epp_code' => null,
                'message' => 'Failed to retrieve EPP code',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'epp_code' => null, 'message' => $e->getMessage()];
        }
    }

    public function setDomainLock(string $domain, bool $lock): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->post("{$this->apiUrl}/lock", [
                    'domain' => $domain,
                    'locked' => $lock,
                ]);

            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? ($lock ? 'Domain locked' : 'Domain unlocked')
                    : 'Lock update failed',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
```

### 3. Registering Your Registrar

Add to `config/numz.php`:

```php
'registrars' => [
    'domainnameapi' => App\Numz\Modules\Registrars\DomainNameAPIRegistrar::class,
    'namecheap' => App\Numz\Modules\Registrars\NamecheapRegistrar::class,
    // Add your custom registrar here
    'your_registrar' => App\Numz\Modules\Registrars\YourRegistrar::class,
],
```

---

## Provisioning Modules

Provisioning modules handle server account creation, suspension, termination, and management.

### 1. Provisioning Interface

All provisioning modules must implement `App\Numz\Contracts\ProvisioningInterface`:

```php
<?php

namespace App\Numz\Contracts;

interface ProvisioningInterface
{
    /**
     * Get the module name
     */
    public function getName(): string;

    /**
     * Get the module display name
     */
    public function getDisplayName(): string;

    /**
     * Get module configuration fields
     */
    public function getConfigFields(): array;

    /**
     * Initialize the module with server settings
     */
    public function initialize(array $settings): void;

    /**
     * Create a new hosting account
     *
     * @param array $params ['username' => string, 'domain' => string, 'package' => string, 'email' => string]
     * @return array ['success' => bool, 'account_id' => string|null, 'username' => string|null, 'password' => string|null, 'message' => string]
     */
    public function createAccount(array $params): array;

    /**
     * Suspend a hosting account
     *
     * @param array $params ['username' => string, 'reason' => string]
     * @return array ['success' => bool, 'message' => string]
     */
    public function suspendAccount(array $params): array;

    /**
     * Unsuspend a hosting account
     *
     * @param array $params ['username' => string]
     * @return array ['success' => bool, 'message' => string]
     */
    public function unsuspendAccount(array $params): array;

    /**
     * Terminate a hosting account
     *
     * @param array $params ['username' => string]
     * @return array ['success' => bool, 'message' => string]
     */
    public function terminateAccount(array $params): array;

    /**
     * Change account password
     *
     * @param array $params ['username' => string, 'password' => string]
     * @return array ['success' => bool, 'message' => string]
     */
    public function changePassword(array $params): array;

    /**
     * Change account package/plan
     *
     * @param array $params ['username' => string, 'package' => string]
     * @return array ['success' => bool, 'message' => string]
     */
    public function changePackage(array $params): array;

    /**
     * Get account details
     *
     * @param string $username
     * @return array Account details including disk usage, bandwidth, etc.
     */
    public function getAccountDetails(string $username): array;

    /**
     * Test connection to the server
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(): array;
}
```

### 2. Creating a Provisioning Module

Example: **cPanel/WHM** provisioning module

**File**: `app/Numz/Modules/Provisioning/CpanelProvisioning.php`

```php
<?php

namespace App\Numz\Modules\Provisioning;

use App\Numz\Contracts\ProvisioningInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CpanelProvisioning implements ProvisioningInterface
{
    protected string $hostname;
    protected string $username;
    protected string $apiToken;
    protected bool $useSsl;

    public function getName(): string
    {
        return 'cpanel';
    }

    public function getDisplayName(): string
    {
        return 'cPanel/WHM';
    }

    public function getConfigFields(): array
    {
        return [
            'hostname' => [
                'label' => 'WHM Hostname',
                'type' => 'text',
                'required' => true,
                'encrypted' => false,
                'help' => 'Server hostname or IP address',
            ],
            'username' => [
                'label' => 'WHM Username',
                'type' => 'text',
                'required' => true,
                'encrypted' => false,
                'help' => 'WHM root username',
            ],
            'api_token' => [
                'label' => 'WHM API Token',
                'type' => 'password',
                'required' => true,
                'encrypted' => true,
                'help' => 'WHM API token for authentication',
            ],
            'use_ssl' => [
                'label' => 'Use SSL',
                'type' => 'checkbox',
                'required' => false,
                'encrypted' => false,
                'default' => true,
            ],
        ];
    }

    public function initialize(array $settings): void
    {
        $this->hostname = $settings['hostname'] ?? '';
        $this->username = $settings['username'] ?? '';
        $this->apiToken = $settings['api_token'] ?? '';
        $this->useSsl = (bool) ($settings['use_ssl'] ?? true);
    }

    protected function makeRequest(string $endpoint, array $params = []): array
    {
        $protocol = $this->useSsl ? 'https' : 'http';
        $url = "{$protocol}://{$this->hostname}:2087/json-api/{$endpoint}";

        try {
            $response = Http::withHeaders([
                'Authorization' => "whm {$this->username}:{$this->apiToken}",
            ])->get($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'message' => 'API request failed'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function createAccount(array $params): array
    {
        try {
            $username = $params['username'] ?? Str::random(8);
            $password = $params['password'] ?? Str::random(16);

            $result = $this->makeRequest('createacct', [
                'username' => $username,
                'domain' => $params['domain'],
                'plan' => $params['package'],
                'contactemail' => $params['email'],
                'password' => $password,
            ]);

            if (isset($result['metadata']['result']) && $result['metadata']['result'] == 1) {
                return [
                    'success' => true,
                    'account_id' => $username,
                    'username' => $username,
                    'password' => $password,
                    'message' => 'Account created successfully',
                ];
            }

            return [
                'success' => false,
                'account_id' => null,
                'username' => null,
                'password' => null,
                'message' => $result['metadata']['reason'] ?? 'Account creation failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'account_id' => null,
                'username' => null,
                'password' => null,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    public function suspendAccount(array $params): array
    {
        $result = $this->makeRequest('suspendacct', [
            'user' => $params['username'],
            'reason' => $params['reason'] ?? 'Suspended by billing system',
        ]);

        return [
            'success' => isset($result['metadata']['result']) && $result['metadata']['result'] == 1,
            'message' => $result['metadata']['reason'] ?? 'Suspension completed',
        ];
    }

    public function unsuspendAccount(array $params): array
    {
        $result = $this->makeRequest('unsuspendacct', [
            'user' => $params['username'],
        ]);

        return [
            'success' => isset($result['metadata']['result']) && $result['metadata']['result'] == 1,
            'message' => $result['metadata']['reason'] ?? 'Account unsuspended',
        ];
    }

    public function terminateAccount(array $params): array
    {
        $result = $this->makeRequest('removeacct', [
            'user' => $params['username'],
            'keepdns' => 0,
        ]);

        return [
            'success' => isset($result['metadata']['result']) && $result['metadata']['result'] == 1,
            'message' => $result['metadata']['reason'] ?? 'Account terminated',
        ];
    }

    public function changePassword(array $params): array
    {
        $result = $this->makeRequest('passwd', [
            'user' => $params['username'],
            'password' => $params['password'],
        ]);

        return [
            'success' => isset($result['metadata']['result']) && $result['metadata']['result'] == 1,
            'message' => $result['metadata']['reason'] ?? 'Password changed',
        ];
    }

    public function changePackage(array $params): array
    {
        $result = $this->makeRequest('changepackage', [
            'user' => $params['username'],
            'pkg' => $params['package'],
        ]);

        return [
            'success' => isset($result['metadata']['result']) && $result['metadata']['result'] == 1,
            'message' => $result['metadata']['reason'] ?? 'Package changed',
        ];
    }

    public function getAccountDetails(string $username): array
    {
        $result = $this->makeRequest('accountsummary', [
            'user' => $username,
        ]);

        if (isset($result['data']['acct'][0])) {
            $account = $result['data']['acct'][0];
            return [
                'username' => $account['user'],
                'domain' => $account['domain'],
                'email' => $account['email'],
                'disk_used' => $account['diskused'],
                'disk_limit' => $account['disklimit'],
                'suspended' => $account['suspended'] == 1,
            ];
        }

        return [];
    }

    public function testConnection(): array
    {
        $result = $this->makeRequest('version');

        if (isset($result['version'])) {
            return [
                'success' => true,
                'message' => 'Connection successful. WHM version: ' . $result['version'],
            ];
        }

        return [
            'success' => false,
            'message' => 'Connection failed',
        ];
    }
}
```

### 3. Registering Your Provisioning Module

Add to `config/numz.php`:

```php
'provisioning_modules' => [
    'cpanel' => App\Numz\Modules\Provisioning\CpanelProvisioning::class,
    'plesk' => App\Numz\Modules\Provisioning\PleskProvisioning::class,
    'directadmin' => App\Numz\Modules\Provisioning\DirectAdminProvisioning::class,
    // Add your custom module here
    'your_panel' => App\Numz\Modules\Provisioning\YourPanelProvisioning::class,
],
```

---

## Module Configuration System

NUMZ.AI uses a database-driven configuration system with automatic encryption for sensitive data.

### ModuleSetting Model

Settings are stored in the `module_settings` table:

```php
Schema::create('module_settings', function (Blueprint $table) {
    $table->id();
    $table->string('module_type'); // 'payment', 'registrar', 'provisioning'
    $table->string('module_name'); // 'stripe', 'cpanel', etc.
    $table->string('setting_key');
    $table->text('setting_value')->nullable();
    $table->boolean('is_encrypted')->default(false);
    $table->timestamps();

    $table->unique(['module_type', 'module_name', 'setting_key']);
});
```

### Helper Functions for Settings

**Retrieve Module Settings:**

```php
use App\Models\ModuleSetting;

// Get all settings for a module
$settings = ModuleSetting::where('module_type', 'payment')
    ->where('module_name', 'stripe')
    ->pluck('setting_value', 'setting_key')
    ->toArray();

// Initialize module
$gateway = new StripeGateway();
$gateway->initialize($settings);
```

**Save Module Settings:**

```php
use App\Models\ModuleSetting;

$configFields = $gateway->getConfigFields();

foreach ($request->settings as $key => $value) {
    $isEncrypted = $configFields[$key]['encrypted'] ?? false;

    ModuleSetting::updateOrCreate(
        [
            'module_type' => 'payment',
            'module_name' => 'stripe',
            'setting_key' => $key,
        ],
        [
            'setting_value' => $value,
            'is_encrypted' => $isEncrypted,
        ]
    );
}
```

**Automatic Encryption:**

The `ModuleSetting` model automatically encrypts/decrypts sensitive data:

```php
class ModuleSetting extends Model
{
    protected function settingValue(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->is_encrypted ? decrypt($value) : $value,
            set: fn ($value) => $this->is_encrypted ? encrypt($value) : $value,
        );
    }
}
```

---

## Best Practices

### 1. Error Handling

Always wrap API calls in try-catch blocks and return standardized responses:

```php
public function charge(array $params): array
{
    try {
        // API call logic
        return [
            'success' => true,
            'transaction_id' => $result->id,
            'message' => 'Payment successful',
        ];
    } catch (\Exception $e) {
        \Log::error('Payment failed: ' . $e->getMessage(), [
            'gateway' => $this->getName(),
            'params' => $params,
        ]);

        return [
            'success' => false,
            'transaction_id' => null,
            'message' => 'Payment failed: ' . $e->getMessage(),
        ];
    }
}
```

### 2. Logging

Log important events for debugging:

```php
use Illuminate\Support\Facades\Log;

Log::info('Domain registered', [
    'domain' => $domain,
    'registrar' => $this->getName(),
]);

Log::error('Provisioning failed', [
    'username' => $username,
    'error' => $e->getMessage(),
]);
```

### 3. Validation

Validate input parameters before making API calls:

```php
public function createAccount(array $params): array
{
    // Validate required parameters
    $required = ['username', 'domain', 'package', 'email'];
    foreach ($required as $field) {
        if (empty($params[$field])) {
            return [
                'success' => false,
                'message' => "Missing required field: {$field}",
            ];
        }
    }

    // Proceed with account creation
}
```

### 4. Testing Mode

Support test/sandbox modes for development:

```php
public function initialize(array $settings): void
{
    $this->testMode = (bool) ($settings['test_mode'] ?? false);
    $this->apiUrl = $this->testMode
        ? 'https://api.sandbox.example.com'
        : 'https://api.example.com';
}
```

### 5. Rate Limiting

Implement rate limiting for API calls to avoid hitting provider limits:

```php
use Illuminate\Support\Facades\Cache;

protected function checkRateLimit(): bool
{
    $key = "ratelimit:{$this->getName()}";
    $calls = Cache::get($key, 0);

    if ($calls >= 100) { // 100 calls per minute
        return false;
    }

    Cache::put($key, $calls + 1, now()->addMinute());
    return true;
}
```

### 6. Webhook Security

Always verify webhook signatures:

```php
public function verifyWebhookSignature(string $signature, string $payload): bool
{
    $computed = hash_hmac('sha256', $payload, $this->webhookSecret);
    return hash_equals($computed, $signature);
}
```

### 7. Idempotency

Implement idempotent operations where possible:

```php
public function registerDomain(array $params): array
{
    // Check if domain already registered
    $existing = DomainRegistration::where('domain', $params['domain'])->first();
    if ($existing) {
        return [
            'success' => false,
            'message' => 'Domain already registered in system',
        ];
    }

    // Proceed with registration
}
```

---

## Testing Your Module

### 1. Unit Testing

Create tests for your module:

**File**: `tests/Unit/Modules/StripeGatewayTest.php`

```php
<?php

namespace Tests\Unit\Modules;

use Tests\TestCase;
use App\Numz\Modules\PaymentGateways\StripeGateway;

class StripeGatewayTest extends TestCase
{
    protected StripeGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new StripeGateway();
        $this->gateway->initialize([
            'api_key' => env('STRIPE_TEST_KEY'),
            'test_mode' => true,
        ]);
    }

    public function test_gateway_name(): void
    {
        $this->assertEquals('stripe', $this->gateway->getName());
        $this->assertEquals('Stripe', $this->gateway->getDisplayName());
    }

    public function test_config_fields(): void
    {
        $fields = $this->gateway->getConfigFields();

        $this->assertArrayHasKey('api_key', $fields);
        $this->assertArrayHasKey('publishable_key', $fields);
        $this->assertTrue($fields['api_key']['encrypted']);
    }

    public function test_charge_with_valid_token(): void
    {
        $result = $this->gateway->charge([
            'amount' => 10.00,
            'currency' => 'usd',
            'token' => 'tok_visa', // Stripe test token
            'description' => 'Test payment',
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['transaction_id']);
    }

    public function test_charge_with_invalid_token(): void
    {
        $result = $this->gateway->charge([
            'amount' => 10.00,
            'currency' => 'usd',
            'token' => 'invalid_token',
            'description' => 'Test payment',
        ]);

        $this->assertFalse($result['success']);
    }
}
```

### 2. Integration Testing

Test the complete flow:

```php
public function test_complete_payment_flow(): void
{
    // Create test invoice
    $invoice = Invoice::factory()->create([
        'total' => 100.00,
        'status' => 'unpaid',
    ]);

    // Process payment
    $gateway = new StripeGateway();
    $gateway->initialize(['api_key' => env('STRIPE_TEST_KEY')]);

    $result = $gateway->charge([
        'amount' => $invoice->total,
        'currency' => 'usd',
        'token' => 'tok_visa',
        'description' => "Invoice #{$invoice->invoice_number}",
    ]);

    $this->assertTrue($result['success']);

    // Verify invoice status changed
    $invoice->refresh();
    $this->assertEquals('paid', $invoice->status);
}
```

### 3. Manual Testing

Use the admin panel to test your module:

1. Configure module settings
2. Test connection (if applicable)
3. Perform test transaction
4. Verify results in logs
5. Check database records

---

## Module Deployment

### 1. Directory Structure

Place your modules in the correct location:

```
app/
├── Numz/
│   ├── Modules/
│   │   ├── PaymentGateways/
│   │   │   ├── StripeGateway.php
│   │   │   ├── PayPalGateway.php
│   │   │   └── YourGateway.php
│   │   ├── Registrars/
│   │   │   ├── DomainNameAPIRegistrar.php
│   │   │   └── YourRegistrar.php
│   │   ├── Provisioning/
│   │   │   ├── CpanelProvisioning.php
│   │   │   └── YourPanelProvisioning.php
│   │   └── Integrations/
│   │       └── SocialAuthModule.php
│   └── Contracts/
│       ├── PaymentGatewayInterface.php
│       ├── RegistrarInterface.php
│       └── ProvisioningInterface.php
```

### 2. Configuration Registration

Add your module to `config/numz.php`:

```php
return [
    'payment_gateways' => [
        'your_gateway' => App\Numz\Modules\PaymentGateways\YourGateway::class,
    ],
    'registrars' => [
        'your_registrar' => App\Numz\Modules\Registrars\YourRegistrar::class,
    ],
    'provisioning_modules' => [
        'your_panel' => App\Numz\Modules\Provisioning\YourPanelProvisioning::class,
    ],
];
```

### 3. Composer Dependencies

If your module requires external packages:

```bash
composer require vendor/package
```

### 4. Database Migrations

If your module needs custom tables:

```bash
php artisan make:migration create_your_module_table
```

### 5. Documentation

Create a README for your module:

**File**: `app/Numz/Modules/YourModule/README.md`

```markdown
# Your Module Name

## Description
Brief description of what your module does.

## Requirements
- PHP 8.4+
- Laravel 12.x
- External dependencies

## Installation
1. Install via Composer: `composer require vendor/package`
2. Configure in admin panel
3. Test connection

## Configuration
- **API Key**: Your API key from provider
- **API Secret**: Your API secret
- **Test Mode**: Enable for development

## Usage
How to use the module...

## Troubleshooting
Common issues and solutions...
```

---

## Advanced Topics

### Webhooks

Handle incoming webhooks from providers:

```php
// routes/web.php
Route::post('/webhooks/{gateway}', [WebhookController::class, 'handle']);
```

```php
// app/Http/Controllers/WebhookController.php
public function handle(Request $request, string $gateway)
{
    $gatewayClass = config("numz.payment_gateways.{$gateway}");
    $gatewayInstance = new $gatewayClass();

    // Verify signature
    $signature = $request->header('X-Signature');
    if (!$gatewayInstance->verifyWebhookSignature($signature, $request->getContent())) {
        return response()->json(['error' => 'Invalid signature'], 401);
    }

    // Handle webhook
    $result = $gatewayInstance->handleWebhook($request->all());

    if ($result['success'] && isset($result['invoice_id'])) {
        $invoice = Invoice::find($result['invoice_id']);
        // Process payment...
    }

    return response()->json(['success' => true]);
}
```

### Queue Jobs

Process long-running operations in background:

```php
use Illuminate\Support\Facades\Queue;

// Dispatch provisioning job
Queue::push(new ProvisionHostingAccount($service));
```

```php
// app/Jobs/ProvisionHostingAccount.php
public function handle()
{
    $module = new CpanelProvisioning();
    $module->initialize($this->server->settings);

    $result = $module->createAccount([
        'username' => $this->service->username,
        'domain' => $this->service->domain,
        'package' => $this->service->product->package_name,
        'email' => $this->service->user->email,
    ]);

    if ($result['success']) {
        $this->service->update([
            'status' => 'active',
            'username' => $result['username'],
            'password' => encrypt($result['password']),
        ]);
    }
}
```

### Event Listeners

Listen to system events:

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    InvoicePaid::class => [
        ProvisionServices::class,
        SendInvoiceReceipt::class,
    ],
];
```

### Custom Admin Pages

Create admin pages for your module:

```php
// app/Filament/Pages/YourModuleSettings.php
class YourModuleSettings extends Page
{
    protected static string $view = 'filament.pages.your-module-settings';
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationGroup = 'Modules';
}
```

---

## Support & Resources

### Official Resources

- **Documentation**: https://docs.numz.ai
- **API Reference**: https://api.numz.ai/docs
- **Community Forum**: https://community.numz.ai
- **GitHub Repository**: https://github.com/numz-ai/numz

### Getting Help

- Open an issue on GitHub
- Join our Discord server
- Post in community forums
- Contact support@numz.ai

### Contributing

We welcome contributions! Please see `CONTRIBUTING.md` for guidelines.

---

## Module Marketplace

Once your module is ready, you can submit it to the NUMZ.AI Module Marketplace:

1. Create a repository on GitHub
2. Add comprehensive documentation
3. Include tests and examples
4. Submit to marketplace: https://marketplace.numz.ai/submit

---

## License

Modules you create are your intellectual property. You may license them as you see fit.

---

**Last Updated**: November 5, 2025
**Version**: 1.0.0
**NUMZ.AI**: The First AI Hosting Billing Software
