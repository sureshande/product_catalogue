<?php

use App\Mail\EnquiryReceived;
use App\Models\Enquiry;
use Illuminate\Support\Facades\View;

/** An enquiry as it would be loaded from the database (not saved: these tests only render the email). */
function savedEnquiry(array $overrides = []): Enquiry
{
    $enquiry = new Enquiry(array_merge([
        'product_id' => 3,
        'product_title' => 'Mens Cotton Jacket',
        'product_price' => 55.99,
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'phone' => '+44 20 7946 0958',
        'message' => "Do you have it in large?\nThanks!",
        'status' => Enquiry::STATUS_PENDING,
    ], $overrides));
    $enquiry->id = 42;
    $enquiry->created_at = now();

    return $enquiry;
}

it('renders the template with every enquiry detail', function () {
    $mail = new EnquiryReceived(savedEnquiry());

    $mail->assertHasSubject('New enquiry: Mens Cotton Jacket');
    $mail->assertHasReplyTo('ada@example.com', 'Ada Lovelace');
    $mail->assertSeeInOrderInHtml([
        'New product enquiry',
        'Ada Lovelace is interested in <strong>Mens Cotton Jacket</strong>',
        'Mens Cotton Jacket (#3)', '$55.99', 'Ada Lovelace', 'ada@example.com', '+44 20 7946 0958',
        'Do you have it in large?<br />',
        'Reply to Ada Lovelace',
        'Enquiry #42',
    ], false);
});

it('uses the subject from config, with placeholders filled in', function () {
    config(['catalogue.enquiries.email.subject' => 'Lead #:enquiry_id: :product (:price) from :name <:email>']);

    (new EnquiryReceived(savedEnquiry()))
        ->assertHasSubject('Lead #42: Mens Cotton Jacket ($55.99) from Ada Lovelace <ada@example.com>');
});

it('uses the Blade template named in config', function () {
    View::addLocation(base_path('tests/Fixtures/views'));
    config(['catalogue.enquiries.email.template' => 'custom-enquiry']);

    (new EnquiryReceived(savedEnquiry()))
        ->assertSeeInHtml('CUSTOM TEMPLATE for Ada Lovelace about Mens Cotton Jacket')
        ->assertDontSeeInHtml('New product enquiry');
});

it('escapes customer input in the email', function () {
    $mail = new EnquiryReceived(savedEnquiry([
        'name' => '<script>alert(1)</script>',
        'message' => '<a href="https://evil.test">click</a>',
    ]));

    $mail->assertDontSeeInHtml('<script>', false);
    $mail->assertDontSeeInHtml('<a href="https://evil.test">', false);
    $mail->assertSeeInHtml('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});

it('keeps the subject on one line even if a placeholder contains a newline', function () {
    config(['catalogue.enquiries.email.subject' => 'Enquiry from :name']);

    $mail = new EnquiryReceived(savedEnquiry(['name' => "Eve\r\nBcc: victim@example.com"]));

    expect($mail->envelope()->subject)->toBe('Enquiry from Eve Bcc: victim@example.com');
});
