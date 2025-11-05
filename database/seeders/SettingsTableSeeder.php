<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     */
    public function run(): void
    {
        // Use updateOrInsert for idempotency instead of delete + insert
        $settings = [
            0 => [
                'id' => 1,
                'key' => 'site.title',
                'display_name' => 'Site Title',
                'value' => 'EvenLeads',
                'details' => '',
                'type' => 'text',
                'order' => 1,
                'group' => 'Site',
            ],
            1 => [
                'id' => 2,
                'key' => 'site.description',
                'display_name' => 'Site Description',
                'value' => 'Lead Management Platform by SoftGala',
                'details' => '',
                'type' => 'text',
                'order' => 2,
                'group' => 'Site',
            ],
            2 => [
                'id' => 3,
                'key' => 'site.logo',
                'display_name' => 'Site Logo',
                'value' => '/images/logos/logo-black.svg',
                'details' => 'Main logo',
                'type' => 'image',
                'order' => 3,
                'group' => 'Site',
            ],
            3 => [
                'id' => 4,
                'key' => 'site.logo_dark',
                'display_name' => 'Site Logo Dark (for Light Mode)',
                'value' => '/images/logos/logo-black.svg',
                'details' => 'Dark/black logo used in light mode',
                'type' => 'image',
                'order' => 4,
                'group' => 'Site',
            ],
            4 => [
                'id' => 5,
                'key' => 'site.logo_white',
                'display_name' => 'Site Logo White (for Dark Mode)',
                'value' => '/images/logos/logo-white.svg',
                'details' => 'White logo used in dark mode',
                'type' => 'image',
                'order' => 5,
                'group' => 'Site',
            ],
            5 => [
                'id' => 6,
                'key' => 'site.mini_logo',
                'display_name' => 'Mini Logo',
                'value' => '/images/logos/mini-logo-black.svg',
                'details' => 'Small logo/icon for compact views',
                'type' => 'image',
                'order' => 6,
                'group' => 'Site',
            ],
            6 => [
                'id' => 7,
                'key' => 'site.mini_logo_dark',
                'display_name' => 'Mini Logo (Dark Mode)',
                'value' => '/images/logos/mini-logo-white.svg',
                'details' => 'Small logo/icon for compact views in dark mode',
                'type' => 'image',
                'order' => 7,
                'group' => 'Site',
            ],
            7 => [
                'id' => 8,
                'key' => 'site.favicon',
                'display_name' => 'Favicon',
                'value' => '/images/logos/mini-logo-black.svg',
                'details' => 'Favicon for the website',
                'type' => 'image',
                'order' => 8,
                'group' => 'Site',
            ],
            8 => [
                'id' => 9,
                'key' => 'site.favicon_dark',
                'display_name' => 'Favicon (Dark Mode)',
                'value' => '/images/logos/mini-logo-white.svg',
                'details' => 'Favicon for dark mode',
                'type' => 'image',
                'order' => 9,
                'group' => 'Site',
            ],
            9 => [
                'id' => 10,
                'key' => 'site.default_profile_photo',
                'display_name' => 'Default Profile Photo',
                'value' => '/storage/settings/01K7BW63A7BXEW13A1SHM81RWM.png',
                'details' => 'Default profile photo for users without avatar',
                'type' => 'image',
                'order' => 10,
                'group' => 'Site',
            ],
            10 => [
                'id' => 11,
                'key' => 'site.google_analytics_tracking_id',
                'display_name' => 'Google Analytics Tracking ID',
                'value' => null,
                'details' => '',
                'type' => 'text',
                'order' => 11,
                'group' => 'Site',
            ],
            11 => [
                'id' => 12,
                'key' => 'site.404_image',
                'display_name' => '404 Error Page Image',
                'value' => null,
                'details' => 'Custom image for 404 error page (optional, uses default illustration if not set)',
                'type' => 'image',
                'order' => 12,
                'group' => 'Site',
            ],
            12 => [
                'id' => 13,
                'key' => 'site.currency',
                'display_name' => 'Currency',
                'value' => 'EUR',
                'details' => 'Currency symbol or code to display for prices (e.g., EUR, USD, $, €)',
                'type' => 'text',
                'order' => 13,
                'group' => 'Site',
            ],
            13 => [
                'id' => 14,
                'key' => 'site.currency_position',
                'display_name' => 'Currency Position',
                'value' => 'append',
                'details' => 'Position of currency symbol: prepend (before price) or append (after price)',
                'type' => 'text',
                'order' => 14,
                'group' => 'Site',
            ],
            14 => [
                'id' => 15,
                'key' => 'site.currency_format',
                'display_name' => 'Currency Format',
                'value' => 'symbol',
                'details' => 'Display as symbol (€, $) or code (EUR, USD)',
                'type' => 'text',
                'order' => 15,
                'group' => 'Site',
            ],
            15 => [
                'id' => 16,
                'key' => 'api.scribe_auth_key',
                'display_name' => 'API Documentation Test Key',
                'value' => '',
                'details' => 'API key used for testing endpoints in the API documentation. Create one from Settings > API page.',
                'type' => 'text',
                'order' => 1,
                'group' => 'API',
            ],
            16 => [
                'id' => 17,
                'key' => 'company_name',
                'display_name' => 'Company Name',
                'value' => 'SoftGala SRL',
                'details' => 'Legal company name',
                'type' => 'text',
                'order' => 1,
                'group' => 'Company',
            ],
            17 => [
                'id' => 18,
                'key' => 'company_address',
                'display_name' => 'Company Address',
                'value' => 'Strada Exemple nr. 123, București, Romania',
                'details' => 'Company address',
                'type' => 'textarea',
                'order' => 2,
                'group' => 'Company',
            ],
            18 => [
                'id' => 19,
                'key' => 'company_registration_code',
                'display_name' => 'Company Registration Code',
                'value' => 'RO12345678',
                'details' => 'Company registration code / CUI',
                'type' => 'text',
                'order' => 3,
                'group' => 'Company',
            ],
            19 => [
                'id' => 20,
                'key' => 'company_email',
                'display_name' => 'Company Email',
                'value' => 'contact@softgala.com',
                'details' => 'Official company contact email',
                'type' => 'text',
                'order' => 4,
                'group' => 'Company',
            ],
            20 => [
                'id' => 21,
                'key' => 'social_facebook',
                'display_name' => 'Facebook URL',
                'value' => '',
                'details' => 'Facebook page URL',
                'type' => 'text',
                'order' => 1,
                'group' => 'Social Media',
            ],
            21 => [
                'id' => 22,
                'key' => 'social_instagram',
                'display_name' => 'Instagram URL',
                'value' => '',
                'details' => 'Instagram profile URL',
                'type' => 'text',
                'order' => 2,
                'group' => 'Social Media',
            ],
            22 => [
                'id' => 23,
                'key' => 'social_twitter',
                'display_name' => 'Twitter URL',
                'value' => '',
                'details' => 'Twitter profile URL',
                'type' => 'text',
                'order' => 3,
                'group' => 'Social Media',
            ],
            23 => [
                'id' => 24,
                'key' => 'social_linkedin',
                'display_name' => 'LinkedIn URL',
                'value' => '',
                'details' => 'LinkedIn page URL',
                'type' => 'text',
                'order' => 4,
                'group' => 'Social Media',
            ],
            24 => [
                'id' => 25,
                'key' => 'social_github',
                'display_name' => 'GitHub URL',
                'value' => '',
                'details' => 'GitHub profile URL',
                'type' => 'text',
                'order' => 5,
                'group' => 'Social Media',
            ],
            25 => [
                'id' => 26,
                'key' => 'legal.privacy_policy',
                'display_name' => 'Privacy Policy',
                'value' => '<h1>Privacy Policy</h1>

<p><strong>Effective Date:</strong> January 1, 2025<br>
<strong>Last Updated:</strong> January 1, 2025</p>

<p>{{ company_name }} ("we," "us," or "our") operates the EvenLeads platform (the "Service"). This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our Service.</p>

<h2>1. Information We Collect</h2>

<h3>1.1 Information You Provide</h3>
<ul>
<li><strong>Account Information:</strong> Name, email address, password, company name, and billing information</li>
<li><strong>Profile Information:</strong> Optional profile details and preferences</li>
<li><strong>Payment Information:</strong> Credit card details and billing address (processed securely through Stripe)</li>
<li><strong>Communication Data:</strong> Messages, feedback, and support requests</li>
</ul>

<h3>1.2 Information Automatically Collected</h3>
<ul>
<li><strong>Usage Data:</strong> Pages visited, features used, time spent, and interaction patterns</li>
<li><strong>Device Information:</strong> IP address, browser type, operating system, device identifiers</li>
<li><strong>Cookies and Tracking:</strong> Session data, preferences, and analytics information</li>
<li><strong>Log Data:</strong> Access times, error logs, and system activity</li>
</ul>

<h3>1.3 Lead Generation Data</h3>
<ul>
<li><strong>Campaign Data:</strong> Search criteria, keywords, and campaign configurations</li>
<li><strong>Lead Information:</strong> Publicly available social media posts and user data collected through our Service</li>
<li><strong>Integration Data:</strong> Data from connected platforms (Reddit, Facebook, etc.)</li>
</ul>

<h2>2. How We Use Your Information</h2>

<p>We use the collected information for:</p>
<ul>
<li><strong>Service Provision:</strong> Operating, maintaining, and improving the Service</li>
<li><strong>Account Management:</strong> Creating and managing your account, authentication, and personalization</li>
<li><strong>Lead Generation:</strong> Processing campaigns, retrieving leads, and AI-powered analysis</li>
<li><strong>Payment Processing:</strong> Billing, subscription management, and transaction processing</li>
<li><strong>Communications:</strong> Sending service updates, newsletters, and marketing materials (with consent)</li>
<li><strong>Analytics:</strong> Understanding usage patterns and improving features</li>
<li><strong>Security:</strong> Detecting fraud, preventing abuse, and ensuring platform security</li>
<li><strong>Legal Compliance:</strong> Complying with legal obligations and enforcing our Terms</li>
</ul>

<h2>3. Legal Basis for Processing (GDPR)</h2>

<p>For users in the European Economic Area (EEA), we process your data based on:</p>
<ul>
<li><strong>Contract Performance:</strong> Processing necessary to provide the Service</li>
<li><strong>Consent:</strong> Where you have given explicit consent</li>
<li><strong>Legitimate Interests:</strong> For service improvement, security, and analytics</li>
<li><strong>Legal Obligations:</strong> Compliance with applicable laws and regulations</li>
</ul>

<h2>4. Data Sharing and Disclosure</h2>

<h3>4.1 Third-Party Service Providers</h3>
<p>We share data with trusted partners who assist in operating our Service:</p>
<ul>
<li><strong>Payment Processing:</strong> Stripe (for secure payment handling)</li>
<li><strong>Cloud Hosting:</strong> AWS or similar providers (for data storage and computing)</li>
<li><strong>Email Services:</strong> For transactional and marketing emails</li>
<li><strong>Analytics:</strong> Google Analytics and similar tools</li>
<li><strong>AI Services:</strong> OpenAI for lead analysis and processing</li>
</ul>

<h3>4.2 Social Media Platforms</h3>
<p>We integrate with social media platforms (Reddit, Facebook, etc.) to retrieve publicly available data. This integration is governed by each platform\'s terms and privacy policies.</p>

<h3>4.3 Legal Requirements</h3>
<p>We may disclose information when required by law, court order, or to:</p>
<ul>
<li>Protect our rights, property, or safety</li>
<li>Prevent fraud or abuse</li>
<li>Comply with legal processes</li>
<li>Enforce our Terms of Service</li>
</ul>

<h3>4.4 Business Transfers</h3>
<p>In the event of a merger, acquisition, or sale of assets, your information may be transferred to the acquiring entity.</p>

<h2>5. International Data Transfers</h2>

<p>Your information may be transferred to and processed in countries outside your country of residence. We ensure appropriate safeguards are in place, including:</p>
<ul>
<li>Standard Contractual Clauses approved by the European Commission</li>
<li>Privacy Shield certification (where applicable)</li>
<li>Adequate protection measures as required by GDPR</li>
</ul>

<h2>6. Data Retention</h2>

<p>We retain your information for as long as necessary to:</p>
<ul>
<li>Provide the Service and fulfill the purposes outlined in this Policy</li>
<li>Comply with legal, accounting, or reporting requirements</li>
<li>Resolve disputes and enforce our agreements</li>
</ul>

<p><strong>Specific Retention Periods:</strong></p>
<ul>
<li><strong>Account Data:</strong> Until account deletion plus 30 days</li>
<li><strong>Lead Data:</strong> According to your plan limits and manual deletions</li>
<li><strong>Billing Records:</strong> 7 years (for tax and accounting purposes)</li>
<li><strong>Analytics Data:</strong> Up to 26 months</li>
</ul>

<h2>7. Your Rights and Choices</h2>

<h3>7.1 GDPR Rights (EEA Users)</h3>
<p>You have the right to:</p>
<ul>
<li><strong>Access:</strong> Request a copy of your personal data</li>
<li><strong>Rectification:</strong> Correct inaccurate or incomplete data</li>
<li><strong>Erasure:</strong> Request deletion of your data ("right to be forgotten")</li>
<li><strong>Restriction:</strong> Limit how we process your data</li>
<li><strong>Portability:</strong> Receive your data in a machine-readable format</li>
<li><strong>Object:</strong> Object to processing based on legitimate interests</li>
<li><strong>Withdraw Consent:</strong> Withdraw consent at any time</li>
<li><strong>Lodge a Complaint:</strong> File a complaint with your local data protection authority</li>
</ul>

<h3>7.2 Account Management</h3>
<ul>
<li><strong>Update Information:</strong> Access and modify your account settings</li>
<li><strong>Delete Account:</strong> Request account deletion through settings or contact support</li>
<li><strong>Export Data:</strong> Download your leads and campaign data</li>
</ul>

<h3>7.3 Marketing Communications</h3>
<ul>
<li>Unsubscribe from marketing emails using the link in each email</li>
<li>Manage notification preferences in account settings</li>
</ul>

<h2>8. Cookies and Tracking Technologies</h2>

<p>We use cookies and similar technologies for:</p>
<ul>
<li><strong>Essential Cookies:</strong> Required for the Service to function</li>
<li><strong>Functional Cookies:</strong> Remember your preferences and settings</li>
<li><strong>Analytics Cookies:</strong> Understand how users interact with the Service</li>
<li><strong>Marketing Cookies:</strong> Deliver personalized content (with consent)</li>
</ul>

<p>You can control cookies through your browser settings. Note that disabling certain cookies may limit Service functionality.</p>

<h2>9. Security Measures</h2>

<p>We implement industry-standard security measures to protect your data:</p>
<ul>
<li>SSL/TLS encryption for data transmission</li>
<li>Encrypted storage of sensitive information</li>
<li>Regular security audits and vulnerability assessments</li>
<li>Access controls and authentication mechanisms</li>
<li>Employee training on data protection</li>
<li>Incident response procedures</li>
</ul>

<p><strong>Data Breach Notification:</strong> In the event of a data breach affecting your personal information, we will notify you within 72 hours as required by GDPR.</p>

<h2>10. Children\'s Privacy</h2>

<p>Our Service is not intended for users under 18 years of age. We do not knowingly collect information from children. If we become aware that we have collected data from a child, we will delete it immediately.</p>

<h2>11. California Privacy Rights (CCPA)</h2>

<p>California residents have additional rights under the California Consumer Privacy Act:</p>
<ul>
<li><strong>Right to Know:</strong> What personal information we collect, use, and share</li>
<li><strong>Right to Delete:</strong> Request deletion of your personal information</li>
<li><strong>Right to Opt-Out:</strong> Opt-out of the sale of personal information (Note: We do not sell personal information)</li>
<li><strong>Non-Discrimination:</strong> We will not discriminate against you for exercising your rights</li>
</ul>

<h2>12. Third-Party Links</h2>

<p>Our Service may contain links to third-party websites and services. We are not responsible for their privacy practices. We encourage you to review their privacy policies.</p>

<h2>13. Changes to This Privacy Policy</h2>

<p>We may update this Privacy Policy periodically. We will notify you of material changes by:</p>
<ul>
<li>Posting the updated policy on our website</li>
<li>Sending an email notification (for significant changes)</li>
<li>Displaying a notice on the Service</li>
</ul>

<p>Your continued use of the Service after changes constitutes acceptance of the updated policy.</p>

<h2>14. Contact Us</h2>

<p>For questions, concerns, or to exercise your rights, contact us at:</p>

<p><strong>{{ company_name }}</strong><br>
{{ company_address }}<br>
Company Registration: {{ company_registration_code }}<br>
Email: {{ company_email }}</p>

<p><strong>Data Protection Officer:</strong> {{ company_email }}</p>

<h2>15. Supervisory Authority</h2>

<p>If you are located in the EEA and believe we have not adequately addressed your concerns, you have the right to lodge a complaint with your local data protection supervisory authority.</p>

<hr>

<p><em>This Privacy Policy is designed to comply with GDPR, CCPA, and other applicable data protection laws. By using EvenLeads, you acknowledge that you have read and understood this Privacy Policy.</em></p>',
                'details' => 'Privacy policy content (HTML)',
                'type' => 'rich_text',
                'order' => 1,
                'group' => 'Legal',
            ],
            // Terms & Conditions moved to separate seeder: TermsConditionsSeeder
            26 => [
                'id' => 28,
                'key' => 'site.footer_description',
                'display_name' => 'Footer Description',
                'value' => 'EvenLeads helps you find and manage leads from social media platforms with AI-powered intelligence.',
                'details' => 'Description text displayed in the footer under the logo',
                'type' => 'textarea',
                'order' => 16,
                'group' => 'Site',
            ],
        ];

        foreach ($settings as $settingData) {
            DB::table('settings')->updateOrInsert(
                ['key' => $settingData['key']],
                $settingData
            );
        }
    }
}
