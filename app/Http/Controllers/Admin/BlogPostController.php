<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    /** A URL segment: lower-case words joined by single hyphens. */
    private const SLUG_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * Tags the rich-text editor is allowed to produce.
     *
     * Matches the allowlist resources/views/pages/blog-show.blade.php renders
     * with, so sanitising on write never strips something the page would have
     * displayed. Sanitising here as well as on output means a payload never
     * reaches the database at all - the row cannot hurt a future consumer that
     * forgets to call safe_html(), such as an email, a feed or an export.
     */
    private const CONTENT_TAGS = '<p><br><strong><b><em><i><u><ul><ol><li>'
        .'<h1><h2><h3><h4><h5><h6><a><span><div><table><tr><td><th><thead><tbody>'
        .'<img><blockquote><hr><figure><figcaption><pre><code>';

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['published', 'draft'])],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $posts = BlogPost::with('author')
            // The two LIKEs must be grouped. Left loose, `title LIKE ? OR
            // category LIKE ?` bound looser than the status filter, so a search
            // on the Published tab still returned drafts whose title matched.
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(
                fn ($sub) => $sub->where('title', 'like', "%{$s}%")
                    ->orWhere('category', 'like', "%{$s}%")
            ))
            ->when(($filters['status'] ?? null) === 'published', fn ($q) => $q->published())
            ->when(($filters['status'] ?? null) === 'draft', fn ($q) => $q->where('is_published', false))
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('category', $c))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = BlogPost::whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        $stats = [
            'total'     => BlogPost::count(),
            'published' => BlogPost::published()->count(),
            'drafts'    => BlogPost::where('is_published', false)->count(),
        ];

        return view('admin.blog-posts.index', compact('posts', 'categories', 'stats'));
    }

    public function create(): View
    {
        return view('admin.blog-posts.create');
    }

    /**
     * The rule set shared by store() and update().
     *
     * The slug is optional on the form and derived from the title when blank,
     * so the uniqueness check has to cover the derived value too - otherwise a
     * second post called "Summer Style" passed validation and then died on the
     * unique index with a 500 instead of a message beside the field.
     *
     * @return array<string, mixed>
     */
    private function rules(Request $request, ?BlogPost $post = null): array
    {
        return [
            'title' => [
                ...V::text(max: 255, min: 2),
                function (string $attribute, mixed $value, \Closure $fail) use ($request, $post): void {
                    if ($request->filled('slug')) {
                        return;
                    }

                    $slug = Str::slug((string) $value);

                    if ($slug === '') {
                        $fail('The title must contain at least one letter or number, or set a slug by hand.');

                        return;
                    }

                    $taken = BlogPost::where('slug', $slug)
                        ->when($post, fn ($q) => $q->whereKeyNot($post->getKey()))
                        ->exists();

                    if ($taken) {
                        $fail('Another post already uses this title. Change it, or set a slug by hand.');
                    }
                },
            ],
            'slug' => [
                'nullable', 'string', 'max:255',
                'regex:'.self::SLUG_REGEX,
                Rule::unique('blog_posts', 'slug')->ignore($post?->id),
            ],
            'excerpt' => V::textarea(required: false, max: 500),
            // Rich text by design, so NoHtml would reject every real post. The
            // allowlist below is what stands in for it.
            'content' => ['nullable', 'string', 'max:200000'],
            'category' => V::text(required: false, max: 100),
            'tags' => V::text(required: false, max: 500),
            'featured_image' => V::image(required: false, maxKb: 2048, allowGif: true),
            'is_published' => V::boolean(),
            // seo_data was `nullable|array`, which accepted any JSON at all and
            // stored it verbatim. Only the two keys the form renders are kept.
            'seo_data' => ['nullable', 'array'],
            'seo_data.meta_title' => V::text(required: false, max: 255),
            'seo_data.meta_description' => V::textarea(required: false, max: 500),
        ];
    }

    private function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lower-case letters, numbers and single hyphens.',
        ];
    }

    /**
     * Shape the validated payload into columns.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function prepare(Request $request, array $validated): array
    {
        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['title']);
        $validated['is_published'] = $request->boolean('is_published');

        $validated['content'] = safe_html($validated['content'] ?? null, self::CONTENT_TAGS) ?: null;

        // Handle tags string -> array. Cap the list so one comma-heavy field
        // cannot balloon the JSON column.
        $validated['tags'] = empty($validated['tags'])
            ? null
            : array_slice(array_values(array_filter(array_map('trim', explode(',', $validated['tags'])))), 0, 25);

        if ($validated['tags'] === []) {
            $validated['tags'] = null;
        }

        // Only the keys the form renders survive into the JSON column.
        $seo = array_filter([
            'meta_title' => $validated['seo_data']['meta_title'] ?? null,
            'meta_description' => $validated['seo_data']['meta_description'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $validated['seo_data'] = $seo ?: null;

        unset($validated['featured_image']);

        return $validated;
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->prepare(
            $request,
            $request->validate($this->rules($request), $this->messages())
        );

        $validated['author_id'] = auth('admin')->id();

        if ($validated['is_published']) {
            $validated['published_at'] = now();
        }

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('blog', 'public');
        }

        BlogPost::create($validated);

        return redirect()->route('admin.blog-posts.index')->with('success', 'Blog post created successfully.');
    }

    public function edit(BlogPost $blogPost): View
    {
        return view('admin.blog-posts.edit', compact('blogPost'));
    }

    public function update(Request $request, BlogPost $blogPost): RedirectResponse
    {
        $validated = $this->prepare(
            $request,
            $request->validate($this->rules($request, $blogPost), $this->messages())
        );

        if ($validated['is_published'] && ! $blogPost->published_at) {
            $validated['published_at'] = now();
        } elseif (! $validated['is_published']) {
            $validated['published_at'] = null;
        }

        if ($request->hasFile('featured_image')) {
            // Delete old image
            if ($blogPost->featured_image) {
                Storage::disk('public')->delete($blogPost->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')->store('blog', 'public');
        }

        $blogPost->update($validated);

        return redirect()->route('admin.blog-posts.index')->with('success', 'Blog post updated successfully.');
    }

    /**
     * Flip a post between published and draft straight from the list, so
     * taking one down does not mean a round trip through the editor.
     */
    public function toggleStatus(BlogPost $blogPost): RedirectResponse
    {
        $publish = ! $blogPost->is_published;

        $blogPost->update([
            'is_published' => $publish,
            'published_at' => $publish ? ($blogPost->published_at ?? now()) : null,
        ]);

        return back()->with('success', $publish ? 'Blog post published' : 'Blog post moved to drafts');
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        if ($blogPost->featured_image) {
            Storage::disk('public')->delete($blogPost->featured_image);
        }

        $blogPost->delete();

        return redirect()->route('admin.blog-posts.index')->with('success', 'Blog post deleted.');
    }
}
