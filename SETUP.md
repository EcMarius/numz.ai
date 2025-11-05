# NUMZ.AI - Setup & Documentation

**The First AI-Powered Hosting Billing Software**

NUMZ.AI is a modern, Laravel 12-based hosting billing and client management platform designed to replace WHMCS with enhanced features, AI capabilities, and full backwards compatibility.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Module System](#module-system)
- [WHMCS Compatibility](#whmcs-compatibility)
- [Payment Gateways](#payment-gateways)
- [Server Modules](#server-modules)
- [API](#api)
- [Cron Jobs](#cron-jobs)

## Features

### Core Features
- Complete billing system with invoicing, recurring billing, and prorata calculations
- Client management with full customer portal
- Product catalog system for hosting, domains, servers, and custom products
- Multiple payment gateway support (Stripe, PayPal, Paysafecard, and more)
- Server provisioning automation (cPanel, Plesk, DirectAdmin, OneProvider)
- Domain registrar integration (DomainNameAPI)
- Support ticket system
- Comprehensive reporting and analytics
- RESTful API for third-party integrations
- Role-based access control with Laravel Sanctum

### WHMCS Compatibility
- **Module Compatibility**: Drop-in support for WHMCS modules
- **API Compatibility**: WHMCS API command support via compatibility layer
- **Theme Compatibility**: Support for WHMCS themes
- **Function Compatibility**: Common WHMCS functions available

### AI Features (Planned)
- Smart pricing recommendations
- Fraud detection and prevention
- Automated customer support
- Predictive analytics

## Requirements

- PHP 8.2 or higher
- MySQL 8.0+ or PostgreSQL 13+
- Composer 2.x
- Node.js & NPM (for asset compilation)
- Web server (Apache/Nginx)

### PHP Extensions
- PDO
- OpenSSL
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- cURL

## Installation

1. **Clone the repository**
   ```bash
   cd /path/to/webroot
   git clone <repository-url> numz.ai
   cd numz.ai
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Edit .env file**
   ```env
   APP_NAME="NUMZ.AI"
   APP_URL=https://yourdomain.com
   
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=numz_ai
   DB_USERNAME=root
   DB_PASSWORD=yourpassword
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed database (optional)**
   ```bash
   php artisan db:seed
   ```

7. **Build assets**
   ```bash
   npm run build
   ```

## Database Setup

### Schema Overview

The database includes the following main tables:

- **clients**: Customer information
- **products**: Hosting packages, domains, etc.
- **product_groups**: Product categories
- **services**: Active customer services
- **invoices**: Billing invoices
- **invoice_items**: Line items on invoices
- **transactions**: Payment transactions
- **payment_gateways**: Configured payment methods
- **servers**: Server configurations
- **tickets**: Support tickets
- **ticket_replies**: Ticket responses
- **domains**: Domain registrations

## Module System

### Server Modules

Server modules handle provisioning and management of hosting accounts.

#### Creating a Server Module

1. Create module directory:
   ```bash
   mkdir -p modules/servers/mymodule
   ```

2. Create module file `modules/servers/mymodule/mymodule.php`:
   ```php
   <?php
   function mymodule_MetaData() {
       return [
           'DisplayName' => 'My Module',
           'APIVersion' => '1.1',
           'RequiresServer' => true,
       ];
   }

   function mymodule_ConfigOptions() {
       return [
           'Package Name' => [
               'Type' => 'text',
               'Size' => '25',
           ],
       ];
   }

   function mymodule_CreateAccount($params) {
       // Provision account
       return 'success';
   }

   function mymodule_SuspendAccount($params) {
       // Suspend account
       return 'success';
   }

   function mymodule_TerminateAccount($params) {
       // Terminate account
       return 'success';
   }
   ```

#### Available Server Modules

- **cPanel/WHM** (`modules/servers/cpanel`)
- **OneProvider** (`modules/servers/oneprovider`)

### Payment Gateway Modules

#### Creating a Payment Gateway

1. Create gateway file `modules/gateways/mygateway.php`:
   ```php
   <?php
   function mygateway_MetaData() {
       return [
           'DisplayName' => 'My Gateway',
           'APIVersion' => '1.1',
       ];
   }

   function mygateway_config() {
       return [
           'FriendlyName' => [
               'Type' => 'System',
               'Value' => 'My Payment Gateway',
           ],
           'apiKey' => [
               'FriendlyName' => 'API Key',
               'Type' => 'password',
           ],
       ];
   }

   function mygateway_capture($params) {
       // Process payment
       return [
           'status' => 'success',
           'transid' => '123456',
       ];
   }
   ```

#### Available Payment Gateways

- **Stripe** (`modules/gateways/stripe.php`)
- **PayPal** (`modules/gateways/paypal.php`)
- **Paysafecard** (`modules/gateways/paysafecard.php`)

### Domain Registrar Modules

#### Available Registrars

- **DomainNameAPI** (`modules/registrars/domainnameapi`)

### Addon Modules

#### Available Addons

- **Tawk.to Live Chat** (`modules/addons/tawkto`)

## WHMCS Compatibility

### Using WHMCS Modules

NUMZ.AI provides full compatibility with WHMCS modules through a compatibility layer.

1. **Copy WHMCS module** to the appropriate directory:
   - Server modules: `modules/servers/`
   - Payment gateways: `modules/gateways/`
   - Registrars: `modules/registrars/`

2. **Module will work automatically** - No modifications needed!

### Available WHMCS Functions

The following WHMCS functions are available:

```php
// API Functions
localAPI($command, $postData, $adminUser);

// Payment Functions
logTransaction($gateway, $data, $result);
addInvoicePayment($invoiceId, $transactionId, $amount, $fees, $gateway);

// Client Functions
getClientDetails($clientId);

// Email Functions
sendMessage($template, $clientId, $customVars);

// Encryption Functions
encryptPassword($password);
decryptPassword($encrypted);

// Module Functions
getGatewayVariables($gateway);
getRegistrarConfigOptions($registrar);
```

### WHMCS API Compatibility

Access the WHMCS-compatible API at:

```
POST /whmcs-compat/api
```

Parameters:
- `action`: API command (e.g., GetClients, GetInvoice, CreateInvoice)
- Additional parameters as required by the command

Example:
```php
$result = localAPI('GetClients', ['search' => 'john@example.com']);
```

## Payment Gateways

### Stripe Setup

1. Get API keys from Stripe Dashboard
2. Configure in Admin > Payment Gateways
3. Add publishable and secret keys

### PayPal Setup

1. Get PayPal business email
2. Configure in Admin > Payment Gateways
3. Set up IPN notifications

### Paysafecard Setup

1. Get API key from Paysafecard
2. Configure environment (test/production)
3. Set up webhook URL

## Server Modules

### cPanel/WHM Module

Fully automated cPanel account management.

**Features:**
- Account creation
- Account suspension/unsuspension
- Account termination
- Password changes
- Package management

**Configuration:**
- WHM API access hash
- Server IP and port
- SSL/TLS support

### OneProvider Module

Cloud VPS provisioning integration.

**Features:**
- Server creation
- Suspend/unsuspend
- Termination
- Multiple OS options
- Resource configuration

## API

NUMZ.AI provides a RESTful API using Laravel Sanctum.

### Authentication

```php
$token = $user->createToken('api-token')->plainTextToken;
```

### Endpoints

- `GET /api/clients` - List clients
- `GET /api/invoices` - List invoices
- `POST /api/invoices` - Create invoice
- `GET /api/services` - List services
- `POST /api/services` - Create service

## Cron Jobs

Set up the following cron jobs:

```bash
# Process recurring invoices (daily)
0 0 * * * php /path/to/numz.ai/artisan billing:generate-invoices

# Suspend overdue services (daily)
0 2 * * * php /path/to/numz.ai/artisan billing:suspend-overdue

# Laravel scheduler (required - runs every minute)
* * * * * php /path/to/numz.ai/artisan schedule:run >> /dev/null 2>&1
```

## Directory Structure

```
numz.ai/
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/           # Admin panel controllers
│   │   └── Client/          # Client area controllers
│   ├── Models/              # Eloquent models
│   ├── Services/            # Business logic services
│   └── WHMCS/               # WHMCS compatibility layer
│       ├── ApiCompat.php    # API compatibility
│       ├── ModuleCompat.php # Module compatibility
│       └── Helpers/         # Helper functions
├── modules/
│   ├── servers/             # Server provisioning modules
│   ├── gateways/            # Payment gateway modules
│   ├── registrars/          # Domain registrar modules
│   └── addons/              # Addon modules
├── database/
│   └── migrations/          # Database migrations
└── routes/
    ├── web.php              # Web routes
    └── api.php              # API routes
```

## Support

For issues and questions:
- GitHub Issues: <repository-url>/issues
- Documentation: https://docs.numz.ai

## License

Proprietary - NUMZ.AI

---

Built with Laravel 12 | Powered by AI
