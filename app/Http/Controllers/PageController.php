<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Enquiry;
use App\Models\Notification;
use App\Models\Page;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        $brands = Brand::active()->featured()
            ->whereNotNull('logo_url')
            ->orderBy('position')
            ->limit(12)
            ->get();

        return view('pages.about', compact('brands'));
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function sendContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $enquiry = Enquiry::create($validated);

        // Notify all admin users
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'new_enquiry',
                'title' => 'New Enquiry',
                'content' => "New enquiry from {$enquiry->name}: {$enquiry->subject}",
                'data' => [
                    'enquiry_id' => $enquiry->id,
                    'name' => $enquiry->name,
                    'email' => $enquiry->email,
                    'subject' => $enquiry->subject,
                ],
                'channel' => 'database',
            ]);
        }

        return back()->with('success', 'Thank you for your message. We will get back to you soon.');
    }

    public function faq(): View
    {
        return view('pages.faq');
    }

    public function blog(): View
    {
        $posts = BlogPost::published()
            ->when(request('category'), fn($q, $c) => $q->where('category', $c))
            ->when(request('search'), fn($q, $s) => $q->where('title', 'like', "%{$s}%")
                ->orWhere('excerpt', 'like', "%{$s}%"))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $categories = BlogPost::published()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return view('pages.blog', compact('posts', 'categories'));
    }

    public function blogShow(string $slug): View
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();
        $post->incrementViews();

        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->when($post->category, fn($q) => $q->where('category', $post->category))
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('pages.blog-show', compact('post', 'related'));
    }

    public function careers(): View
    {
        return view('pages.careers');
    }

    public function help(): View
    {
        return view('pages.help');
    }

    public function returns(): View
    {
        return view('pages.returns');
    }

    public function shipping(): View
    {
        return view('pages.shipping');
    }

    public function sizeGuide(): View
    {
        return view('pages.size-guide');
    }

    public function privacy(): View
    {
        $page = Page::where('slug', 'privacy-policy')->firstOrFail();

        return view('pages.legal-page', compact('page'));
    }

    public function terms(): View
    {
        $page = Page::where('slug', 'terms-of-service')->firstOrFail();

        return view('pages.legal-page', compact('page'));
    }

    public function cookiePolicy(): View
    {
        $page = Page::where('slug', 'cookie-policy')->firstOrFail();

        return view('pages.legal-page', compact('page'));
    }

    public function gdpr(): View
    {
        $page = Page::where('slug', 'gdpr')->firstOrFail();

        return view('pages.legal-page', compact('page'));
    }

    public function show(Page $page): View
    {
        abort_unless($page->is_published, 404);

        return view('pages.legal-page', compact('page'));
    }
}
