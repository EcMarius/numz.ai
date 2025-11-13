<?php
/**
 * Hetzner Module Configuration Example
 *
 * Copy this to your WHMCS configuration or use the admin interface
 */

return [
    // Cloud API Configuration
    'cloud' => [
        'api_token' => 'your_hetzner_cloud_api_token_here',
        'default_location' => 'fsn1', // Default datacenter
        'default_image' => 'ubuntu-22.04', // Default OS image
        'enable_ipv6' => true,
        'enable_backups' => false,
        'auto_snapshots' => false,
    ],

    // Robot API Configuration (for dedicated servers)
    'robot' => [
        'username' => 'your_robot_username',
        'password' => 'your_robot_password',
    ],

    // Module Settings
    'settings' => [
        'auto_setup_firewall' => true, // Auto-create basic firewall
        'send_welcome_email' => true, // Send email with server details
        'create_snapshot_on_upgrade' => true, // Auto snapshot before upgrades
        'cleanup_old_snapshots' => true, // Remove snapshots older than X days
        'snapshot_retention_days' => 30,
        'enable_debug_logging' => false,
    ],

    // Default Firewall Rules
    'default_firewall_rules' => [
        [
            'direction' => 'in',
            'protocol' => 'tcp',
            'port' => '22',
            'source_ips' => ['0.0.0.0/0', '::/0'],
        ],
        [
            'direction' => 'in',
            'protocol' => 'tcp',
            'port' => '80',
            'source_ips' => ['0.0.0.0/0', '::/0'],
        ],
        [
            'direction' => 'in',
            'protocol' => 'tcp',
            'port' => '443',
            'source_ips' => ['0.0.0.0/0', '::/0'],
        ],
        [
            'direction' => 'in',
            'protocol' => 'icmp',
            'source_ips' => ['0.0.0.0/0', '::/0'],
        ],
    ],

    // Email Templates
    'email_templates' => [
        'welcome' => [
            'subject' => 'Your Hetzner Server is Ready!',
            'template' => 'hetzner_welcome',
        ],
        'suspended' => [
            'subject' => 'Server Suspended',
            'template' => 'hetzner_suspended',
        ],
        'unsuspended' => [
            'subject' => 'Server Reactivated',
            'template' => 'hetzner_unsuspended',
        ],
        'upgraded' => [
            'subject' => 'Server Upgraded Successfully',
            'template' => 'hetzner_upgraded',
        ],
    ],

    // Pricing Multipliers (optional - for dynamic pricing)
    'pricing' => [
        'markup_percentage' => 20, // Add 20% markup to Hetzner prices
        'setup_fee' => 0, // One-time setup fee
        'backup_cost' => 0.20, // 20% of server cost for backups
    ],

    // Advanced Options
    'advanced' => [
        'max_retries' => 3, // API call retries
        'retry_delay' => 5, // Seconds between retries
        'timeout' => 30, // API timeout in seconds
        'verify_ssl' => true, // Verify SSL certificates
        'user_agent' => 'WHMCS-Hetzner-Module/1.0',
    ],
];
