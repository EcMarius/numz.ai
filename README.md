# NUMZ.AI

**The First AI-Powered Hosting Billing Software**

NUMZ.AI is a modern, Laravel-based hosting billing and client management platform designed to replace WHMCS with enhanced features, AI capabilities, and full backwards compatibility.

## Features

- **Complete Billing System**: Invoicing, recurring billing, prorata calculations, and payment processing
- **Multiple Payment Gateways**: Stripe, PayPal, and more
- **Client Management**: Full customer portal with service management
- **Product Catalog**: Hosting, domains, servers, and custom products
- **Module System**: Native modules with WHMCS compatibility layer
- **Theme System**: Modern themes with WHMCS template support
- **Server Provisioning**: cPanel, Plesk, DirectAdmin integration
- **Domain Management**: Registration, transfer, and management
- **Support System**: Ticketing and knowledge base
- **AI Features**: Smart pricing, fraud detection, automated support
- **API**: RESTful API for third-party integrations
- **Reporting**: Comprehensive analytics and reporting

## Requirements

- PHP 8.2 or higher
- MySQL 8.0+ or PostgreSQL 13+
- Composer
- Node.js & NPM

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

## License

Proprietary - NUMZ.AI

## About

Built with Laravel 12, NUMZ.AI combines the power of modern PHP with AI capabilities to deliver the most advanced hosting billing platform available.
