<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        // The payload is cached, and nothing ever forgets the key: no admin
        // save clears it and a banner's own start time certainly cannot. At the
        // original 900 seconds a campaign scheduled for 10:00 first reached the
        // app somewhere before 10:15, which defeats the point of scheduling it.
        //
        // Bucketing the key by the current minute, and letting an entry live
        // exactly as long as its bucket, caps that lateness at 60 seconds while
        // still absorbing every request inside a minute - the traffic this
        // cache exists for. Shortening the TTL alone would not do it: the entry
        // written at 09:59 would still be answering at 10:00.
        $data = Cache::remember('api.home.'.now()->format('YmdHi'), 60, function () {
            return [
                // `visible` is the model's one definition of what a shopper
                // should be seeing right now - switched on, started, not yet
                // ended - and the website reads the same scope, so a campaign
                // cannot be live in the app an hour before it is live on the
                // site.
                //
                // Ordering by `priority` is a deliberate behaviour fix. This
                // ordered by `position`, which is the PLACEMENT (hero, footer,
                // popup) and not a sort key, so the app's banners came back
                // alphabetically by placement and ignored the order the admin
                // dragged them into.
                //
                // The five original keys are returned unchanged, raw disk key
                // and all, because a mobile client is reading `image_url`
                // today. The resolved keys beside them are the ones anything
                // new should read: `image_url` is a path on the public disk,
                // not a URL, so no client outside the web app can fetch it.
                // The three extra columns are selected only so the accessors
                // that resolve those URLs have something to work from.
                'banners' => Banner::visible()
                    ->orderBy('priority')
                    ->limit(10)
                    ->get(['id', 'title', 'image_url', 'link', 'position', 'mobile_image_url', 'video_url', 'mobile_video_url'])
                    ->map(fn (Banner $banner) => [
                        'id' => $banner->id,
                        'title' => $banner->title,
                        'image_url' => $banner->image_url,
                        'link' => $banner->link,
                        'position' => $banner->position,
                        'desktop_image' => $banner->image,
                        'mobile_image' => $banner->mobile_image,
                        // '' means "no clip"; null reads better in JSON.
                        'desktop_video' => $banner->video ?: null,
                        'mobile_video' => $banner->mobile_video ?: null,
                    ]),

                'categories' => Category::whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('position')
                    ->limit(12)
                    ->get(['id', 'name', 'slug', 'image_url']),

                'featured' => Product::where('is_active', true)
                    ->where('is_featured', true)
                    ->whereNull('deleted_at')
                    ->with(['images' => fn ($q) => $q->orderBy('position')->limit(1)])
                    ->inStockFirst()
                    ->orderByDesc('sales_count')
                    ->limit(10)
                    ->get(['id', 'name', 'slug', 'price', 'mrp', 'rating', 'review_count']),

                'new_arrivals' => Product::where('is_active', true)
                    ->whereNull('deleted_at')
                    ->with(['images' => fn ($q) => $q->orderBy('position')->limit(1)])
                    ->inStockFirst()
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get(['id', 'name', 'slug', 'price', 'mrp', 'rating', 'created_at']),

                'bestsellers' => Product::where('is_active', true)
                    ->whereNull('deleted_at')
                    ->with(['images' => fn ($q) => $q->orderBy('position')->limit(1)])
                    ->inStockFirst()
                    ->orderByDesc('sales_count')
                    ->limit(10)
                    ->get(['id', 'name', 'slug', 'price', 'mrp', 'rating', 'sales_count']),

                'flash_sales' => FlashSale::where('is_active', true)
                    ->where('starts_at', '<=', now())
                    ->where('ends_at', '>=', now())
                    ->with(['products' => fn ($q) => $q->inStockFirst()->limit(6)])
                    ->limit(3)
                    ->get(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
