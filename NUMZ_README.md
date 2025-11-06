# NUMZ.AI - The First AI-Powered Hosting Billing Software

NUMZ.AI is a comprehensive hosting billing platform built on Laravel, featuring native integrations with payment gateways, domain registrars, and hosting automation.

## Features

### Core Features
- Complete hosting billing and management
- Domain registration and management
- Multiple payment gateway support
- Server provisioning automation
- Client portal for service management
- WHMCS API compatibility layer
- AI-powered features (coming soon)

### Native Modules

#### Payment Gateways
- **Stripe** - Credit card processing with full refund support
- **PayPal** - PayPal payments and subscriptions
- **Paysafecard** - Prepaid voucher payments

#### Domain Registrars
- **DomainNameAPI** - Domain registration, transfer, renewal, and DNS management

#### Hosting Provisioning
- **OneProvider** - VPS and cloud server provisioning
- **cPanel** - Automated cPanel account management (via Wave)

#### Integrations
- **Tawk.to** - Live chat integration with customer data sync

### WHMCS Compatibility
NUMZ.AI includes a complete WHMCS API compatibility layer, allowing existing WHMCS integrations to work seamlessly:
- Client management APIs
- Service retrieval APIs
- Domain management APIs
- Invoice creation APIs

## Installation

1. **Install Dependencies**
```bash
composer install --ignore-platform-reqs
npm install
```

2. **Configure Environment**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Configure Database**
Edit `.env` with your database credentials, then run:
```bash
php artisan migrate
```

4. **Configure Payment Gateways**
Add your API keys to `.env`:
```env
# Stripe
STRIPE_SECRET_KEY=sk_test_xxx
STRIPE_PUBLISHABLE_KEY=pk_test_xxx

# PayPal
PAYPAL_CLIENT_ID=xxx
PAYPAL_SECRET=xxx
PAYPAL_SANDBOX=true

# Paysafecard
PAYSAFECARD_API_KEY=xxx
```

5. **Configure Registrars**
```env
# DomainNameAPI
DOMAINNAMEAPI_USERNAME=your_username
DOMAINNAMEAPI_PASSWORD=your_password
DOMAINNAMEAPI_TEST_MODE=true
```

6. **Configure Provisioning**
```env
# OneProvider
ONEPROVIDER_API_KEY=your_api_key
```

7. **Configure Integrations** 
```env
# Tawk.to
TAWKTO_PROPERTY_ID=your_property_id
TAWKTO_WIDGET_ID=your_widget_id
```

## Usage

### Creating a Hosting Service
```php
use App\Models\HostingService;
use App\Models\HostingProduct;

$product = HostingProduct::find(1);

$service = HostingService::create([
    'user_id' => auth()->id(),
    'hosting_product_id' => $product->id,
    'domain' => 'example.com',
    'billing_cycle' => 'monthly',
    'price' => $product->monthly_price,
    'status' => 'pending',
]);
```

### Processing Payments
```php
use App\Numz\Services\BillingService;
use App\Numz\Modules\PaymentGateways\StripeGateway;

$billing = new BillingService();
$stripe = new StripeGateway();

// Charge customer
$result = $stripe->charge([
    'amount' => $service->price,
    'currency' => 'USD',
    'token' => $request->stripe_token,
    'description' => "Payment for {$service->domain}",
]);

// Process payment
if ($result['success']) {
    $billing->processPayment(auth()->user(), $service, 'stripe', $result);
}
```

### Registering Domains
```php
use App\Numz\Modules\Registrars\DomainNameAPIRegistrar;

$registrar = new DomainNameAPIRegistrar();

$result = $registrar->registerDomain([
    'domain' => 'example.com',
    'years' => 1,
    'firstname' => 'John',
    'lastname' => 'Doe',
    'email' => 'john@example.com',
    'address' => '123 Main St',
    'city' => 'New York',
    'state' => 'NY',
    'postcode' => '10001',
    'country' => 'US',
    'phone' => '+1.2125551234',
    'nameservers' => ['ns1.example.com', 'ns2.example.com'],
]);
```

### Provisioning Hosting
```php
use App\Numz\Modules\Provisioning\OneProviderProvisioning;

$provisioner = new OneProviderProvisioning();

$result = $provisioner->createAccount([
    'package_id' => 'vps-basic',
    'domain' => 'example.com',
    'os' => 'ubuntu20',
    'ram' => 2,
    'storage' => 50,
    'location' => 'us-east',
]);
```

## WHMCS API Compatibility

Access WHMCS-compatible APIs at `/api/whmcs/*`:

```php
// Get client details
POST /api/whmcs/client/details
{
    "clientid": 123
}

// Get client services
POST /api/whmcs/client/services
{
    "clientid": 123
}

// Get client domains
POST /api/whmcs/client/domains
{
    "clientid": 123
}
```

## Module Architecture

All modules follow a consistent interface pattern:

- **Payment Gateways** implement `PaymentGatewayInterface`
- **Registrars** implement `RegistrarInterface`
- **Provisioning** implements `ProvisioningInterface`

This allows for easy extension and customization.

## Configuration

All NUMZ.AI settings are in `config/numz.php`:

```php
return [
    'gateways' => [...],
    'registrars' => [...],
    'provisioning' => [...],
    'integrations' => [...],
    'billing' => [
        'currency' => 'USD',
        'invoice_prefix' => 'INV',
        'due_days' => 14,
    ],
];
```

## Cron Jobs

Setup automated billing tasks:

```bash
# Generate recurring invoices
* * * * * php artisan numz:generate-invoices

# Suspend overdue services  
0 0 * * * php artisan numz:suspend-overdue

# Terminate long-overdue services
0 0 * * * php artisan numz:terminate-overdue
```

## Support

For issues and questions, please contact support or visit the documentation.

## License

Proprietary - NUMZ.AI

---

**Powered by Laravel | Built for Hosting Providers**
