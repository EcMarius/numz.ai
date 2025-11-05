<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Welcome to EvenLeads</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #ffffff; line-height: 1.6;">

    <!-- Main Container -->
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #ffffff;">
        <tr>
            <td align="center" style="padding: 40px 20px;">

                <!-- Email Content Wrapper -->
                <table cellpadding="0" cellspacing="0" border="0" width="600" style="max-width: 600px; width: 100%;">

                    <!-- Header with Branding -->
                    <tr>
                        <td style="padding: 0 0 40px 0;">
                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 0;">
                                        <span style="font-size: 24px; font-weight: 700; color: #000000; letter-spacing: -0.5px;">EvenLeads</span>
                                    </td>
                                    <td align="right" style="padding: 0;">
                                        <span style="display: inline-block; padding: 6px 14px; background-color: #000000; color: #ffffff; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                            {{ strtoupper($plan->name) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Headline -->
                    <tr>
                        <td style="padding: 0 0 12px 0;">
                            <h1 style="margin: 0; font-size: 32px; font-weight: 700; color: #000000; line-height: 1.2; letter-spacing: -1px;">
                                @if($isTrial)
                                    Welcome to Your Trial!
                                @else
                                    Welcome to EvenLeads!
                                @endif
                            </h1>
                        </td>
                    </tr>

                    <!-- Welcome Message -->
                    <tr>
                        <td style="padding: 0 0 32px 0;">
                            <p style="margin: 0; font-size: 16px; color: #666666; line-height: 1.6;">
                                Hi <strong style="color: #000000;">{{ $user->name }}</strong>,
                            </p>
                            <p style="margin: 16px 0 0 0; font-size: 16px; color: #666666; line-height: 1.6;">
                                @if($isTrial)
                                    Thank you for starting your free trial of EvenLeads! You now have full access to the <strong style="color: #000000;">{{ $plan->name }}</strong> plan to discover high-quality leads across multiple platforms.
                                @else
                                    Thank you for subscribing to EvenLeads! You now have full access to the <strong style="color: #000000;">{{ $plan->name }}</strong> plan. We're excited to help you grow your business by connecting you with the right opportunities.
                                @endif
                            </p>
                        </td>
                    </tr>

                    <!-- Plan Features -->
                    <tr>
                        <td style="padding: 0 0 32px 0;">
                            <h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: #000000;">
                                Your Plan Includes
                            </h2>

                            <!-- Feature Box -->
                            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #F5F5F5; border: 2px solid #000000; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        @php
                                            $features = is_string($plan->features) ? json_decode($plan->features, true) : $plan->features;
                                            $leadsSyncLimit = $plan->leads_sync_limit ?? 'Unlimited';
                                            $leadsPerSync = $plan->leads_per_sync ?? 'Unlimited';
                                            $maxAccounts = $plan->max_accounts ?? 'Unlimited';

                                            // Ensure numeric values for number_format
                                            if (is_numeric($leadsSyncLimit)) {
                                                $leadsSyncLimit = (int) $leadsSyncLimit;
                                            }
                                            if (is_numeric($leadsPerSync)) {
                                                $leadsPerSync = (int) $leadsPerSync;
                                            }
                                            if (is_numeric($maxAccounts)) {
                                                $maxAccounts = (int) $maxAccounts;
                                            }
                                        @endphp

                                        <!-- Leads Sync Limit -->
                                        <div style="margin-bottom: 16px;">
                                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 0; vertical-align: middle;">
                                                        <div style="display: inline-block; width: 8px; height: 8px; background-color: #000000; border-radius: 50%; margin-right: 12px;"></div>
                                                        <span style="font-size: 15px; color: #000000; font-weight: 500;">
                                                            <strong>{{ $leadsSyncLimit == -1 ? 'Unlimited' : (is_numeric($leadsSyncLimit) ? number_format($leadsSyncLimit) : $leadsSyncLimit) }}</strong> Monthly Lead Syncs
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>

                                        <!-- Leads Per Sync -->
                                        <div style="margin-bottom: 16px;">
                                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 0; vertical-align: middle;">
                                                        <div style="display: inline-block; width: 8px; height: 8px; background-color: #000000; border-radius: 50%; margin-right: 12px;"></div>
                                                        <span style="font-size: 15px; color: #000000; font-weight: 500;">
                                                            <strong>{{ $leadsPerSync == -1 ? 'Unlimited' : (is_numeric($leadsPerSync) ? number_format($leadsPerSync) : $leadsPerSync) }}</strong> Leads Per Sync
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>

                                        <!-- Max Accounts -->
                                        <div style="margin-bottom: 16px;">
                                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 0; vertical-align: middle;">
                                                        <div style="display: inline-block; width: 8px; height: 8px; background-color: #000000; border-radius: 50%; margin-right: 12px;"></div>
                                                        <span style="font-size: 15px; color: #000000; font-weight: 500;">
                                                            <strong>{{ $maxAccounts == -1 ? 'Unlimited' : (is_numeric($maxAccounts) ? number_format($maxAccounts) : $maxAccounts) }}</strong> Connected Accounts
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>

                                        @if($features && is_array($features))
                                            @foreach(array_slice($features, 0, 5) as $feature)
                                                @if($feature && trim($feature))
                                                <div style="margin-bottom: 16px;">
                                                    <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                        <tr>
                                                            <td style="padding: 0; vertical-align: middle;">
                                                                <div style="display: inline-block; width: 8px; height: 8px; background-color: #000000; border-radius: 50%; margin-right: 12px;"></div>
                                                                <span style="font-size: 15px; color: #000000; font-weight: 500;">{{ $feature }}</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                @endif
                                            @endforeach
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Next Steps -->
                    <tr>
                        <td style="padding: 0 0 32px 0;">
                            <h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: #000000;">
                                Get Started
                            </h2>

                            <div style="background-color: #F5F5F5; border: 1px solid #E5E5E5; border-radius: 8px; padding: 20px; margin-bottom: 12px;">
                                <div style="font-size: 15px; font-weight: 600; color: #000000; margin-bottom: 8px;">
                                    1. Connect Your Social Accounts
                                </div>
                                <div style="font-size: 14px; color: #666666; line-height: 1.5;">
                                    Link your Reddit, LinkedIn, Twitter/X, or Facebook accounts to start finding leads.
                                </div>
                            </div>

                            <div style="background-color: #F5F5F5; border: 1px solid #E5E5E5; border-radius: 8px; padding: 20px; margin-bottom: 12px;">
                                <div style="font-size: 15px; font-weight: 600; color: #000000; margin-bottom: 8px;">
                                    2. Create Your First Campaign
                                </div>
                                <div style="font-size: 14px; color: #666666; line-height: 1.5;">
                                    Set up a campaign with your target keywords and let our AI find relevant leads for you.
                                </div>
                            </div>

                            <div style="background-color: #F5F5F5; border: 1px solid #E5E5E5; border-radius: 8px; padding: 20px;">
                                <div style="font-size: 15px; font-weight: 600; color: #000000; margin-bottom: 8px;">
                                    3. Engage With Your Leads
                                </div>
                                <div style="font-size: 14px; color: #666666; line-height: 1.5;">
                                    Review your leads, use AI-powered replies, and start building meaningful connections.
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- CTA Button -->
                    <tr>
                        <td style="padding: 0 0 32px 0;" align="center">
                            <table cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="border-radius: 8px; background-color: #000000;">
                                        <a href="{{ route('dashboard') }}" style="display: inline-block; padding: 16px 40px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px;">
                                            Go to Dashboard
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if($isTrial)
                    <!-- Trial Notice -->
                    <tr>
                        <td style="padding: 0 0 32px 0;">
                            <div style="background-color: #F5F5F5; border-left: 4px solid #000000; border-radius: 4px; padding: 16px 20px;">
                                <div style="font-size: 14px; color: #666666; line-height: 1.5;">
                                    <strong style="color: #000000;">Trial Period:</strong> Your trial will end on {{ $subscription->trial_ends_at ? $subscription->trial_ends_at->format('F j, Y') : 'the trial end date' }}. You can cancel anytime before then.
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif

                    <!-- Support Section -->
                    <tr>
                        <td style="padding: 0 0 32px 0;">
                            <h2 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: #000000;">
                                Need Help?
                            </h2>
                            <p style="margin: 0; font-size: 14px; color: #666666; line-height: 1.6;">
                                Our team is here to help you succeed. If you have any questions or need assistance, feel free to reach out:
                            </p>
                            <p style="margin: 12px 0 0 0; font-size: 14px;">
                                <a href="mailto:support@evenleads.com" style="color: #000000; text-decoration: underline; font-weight: 500;">support@evenleads.com</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 0 32px 0;">
                            <div style="height: 1px; background-color: #E5E5E5;"></div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 0;">
                            <p style="margin: 0; font-size: 13px; color: #999999; line-height: 1.5; text-align: center;">
                                You're receiving this email because you subscribed to EvenLeads.
                            </p>
                            <p style="margin: 8px 0 0 0; font-size: 13px; color: #999999; line-height: 1.5; text-align: center;">
                                © {{ date('Y') }} EvenLeads. All rights reserved.
                            </p>
                            <p style="margin: 8px 0 0 0; font-size: 13px; text-align: center;">
                                <a href="{{ route('dashboard') }}" style="color: #666666; text-decoration: underline;">Manage Subscription</a>
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
