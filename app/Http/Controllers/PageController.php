<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Enquiry;
use App\Models\Notification;
use App\Models\Page;
use App\Models\User;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PageController extends Controller
{
    /** The longest blog search phrase we accept. */
    private const MAX_BLOG_SEARCH = 100;

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
        // This one endpoint backs two forms: /contact and the wholesale enquiry
        // on /wholesale, which posts an extra business_name and a fixed hidden
        // subject. business_name had no rule here, so `Enquiry::create()`
        // dropped it on the floor (there is no such column, and it is not
        // fillable) - every wholesale enquiry reached the admin inbox with the
        // one field that identifies the business missing. It is validated and
        // folded into the message body below instead.
        //
        // Phone is checked on digit count rather than with the Indian mobile
        // rule: a wholesale enquiry can legitimately come from abroad, and this
        // is a "we will call you back" field, not a number we transact on.
        $validated = $request->validate([
            'name' => V::name(max: 30, lettersOnly: true),
            'email' => V::email(),
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/', function ($attribute, $value, $fail) {
                $digits = preg_replace('/\D/', '', (string) $value);
                if (strlen($digits) < 10 || strlen($digits) > 15) {
                    // The box's own title, which is what app.js prints when the
                    // browser's pattern rejects the same number. The closure and
                    // the regex above are two halves of one rule, so they say one
                    // sentence between them.
                    $fail('Enter a phone number with 10 to 15 digits. Spaces, brackets, hyphens and a leading + are fine.');
                }
            }],
            'subject' => V::text(max: 200, min: 3),
            'message' => V::textarea(max: 5000, min: 10),
            'business_name' => V::text(required: false, max: 120),
        ], [
            // Every sentence here is the one the BOX would say for the same
            // failure. /contact carries no `novalidate`, so the site-wide
            // validator in app.js owns these inputs and derives its wording from
            // the <label> and the constraint: "Your Name is required.", "Subject
            // must be at least 3 characters.", "Message must be 5000 characters
            // or fewer." This array used to answer all of them in a different
            // voice - "Please write your message." for the box that had just said
            // "Message is required." - and both can be on screen at once, because
            // a value the browser accepts is not always one Laravel does: a
            // message of twelve spaces satisfies minlength="10" and is then
            // trimmed to nothing and refused here.
            //
            // The labels are /contact's. The wholesale enquiry at
            // resources/views/wholesale/index.blade.php posts to this same
            // endpoint and names two of its boxes differently ("Contact Name",
            // "Email"), which one shared message set cannot match; the fields it
            // has in common - message, phone, business_name - agree.
            'name.required' => 'Your Name is required.',
            'name.min' => 'Your Name must be at least 2 characters.',
            'name.max' => 'Your Name must be 30 characters or fewer.',
            'email.required' => 'Email Address is required.',
            'email.email' => 'Enter a valid email address, like you@example.com.',
            'phone.regex' => 'Enter a phone number with 10 to 15 digits. Spaces, brackets, hyphens and a leading + are fine.',
            'subject.required' => 'Subject is required.',
            'subject.min' => 'Subject must be at least 3 characters.',
            'subject.max' => 'Subject must be 200 characters or fewer.',
            'message.required' => 'Message is required.',
            'message.min' => 'Message must be at least 10 characters.',
            'message.max' => 'Message must be 5000 characters or fewer.',
            'business_name.max' => 'Business Name must be 120 characters or fewer.',
        ]);

        $body = $validated['message'];

        if (! empty($validated['business_name'])) {
            $body = 'Business: '.$validated['business_name']."\n\n".$body;
        }

        $enquiry = Enquiry::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'],
            'message' => $body,
        ]);

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

    public function blog(Request $request): View
    {
        // A blog index URL is public and crawled, so a malformed parameter has
        // to degrade to "no filter" rather than to a 422. The check is still a
        // boundary: `?search[]=x` used to reach the LIKE and fatal the page,
        // and an unbounded phrase was interpolated into a LIKE pattern with its
        // wildcards intact.
        $filters = $this->blogFilters($request);
        $search = $filters['search'];
        $category = $filters['category'];

        $posts = BlogPost::published()
            ->when($category, fn ($q, $c) => $q->where('category', $c))
            // The orWhere must be grouped, or it breaks out of published():
            // "WHERE published AND title LIKE x OR excerpt LIKE y" matched
            // drafts whose excerpt happened to contain the search term.
            ->when($search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$this->escapeLike($s).'%')
                ->orWhere('excerpt', 'like', '%'.$this->escapeLike($s).'%')))
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

    /**
     * The blog index query string, validated and normalised.
     *
     * @return array{search: ?string, category: ?string}
     */
    private function blogFilters(Request $request): array
    {
        $keys = ['search', 'category'];

        $validator = Validator::make(Arr::only($request->query(), $keys), [
            'search' => ['nullable', 'string', 'max:'.self::MAX_BLOG_SEARCH],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $safe = Arr::only($validator->valid(), $keys);

        $string = function (string $key) use ($safe): ?string {
            $value = $safe[$key] ?? null;
            if (! is_string($value)) {
                return null;
            }
            $value = trim($value);

            return $value === '' ? null : $value;
        };

        return [
            'search' => $string('search'),
            'category' => $string('category'),
        ];
    }

    /** % and _ are LIKE wildcards; a reader typing them means them literally. */
    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
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
