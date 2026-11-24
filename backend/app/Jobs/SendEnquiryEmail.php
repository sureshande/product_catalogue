<?php

namespace App\Jobs;

use App\Mail\EnquiryReceived;
use App\Models\Enquiry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Emails a saved enquiry to the sales inbox. Retried with backoff if the mail provider fails. */
class SendEnquiryEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var int[] seconds between attempts */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $enquiryId) {}

    public function handle(): void
    {
        $enquiry = Enquiry::find($this->enquiryId);

        if ($enquiry === null || $enquiry->status === Enquiry::STATUS_SENT) {
            return; // deleted, or already sent by an earlier attempt
        }

        Mail::to(config('catalogue.enquiries.sales_inbox'))->send(new EnquiryReceived($enquiry));

        $enquiry->update(['status' => Enquiry::STATUS_SENT, 'sent_at' => now()]);
    }

    public function failed(Throwable $e): void
    {
        Enquiry::whereKey($this->enquiryId)->update(['status' => Enquiry::STATUS_FAILED]);

        Log::error('enquiry.email_failed', ['enquiry_id' => $this->enquiryId, 'error' => $e->getMessage()]);
    }
}
