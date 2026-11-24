{{--
    Enquiry email to the sales inbox. This ONE file is the complete email body: wording, layout and styling.
    Edit it freely, or point config('catalogue.enquiries.email.template') at another view.
    The subject is set separately: config('catalogue.enquiries.email.subject').

    Available variables:
      $enquiry  App\Models\Enquiry  (product_id, product_title, product_price, name, email, phone, message, id, created_at)
      $price    string             formatted price, e.g. "$55.99"
      $brand    string             config('app.name')

    Always print customer input with {{ }} so it is escaped and can never become markup.
    Table layout with inline styles because many email clients ignore <style> blocks.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>New product enquiry</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f6f8; font-family:-apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#1e2430;">
    {{-- Preview text shown in the inbox list. --}}
    <div style="display:none; max-height:0; overflow:hidden;">{{ $enquiry->name }} is interested in {{ $enquiry->product_title }}.</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f6f8;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">

                    <tr>
                        <td style="padding:0 0 16px 0; font-size:18px; font-weight:700; color:#2c5be0;">{{ $brand }}</td>
                    </tr>

                    <tr>
                        <td style="background-color:#ffffff; border:1px solid #e3e6eb; border-radius:8px; padding:32px;">
                            <h1 style="margin:0 0 12px 0; font-size:22px; line-height:28px;">New product enquiry</h1>
                            <p style="margin:0 0 24px 0; font-size:15px; line-height:22px;">
                                {{ $enquiry->name }} is interested in <strong>{{ $enquiry->product_title }}</strong>
                                and would like to hear from you.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; line-height:20px; border-collapse:collapse;">
                                @foreach ([
                                    'Product' => $enquiry->product_title.' (#'.$enquiry->product_id.')',
                                    'Price shown' => $price,
                                    'Name' => $enquiry->name,
                                    'Email' => $enquiry->email,
                                    'Phone' => $enquiry->phone ?? 'Not provided',
                                ] as $label => $value)
                                    <tr>
                                        <td style="padding:8px 12px 8px 0; width:120px; color:#5f6b7a; border-bottom:1px solid #eef0f3; vertical-align:top;">{{ $label }}</td>
                                        <td style="padding:8px 0; border-bottom:1px solid #eef0f3; vertical-align:top;">{{ $value }}</td>
                                    </tr>
                                @endforeach
                            </table>

                            <h2 style="margin:24px 0 8px 0; font-size:15px;">Message</h2>
                            <div style="padding:12px 16px; background-color:#f5f6f8; border-radius:6px; font-size:14px; line-height:20px;">
                                @if ($enquiry->message)
                                    {!! nl2br(e($enquiry->message)) !!}
                                @else
                                    <em>No message.</em>
                                @endif
                            </div>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px 0 0 0;">
                                <tr>
                                    <td style="border-radius:6px; background-color:#2c5be0;">
                                        <a href="mailto:{{ rawurlencode($enquiry->email) }}?subject={{ rawurlencode('Re: '.$enquiry->product_title) }}"
                                           style="display:inline-block; padding:12px 20px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none;">
                                            Reply to {{ $enquiry->name }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0 0; font-size:13px; line-height:20px; color:#5f6b7a;">
                                You can also reply to this email to respond to the customer directly.
                            </p>
                            <p style="margin:8px 0 0 0; font-size:12px; color:#8a94a3;">
                                Enquiry #{{ $enquiry->id }} · received {{ $enquiry->created_at?->toDayDateTimeString() }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 0 0 0; font-size:12px; line-height:18px; color:#5f6b7a; text-align:center;">
                            Sent automatically by {{ $brand }}.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
