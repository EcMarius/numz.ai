<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Wave\Setting;

class TermsConditionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'legal.terms_conditions'],
            [
                'display_name' => 'Terms and Conditions',
                'value' => '<h1>Terms and Conditions</h1>

<p><strong>Effective Date:</strong> January 1, 2025<br>
<strong>Last Updated:</strong> January 1, 2025</p>

<p>These Terms and Conditions ("Terms") govern your use of the EvenLeads platform ("Service", "Platform"). By accessing or using EvenLeads, you agree to be bound by these Terms.</p>

<p><strong>IF YOU DO NOT AGREE TO THESE TERMS, DO NOT USE THE SERVICE.</strong></p>

<h2>1. Definitions and Interpretation</h2>

<ul>
<li><strong>"Service" or "Platform"</strong> refers to the EvenLeads lead generation platform and all associated features</li>
<li><strong>"User", "you", or "your"</strong> means the individual or organization using the Service</li>
<li><strong>"Account"</strong> means your registered user profile on the Platform</li>
<li><strong>"Subscription"</strong> means your paid access plan for using the Service</li>
<li><strong>"Leads"</strong> means publicly available social media posts and discussions retrieved through the Service</li>
</ul>

<h2>2. Account Registration and Access</h2>

<p>To use EvenLeads, users must create a personal account with accurate and current information. You are solely responsible for maintaining the confidentiality of your login credentials and all activities that occur under your account.</p>

<p><strong>Important:</strong> Accounts are non-transferable and intended for individual or organizational use only. You may not share, sell, or transfer your account to any third party.</p>

<h2>3. Service Description</h2>

<p>EvenLeads tracks public discussions on Reddit using AI-powered keyword matching and relevance scoring. The platform surfaces posts likely to contain potential leads relevant to your campaigns.</p>

<h3>Key Features:</h3>
<ul>
<li>Create campaigns by defining a product description and target audience</li>
<li>Manually enter keywords or use AI-generated keyword suggestions</li>
<li>Daily AI scans of Reddit communities with ranked list of relevant posts</li>
<li>Lead management dashboard with filtering and export capabilities</li>
<li>AI-generated reply suggestions for engaging with leads</li>
</ul>

<p><strong>Supported Platform:</strong> Currently, EvenLeads supports Reddit. Additional platforms may be added in the future.</p>

<h2>4. Free Trial</h2>

<p>New subscribers are eligible for a <strong>7-day free trial</strong> with the following conditions:</p>
<ul>
<li>You may be required to provide a valid payment method to start the trial</li>
<li>You will <strong>not be charged</strong> during the trial period</li>
<li>You can cancel at any time during the trial from your account settings without being charged</li>
<li>Unless you cancel before the trial ends, your subscription will <strong>automatically convert to a paid plan</strong> and the first monthly fee will be charged at the end of the trial</li>
<li>Trials are intended for evaluation purposes and may be limited to one per customer or account</li>
</ul>

<h2>5. Subscription Plans and Payment Terms</h2>

<h3>5.1 Available Plans</h3>
<ul>
<li><strong>Founder Plan ($19/month):</strong> Includes 1 Campaign, 5 Keywords per Campaign, Unlimited Leads, Daily Syncs, 10 Manual Syncs/Month, AI Filtering, Reddit Scanning, AI-Generated Replies, and CSV Export</li>
<li><strong>Custom Plans:</strong> For users requiring more campaigns, keywords, platforms, higher sync frequency, or advanced features, custom pricing is available upon request</li>
</ul>

<h3>5.2 Payment Terms</h3>
<ul>
<li>All subscriptions are billed monthly in EUR unless otherwise specified</li>
<li>Payments are processed securely through Stripe, our third-party payment processor</li>
<li>Post-trial billing: If you do not cancel during the free trial, the first monthly fee will be charged automatically on the day the trial ends</li>
<li>Subscriptions renew monthly until cancelled</li>
<li><strong>Subscription fees are non-refundable, unless otherwise required by applicable consumer protection law</strong></li>
<li>We reserve the right to adjust pricing, plan features, or billing structure with advance notice to users</li>
</ul>

<h2>6. User Obligations</h2>

<p>Users agree to use the Service only for lawful and intended business purposes. You must not:</p>
<ul>
<li>Resell or redistribute access to the platform or lead data</li>
<li>Use bots, scrapers, or automated methods to extract data from the platform beyond the provided features</li>
<li>Violate Reddit\'s Terms of Service or community guidelines when using the platform</li>
<li>Engage in spamming, harassment, or any abusive behavior on connected platforms</li>
<li>Use the Service for any illegal or fraudulent activities</li>
</ul>

<p>Violation of these obligations may result in suspension or termination of access without refund.</p>

<h2>7. Suspension and Termination</h2>

<p>We reserve the right to suspend or terminate access to the Service at our discretion if a user:</p>
<ul>
<li>Violates these Terms</li>
<li>Exceeds fair use limits</li>
<li>Engages in fraudulent or abusive behavior</li>
<li>Disrupts the functionality or integrity of the platform</li>
<li>Fails to pay subscription fees</li>
</ul>

<p>In the event of termination, all user access and data may be revoked or deleted, unless retention is required for legal or compliance purposes.</p>

<h2>8. Fair Use and Abuse Prevention</h2>

<p>To maintain platform integrity and prevent misuse, EvenLeads enforces fair use limits across all plans to protect against excessive usage, abuse, or automated activity that could harm system performance.</p>

<p>Specific usage limits vary by subscription plan and are detailed in your account dashboard and plan documentation. These limits may include restrictions on:</p>
<ul>
<li>Number of campaigns that can be created per day</li>
<li>AI-powered feature usage (keyword generation, reply suggestions, etc.)</li>
<li>API requests and automated operations</li>
<li>Data export frequency</li>
</ul>

<p>We reserve the right to temporarily block, throttle, or suspend usage that exceeds fair use limits or appears abusive.</p>

<p><strong>Inactive Accounts:</strong> Free plan users who have been inactive for extended periods may have their campaign syncs automatically paused. Syncing will automatically resume once the user logs back in.</p>

<h3>8.1 Manual Sync Usage Policy</h3>

<p><strong>IMPORTANT:</strong> Manual campaign syncs are counted towards your monthly manual sync limit at the moment you initiate the sync operation. This limit is consumed <strong>regardless of whether the sync completes successfully or is stopped mid-process</strong>.</p>

<p>When you click "Sync Now" or initiate a manual sync through the API:</p>
<ul>
<li>One (1) manual sync credit is immediately deducted from your monthly allowance</li>
<li>The sync operation begins processing in the background</li>
<li>If you stop the sync before completion, the manual sync credit is <strong>not refunded</strong></li>
<li>This policy prevents abuse and ensures fair resource allocation across all users</li>
</ul>

<p>Manual sync limits reset at the beginning of each billing cycle. Automated syncs (scheduled background syncs) do not count towards manual sync limits and are managed separately according to your plan\'s sync interval settings.</p>

<p><strong>Rationale:</strong> This policy is necessary because initiating a sync consumes server resources, API quota from third-party platforms (Reddit, Twitter/X, etc.), and processing capacity - regardless of whether the operation completes. Stopping a sync mid-process does not eliminate these costs.</p>

<h2>9. Lead Data Retention</h2>

<p><strong>Important:</strong> EvenLeads retains discovered leads in your account for <strong>60 days</strong>. After 60 days, lead records may be permanently deleted and may no longer be accessible.</p>

<p>You are responsible for exporting any data you wish to retain before the retention period ends. CSV export functionality is available in all paid plans.</p>

<h2>10. Data and Privacy</h2>

<p>EvenLeads only collects and displays publicly available discussion data from Reddit. No private user data from third-party platforms is accessed or stored.</p>

<p>All user-provided data (campaign information, keywords, account details, etc.) is handled in accordance with our Privacy Policy and applicable data protection laws.</p>

<h2>11. AI Limitations and Accuracy Disclaimer</h2>

<p>EvenLeads uses artificial intelligence to evaluate and rank public social media posts based on their potential relevance to a user\'s product or service.</p>

<p><strong>While the AI system is designed to surface high-intent or relevant posts, it may occasionally return false positives or miss qualified leads</strong> — especially if the input campaign description or keywords are unclear, vague, or overly broad.</p>

<p>Users are encouraged to regularly review and refine their campaigns to improve accuracy and results. We do not guarantee specific outcomes or lead quality.</p>

<h2>12. Third-Party Services Disclaimer</h2>

<p>EvenLeads interacts with third-party services and APIs, including:</p>
<ul>
<li><strong>Reddit</strong> (for lead discovery and social media data)</li>
<li><strong>Stripe</strong> (for secure payment processing)</li>
<li><strong>Supabase</strong> (for data storage and management)</li>
<li><strong>OpenAI</strong> (for AI-powered features)</li>
</ul>

<p>While we strive to provide continuous integration with these services, we do not control their availability, functionality, or data accuracy.</p>

<p>Changes or outages on these platforms may affect certain features of EvenLeads. We are not liable for service interruptions or limitations resulting from third-party platform issues beyond our control.</p>

<h2>13. Right of Withdrawal and Cancellation</h2>

<p>Users may cancel their subscription at any time from their account settings.</p>

<ul>
<li><strong>Trial cancellations:</strong> If you cancel during the 7-day free trial, you will not be charged</li>
<li><strong>After the trial converts to a paid subscription:</strong> Refunds are not issued for partial billing periods unless required by applicable law</li>
<li>Your access remains active until the end of the current billing period</li>
<li>Data may be deleted 30 days after account termination</li>
</ul>

<h2>14. Limitation of Liability</h2>

<p>EvenLeads is not liable for actions taken by users based on leads surfaced by the Service. We do not guarantee the conversion or accuracy of any lead.</p>

<p><strong>THE SERVICE IS PROVIDED "AS IS" WITHOUT WARRANTIES OF ANY KIND.</strong> To the maximum extent permitted by law, our total liability shall not exceed the amount you paid in the 12 months preceding any claim.</p>

<p>Users are solely responsible for ensuring that their outreach — including AI-generated comments or messages — complies with the rules, guidelines, and terms of the platforms they use. We are not responsible for account suspensions, bans, or penalties resulting from user actions on third-party platforms.</p>

<h2>15. Modifications to Terms</h2>

<p>We reserve the right to modify these Terms at any time. Users will be notified of material changes via email or platform notification. Continued use of the Service after changes have been made constitutes acceptance of the new Terms.</p>

<h2>16. Governing Law and Jurisdiction</h2>

<p>These Terms shall be governed by and construed in accordance with the laws of the <strong>European Union</strong> and applicable international regulations.</p>

<p>Any disputes arising from these Terms or your use of EvenLeads shall be resolved in accordance with EU consumer protection laws and dispute resolution mechanisms.</p>

<h2>17. Contact and Support</h2>

<p>For questions, support requests, or concerns regarding these Terms, please contact us through the support channels provided in your account dashboard.</p>

<p><strong>Email:</strong> contact@evenleads.com</p>

<hr>

<p><em>By creating an account and using EvenLeads, you acknowledge that you have read, understood, and agree to be bound by these Terms and Conditions.</em></p>

<p><strong>Effective Date:</strong> January 1, 2025<br>
<strong>Last Updated:</strong> January 1, 2025</p>',
                'details' => 'Terms and conditions content (HTML)',
                'type' => 'rich_text',
                'order' => 2,
                'group' => 'Legal',
            ]
        );

        echo "✓ Terms and Conditions updated successfully\n";
    }
}
