<?php

namespace App\Services;

use App\Contracts\ProductSource;
use App\Jobs\SendEnquiryEmail;
use App\Models\Enquiry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnquiryService
{
    public function __construct(private readonly ProductSource $products) {}

    /**
     * Saves the enquiry, then queues the email to sales.
     * Saving first means an enquiry is never lost if the mail provider is down.
     *
     * @param  array{product_id:int, name:string, email:string, phone:?string, message:?string}  $data
     */
    public function submit(array $data): Enquiry
    {
        // Product details come from our source, never from the client.
        $product = $this->products->find($data['product_id'])
            ?? throw ValidationException::withMessages(['product_id' => 'The selected product does not exist.']);

        return DB::transaction(function () use ($data, $product) {
            $enquiry = Enquiry::create([
                'product_id' => $product->id,
                'product_title' => $product->title,
                'product_price' => $product->price,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'message' => $data['message'] ?? null,
                'status' => Enquiry::STATUS_PENDING,
            ]);

            SendEnquiryEmail::dispatch($enquiry->id)->afterCommit();

            return $enquiry;
        });
    }
}
