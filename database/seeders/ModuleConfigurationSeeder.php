<?php

namespace Database\Seeders;

use App\Models\ModuleConfiguration;
use Illuminate\Database\Seeder;

class ModuleConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            // Payment Gateways
            [
                'module_type' => 'payment_gateway',
                'module_name' => 'stripe',
                'display_name' => 'Stripe',
                'description' => 'Accept credit cards, debit cards, and other payment methods with Stripe.',
                'is_enabled' => false,
                'capabilities' => ['cards', 'subscriptions', 'refunds', 'webhooks', 'test_mode'],
                'required_fields' => ['secret_key', 'publishable_key'],
                'sort_order' => 1,
            ],
            [
                'module_type' => 'payment_gateway',
                'module_name' => 'paypal',
                'display_name' => 'PayPal',
                'description' => 'Accept payments via PayPal, credit cards, and PayPal Credit.',
                'is_enabled' => false,
                'capabilities' => ['paypal', 'cards', 'refunds', 'webhooks', 'test_mode'],
                'required_fields' => ['client_id', 'secret'],
                'sort_order' => 2,
            ],
            [
                'module_type' => 'payment_gateway',
                'module_name' => 'authorize_net',
                'display_name' => 'Authorize.Net',
                'description' => 'Accept credit card payments with Authorize.Net payment gateway.',
                'is_enabled' => false,
                'capabilities' => ['cards', 'refunds', 'test_mode'],
                'required_fields' => ['api_login_id', 'transaction_key'],
                'sort_order' => 3,
            ],
            [
                'module_type' => 'payment_gateway',
                'module_name' => 'square',
                'display_name' => 'Square',
                'description' => 'Accept payments with Square - modern payment processing for businesses.',
                'is_enabled' => false,
                'capabilities' => ['cards', 'refunds', 'test_mode'],
                'required_fields' => ['access_token', 'location_id'],
                'sort_order' => 4,
            ],
            [
                'module_type' => 'payment_gateway',
                'module_name' => 'mollie',
                'display_name' => 'Mollie',
                'description' => 'Accept payments across Europe with Mollie - supports 20+ payment methods.',
                'is_enabled' => false,
                'capabilities' => ['cards', 'ideal', 'bancontact', 'refunds', 'webhooks', 'test_mode'],
                'required_fields' => ['api_key'],
                'sort_order' => 5,
            ],
            [
                'module_type' => 'payment_gateway',
                'module_name' => 'razorpay',
                'display_name' => 'Razorpay',
                'description' => 'Accept payments in India with Razorpay - UPI, cards, wallets and more.',
                'is_enabled' => false,
                'capabilities' => ['cards', 'upi', 'wallets', 'subscriptions', 'refunds', 'webhooks', 'test_mode'],
                'required_fields' => ['key_id', 'key_secret'],
                'sort_order' => 6,
            ],
            [
                'module_type' => 'payment_gateway',
                'module_name' => 'coinbase',
                'display_name' => 'Coinbase Commerce',
                'description' => 'Accept cryptocurrency payments with Coinbase - Bitcoin, Ethereum, USDC and more.',
                'is_enabled' => false,
                'capabilities' => ['crypto', 'webhooks'],
                'required_fields' => ['api_key'],
                'sort_order' => 7,
            ],
            [
                'module_type' => 'payment_gateway',
                'module_name' => 'paysafecard',
                'display_name' => 'Paysafecard',
                'description' => 'Accept prepaid payments with Paysafecard.',
                'is_enabled' => false,
                'capabilities' => ['prepaid', 'test_mode'],
                'required_fields' => ['api_key'],
                'sort_order' => 8,
            ],
            [
                'module_type' => 'payment_gateway',
                'module_name' => '2checkout',
                'display_name' => '2Checkout (Verifone)',
                'description' => 'Global payment processing with 2Checkout.',
                'is_enabled' => false,
                'capabilities' => ['cards', 'paypal', 'refunds'],
                'required_fields' => ['merchant_code', 'secret_key'],
                'sort_order' => 9,
            ],

            // Provisioning Modules
            [
                'module_type' => 'provisioning',
                'module_name' => 'cpanel',
                'display_name' => 'cPanel/WHM',
                'description' => 'Automatically provision and manage cPanel hosting accounts.',
                'is_enabled' => false,
                'capabilities' => ['create_account', 'suspend', 'unsuspend', 'terminate', 'change_package', 'change_password'],
                'required_fields' => ['hostname', 'username', 'access_key'],
                'sort_order' => 1,
            ],
            [
                'module_type' => 'provisioning',
                'module_name' => 'plesk',
                'display_name' => 'Plesk',
                'description' => 'Automatically provision and manage Plesk hosting accounts.',
                'is_enabled' => false,
                'capabilities' => ['create_account', 'suspend', 'unsuspend', 'terminate', 'change_package'],
                'required_fields' => ['hostname', 'username', 'password'],
                'sort_order' => 2,
            ],
            [
                'module_type' => 'provisioning',
                'module_name' => 'directadmin',
                'display_name' => 'DirectAdmin',
                'description' => 'Automatically provision and manage DirectAdmin hosting accounts.',
                'is_enabled' => false,
                'capabilities' => ['create_account', 'suspend', 'unsuspend', 'terminate'],
                'required_fields' => ['hostname', 'username', 'password'],
                'sort_order' => 3,
            ],
            [
                'module_type' => 'provisioning',
                'module_name' => 'oneprovider',
                'display_name' => 'OneProvider',
                'description' => 'Provision dedicated servers and VPS with OneProvider.',
                'is_enabled' => false,
                'capabilities' => ['create_server', 'terminate', 'reboot'],
                'required_fields' => ['api_key'],
                'sort_order' => 4,
            ],

            // Domain Registrars
            [
                'module_type' => 'registrar',
                'module_name' => 'domainnameapi',
                'display_name' => 'DomainNameAPI',
                'description' => 'Register and manage domains through DomainNameAPI.',
                'is_enabled' => false,
                'capabilities' => ['register', 'transfer', 'renew', 'whois_privacy', 'nameservers'],
                'required_fields' => ['username', 'password'],
                'sort_order' => 1,
            ],

            // Integrations
            [
                'module_type' => 'integration',
                'module_name' => 'tawkto',
                'display_name' => 'Tawk.to',
                'description' => 'Free live chat widget for customer support.',
                'is_enabled' => false,
                'capabilities' => ['live_chat'],
                'required_fields' => ['property_id', 'widget_id'],
                'sort_order' => 1,
            ],
            [
                'module_type' => 'integration',
                'module_name' => 'slack',
                'display_name' => 'Slack',
                'description' => 'Send notifications to Slack channels.',
                'is_enabled' => false,
                'capabilities' => ['notifications', 'webhooks'],
                'required_fields' => ['webhook_url'],
                'sort_order' => 2,
            ],
            [
                'module_type' => 'integration',
                'module_name' => 'google_analytics',
                'display_name' => 'Google Analytics',
                'description' => 'Track website analytics with Google Analytics.',
                'is_enabled' => false,
                'capabilities' => ['analytics'],
                'required_fields' => ['tracking_id'],
                'sort_order' => 3,
            ],
        ];

        foreach ($modules as $module) {
            ModuleConfiguration::updateOrCreate(
                [
                    'module_type' => $module['module_type'],
                    'module_name' => $module['module_name'],
                ],
                $module
            );
        }

        $this->command->info('Module configurations seeded successfully!');
    }
}
