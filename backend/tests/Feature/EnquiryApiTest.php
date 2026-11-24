<?php

use App\Jobs\SendEnquiryEmail;
use App\Mail\EnquiryReceived;
use App\Models\Enquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    fakeStore();
    config(['catalogue.enquiries.sales_inbox' => 'sales@shop.test']);
});

function enquiry(array $overrides = []): array
{
    return array_merge([
        'product_id' => 3,
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'phone' => '+44 20 7946 0958',
        'message' => 'Do you have this in large?',
    ], $overrides);
}

describe('Enquiry: WHEN I submit the form', function () {
    it('thanks the customer and the sales inbox receives an email', function () {
        Mail::fake();

        $this->postJson('/api/enquiries', enquiry())
            ->assertStatus(202)
            ->assertJsonPath('message', 'Thank you! Our sales team will be in touch soon.');

        Mail::assertSent(EnquiryReceived::class, function (EnquiryReceived $mail) {
            return $mail->hasTo('sales@shop.test')
                && $mail->hasReplyTo('ada@example.com', 'Ada Lovelace')
                && $mail->hasSubject('New enquiry: Mens Cotton Jacket');
        });
        expect(Enquiry::sole()->status)->toBe(Enquiry::STATUS_SENT);
    });

    it('saves the enquiry with a product snapshot taken from our catalogue, then queues the email', function () {
        Queue::fake();

        $this->postJson('/api/enquiries', enquiry(['product_title' => 'Free TV', 'product_price' => 0]))
            ->assertStatus(202);

        $saved = Enquiry::sole();
        expect($saved->product_title)->toBe('Mens Cotton Jacket')   // not the client's "Free TV"
            ->and($saved->product_price)->toBe('55.99')
            ->and($saved->status)->toBe(Enquiry::STATUS_PENDING);
        Queue::assertPushed(SendEnquiryEmail::class, fn ($job) => $job->enquiryId === $saved->id);
    });

    it('accepts an enquiry without the optional phone and message', function () {
        Mail::fake();

        $this->postJson('/api/enquiries', enquiry(['phone' => null, 'message' => null]))->assertStatus(202);

        expect(Enquiry::sole()->phone)->toBeNull();
    });
});

describe('Enquiry validation', function () {
    it('rejects missing or invalid contact details', function (array $data, string $field) {
        Queue::fake();

        $this->postJson('/api/enquiries', enquiry($data))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        expect(Enquiry::count())->toBe(0);
        Queue::assertNothingPushed();
    })->with([
        'name missing' => [['name' => ''], 'name'],
        'name too long' => [['name' => str_repeat('a', 101)], 'name'],
        'email missing' => [['email' => ''], 'email'],
        'email invalid' => [['email' => 'not-an-email'], 'email'],
        'phone invalid' => [['phone' => 'call me'], 'phone'],
        'message too long' => [['message' => str_repeat('a', 2001)], 'message'],
        'product missing' => [['product_id' => null], 'product_id'],
        'honeypot filled' => [['website' => 'http://spam.test'], 'website'],
    ]);

    it('rejects a product that does not exist', function () {
        $this->postJson('/api/enquiries', enquiry(['product_id' => 999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id' => 'The selected product does not exist.']);
    });

    it('rate limits enquiries per IP', function () {
        Queue::fake();
        config(['catalogue.enquiries.rate_limit_per_minute' => 2]);

        $this->postJson('/api/enquiries', enquiry())->assertStatus(202);
        $this->postJson('/api/enquiries', enquiry())->assertStatus(202);
        $this->postJson('/api/enquiries', enquiry())->assertTooManyRequests();
    });
});

describe('SendEnquiryEmail job', function () {
    it('does not email twice if the enquiry was already sent', function () {
        Mail::fake();
        $enquiry = Enquiry::create([...enquiry(), 'product_title' => 'X', 'product_price' => 1, 'status' => Enquiry::STATUS_SENT]);

        (new SendEnquiryEmail($enquiry->id))->handle();

        Mail::assertNothingSent();
    });

    it('marks the enquiry as failed when all retries are exhausted', function () {
        $enquiry = Enquiry::create([...enquiry(), 'product_title' => 'X', 'product_price' => 1, 'status' => Enquiry::STATUS_PENDING]);

        (new SendEnquiryEmail($enquiry->id))->failed(new RuntimeException('SMTP down'));

        expect($enquiry->fresh()->status)->toBe(Enquiry::STATUS_FAILED);
    });
});
