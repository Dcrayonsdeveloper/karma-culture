<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The newsletter pill told the customer to enter an email address and left
 * nowhere to type one.
 *
 * .kk-field-error carries flex-basis: 100%, which is what keeps a message under
 * its field rather than beside it - but that only works if the row can wrap.
 * The pill cannot:
 *
 *     .kk-newsletter-form { display: flex }            <- no flex-wrap
 *     .kk-newsletter-form input { flex: 1; min-width: 0 }
 *
 * so the note claimed the full width and the input shrank to zero against it.
 * Reproduced in Chrome with the real CSS: input 40px wide, message sitting
 * inside the pill. With the fix the input measures 345px and the message sits
 * under the whole control - and a normal column form is left alone, its
 * message still between its own field and the next one.
 */
class NewsletterErrorPlacementTest extends TestCase
{
    private function appJs(): string
    {
        return file_get_contents(resource_path('js/app.js'));
    }

    public function test_the_message_steps_out_of_a_row_that_cannot_wrap(): void
    {
        $js = $this->appJs();

        $this->assertStringContainsString(
            'function rowAwareAnchor(',
            $js,
            'Without it the note is inserted into the pill and squeezes the input to nothing.'
        );
        $this->assertStringContainsString(
            'rowAwareAnchor(wrapperFor(field))',
            $js,
            'The helper exists but showError() is not using it.'
        );
    }

    /**
     * The three conditions are the whole point. Drop any one and the fix
     * either stops working or starts moving messages that were fine.
     */
    public function test_it_only_steps_out_of_a_nowrap_flex_row(): void
    {
        $js = $this->appJs();

        $this->assertMatchesRegularExpression('/style\.display === .flex./', $js, 'It would step out of block containers too.');
        $this->assertMatchesRegularExpression(
            '/flexDirection\.indexOf\(.column.\)/',
            $js,
            'A flex COLUMN is the ordinary stack of fields; stepping out of one carries the message away from its field.'
        );
        $this->assertMatchesRegularExpression(
            '/flexWrap !== .nowrap./',
            $js,
            'A wrapping row already breaks the note onto its own line and needs no help.'
        );
    }

    /**
     * The guard used to stop at `form`, which is exactly where it had to keep
     * going: on the newsletter pill the FORM is the flex row.
     */
    public function test_the_walk_is_not_stopped_by_the_form_itself(): void
    {
        $js = $this->appJs();

        $this->assertStringNotContainsString(
            "parent.matches('body, form, fieldset')",
            $js,
            'Stopping at the form leaves the note inside the very row that squeezes it.'
        );
    }

    /**
     * The storefront newsletter is the form this came from, so its shape is
     * worth pinning: a flex row with no wrap, holding an input that shrinks.
     */
    public function test_the_newsletter_pill_is_still_the_shape_that_needed_this(): void
    {
        $home = file_get_contents(resource_path('views/home.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.kk-newsletter-form \{[^}]*display: flex/',
            $home
        );
        $this->assertMatchesRegularExpression(
            '/\.kk-newsletter-form input \{[^}]*min-width: 0/',
            $home,
            'If the input stopped shrinking, this whole rule could be reconsidered.'
        );
    }

    /**
     * One complaint per mistake.
     *
     * Placing the note correctly still left two of them on screen for the same
     * typo: the centred line the section prints for itself, and the shared
     * validator's own note under the pill, left-aligned against the section
     * edge where it read as a complaint about something else on the page.
     *
     * The pill answers for itself, so it opts out of the shared one.
     */
    public function test_the_newsletter_pill_opts_out_of_the_shared_validator(): void
    {
        $home = file_get_contents(resource_path('views/home.blade.php'));

        $this->assertMatchesRegularExpression(
            '/<form[^>]*class="kk-newsletter-form"[^>]*data-no-validate|<form[^>]*data-no-validate[^>]*class="kk-newsletter-form"/',
            $home,
            'Without the opt-out the shared validator prints a second copy of the message beside the centred one.'
        );
    }

    /**
     * And the opt-out has to be the one the blur handler actually reads.
     * `novalidate` is not it: the submit handler stands down for that, the
     * blur handler does not, which is how the second message got in.
     */
    public function test_the_blur_handler_honours_that_opt_out(): void
    {
        $this->assertMatchesRegularExpression(
            '/function skipOnBlur\([^)]*\)\s*\{(?:[^}]|\}(?!\s*\n\s*\}))*data-no-validate/s',
            $this->appJs(),
            'skipOnBlur no longer reads data-no-validate, so opting a form out does nothing on blur.'
        );
    }
}
