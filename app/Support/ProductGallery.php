<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductImage;

/**
 * The product page's media list, and the one rule that decides which of it a
 * shopper is looking at.
 *
 * The gallery used to be a flat list built inline in the Blade template. It is
 * lifted out here because it stopped being flat: each photo can now name the
 * shade and the fabric it shows, the pickers beside the gallery choose those,
 * and "which frames are on screen right now" became an answer worth having in
 * exactly one place - the browser filters the list as the shopper taps, and the
 * server has to reach the *same* answer for the first paint, before Alpine has
 * booted. Two implementations of that rule would disagree, and the way they
 * would disagree is a flash of the wrong photograph on every page load.
 *
 * The rule, in full:
 *
 *  - A photo tagged with a colour shows only when that colour is selected.
 *  - A photo tagged with a texture shows only when that texture is selected.
 *  - A photo tagged with both shows only when both match. The two are ANDed,
 *    because a tag is a statement about what is *in* the picture: a photo of
 *    the indigo linen is not a photo of the indigo cotton.
 *  - A photo tagged with NEITHER is shared, and shows against every choice.
 *    This is what makes the feature safe to switch on: every image in the
 *    catalogue is untagged today, so every gallery behaves exactly as it did.
 *  - If a selection matches nothing at all, the whole list is shown rather than
 *    an empty frame. A half-tagged product is the normal state of a catalogue
 *    mid-way through being tagged, and a shopper who taps a colour must never
 *    be punished with a blank gallery for it.
 *
 * Names are compared the way every other colour/texture join in this app
 * compares them - {@see ShopFilterCatalogue::normaliseKey()}, i.e. trimmed,
 * inner whitespace collapsed, lower-cased. The library says "Cotton" and a
 * product's attributes JSON may say "cotton"; they are the same fabric.
 */
final class ProductGallery
{
    /**
     * @param  array<int, array<string, mixed>>  $media  the full, ordered list
     * @param  int  $initialIndex  index into $media of the frame that leads
     */
    private function __construct(
        public readonly array $media,
        public readonly int $initialIndex,
    ) {}

    /**
     * Build the list for a product, opened on the given colour and texture.
     *
     * The two fallbacks are different pictures and are kept apart on purpose:
     * a row whose file has gone missing gets the broken-media placeholder, but
     * a product with no rows at all falls through to whatever
     * Product::primary_image_url decides, which is its own separate answer.
     * Collapsing them would quietly change what an image-less product shows.
     *
     * A tag naming something the product does not offer is discarded rather
     * than honoured, and that is a safety belt rather than tidiness: a tagged
     * photo shows only when its tag is selected, so if the shade it names has
     * since been taken off the product, NO selection can ever reach it and the
     * photograph is gone from the shop while still sitting in the admin grid
     * looking present. The admin form drops such a tag on save; this catches
     * the rows that got in another way - the CSV importer, a clone, a hand-run
     * UPDATE - and treats them as what they now are, an untagged shared photo.
     *
     * @param  callable(?string): string  $resolveUrl  turns a stored url into a
     *                                                 browser-usable one
     * @param  string  $brokenUrl  placeholder for a row pointing at nothing
     * @param  string  $emptyUrl  stands in for a product with no media at all
     * @param  iterable<string>|null  $offeredColours  null to accept any tag
     * @param  iterable<string>|null  $offeredTextures  null to accept any tag
     */
    public static function for(
        Product $product,
        callable $resolveUrl,
        string $brokenUrl,
        string $emptyUrl,
        ?string $colour = null,
        ?string $texture = null,
        ?iterable $offeredColours = null,
        ?iterable $offeredTextures = null,
    ): self {
        $colours = self::offered($offeredColours);
        $textures = self::offered($offeredTextures);
        $media = $product->images
            ->sortBy('position')
            ->map(fn (ProductImage $image) => [
                'url' => $resolveUrl($image->url) ?: $brokenUrl,
                'type' => $image->media_type ?? 'image',
                'thumb' => $image->thumbnail_url ? $resolveUrl($image->thumbnail_url) : null,
                'main' => (bool) $image->is_primary,
                // Emitted for the browser to filter on. Normalised here rather
                // than in the template so the two sides of the comparison are
                // prepared by the same code.
                'colour' => self::keep(self::tag($image->colour), $colours),
                'texture' => self::keep(self::tag($image->texture), $textures),
            ])
            ->values()
            ->all();

        if ($media === []) {
            $media = [[
                'url' => $emptyUrl,
                'type' => 'image',
                'thumb' => null,
                'main' => true,
                'colour' => null,
                'texture' => null,
            ]];
        }

        return new self($media, self::leadIndex($media, $colour, $texture));
    }

    /**
     * The offered names, normalised, or null when the caller did not say.
     *
     * @param  iterable<string>|null  $names
     * @return array<int, string>|null
     */
    private static function offered(?iterable $names): ?array
    {
        if ($names === null) {
            return null;
        }

        $keys = [];

        foreach ($names as $name) {
            $key = self::tag((string) $name);

            if ($key !== null) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Honour a tag, or forget it because the product no longer offers it.
     *
     * @param  array<int, string>|null  $offered
     */
    private static function keep(?string $tag, ?array $offered): ?string
    {
        if ($tag === null || $offered === null) {
            return $tag;
        }

        return in_array($tag, $offered, true) ? $tag : null;
    }

    /** Normalised for comparison, or null when the photo carries no such tag. */
    public static function tag(?string $value): ?string
    {
        $value = ShopFilterCatalogue::normaliseKey((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Does this media row belong on screen for the given selection?
     *
     * The mirror of kkMatches() in the product page's Alpine component. Change
     * one and you must change the other; they exist twice only because one runs
     * before the page is interactive and the other runs after.
     *
     * @param  array<string, mixed>  $row
     */
    public static function matches(array $row, ?string $colour, ?string $texture): bool
    {
        $wantColour = self::tag($colour);
        $wantTexture = self::tag($texture);

        // An untagged photo is shared and always shows. A tagged one shows only
        // when the shopper has actually chosen that value - a product page with
        // no colour picker at all therefore hides nothing, because nothing on
        // it could ever be selected.
        if ($row['colour'] !== null && $row['colour'] !== $wantColour) {
            return false;
        }

        return $row['texture'] === null || $row['texture'] === $wantTexture;
    }

    /**
     * Indices of the rows on screen for a selection, in order.
     *
     * Empty is never returned: a selection that matches nothing falls back to
     * the whole list, so the frame is never blank.
     *
     * @param  array<int, array<string, mixed>>  $media
     * @return array<int, int>
     */
    public static function visibleIndices(array $media, ?string $colour, ?string $texture): array
    {
        $visible = [];

        foreach ($media as $index => $row) {
            if (self::matches($row, $colour, $texture)) {
                $visible[] = $index;
            }
        }

        return $visible === [] ? array_keys($media) : $visible;
    }

    /**
     * Which frame the page opens on.
     *
     * The one the admin marked as main when that photo is on screen for this
     * selection, otherwise the first that is. This index is load-bearing beyond
     * the obvious: the product page exempts exactly one slide from x-cloak and
     * pins loading="eager"/fetchpriority="high" to it, so getting it wrong
     * means either a blank gallery until Alpine boots or the browser racing to
     * fetch a photograph nobody is looking at.
     *
     * @param  array<int, array<string, mixed>>  $media
     */
    public static function leadIndex(array $media, ?string $colour, ?string $texture): int
    {
        $visible = self::visibleIndices($media, $colour, $texture);

        foreach ($visible as $index) {
            if (! empty($media[$index]['main'])) {
                return $index;
            }
        }

        return $visible[0] ?? 0;
    }

    /**
     * The tags alone, in media order, for the browser to filter on.
     *
     * A flat list rather than the whole media array: the frames themselves are
     * already rendered into the page as HTML, and shipping their urls a second
     * time as JSON would put every product photograph on the page twice.
     *
     * @return array<int, array{colour: ?string, texture: ?string}>
     */
    public function tags(): array
    {
        return array_map(
            fn (array $row) => ['colour' => $row['colour'], 'texture' => $row['texture']],
            $this->media,
        );
    }

    /**
     * Where the frame the admin marked as main sits in the list.
     *
     * Not the same question as {@see $initialIndex}: the main photo may be
     * tagged with a colour the page does not open on, in which case the page
     * leads with something else and this still points at the main one. The
     * product page needs both - it opens on the lead, and it restarts the main
     * video when a shopper steps back onto it.
     */
    public function mainIndex(): int
    {
        foreach ($this->media as $index => $row) {
            if (! empty($row['main'])) {
                return $index;
            }
        }

        return 0;
    }

    /** Is the media marked main a video, and so allowed to start on its own? */
    public function mainIsVideo(): bool
    {
        return ($this->media[$this->mainIndex()]['type'] ?? 'image') === 'video';
    }

    /**
     * The frames the page opens with, which is not always all of them.
     *
     * Rendered into the markup as well as handed to the browser: the arrows,
     * the counter, the thumbnail rail and the width the gallery reserves for it
     * are all bound with x-show, and x-show only takes effect once Alpine has
     * booted. Without the server reaching the same answer first, every tagged
     * product would paint the full nine-frame gallery and then visibly collapse
     * to four.
     *
     * @return array<int, int>
     */
    public function initialVisible(?string $colour, ?string $texture): array
    {
        return self::visibleIndices($this->media, $colour, $texture);
    }

    /** How many frames the page opens with, which is not always all of them. */
    public function initialVisibleCount(?string $colour, ?string $texture): int
    {
        return count($this->initialVisible($colour, $texture));
    }

    /**
     * Does any photo on this product carry a tag at all?
     *
     * The pickers only need to drive the gallery on a product somebody has
     * actually tagged. On every other product the extra reactivity is dead
     * weight, and - more to the point - the arrows, the counter and the
     * thumbnail rail can keep their original, simpler behaviour.
     */
    public function isTagged(): bool
    {
        foreach ($this->media as $row) {
            if ($row['colour'] !== null || $row['texture'] !== null) {
                return true;
            }
        }

        return false;
    }
}
