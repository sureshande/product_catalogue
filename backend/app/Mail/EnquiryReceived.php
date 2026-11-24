<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The enquiry email to the sales inbox.
 * Content: one Blade template; subject: a config string. Both from config('catalogue.enquiries.email').
 */
class EnquiryReceived extends Mailable
{
    public function __construct(public readonly Enquiry $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            // The customer is Reply-To, never From: we can only send as our own domain (SPF/DKIM).
            replyTo: [new Address($this->enquiry->email, $this->enquiry->name)],
            subject: $this->subjectLine(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: config('catalogue.enquiries.email.template'),
            with: [
                'enquiry' => $this->enquiry,
                'price' => $this->price(),
                'brand' => config('app.name'),
            ],
        );
    }

    /** The configured subject with :placeholders filled in, kept on one line (it is an email header). */
    private function subjectLine(): string
    {
        $subject = strtr((string) config('catalogue.enquiries.email.subject'), [
            ':product_id' => (string) $this->enquiry->product_id,
            ':product' => $this->enquiry->product_title,
            ':price' => $this->price(),
            ':name' => $this->enquiry->name,
            ':email' => $this->enquiry->email,
            ':enquiry_id' => (string) $this->enquiry->id,
        ]);

        return trim(preg_replace('/\s+/', ' ', $subject));
    }

    private function price(): string
    {
        return '$'.number_format((float) $this->enquiry->product_price, 2);
    }
}
