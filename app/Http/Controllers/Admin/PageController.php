<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends Controller
{
    /** A URL segment: lower-case words joined by single hyphens. */
    private const SLUG_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * Where a page can be listed, keyed by the navigation_menus.location value.
     *
     * The wording matches the Navigation editor's own labels, so an admin who
     * picks "Footer - Policies" here finds the link under that same heading
     * there. Header and the three footer columns are the only locations the
     * storefront actually renders; there is no sidebar menu to offer.
     */
    private const NAV_LOCATIONS = [
        // The header is deliberately not offered. It is a short, ordered bar of
        // shopping destinations, and a CMS page dropped into it pushes those
        // out; policy and information pages belong in the footer columns. A
        // header link to a page is still possible by hand under Online Store ->
        // Homepage -> Navigation, which is where menu order is managed anyway.
        'footer_col1' => 'Footer - Quick Links',
        'footer_col2' => 'Footer - Customer Service',
        'footer_col3' => 'Footer - Policies',
    ];

    /**
     * Tags the rich-text editor is allowed to produce.
     *
     * The union of the allowlists resources/views/pages/show.blade.php and
     * pages/legal-page.blade.php render with, so sanitising on write never
     * strips markup those pages would have displayed. Doing it on write as
     * well as on output keeps the payload out of the row entirely, which
     * matters for every consumer that is not a Blade template.
     */
    private const CONTENT_TAGS = '<p><br><strong><b><em><i><u><ul><ol><li>'
        .'<h1><h2><h3><h4><h5><h6><a><span><div><table><tr><td><th><thead><tbody>'
        .'<img><blockquote><hr><figure><figcaption>';

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['published', 'draft'])],
        ]);

        $query = Page::query();

        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($status = $filters['status'] ?? null) {
            $query->where('is_published', $status === 'published');
        }

        $pages = $query->latest()->paginate(15)->withQueryString();

        return view('admin.pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('admin.pages.create', ['navLocations' => self::NAV_LOCATIONS]);
    }

    /**
     * The rule set shared by store() and update().
     *
     * The slug is derived from the title when the field is left blank, so the
     * derived value needs the same uniqueness check as a typed one - without
     * it a second page called "Shipping Policy" passed validation and then hit
     * the unique index as a 500.
     *
     * @return array<string, mixed>
     */
    private function rules(Request $request, ?Page $page = null): array
    {
        return [
            'title' => [
                ...V::text(max: 255, min: 2),
                function (string $attribute, mixed $value, \Closure $fail) use ($request, $page): void {
                    if ($request->filled('slug')) {
                        return;
                    }

                    $slug = Str::slug((string) $value);

                    if ($slug === '') {
                        $fail('The title must contain at least one letter or number, or set a slug by hand.');

                        return;
                    }

                    $taken = Page::where('slug', $slug)
                        ->when($page, fn ($q) => $q->whereKeyNot($page->getKey()))
                        ->exists();

                    if ($taken) {
                        $fail('Another page already uses this title. Change it, or set a slug by hand.');
                    }
                },
            ],
            'slug' => [
                'nullable', 'string', 'max:255',
                'regex:'.self::SLUG_REGEX,
                Rule::unique('pages', 'slug')->ignore($page?->id),
            ],
            // Rich text by design, so NoHtml would reject every real page. The
            // allowlist in prepare() is what stands in for it.
            'content' => ['nullable', 'string', 'max:200000'],
            'seo_data' => ['nullable', 'array'],
            'seo_data.meta_title' => V::text(required: false, max: 255),
            'seo_data.meta_description' => V::textarea(required: false, max: 500),
            'is_published' => V::boolean(),

            // Blank means "do not list this page anywhere", which is also what
            // every page created before this field existed says.
            'nav_location' => ['nullable', Rule::in(array_keys(self::NAV_LOCATIONS))],
        ];
    }

    private function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lower-case letters, numbers and single hyphens.',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function prepare(Request $request, array $validated): array
    {
        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['title']);
        $validated['is_published'] = $request->boolean('is_published');
        $validated['content'] = safe_html($validated['content'] ?? null, self::CONTENT_TAGS) ?: null;

        $seo = array_filter([
            'meta_title' => $validated['seo_data']['meta_title'] ?? null,
            'meta_description' => $validated['seo_data']['meta_description'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $validated['seo_data'] = $seo ?: null;

        return $validated;
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->prepare(
            $request,
            $request->validate($this->rules($request), $this->messages())
        );

        if ($validated['is_published']) {
            $validated['published_at'] = now();
        }

        $page = Page::create($validated);
        $this->syncMenuLink($page, $request->input('nav_location'));

        return redirect()->route('admin.pages.index')->with('success', 'Page created successfully');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', [
            'page' => $page,
            'navLocations' => self::NAV_LOCATIONS,
            'navLocation' => $page->menuLink?->location,
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $validated = $this->prepare(
            $request,
            $request->validate($this->rules($request, $page), $this->messages())
        );

        if ($validated['is_published'] && !$page->published_at) {
            $validated['published_at'] = now();
        } elseif (!$validated['is_published']) {
            $validated['published_at'] = null;
        }

        // Read before the save: update() re-syncs the model's originals, so
        // getOriginal('title') inside syncMenuLink() would hand back the new
        // title and every label would look like it had been retyped by hand.
        $previousTitle = $page->title;

        $page->update($validated);
        $this->syncMenuLink($page, $request->input('nav_location'), $previousTitle);

        return redirect()->route('admin.pages.index')->with('success', 'Page updated successfully');
    }

    /**
     * Put the page's link where the form says, or take it out of the menus.
     *
     * One row per page, found by page_id rather than by URL so a renamed slug
     * moves its own link instead of orphaning it and adding a second.
     *
     * The link tracks the page on three counts: its address (a renamed slug
     * would otherwise 404 from the menu), the column it belongs in, and
     * whether it is live. A draft page's link is parked inactive rather than
     * pointing visitors at a page that answers 404 - publishing lights it up,
     * and the form says so.
     *
     * The label is the exception. It follows the page title only while nobody
     * has retyped it in the Navigation editor: an admin who shortened "Shipping
     * & Delivery Policy" to "Shipping" there does not want the next content
     * edit to undo that.
     */
    private function syncMenuLink(Page $page, ?string $location, ?string $previousTitle = null): void
    {
        $existing = NavigationMenu::where('page_id', $page->id)->first();

        if (! $location) {
            $existing?->delete();

            return;
        }

        $attributes = [
            'location' => $location,
            'url' => route('page.show', $page->slug, absolute: false),
            'is_active' => (bool) $page->is_published,
        ];

        if (! $existing) {
            NavigationMenu::create($attributes + [
                'page_id' => $page->id,
                'label' => $page->title,
                'position' => (int) NavigationMenu::where('location', $location)->max('position') + 1,
            ]);

            return;
        }

        if ($existing->label === ($previousTitle ?? $page->title)) {
            $attributes['label'] = $page->title;
        }

        $existing->update($attributes);
    }

    /**
     * Flip a page between published and draft straight from the list.
     *
     * Opening the editor and unticking a checkbox was the only way to take a
     * page down, which is not something the All or Published tab hinted at.
     */
    public function toggleStatus(Page $page): RedirectResponse
    {
        $publish = !$page->is_published;

        $page->update([
            'is_published' => $publish,
            // Dropped on a takedown, so the Published column cannot show a
            // date against a row badged Draft. The ?? is for rows taken down
            // before that was the case: those keep the date they went live
            // with rather than being restamped as new.
            'published_at' => $publish ? ($page->published_at ?? now()) : null,
        ]);

        return back()->with('success', $publish ? 'Page published' : 'Page moved to drafts');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted successfully');
    }
}
