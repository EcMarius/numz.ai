<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: bold;">
                                Welcome to {{ config('app.name') }}!
                            </h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 20px; color: #374151; font-size: 16px; line-height: 1.6;">
                                Hi {{ $user->name }},
                            </p>

                            <p style="margin: 0 0 20px; color: #374151; font-size: 16px; line-height: 1.6;">
                                Thank you for choosing {{ config('app.name') }}! We're excited to have you on board. Your account has been successfully created and you're ready to get started.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0; background-color: #f9fafb; border-radius: 8px; padding: 20px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 10px; color: #6b7280; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Your Account Details
                                        </p>
                                        <p style="margin: 0 0 8px; color: #374151; font-size: 15px;">
                                            <strong>Email:</strong> {{ $user->email }}
                                        </p>
                                        <p style="margin: 0; color: #374151; font-size: 15px;">
                                            <strong>Account ID:</strong> #{{ $user->id }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{ route('dashboard') }}" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
                                    Access Your Dashboard
                                </a>
                            </div>

                            <h3 style="margin: 30px 0 15px; color: #111827; font-size: 18px; font-weight: 600;">
                                Quick Start Guide
                            </h3>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0;">
                                <tr>
                                    <td style="padding: 15px; background-color: #f9fafb; border-left: 3px solid #3b82f6; margin-bottom: 10px;">
                                        <p style="margin: 0 0 5px; color: #111827; font-weight: 600; font-size: 15px;">
                                            1. Complete Your Profile
                                        </p>
                                        <p style="margin: 0; color: #6b7280; font-size: 14px;">
                                            Add your billing information and set up your account preferences.
                                        </p>
                                    </td>
                                </tr>
                                <tr><td style="height: 10px;"></td></tr>
                                <tr>
                                    <td style="padding: 15px; background-color: #f9fafb; border-left: 3px solid #10b981; margin-bottom: 10px;">
                                        <p style="margin: 0 0 5px; color: #111827; font-weight: 600; font-size: 15px;">
                                            2. Choose a Hosting Plan
                                        </p>
                                        <p style="margin: 0; color: #6b7280; font-size: 14px;">
                                            Browse our hosting plans and select the one that fits your needs.
                                        </p>
                                    </td>
                                </tr>
                                <tr><td style="height: 10px;"></td></tr>
                                <tr>
                                    <td style="padding: 15px; background-color: #f9fafb; border-left: 3px solid #8b5cf6; margin-bottom: 10px;">
                                        <p style="margin: 0 0 5px; color: #111827; font-weight: 600; font-size: 15px;">
                                            3. Launch Your Website
                                        </p>
                                        <p style="margin: 0; color: #6b7280; font-size: 14px;">
                                            Use our one-click installer to set up WordPress or upload your files via FTP.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <div style="margin: 30px 0; padding: 20px; background-color: #eff6ff; border-radius: 8px; border: 1px solid #bfdbfe;">
                                <p style="margin: 0 0 10px; color: #1e40af; font-weight: 600; font-size: 15px;">
                                    💡 Need Help?
                                </p>
                                <p style="margin: 0; color: #1e3a8a; font-size: 14px; line-height: 1.6;">
                                    Our support team is available 24/7 to assist you. Visit our <a href="{{ route('knowledge-base') }}" style="color: #2563eb; text-decoration: none;">Knowledge Base</a> or <a href="{{ route('contact') }}" style="color: #2563eb; text-decoration: none;">contact support</a>.
                                </p>
                            </div>

                            <p style="margin: 30px 0 0; color: #374151; font-size: 16px; line-height: 1.6;">
                                Welcome aboard!<br>
                                <strong>The {{ config('app.name') }} Team</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px; background-color: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center;">
                            <p style="margin: 0 0 10px; color: #6b7280; font-size: 14px;">
                                {{ config('app.name') }} | Premium Web Hosting
                            </p>
                            <p style="margin: 0 0 15px; color: #9ca3af; font-size: 12px;">
                                This email was sent to {{ $user->email }}
                            </p>
                            <p style="margin: 0; font-size: 12px;">
                                <a href="{{ route('terms') }}" style="color: #6b7280; text-decoration: none; margin: 0 10px;">Terms</a>
                                <a href="{{ route('privacy') }}" style="color: #6b7280; text-decoration: none; margin: 0 10px;">Privacy</a>
                                <a href="{{ route('contact') }}" style="color: #6b7280; text-decoration: none; margin: 0 10px;">Support</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
