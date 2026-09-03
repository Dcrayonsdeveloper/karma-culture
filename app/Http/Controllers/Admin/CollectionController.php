<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCollection;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Collections: hand-picked groups of products with their own page and an
 * optional header link.
 *
 * The four built-in listings the header already carries - Shop All, New In,
 * Bestsellers, Introductory Offer - are computed from the catalogue and have
 * no list to add a product to. This is the assignable kind alongside them.
 * Products are ticked on the product form itself, next to the category
 * shelves, because that is where someone is already deciding where a product
 * belongs.
 */
class CollectionController extends Controller
{
    /** A URL segment: lower-case words joined by single hyphens. */
    private const SLUG_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * Slugs the storefront already answers on.
     *
     * A collection called "shop" would sit at /collection/shop, which is fine,
     * but one called "new-arrivals" reads as though it were the built-in New
     * In page and is not - so the names that would confuse are refused here
     * rather than explained later.
     */
    private const RESERVED_SLUGS = ['shop', 'new-arrivals', 'bestsellers', 'deals', 'search', 'cart', 'checkout'];

    public function index(): View
    {
        $collections = ProductCollection::query()
            ->withCount('products')
            ->orderBy('position')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.collections.index', compact('collections'));
    }

    public function create(): View
    {
        return view('admin.collections.create');
    }

    public function edit(ProductCollection $collection): View
    {
        return view('admin.collections.edit', compact('collection'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?ProductCollection $collection = null): array
    {
        return [
            'name' => V::text(max: 100, min: 2),
            'slug' => [
                'nullable', 'string', 'max:120',
                'regex:'.self::SLUG_REGEX,
                Rule::notIn(self::RESERVED_SLUGS),
                Rule::unique('collections', 'slug')->ignore($collection?->id),
            ],
            'description' => V::text(required: false, max: 255),
            'is_active' => V::boolean(),
            'show_in_header' => V::boolean(),
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    private function messages(): array
    {
        return [
            'slug.regex' => 'The URL may only contain lower-case letters, numbers and single hyphens.',
            'slug.not_in' => 'That URL is already used by one of the built-in pages. Pick another.',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function prepare(Request $request, array $validated): array
    {
        // Derived from the name when left blank, the same way pages do it.
        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['show_in_header'] = $request->boolean('show_in_header');
        $validated['position'] = (int) ($validated['position'] ?? 0);

        return $validated;
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->prepare($request, $request->validate($this->rules(), $this->messages()));

        // A slug derived from the name still has to be unique and still has to
        // dodge the built-ins - the rules above only checked what was typed.
        if ($this->slugTaken($validated['slug'])) {
            return back()->withInput()->withErrors([
                'name' => 'Another collection already uses this name. Change it, or set the URL by hand.',
            ]);
        }

        ProductCollection::create($validated);

        return redirect()->route('admin.collections.index')->with('success', 'Collection created');
    }

    public function update(Request $request, ProductCollection $collection): RedirectResponse
    {
        $validated = $this->prepare($request, $request->validate($this->rules($collection), $this->messages()));

        if ($this->slugTaken($validated['slug'], $collection->id)) {
            return back()->withInput()->withErrors([
                'name' => 'Another collection already uses this name. Change it, or set the URL by hand.',
            ]);
        }

        $collection->update($validated);

        return redirect()->route('admin.collections.index')->with('success', 'Collection updated');
    }

    public function destroy(ProductCollection $collection): RedirectResponse
    {
        $collection->delete();

        return redirect()->route('admin.collections.index')->with('success', 'Collection deleted');
    }

    private function slugTaken(string $slug, ?int $ignoreId = null): bool
    {
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return true;
        }

        return ProductCollection::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }
}
