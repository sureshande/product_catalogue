<x-mail::message>
# New product enquiry

- **Product:** {{ $enquiry->product_title }} (#{{ $enquiry->product_id }})
- **Price shown:** ${{ number_format((float) $enquiry->product_price, 2) }}
- **Name:** {{ $enquiry->name }}
- **Email:** {{ $enquiry->email }}
- **Phone:** {{ $enquiry->phone ?? 'Not provided' }}

**Message**

{{ $enquiry->message ?? 'No message.' }}

Reply to this email to respond to the customer directly.

<small>Enquiry #{{ $enquiry->id }}, received {{ $enquiry->created_at?->toDayDateTimeString() }}</small>
</x-mail::message>
