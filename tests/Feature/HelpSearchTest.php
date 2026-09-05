<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The Help Center's search box submits ?q= to /faq.
 *
 * Nothing read it. PageController::faq() takes no Request at all, so a shopper
 * who typed "refund" into the one prominent search box on /help landed on the
 * complete, unfiltered FAQ accordion and had to hunt through it by hand - the
 * search appeared to work and did nothing.
 *
 * The questions are static markup, so the parameter is honoured client-side.
 * That keeps /faq a single indexable URL and needs no controller change; these
 * tests pin the wiring the JS depends on, which is what would silently rot.
 */
class HelpSearchTest extends TestCase
{
    public function test_the_help_search_box_submits_to_a_page_that_reads_the_parameter(): void
    {
        $help = $this->get('/help')->assertOk()->getContent();

        // The box still points at /faq and still sends q.
        $this->assertStringContainsString('action="'.route('faq').'"', $help);
        $this->assertStringContainsString('name="q"', $help);
    }

    public function test_the_faq_page_is_wired_to_filter_on_the_query(): void
    {
        $faq = $this->get('/faq?q=refund')->assertOk()->getContent();

        $this->assertStringContainsString('faqAccordion()', $faq);
        $this->assertStringContainsString('applyQuery()', $faq);

        // The filter walks these markers; without them it silently matches nothing.
        $this->assertSame(8, substr_count($faq, 'data-faq '), 'the question blocks lost their marker');
        $this->assertStringContainsString('data-faq-section', $faq);
    }

    public function test_every_question_block_is_marked_so_none_is_invisible_to_the_filter(): void
    {
        $faq = $this->get('/faq')->assertOk()->getContent();

        // One marker per accordion button - a question added without the marker
        // would be silently unreachable through search.
        $this->assertSame(
            substr_count($faq, 'data-faq '),
            substr_count($faq, 'x-collapse'),
            'a question block is missing its data-faq marker'
        );
    }

    public function test_the_faq_page_still_answers_without_a_query(): void
    {
        $this->get('/faq')->assertOk();
    }
}
