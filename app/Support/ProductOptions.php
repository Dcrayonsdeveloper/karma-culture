<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The sizes and colours a product actually offers.
 *
 * There were three copies of this derivation - the product page rendered its
 * size buttons and colour swatches from one, CartController::add validated the
 * chosen size and colour against another, and anything else that wanted to
 * offer the same choice had to write a third. They already disagreed in small
 * ways, and the quick-add popup on the listing cards would have been a fourth:
 * a card that offers a size the cart then refuses is worse than a card with no
 * quick-add at all.
 *
 * Both fallbacks are kept exactly as the product page had them, because the
 * server must accept precisely what a page can offer and nothing else:
 *  - sizes come from the active "Sizes & pricing" variant rows, falling back to
 *    a free-text Size attribute on older products;
 *  - colours come from the product-level Colours attribute, falling back to the
 *    Colour recorded on the variant rows.
 *
 * Textures are the same kind of product-level list as colours, but plain names
 * with no swatch, and they have no fallback: nothing has ever written a texture
 * onto a variant row, so there is no older shape to read.
 */
final class ProductOptions
{
    /**
     * @param  Collection<int, string>  $sizes
     * @param  Collection<int, array{name: string, hex: ?string}>  $colours
     * @param  Collection<int, string>  $textures
     * @param  Collection<string, int>  $sizeVariants  size label => variant id
     * @param  Collection<int, ProductVariant>  $rows
     */
    private function __construct(
        public readonly Collection $sizes,
        public readonly Collection $colours,
        public readonly Collection $textures,
        public readonly Collection $sizeVariants,
        public readonly Collection $rows,
    ) {}

    public static function for(Product $product): self
    {
        $rows = self::rows($product);

        $sizes = $rows->pluck('name')->map(fn ($n) => trim((string) $n))->filter()->unique()->values();

        // Fallback for products still holding sizes as free text on the old Size
        // attribute (e.g. "CX   M   XL"), so each size is still offered on its
        // own until the product is given proper size rows.
        if ($sizes->isEmpty()) {
            $sizes = collect($product->attributes ?? [])
                ->filter(fn ($v, $k) => Str::contains(Str::lower($k), 'size'))
                ->flatMap(fn ($v) => is_array($v) ? $v : preg_split('/[,\/|]+|\s{2,}/', (string) $v))
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique()
                ->values();
        }

        // Colours are a product-level list set in its own admin section, so a
        // product can come in any colour without one size row per combination.
        $colours = collect(data_get($product->attributes, 'Colours', []))
            ->map(fn ($c) => is_array($c)
                ? ['name' => trim((string) ($c['name'] ?? '')), 'hex' => $c['hex'] ?? null]
                : ['name' => trim((string) $c), 'hex' => null])
            ->filter(fn ($c) => $c['name'] !== '');

        if ($colours->isEmpty()) {
            $colours = $rows
                ->map(fn ($v) => [
                    'name' => trim((string) data_get($v->attributes, 'Colour', '')),
                    'hex' => data_get($v->attributes, 'colour_hex'),
                ])
                ->filter(fn ($c) => $c['name'] !== '');
        }

        // Textures read through the catalogue's own list reader, so the shop
        // rail and the cart cannot come to different conclusions about what a
        // product offers - and so a hand-edited {name: ...} entry is still
        // understood.
        $textures = collect(ShopFilterCatalogue::listFrom($product->attributes, ShopFilterCatalogue::TEXTURES_KEY))
            ->map(fn (array $entry) => $entry[0])
            ->unique()
            ->values();

        // reverse() so the FIRST row wins a duplicated size label: mapWithKeys
        // keeps the last write, and the page has always pointed a repeated size
        // at the row nearest the top of the admin list.
        $sizeVariants = $rows->reverse()
            ->mapWithKeys(fn ($v) => [trim((string) $v->name) => $v->id])
            ->filter(fn ($id, $name) => $name !== '');

        return new self(
            $sizes,
            $colours->unique('name')->values(),
            $textures,
            $sizeVariants,
            $rows,
        );
    }

    /** @return Collection<int, string> */
    public function colourNames(): Collection
    {
        return $this->colours->pluck('name');
    }

    /** Colour name => hex, with the colours that have no hex left out. */
    public function colourHex(): Collection
    {
        return $this->colours->pluck('hex', 'name')->filter();
    }

    public function offersSize(string $chosen): bool
    {
        return self::contains($this->sizes, $chosen);
    }

    public function offersColour(string $chosen): bool
    {
        return self::contains($this->colourNames(), $chosen);
    }

    public function offersTexture(string $chosen): bool
    {
        return self::contains($this->textures, $chosen);
    }

    /**
     * The active "Sizes & pricing" rows, read off the loaded relation when the
     * caller already has it so a page rendering a grid of cards does not pay a
     * query per card.
     *
     * @return Collection<int, ProductVariant>
     */
    private static function rows(Product $product): Collection
    {
        return $product->relationLoaded('variants')
            ? $product->variants->where('is_active', true)->values()
            : $product->variants()->where('is_active', true)->get();
    }

    /**
     * Case- and spacing-insensitive membership, so a value that made the round
     * trip through a page is never rejected over its casing.
     *
     * @param  Collection<int, string>  $offered
     */
    private static function contains(Collection $offered, string $chosen): bool
    {
        $needle = Str::lower(trim($chosen));

        return $offered->contains(fn ($option) => Str::lower(trim((string) $option)) === $needle);
    }
}
