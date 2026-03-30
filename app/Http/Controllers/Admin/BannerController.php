<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        $perPage = request()->input('per_page', 10);
        $banners = Banner::orderBy('priority')->paginate($perPage)->withQueryString();

        return view('admin.banners.index', compact('banners'));
    }

    public function create(): View
    {
        return view('admin.banners.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:50',
            'image' => 'required|image|max:5120',
            'mobile_image' => 'nullable|image|max:5120',
            'link' => 'nullable|url',
            'priority' => 'nullable|integer',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:50',
            'image' => 'nullable|image|max:5120',
            'mobile_image' => 'nullable|image|max:5120',
            'link' => 'nullable|url',
            'priority' => 'nullable|integer',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

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
            'banners' => 'required|array',
            'banners.*.id' => 'required|exists:banners,id',
            'banners.*.priority' => 'required|integer',
        ]);

        foreach ($validated['banners'] as $item) {
            Banner::where('id', $item['id'])->update(['priority' => $item['priority']]);
        }

        return back()->with('success', 'Banners reordered');
    }
}
