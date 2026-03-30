<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::with('author')
            ->when(request('search'), fn($q, $s) => $q->where('title', 'like', "%{$s}%")
                ->orWhere('category', 'like', "%{$s}%"))
            ->when(request('status') === 'published', fn($q) => $q->published())
            ->when(request('status') === 'draft', fn($q) => $q->where('is_published', false))
            ->when(request('category'), fn($q, $c) => $q->where('category', $c))
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'slug'          => 'nullable|string|max:255|unique:blog_posts',
            'excerpt'       => 'nullable|string|max:500',
            'content'       => 'nullable|string',
            'category'      => 'nullable|string|max:100',
            'tags'          => 'nullable|string',
            'featured_image'=> 'nullable|image|max:2048',
            'is_published'  => 'boolean',
            'seo_data'      => 'nullable|array',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['author_id'] = auth('admin')->id();

        if ($validated['is_published'] ?? false) {
            $validated['published_at'] = now();
        }

        // Handle tags string → array
        if (!empty($validated['tags'])) {
            $validated['tags'] = array_filter(array_map('trim', explode(',', $validated['tags'])));
        } else {
            $validated['tags'] = null;
        }

        // Handle featured image upload
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
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'slug'          => 'nullable|string|max:255|unique:blog_posts,slug,' . $blogPost->id,
            'excerpt'       => 'nullable|string|max:500',
            'content'       => 'nullable|string',
            'category'      => 'nullable|string|max:100',
            'tags'          => 'nullable|string',
            'featured_image'=> 'nullable|image|max:2048',
            'is_published'  => 'boolean',
            'seo_data'      => 'nullable|array',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);

        if (($validated['is_published'] ?? false) && ! $blogPost->published_at) {
            $validated['published_at'] = now();
        } elseif (! ($validated['is_published'] ?? false)) {
            $validated['published_at'] = null;
        }

        if (!empty($validated['tags'])) {
            $validated['tags'] = array_filter(array_map('trim', explode(',', $validated['tags'])));
        } else {
            $validated['tags'] = null;
        }

        if ($request->hasFile('featured_image')) {
            // Delete old image
            if ($blogPost->featured_image) {
                \Storage::disk('public')->delete($blogPost->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')->store('blog', 'public');
        }

        $blogPost->update($validated);

        return redirect()->route('admin.blog-posts.index')->with('success', 'Blog post updated successfully.');
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        if ($blogPost->featured_image) {
            \Storage::disk('public')->delete($blogPost->featured_image);
        }

        $blogPost->delete();

        return redirect()->route('admin.blog-posts.index')->with('success', 'Blog post deleted.');
    }
}
