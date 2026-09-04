<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Rules\ValidationRules as V;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    private const SLUG_RULES = ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'];

    /** categories.position is an UNSIGNED SMALLINT. */
    private const POSITION_RULES = ['nullable', 'integer', 'min:0', 'max:65535'];

    private const VIDEO_FILE_RULES = ['nullable', 'file', 'extensions:mp4,webm,mov', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:51200'];

    /**
     * Either an absolute http(s) URL or a path under storage/ that this
     * controller wrote itself. The value is interpolated into a <video src>, so
     * an unconstrained string is a stored-XSS vector on the home page.
     */
    private const VIDEO_URL_RULES = ['nullable', 'string', 'max:500', 'regex:#^(https?://[^\s<>"\']+|storage/[A-Za-z0-9._/-]+)$#'];

    public function index(Request $request): View
    {
        $query = Category::withCount('products');

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by parent
        if ($request->filled('parent')) {
            if ($request->parent === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent);
            }
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $categories = $query->orderBy('position')->orderBy('name')->paginate($perPage)->withQueryString();

        $parentCategories = Category::whereNull('parent_id')->orderBy('name')->get();

        // The built-in listings, listed separately rather than mixed into the
        // tree. They are rows in this table now, but they are a different kind
        // of thing - a destination with a hand-picked list, not something a
        // product IS - so putting them among the categories would invite
        // somebody to file a product under "Bestsellers".
        //
        // shownProducts is the pivot; products() would be products.category_id,
        // which a system row never holds.
        $systemRows = Category::system()
            ->withCount('shownProducts')
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        // Stats
        $stats = [
            'total' => Category::count(),
            'active' => Category::where('is_active', true)->count(),
            'root' => Category::whereNull('parent_id')->count(),
        ];

        return view('admin.categories.index', compact('categories', 'parentCategories', 'stats', 'systemRows'));
    }

    public function create(): View
    {
        $parentCategories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.categories.create', compact('parentCategories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => V::text(max: 255, min: 2),
            'slug' => [...self::SLUG_RULES, 'unique:categories,slug'],
            'description' => V::textarea(required: false, max: 2000),
            'parent_id' => V::foreignId('categories', required: false),
            'position' => self::POSITION_RULES,
            'is_active' => V::boolean(),
            'image' => V::image(required: false, maxKb: 2048),
            'video_url_text' => self::VIDEO_URL_RULES,
            'video_file' => self::VIDEO_FILE_RULES,
            'meta_title' => V::text(required: false, max: 255),
            'meta_description' => V::textarea(required: false, max: 500),
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['position'] = $validated['position'] ?? 0;

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('categories', 'public');
        }

        // Video: uploaded file takes precedence over pasted URL.
        if ($request->hasFile('video_file')) {
            $validated['video_url'] = 'storage/' . $request->file('video_file')->store('categories/videos', 'public');
        } elseif (!empty($validated['video_url_text'])) {
            $validated['video_url'] = $validated['video_url_text'];
        }

        unset($validated['image'], $validated['video_url_text'], $validated['video_file']);

        Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(Category $category): View
    {
        $category->load(['parent', 'children', 'products']);

        $perPage = min(max((int) request()->input('per_page', 10), 1), 100);
        $products = $category->products()
            ->with('seller')
            ->latest()
            ->paginate($perPage)->withQueryString();

        return view('admin.categories.show', compact('category', 'products'));
    }

    public function edit(Category $category): View
    {
        $parentCategories = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        // The name is what ties a built-in listing to the page it overrides in
        // the admin's head, and giving one a parent would file it into the tree
        // as though a product could BE a Bestseller. Everything else about it
        // stays editable.
        if ($category->is_system) {
            $request->merge([
                'name' => $category->name,
                'slug' => $category->slug,
                'parent_id' => null,
            ]);
        }

        $validated = $request->validate([
            'name' => V::text(max: 255, min: 2),
            'slug' => [...self::SLUG_RULES, Rule::unique('categories', 'slug')->ignore($category->id)],
            'description' => V::textarea(required: false, max: 2000),
            'parent_id' => [...V::foreignId('categories', required: false), $this->notADescendant($category)],
            'position' => self::POSITION_RULES,
            'is_active' => V::boolean(),
            'image' => V::image(required: false, maxKb: 2048),
            'remove_image' => V::boolean(),
            'video_url_text' => self::VIDEO_URL_RULES,
            'video_file' => self::VIDEO_FILE_RULES,
            'remove_video' => V::boolean(),
            'meta_title' => V::text(required: false, max: 255),
            'meta_description' => V::textarea(required: false, max: 500),
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            if ($category->image_url) {
                Storage::disk('public')->delete($category->image_url);
            }
            $validated['image_url'] = $request->file('image')->store('categories', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($category->image_url) {
                Storage::disk('public')->delete($category->image_url);
            }
            $validated['image_url'] = null;
        }

        // Video: file upload wins; otherwise URL field; or remove.
        if ($request->hasFile('video_file')) {
            if ($category->video_url && str_starts_with($category->video_url, 'storage/')) {
                Storage::disk('public')->delete(substr($category->video_url, 8));
            }
            $validated['video_url'] = 'storage/' . $request->file('video_file')->store('categories/videos', 'public');
        } elseif ($request->boolean('remove_video')) {
            if ($category->video_url && str_starts_with($category->video_url, 'storage/')) {
                Storage::disk('public')->delete(substr($category->video_url, 8));
            }
            $validated['video_url'] = null;
        } elseif (array_key_exists('video_url_text', $validated)) {
            $validated['video_url'] = $validated['video_url_text'] ?: $category->video_url;
        }

        unset($validated['image'], $validated['remove_image'],
              $validated['video_url_text'], $validated['video_file'], $validated['remove_video']);

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // A built-in listing is part of the storefront, not a shelf somebody
        // added: /products, /new-arrivals, /bestsellers and /deals are wired to
        // these rows, and deleting one would take a page with it.
        if ($category->is_system) {
            return back()->with('error', 'Built-in listings cannot be deleted. Untick the products in it instead, and the page goes back to working itself out.');
        }

        // Move children to parent (or root)
        $category->children()->update(['parent_id' => $category->parent_id]);

        // Unassign products (category_id is now nullable)
        $category->products()->update(['category_id' => null]);

        if ($category->image_url) {
            Storage::disk('public')->delete($category->image_url);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    public function toggleStatus(Category $category): RedirectResponse
    {
        $category->update(['is_active' => !$category->is_active]);

        $status = $category->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Category {$status} successfully.");
    }

    /**
     * A category may not be re-parented under itself or under one of its own
     * descendants: that detaches the whole branch from the tree and makes every
     * ancestor walk loop for ever. The edit form only offers root categories,
     * but the posted id is whatever the client sent.
     */
    private function notADescendant(Category $category): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($category): void {
            if ((int) $value === $category->id) {
                $fail('A category cannot be its own parent.');

                return;
            }

            // Walk up from the proposed parent; hitting this category means the
            // proposed parent sits below it. Bounded by the tree's depth, with a
            // hard stop in case existing data already contains a cycle.
            $ancestor = Category::find($value);

            for ($depth = 0; $ancestor !== null && $depth < 50; $depth++) {
                if ($ancestor->id === $category->id) {
                    $fail('A category cannot be moved under one of its own sub-categories.');

                    return;
                }

                $ancestor = $ancestor->parent_id ? Category::find($ancestor->parent_id) : null;
            }
        };
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'categories' => ['required', 'array', 'max:500'],
            'categories.*.id' => V::foreignId('categories'),
            'categories.*.order' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);

        foreach ($request->categories as $item) {
            Category::where('id', $item['id'])->update(['position' => $item['order']]);
        }

        return back()->with('success', 'Category order updated.');
    }
}
