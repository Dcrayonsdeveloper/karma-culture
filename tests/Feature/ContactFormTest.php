<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * The contact form and the wholesale enquiry form both POST to /contact.
 *
 * The server rules were there, but neither form told the customer anything
 * before the round trip: the inputs carried a bare `required` and nothing else,
 * so a one-letter name or a three-word message sailed past the browser and came
 * back as a full page reload. The wholesale form was worse - it rendered no
 * @error blocks and no old() values at all, so a rejected enquiry bounced back
 * to an empty form with no explanation.
 *
 * These lock both halves together: the markup advertises exactly the limits the
 * server enforces, and the server enforces them regardless of the markup.
 */
class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Asha Menon',
            'email' => 'asha@example.test',
            'phone' => '+91 98765 43210',
            'subject' => 'Question about an order',
            'message' => 'I placed an order last week and would like to change the delivery address.',
        ], $overrides);
    }

    public function test_the_contact_form_advertises_the_limits_the_server_enforces(): void
    {
        $response = $this->get(route('contact'));

        $response->assertStatus(200);
        $response->assertSee('minlength="2" maxlength="100"', false);   // name
        $response->assertSee('required maxlength="255"', false);        // email
        $response->assertSee('minlength="3" maxlength="200"', false);   // subject
        $response->assertSee('minlength="10" maxlength="5000"', false); // message
        $response->assertSee('inputmode="tel"', false);
    }

    public function test_the_wholesale_form_advertises_the_same_limits(): void
    {
        $response = $this->get(route('wholesale'));

        $response->assertStatus(200);
        $response->assertSee('minlength="2" maxlength="100"', false);   // contact name
        $response->assertSee('required maxlength="120"', false);        // business name
        $response->assertSee('minlength="10" maxlength="5000"', false); // message
        $response->assertSee('inputmode="tel"', false);
    }

    public function test_a_complete_message_reaches_the_enquiry_inbox(): void
    {
        $response = $this->post(route('contact.send'), $this->validPayload());

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('enquiries', [
            'name' => 'Asha Menon',
            'email' => 'asha@example.test',
            'subject' => 'Question about an order',
        ]);
    }

    public function test_an_empty_submission_is_rejected_field_by_field(): void
    {
        $response = $this->post(route('contact.send'), []);

        $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
        $this->assertSame(0, Enquiry::count());
    }

    public function test_a_one_letter_name_and_a_terse_message_are_rejected(): void
    {
        $response = $this->post(route('contact.send'), $this->validPayload([
            'name' => 'J',
            'subject' => 'Hi',
            'message' => 'call me',
        ]));

        $response->assertSessionHasErrors(['name', 'subject', 'message']);
        $this->assertSame(0, Enquiry::count());
    }

    /** 'email:strict' is what rejects a missing TLD; plain 'email' accepts it. */
    public function test_an_address_without_a_tld_is_rejected(): void
    {
        $response = $this->post(route('contact.send'), $this->validPayload([
            'email' => 'asha@gmail',
        ]));

        $response->assertSessionHasErrors('email');
        $this->assertSame(0, Enquiry::count());
    }

    public function test_markup_in_the_message_never_reaches_the_database(): void
    {
        $response = $this->post(route('contact.send'), $this->validPayload([
            'message' => 'Please call me back <script>alert(1)</script> about my order.',
        ]));

        $response->assertSessionHasErrors('message');
        $this->assertSame(0, Enquiry::count());
    }

    public function test_the_phone_is_optional_but_junk_is_refused(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $this->post(route('contact.send'), $this->validPayload(['phone' => null]))
            ->assertSessionHasNoErrors();

        $this->post(route('contact.send'), $this->validPayload(['phone' => 'call me maybe']))
            ->assertSessionHasErrors('phone');

        // Too few digits to be a number anyone can call back.
        $this->post(route('contact.send'), $this->validPayload(['phone' => '12345']))
            ->assertSessionHasErrors('phone');

        // A wholesale buyer abroad is not an error.
        $this->post(route('contact.send'), $this->validPayload(['phone' => '+1 (555) 123-4567']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Enquiry::count());
    }

    public function test_a_wholesale_enquiry_keeps_the_business_name(): void
    {
        $response = $this->post(route('contact.send'), $this->validPayload([
            'business_name' => 'Kumar Textiles',
            'subject' => 'Wholesale Enquiry',
        ]));

        $response->assertSessionHasNoErrors();

        $enquiry = Enquiry::firstOrFail();
        $this->assertStringContainsString('Business: Kumar Textiles', $enquiry->message);
    }

    public function test_a_rejected_wholesale_enquiry_comes_back_with_the_answers_intact(): void
    {
        $this->from(route('wholesale'))
            ->post(route('contact.send'), $this->validPayload([
                'business_name' => 'Kumar Textiles',
                'subject' => 'Wholesale Enquiry',
                'message' => 'too short',
            ]))
            ->assertRedirect(route('wholesale'));

        $response = $this->get(route('wholesale'));

        $response->assertSee('value="Kumar Textiles"', false);
        $response->assertSee('value="asha@example.test"', false);
        $response->assertSee('at least 10 characters', false);
    }

    public function test_the_endpoint_is_still_throttled(): void
    {
        $hitLimit = false;

        for ($i = 0; $i < 8; $i++) {
            $response = $this->post(route('contact.send'), $this->validPayload([
                'email' => "asha{$i}@example.test",
            ]));

            if ($response->getStatusCode() === 429) {
                $hitLimit = true;
                break;
            }
        }

        $this->assertTrue($hitLimit, 'POST /contact should still be throttled.');
    }
}
