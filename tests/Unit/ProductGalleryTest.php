<?php

namespace Tests\Unit;

use App\Support\ProductGallery;
use PHPUnit\Framework\TestCase;

/**
 * The rule that decides which photographs a shopper is looking at.
 *
 * This is the half of the feature that exists twice - once in PHP for the first
 * paint and once in JavaScript for every tap after it - so it is the half worth
 * pinning down. Everything here is the PHP copy; the JavaScript copy is
 * kkMatches()/visibleImages in resources/views/products/show.blade.php and is
 * written to read the same way on purpose.
 *
 * Deliberately a unit test with no database: the rule is about arrays of tags
 * and nothing else, and it should be possible to change a product's gallery
 * behaviour and find out in a second whether the rule still holds.
 */
class ProductGalleryTest extends TestCase
{
    /** @return array<string, mixed> */
    private function frame(?string $colour = null, ?string $texture = null, bool $main = false): array
    {
        return [
            'url' => '/storage/products/x.webp',
            'type' => 'image',
            'thumb' => null,
            'main' => $main,
            'colour' => ProductGallery::tag($colour),
            'texture' => ProductGallery::tag($texture),
        ];
    }

    public function test_an_untagged_photo_shows_whatever_is_chosen(): void
    {
        $shared = $this->frame();

        $this->assertTrue(
            ProductGallery::matches($shared, 'Indigo', 'Linen'),
            'A photo naming no shade and no fabric is a shared shot - the size chart, the fabric close-up - and belongs on screen against every choice.'
        );
        $this->assertTrue(
            ProductGallery::matches($shared, null, null),
            'It is also what an untagged catalogue is made of, so it must show when nothing at all is selected.'
        );
    }

    public function test_a_photo_shows_only_for_the_shade_it_names(): void
    {
        $indigo = $this->frame('Indigo');

        $this->assertTrue(ProductGallery::matches($indigo, 'Indigo', null));
        $this->assertFalse(
            ProductGallery::matches($indigo, 'Rust', null),
            'Tapping Rust must not leave the Indigo photograph on screen - that is the whole feature.'
        );
    }

    public function test_shade_and_fabric_must_both_agree(): void
    {
        $indigoLinen = $this->frame('Indigo', 'Linen');

        $this->assertTrue(ProductGallery::matches($indigoLinen, 'Indigo', 'Linen'));
        $this->assertFalse(
            ProductGallery::matches($indigoLinen, 'Indigo', 'Cotton'),
            'A photo of the indigo linen is not a photo of the indigo cotton.'
        );
        $this->assertFalse(ProductGallery::matches($indigoLinen, 'Rust', 'Linen'));
    }

    public function test_names_match_across_spacing_and_case(): void
    {
        $frame = $this->frame('  Deep   Indigo ');

        $this->assertTrue(
            ProductGallery::matches($frame, 'deep indigo', null),
            'The library and the product are typed by different people on different screens; "Deep Indigo" and "deep indigo" are one shade.'
        );
    }

    public function test_a_selection_with_no_photographs_shows_the_whole_strip(): void
    {
        $media = [$this->frame('Indigo'), $this->frame('Rust')];

        $this->assertSame(
            [0, 1],
            ProductGallery::visibleIndices($media, 'Moss', null),
            'A half-tagged product is the normal state of a catalogue mid-way through being tagged. A shopper who taps a shade nobody has photographed yet gets the whole gallery, never an empty frame.'
        );
    }

    public function test_only_the_photographs_of_the_chosen_shade_are_shown(): void
    {
        $media = [
            $this->frame('Indigo'),
            $this->frame('Rust'),
            $this->frame(),           // shared
            $this->frame('Indigo', 'Linen'),
        ];

        $this->assertSame([0, 2, 3], ProductGallery::visibleIndices($media, 'Indigo', 'Linen'));
        $this->assertSame(
            [0, 2],
            ProductGallery::visibleIndices($media, 'Indigo', 'Cotton'),
            'The indigo linen shot drops out when cotton is chosen; the plain indigo one and the shared one stay.'
        );
    }

    public function test_the_page_opens_on_the_main_photo_when_the_chosen_shade_shows_it(): void
    {
        $media = [$this->frame('Rust'), $this->frame('Indigo', main: true)];

        $this->assertSame(1, ProductGallery::leadIndex($media, 'Indigo', null));
    }

    public function test_the_page_opens_on_the_first_visible_photo_when_it_does_not(): void
    {
        $media = [$this->frame('Rust'), $this->frame('Indigo', main: true), $this->frame('Rust')];

        $this->assertSame(
            0,
            ProductGallery::leadIndex($media, 'Rust', null),
            'Leading with the main photo when the opening shade does not show it would cloak the frame the shopper can actually see - x-cloak and fetchpriority are both pinned to this index.'
        );
    }

    public function test_the_lead_is_a_visible_index_even_when_nothing_matches(): void
    {
        $media = [$this->frame('Indigo'), $this->frame('Rust')];

        $this->assertSame(
            0,
            ProductGallery::leadIndex($media, 'Moss', null),
            'The fallback-to-everything rule has to reach leadIndex too, or the page opens on an index it will not draw.'
        );
    }

    public function test_a_blank_tag_is_the_same_as_no_tag(): void
    {
        $this->assertNull(ProductGallery::tag(''));
        $this->assertNull(ProductGallery::tag('   '));
        $this->assertNull(ProductGallery::tag(null));
        $this->assertSame('indigo', ProductGallery::tag('Indigo'));
    }
}
