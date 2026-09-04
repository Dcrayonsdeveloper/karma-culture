<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\Quality;
use App\Models\Setting;
use App\Support\ShopFilterCatalogue;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // Featured products
        $featuredProducts = Product::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->with(['category', 'brand', 'primaryImage'])
            ->inStockFirst()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // New arrivals
        $newArrivals = Product::query()
            ->where('is_active', true)
            ->with(['category', 'brand', 'primaryImage'])
            ->inStockFirst()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Bestsellers
        $bestsellers = Product::query()
            ->where('is_active', true)
            ->with(['category', 'brand', 'primaryImage'])
            ->inStockFirst()
            ->orderBy('sales_count', 'desc')
            ->take(10)
            ->get();

        // Trending: what customers are actually looking at right now, over the
        // last 30 days. Distinct from Bestsellers, which is all-time sales.
        // Falls back to recent sellers so the row is never empty on a quiet week.
        $trending = Product::query()
            ->where('is_active', true)
            ->with(['category', 'brand', 'primaryImage'])
            ->withCount(['views as recent_views' => fn ($q) => $q->where('product_views.created_at', '>=', now()->subDays(30))])
            ->having('recent_views', '>', 0)
            ->inStockFirst()
            ->orderByDesc('recent_views')
            ->take(10)
            ->get();

        if ($trending->count() < 4) {
            $trending = Product::query()
                ->where('is_active', true)
                ->with(['category', 'brand', 'primaryImage'])
                ->inStockFirst()
                ->orderByDesc('sales_count')
                ->orderByDesc('created_at')
                ->take(10)
                ->get();
        }

        // Deal products (where price < mrp)
        $deals = Product::query()
            ->where('is_active', true)
            ->whereColumn('price', '<', 'mrp')
            ->with(['category', 'brand', 'primaryImage'])
            ->inStockFirst()
            ->orderByRaw('(mrp - price) / mrp DESC')
            ->take(10)
            ->get();

        // Categories with featured image
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with('children')
            ->orderBy('position')
            ->take(8)
            ->get();

        // Banners. `visible` is the model's one definition of what a shopper
        // should be seeing right now - switched on, started, not yet ended - and
        // the API reads the same scope, so a scheduled campaign cannot go live
        // on the website an hour before it goes live in the app.
        //
        // A banner carrying no artwork at all is dropped here rather than in the
        // view: the slide count decides how many dots the carousel draws, so a
        // banner filtered out downstream would leave a dot pointing at nothing.
        $banners = Banner::query()
            ->visible()
            ->where('position', 'hero')
            ->orderBy('priority')
            ->get()
            ->filter(fn (Banner $banner) => $banner->has_media)
            ->values();

        // Homepage sections. Inactive rows are loaded too, not filtered out: the
        // home page markup is hand-built rather than generated from this table,
        // so a section that is simply missing from the collection falls back to
        // its hardcoded default and renders anyway. The view needs to be able to
        // tell "admin switched this off" apart from "never configured", which it
        // can only do if the switched-off row is present to be inspected.
        $sections = HomepageSection::ordered()->get()->keyBy('key');

        // Active flash sale for popup
        $flashSale = FlashSale::active()
            ->withCount('products')
            ->first();

        // Shop It Your Way rails, worked out from the catalogue rather than
        // from a list an admin retyped: Size off the variants, Shade and
        // Texture off each product's own lists, Price off the live spread. A
        // hanger can no longer be a dead end, because a value only exists here
        // while a product carries it - which is what the old screen printed a
        // "0 - hidden" badge to warn about. Values the admin has hidden on
        // Homepage > Shop Filters are already taken out.
        $shopFilters = collect(ShopFilterCatalogue::groups());

        // Our Qualities cards
        $qualities = Quality::active()->ordered()->get();

        // Site settings
        $siteSettings = [
            'site_name' => Setting::get('site_name', 'Karmaa Kulture'),
            'site_tagline' => Setting::get('site_tagline', 'Premium tailored essentials'),
            'site_logo' => Setting::get('site_logo', ''),
            'footer_about' => Setting::get('footer_about', 'Curated fashion for the modern individual. Discover timeless pieces crafted with care and devotion to our culture.'),
        ];

        return view('home', compact(
            'featuredProducts',
            'newArrivals',
            'bestsellers',
            'trending',
            'deals',
            'categories',
            'banners',
            'sections',
            'siteSettings',
            'flashSale',
            'shopFilters',
            'qualities'
        ));
    }
}
