<?php

namespace Tests\Feature\Admin;

use App\Mail\EnquiryReplied;
use App\Models\Admin;
use App\Models\Enquiry;
use App\Models\EnquiryReply;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Contact-form enquiries had a reply table and a replies() relation, but no way
 * to use them: the admin detail page offered a status dropdown and internal
 * notes only, so staff had to leave the panel and mail the sender by hand.
 *
 * A support ticket belongs to an account and can be answered in-app. An enquiry
 * does not - email is the only channel back to the sender - so the reply has to
 * survive a mailer that cannot deliver rather than being lost with the 500.
 */
class EnquiryReplyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'first_name' => 'Farah',
            'last_name' => 'Khan',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->admin->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        Auth::guard('admin')->login($this->admin);
    }

    private function enquiry(array $overrides = []): Enquiry
    {
        return Enquiry::create(array_merge([
            'name' => 'Asha Menon',
            'email' => 'asha@example.test',
            'phone' => '+91 98765 43210',
            'subject' => 'Question about an order',
            'message' => "I placed an order last week.\nCan I change the delivery address?",
            'status' => 'new',
        ], $overrides));
    }

    public function test_the_detail_page_offers_a_reply_box(): void
    {
        $enquiry = $this->enquiry();

        $this->get(route('admin.enquiries.show', $enquiry))
            ->assertOk()
            ->assertSee('Reply to Customer')
            ->assertSee(route('admin.enquiries.reply', $enquiry), false)
            ->assertSee('name="message"', false);
    }

    public function test_a_reply_is_stored_and_emailed_to_the_sender(): void
    {
        Mail::fake();

        $enquiry = $this->enquiry();

        $this->post(route('admin.enquiries.reply', $enquiry), [
            'message' => "Yes - we can update the address before dispatch.\nSend us the new one.",
        ])->assertRedirect()->assertSessionHas('success');

        $reply = EnquiryReply::where('enquiry_id', $enquiry->id)->sole();

        $this->assertSame($this->admin->id, $reply->user_id);
        $this->assertStringContainsString('update the address', $reply->message);
        $this->assertSame('replied', $enquiry->fresh()->status);

        Mail::assertSent(EnquiryReplied::class, function (EnquiryReplied $mail) use ($enquiry) {
            return $mail->hasTo($enquiry->email)
                && $mail->enquiry->is($enquiry)
                && str_contains($mail->replyMessage, 'update the address');
        });
    }

    public function test_the_reply_shows_in_the_thread_afterwards(): void
    {
        Mail::fake();

        $enquiry = $this->enquiry();

        $this->post(route('admin.enquiries.reply', $enquiry), [
            'message' => 'Thanks for writing in - the address has been updated.',
        ]);

        $this->get(route('admin.enquiries.show', $enquiry))
            ->assertOk()
            ->assertSee('the address has been updated', false)
            ->assertSee('Farah Khan');
    }

    public function test_a_too_short_reply_is_rejected_and_nothing_is_sent(): void
    {
        Mail::fake();

        $enquiry = $this->enquiry();

        $this->from(route('admin.enquiries.show', $enquiry))
            ->post(route('admin.enquiries.reply', $enquiry), ['message' => 'ok'])
            ->assertRedirect(route('admin.enquiries.show', $enquiry))
            ->assertSessionHasErrors('message');

        $this->assertSame(0, EnquiryReply::where('enquiry_id', $enquiry->id)->count());
        $this->assertSame('new', $enquiry->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_the_reply_survives_a_mailer_that_cannot_deliver(): void
    {
        // The contact form accepts anything shaped like an address, so unroutable
        // senders reach this page. The typed answer must not die with the send.
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Address "test@test" is invalid.'));

        $enquiry = $this->enquiry(['email' => 'test@test']);

        $this->post(route('admin.enquiries.reply', $enquiry), [
            'message' => 'We have received your enquiry and are looking into it.',
        ])->assertRedirect()->assertSessionHas('warning');

        $this->assertSame(1, EnquiryReply::where('enquiry_id', $enquiry->id)->count());

        // An undelivered reply is still an open enquiry - flipping it to replied
        // would drop it out of the triage queue with nobody the wiser.
        $this->assertSame('new', $enquiry->fresh()->status);
    }

    public function test_the_emailed_reply_says_what_the_admin_typed(): void
    {
        // The mail is markdown, and CommonMark quietly eats plain text that looks
        // like markup: "[Order]: KK-4821" on its own line is a link reference
        // definition and renders as nothing at all, and an indented line becomes a
        // code block that breaks out of the quoted-message panel.
        $enquiry = $this->enquiry([
            'subject' => 'Order *KK-4821*',
            'message' => "My address is:\n    Flat 3, Green Park\n    New Delhi 110016",
        ]);

        $html = (new EnquiryReplied(
            $enquiry,
            "Here are the details:\n[Order]: KK-4821\n[Status]: Shipped\nLet us know if you need anything else."
        ))->render();

        $this->assertStringContainsString('[Order]: KK-4821', $html);
        $this->assertStringContainsString('[Status]: Shipped', $html);
        $this->assertStringContainsString('Green Park', $html);
        $this->assertStringContainsString('New Delhi 110016', $html);
        $this->assertStringNotContainsString('<pre', $html);
    }

    public function test_a_junk_contact_email_setting_does_not_block_the_send(): void
    {
        Setting::updateOrCreate(
            ['key' => 'contact_email'],
            ['group' => 'contact', 'value' => 'not-an-email', 'type' => 'string']
        );

        Mail::fake();

        $enquiry = $this->enquiry();

        $this->post(route('admin.enquiries.reply', $enquiry), [
            'message' => 'We have received your enquiry and are looking into it.',
        ])->assertRedirect()->assertSessionHas('success');

        Mail::assertSent(EnquiryReplied::class);
        $this->assertSame('replied', $enquiry->fresh()->status);
    }
}
