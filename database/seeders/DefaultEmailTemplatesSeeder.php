<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class DefaultEmailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Billing Templates
            [
                'name' => 'Invoice Created',
                'slug' => 'invoice-created',
                'category' => 'billing',
                'subject' => 'New Invoice {{ invoice.number }} - Due {{ invoice.due_date }}',
                'html_body' => '<h2>Invoice {{ invoice.number }}</h2><p>Dear {{ user.name }},</p><p>A new invoice has been generated for your account.</p><p><strong>Amount:</strong> {{ invoice.total }}</p><p><strong>Due Date:</strong> {{ invoice.due_date }}</p><p><a href="{{ invoice.url }}">View Invoice</a></p>',
                'text_body' => 'Invoice {{ invoice.number }}\n\nDear {{ user.name }},\n\nA new invoice has been generated for your account.\n\nAmount: {{ invoice.total }}\nDue Date: {{ invoice.due_date }}\n\nView Invoice: {{ invoice.url }}',
                'available_variables' => ['user.name', 'user.email', 'invoice.number', 'invoice.total', 'invoice.due_date', 'invoice.url'],
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Payment Received',
                'slug' => 'payment-received',
                'category' => 'billing',
                'subject' => 'Payment Received - Invoice {{ invoice.number }}',
                'html_body' => '<h2>Payment Confirmation</h2><p>Dear {{ user.name }},</p><p>We have received your payment of {{ payment.amount }} for invoice {{ invoice.number }}.</p><p>Thank you for your payment!</p>',
                'text_body' => 'Payment Confirmation\n\nDear {{ user.name }},\n\nWe have received your payment of {{ payment.amount }} for invoice {{ invoice.number }}.\n\nThank you for your payment!',
                'available_variables' => ['user.name', 'invoice.number', 'payment.amount', 'payment.date'],
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Invoice Overdue',
                'slug' => 'invoice-overdue',
                'category' => 'billing',
                'subject' => 'Overdue Invoice Reminder - {{ invoice.number }}',
                'html_body' => '<h2>Invoice Overdue</h2><p>Dear {{ user.name }},</p><p>Your invoice {{ invoice.number }} is now overdue. Please pay as soon as possible to avoid service suspension.</p><p><strong>Amount Due:</strong> {{ invoice.total }}</p><p><a href="{{ invoice.url }}">Pay Now</a></p>',
                'text_body' => 'Invoice Overdue\n\nDear {{ user.name }},\n\nYour invoice {{ invoice.number }} is now overdue. Please pay as soon as possible to avoid service suspension.\n\nAmount Due: {{ invoice.total }}\n\nPay Now: {{ invoice.url }}',
                'available_variables' => ['user.name', 'invoice.number', 'invoice.total', 'invoice.due_date', 'invoice.url'],
                'is_active' => true,
                'is_system' => true,
            ],

            // Service Templates
            [
                'name' => 'Service Activated',
                'slug' => 'service-activated',
                'category' => 'system',
                'subject' => 'Your {{ service.name }} Service is Now Active',
                'html_body' => '<h2>Service Activated</h2><p>Dear {{ user.name }},</p><p>Your {{ service.name }} service has been activated successfully.</p><p><strong>Service Details:</strong></p><ul><li>Domain: {{ service.domain }}</li><li>Package: {{ service.package }}</li></ul><p><a href="{{ service.url }}">Manage Service</a></p>',
                'text_body' => 'Service Activated\n\nDear {{ user.name }},\n\nYour {{ service.name }} service has been activated successfully.\n\nService Details:\n- Domain: {{ service.domain }}\n- Package: {{ service.package }}\n\nManage Service: {{ service.url }}',
                'available_variables' => ['user.name', 'service.name', 'service.domain', 'service.package', 'service.url'],
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Service Suspended',
                'slug' => 'service-suspended',
                'category' => 'system',
                'subject' => 'Service Suspended - {{ service.name }}',
                'html_body' => '<h2>Service Suspended</h2><p>Dear {{ user.name }},</p><p>Your {{ service.name }} service has been suspended due to non-payment.</p><p>Please pay your outstanding invoices to restore service.</p><p><a href="{{ invoices.url }}">View Invoices</a></p>',
                'text_body' => 'Service Suspended\n\nDear {{ user.name }},\n\nYour {{ service.name }} service has been suspended due to non-payment.\n\nPlease pay your outstanding invoices to restore service.\n\nView Invoices: {{ invoices.url }}',
                'available_variables' => ['user.name', 'service.name', 'service.domain', 'invoices.url'],
                'is_active' => true,
                'is_system' => true,
            ],

            // Support Templates
            [
                'name' => 'Ticket Opened',
                'slug' => 'ticket-opened',
                'category' => 'support',
                'subject' => 'Ticket #{{ ticket.number }} Opened',
                'html_body' => '<h2>Support Ticket Opened</h2><p>Dear {{ user.name }},</p><p>Your support ticket has been created.</p><p><strong>Ticket #:</strong> {{ ticket.number }}</p><p><strong>Subject:</strong> {{ ticket.subject }}</p><p><a href="{{ ticket.url }}">View Ticket</a></p>',
                'text_body' => 'Support Ticket Opened\n\nDear {{ user.name }},\n\nYour support ticket has been created.\n\nTicket #: {{ ticket.number }}\nSubject: {{ ticket.subject }}\n\nView Ticket: {{ ticket.url }}',
                'available_variables' => ['user.name', 'ticket.number', 'ticket.subject', 'ticket.url'],
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Ticket Reply',
                'slug' => 'ticket-reply',
                'category' => 'support',
                'subject' => 'New Reply to Ticket #{{ ticket.number }}',
                'html_body' => '<h2>New Ticket Reply</h2><p>Dear {{ user.name }},</p><p>A new reply has been added to your support ticket.</p><p><strong>Ticket #:</strong> {{ ticket.number }}</p><p><a href="{{ ticket.url }}">View Reply</a></p>',
                'text_body' => 'New Ticket Reply\n\nDear {{ user.name }},\n\nA new reply has been added to your support ticket.\n\nTicket #: {{ ticket.number }}\n\nView Reply: {{ ticket.url }}',
                'available_variables' => ['user.name', 'ticket.number', 'ticket.subject', 'ticket.url'],
                'is_active' => true,
                'is_system' => true,
            ],

            // Account Templates
            [
                'name' => 'Welcome Email',
                'slug' => 'welcome-email',
                'category' => 'system',
                'subject' => 'Welcome to {{ company.name }}!',
                'html_body' => '<h2>Welcome!</h2><p>Dear {{ user.name }},</p><p>Thank you for registering with {{ company.name }}.</p><p>Your account has been created successfully.</p><p><a href="{{ login.url }}">Login to Your Account</a></p>',
                'text_body' => 'Welcome!\n\nDear {{ user.name }},\n\nThank you for registering with {{ company.name }}.\n\nYour account has been created successfully.\n\nLogin to Your Account: {{ login.url }}',
                'available_variables' => ['user.name', 'user.email', 'company.name', 'login.url'],
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Password Reset',
                'slug' => 'password-reset',
                'category' => 'system',
                'subject' => 'Password Reset Request',
                'html_body' => '<h2>Password Reset</h2><p>Dear {{ user.name }},</p><p>You have requested to reset your password.</p><p><a href="{{ reset.url }}">Reset Password</a></p><p>This link will expire in {{ reset.expiry }} minutes.</p>',
                'text_body' => 'Password Reset\n\nDear {{ user.name }},\n\nYou have requested to reset your password.\n\nReset Password: {{ reset.url }}\n\nThis link will expire in {{ reset.expiry }} minutes.',
                'available_variables' => ['user.name', 'reset.url', 'reset.expiry'],
                'is_active' => true,
                'is_system' => true,
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}
