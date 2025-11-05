<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $totalNewLeads }} leads found - {{ $campaign->name }}</title>
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
                                            {{ $totalNewLeads }} NEW
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
                                {{ $totalNewLeads }} lead{{ $totalNewLeads !== 1 ? 's' : '' }} found
                            </h1>
                        </td>
                    </tr>

                    <!-- Campaign Name -->
                    <tr>
                        <td style="padding: 0 0 32px 0;">
                            <p style="margin: 0; font-size: 16px; color: #666666;">
                                Campaign: <strong style="color: #000000;">{{ $campaign->name }}</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Match Stats Bar -->
                    @if($strongMatchesCount > 0 || $partialMatchesCount > 0)
                    <tr>
                        <td style="padding: 0 0 32px 0;">
                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    @if($strongMatchesCount > 0)
                                    <td style="padding: 0 8px 0 0; width: 50%;">
                                        <div style="background-color: #F5F5F5; border: 2px solid #000000; border-radius: 8px; padding: 20px; text-align: center;">
                                            <div style="font-size: 36px; font-weight: 700; color: #000000; line-height: 1; margin-bottom: 8px;">
                                                {{ $strongMatchesCount }}
                                            </div>
                                            <div style="font-size: 13px; font-weight: 600; color: #000000; text-transform: uppercase; letter-spacing: 0.5px;">
                                                Strong Matches
                                            </div>
                                        </div>
                                    </td>
                                    @endif
                                    @if($partialMatchesCount > 0)
                                    <td style="padding: 0 0 0 8px; width: 50%;">
                                        <div style="background-color: #F5F5F5; border: 1px solid #E5E5E5; border-radius: 8px; padding: 20px; text-align: center;">
                                            <div style="font-size: 36px; font-weight: 700; color: #666666; line-height: 1; margin-bottom: 8px;">
                                                {{ $partialMatchesCount }}
                                            </div>
                                            <div style="font-size: 13px; font-weight: 600; color: #666666; text-transform: uppercase; letter-spacing: 0.5px;">
                                                Partial Matches
                                            </div>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- Lead Previews -->
                    @if($sampleLeads->count() > 0)
                    <tr>
                        <td style="padding: 0 0 24px 0;">
                            <h2 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: #000000;">
                                Top Leads
                            </h2>

                            @foreach($sampleLeads->take(2) as $index => $lead)
                            <!-- Lead Card -->
                            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: {{ $index === 0 ? '16px' : '0' }}; background-color: #FAFAFA; border: 1px solid #E5E5E5; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 24px;">

                                        <!-- Platform & Score -->
                                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 16px;">
                                            <tr>
                                                <td style="padding: 0;">
                                                    <table cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td style="padding: 0 8px 0 0;">
                                                                <span style="display: inline-block; padding: 4px 10px; background-color: #000000; color: #ffffff; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                                                    {{ ucfirst($lead->platform) }}
                                                                </span>
                                                            </td>
                                                            @if($lead->match_type)
                                                            <td style="padding: 0 8px 0 0;">
                                                                <span style="display: inline-block; padding: 4px 10px; background-color: {{ $lead->match_type === 'strong' ? '#000000' : '#F5F5F5' }}; color: {{ $lead->match_type === 'strong' ? '#ffffff' : '#666666' }}; border: {{ $lead->match_type === 'strong' ? 'none' : '1px solid #E5E5E5' }}; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                                                    {{ ucfirst($lead->match_type) }}
                                                                </span>
                                                            </td>
                                                            @endif
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td align="right" style="padding: 0;">
                                                    <span style="display: inline-block; padding: 6px 12px; background-color: #000000; color: #ffffff; border-radius: 6px; font-size: 14px; font-weight: 700;">
                                                        {{ $lead->confidence_score }}/10
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Title -->
                                        <h3 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; color: #000000; line-height: 1.4;">
                                            {{ $lead->title }}
                                        </h3>

                                        <!-- Description -->
                                        @if($lead->description)
                                        <p style="margin: 0 0 16px 0; font-size: 14px; color: #666666; line-height: 1.6;">
                                            {{ \Str::limit($lead->description, 140) }}
                                        </p>
                                        @endif

                                        <!-- Meta Info -->
                                        <div style="padding-top: 12px; border-top: 1px solid #E5E5E5;">
                                            <span style="font-size: 12px; color: #999999;">
                                                @if($lead->subreddit)
                                                    r/{{ $lead->subreddit }}
                                                @endif
                                                @if($lead->author && $lead->subreddit)
                                                    <span style="margin: 0 6px;">·</span>
                                                @endif
                                                @if($lead->author)
                                                    @php
                                                        $authorPrefix = match(strtolower($lead->platform)) {
                                                            'reddit' => 'u/',
                                                            'x', 'facebook' => '@',
                                                            'linkedin' => '',
                                                            default => ''
                                                        };
                                                    @endphp
                                                    {{ $authorPrefix }}{{ $lead->author }}
                                                @endif
                                                @if($lead->comments_count)
                                                    <span style="margin: 0 6px;">·</span>
                                                    {{ $lead->comments_count }} comments
                                                @endif
                                            </span>
                                        </div>

                                    </td>
                                </tr>
                            </table>
                            @endforeach

                        </td>
                    </tr>
                    @endif

                    <!-- More Leads Notice -->
                    @if($totalNewLeads > 2)
                    <tr>
                        <td style="padding: 0 0 32px 0;">
                            <div style="text-align: center; padding: 20px; background-color: #FAFAFA; border: 1px solid #E5E5E5; border-radius: 8px;">
                                <p style="margin: 0; font-size: 15px; font-weight: 600; color: #000000;">
                                    + {{ $totalNewLeads - 2 }} more lead{{ ($totalNewLeads - 2) !== 1 ? 's' : '' }} waiting for you
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endif

                    <!-- CTA Button -->
                    <tr>
                        <td style="padding: 0 0 16px 0;" align="center">
                            <a href="{{ url('/dashboard/leads?campaign=' . $campaign->id) }}" style="display: inline-block; padding: 16px 48px; background-color: #000000; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; letter-spacing: 0.3px;">
                                View All Leads
                            </a>
                        </td>
                    </tr>

                    <!-- Fallback Link -->
                    <tr>
                        <td style="padding: 0 0 40px 0;" align="center">
                            <p style="margin: 0; font-size: 12px; color: #999999;">
                                or copy this link:
                                <a href="{{ url('/dashboard/leads?campaign=' . $campaign->id) }}" style="color: #000000; text-decoration: underline;">
                                    {{ url('/dashboard/leads?campaign=' . $campaign->id) }}
                                </a>
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
                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 0 0 16px 0;">
                                        <p style="margin: 0; font-size: 13px; color: #666666;">
                                            <strong style="color: #000000;">EvenLeads</strong> · Lead discovery notification
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 0 0 12px 0;">
                                        <p style="margin: 0; font-size: 12px; color: #999999;">
                                            Don't want these notifications?
                                            <a href="{{ $unsubscribeUrl }}" style="color: #000000; text-decoration: underline;">Unsubscribe</a>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 0;">
                                        <p style="margin: 0; font-size: 11px; color: #CCCCCC; line-height: 1.6;">
                                            © {{ date('Y') }} EvenLeads. All rights reserved.<br>
                                            You're receiving this because you opted in to lead notifications.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
