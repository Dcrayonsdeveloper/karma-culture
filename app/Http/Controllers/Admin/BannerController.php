<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BannerController extends Controller
{
    /** The placements the storefront actually renders. */
    private const POSITIONS = ['hero', 'sidebar', 'footer', 'category', 'popup'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $banners = Banner::orderBy('priority')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return view('admin.banners.index', compact('banners'));
    }

    public function create(): View
    {
        return view('admin.banners.create');
    }

    /**
     * The rule set shared by store() and update().
     *
     * The image is required when creating and optional when editing - leaving
     * the file input empty on the edit form keeps the picture already on disk.
     *
     * `link` goes through V::url(), which restricts the scheme to http/https.
     * That is the rule that stops a `javascript:` link being stored and then
     * rendered into the banner's href on the storefront: Blade escapes the
     * value, but escaping does not stop a scheme from running when clicked.
     *
     * @return array<string, mixed>
     */
    private function rules(bool $imageRequired): array
    {
        return [
            'name' => V::text(max: 255, min: 2),
            'position' => V::option(self::POSITIONS),
            'image' => V::image(required: $imageRequired, maxKb: 5120, allowGif: true),
            'mobile_image' => V::image(required: false, maxKb: 5120, allowGif: true),
            'link' => V::url(required: false, max: 255),
            // priority is an unsignedSmallInteger.
            'priority' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => V::boolean(),
        ];
    }

    private function messages(): array
    {
        return [
            'link.url' => 'Enter a full web address starting with http:// or https://',
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(imageRequired: true), $this->messages());

        // The uploads are stored by hand below; they are not columns, so drop
        // them before the array reaches a mass assignment.
        unset($validated['image'], $validated['mobile_image']);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['image_url'] = $request->file('image')->store('banners', 'public');

        if ($request->hasFile('mobile_image')) {
            $validated['mobile_image_url'] = $request->file('mobile_image')->store('banners', 'public');
        }

        Banner::create($validated);

        return redirect()->route('admin.banners.index')->with('success', 'Banner created successfully');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $validated = $request->validate($this->rules(imageRequired: false), $this->messages());

        unset($validated['image'], $validated['mobile_image']);

        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('banners', 'public');
        }

        if ($request->hasFile('mobile_image')) {
            $validated['mobile_image_url'] = $request->file('mobile_image')->store('banners', 'public');
        }

        $banner->update($validated);

        return redirect()->route('admin.banners.index')->with('success', 'Banner updated successfully');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return redirect()->route('admin.banners.index')->with('success', 'Banner deleted successfully');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'banners' => ['required', 'array', 'max:500'],
            'banners.*.id' => ['required', 'integer', Rule::exists('banners', 'id')],
            'banners.*.priority' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);

        foreach ($validated['banners'] as $item) {
            Banner::where('id', $item['id'])->update(['priority' => $item['priority']]);
        }

        return back()->with('success', 'Banners reordered');
    }
}
