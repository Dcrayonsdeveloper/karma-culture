<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $content .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $content .= '  <sitemap><loc>' . url('/sitemap-pages.xml') . '</loc></sitemap>' . "\n";
        $content .= '  <sitemap><loc>' . url('/sitemap-products.xml') . '</loc></sitemap>' . "\n";
        $content .= '  <sitemap><loc>' . url('/sitemap-categories.xml') . '</loc></sitemap>' . "\n";
        $content .= '  <sitemap><loc>' . url('/sitemap-blog.xml') . '</loc></sitemap>' . "\n";
        $content .= '</sitemapindex>';

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    public function pages(): Response
    {
        $urls = collect();

        // Homepage
        $urls->push(['loc' => url('/'), 'changefreq' => 'daily', 'priority' => '1.0']);

        // Static pages
        $staticPages = [
            ['url' => url('/products'), 'freq' => 'daily', 'priority' => '0.9'],
            ['url' => url('/blog'), 'freq' => 'weekly', 'priority' => '0.7'],
            ['url' => url('/about'), 'freq' => 'monthly', 'priority' => '0.5'],
            ['url' => url('/contact'), 'freq' => 'monthly', 'priority' => '0.5'],
            ['url' => url('/faq'), 'freq' => 'monthly', 'priority' => '0.4'],
            ['url' => url('/shipping'), 'freq' => 'monthly', 'priority' => '0.4'],
            ['url' => url('/returns-policy'), 'freq' => 'monthly', 'priority' => '0.4'],
            ['url' => url('/size-guide'), 'freq' => 'monthly', 'priority' => '0.4'],
        ];

        foreach ($staticPages as $page) {
            $urls->push(['loc' => $page['url'], 'changefreq' => $page['freq'], 'priority' => $page['priority']]);
        }

        return $this->buildUrlset($urls);
    }

    public function products(): Response
    {
        $urls = collect();

        Product::where('is_active', true)
            ->select('slug', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->chunk(500, function ($products) use ($urls) {
                foreach ($products as $product) {
                    $urls->push([
                        'loc' => route('products.show', $product->slug),
                        'lastmod' => $product->updated_at->toW3cString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ]);
                }
            });

        return $this->buildUrlset($urls);
    }

    public function categories(): Response
    {
        $urls = collect();

        Category::where('is_active', true)
            ->select('slug', 'updated_at')
            ->get()
            ->each(function ($category) use ($urls) {
                $urls->push([
                    'loc' => url('/products?category=' . $category->slug),
                    'lastmod' => $category->updated_at->toW3cString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ]);
            });

        Brand::where('is_active', true)
            ->select('slug', 'updated_at')
            ->get()
            ->each(function ($brand) use ($urls) {
                $urls->push([
                    'loc' => url('/products?brand=' . $brand->slug),
                    'lastmod' => $brand->updated_at->toW3cString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                ]);
            });

        return $this->buildUrlset($urls);
    }

    public function blog(): Response
    {
        $urls = collect();

        BlogPost::published()
            ->select('slug', 'published_at', 'updated_at')
            ->get()
            ->each(function ($post) use ($urls) {
                $urls->push([
                    'loc' => route('blog.show', $post->slug),
                    'lastmod' => $post->updated_at->toW3cString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ]);
            });

        return $this->buildUrlset($urls);
    }

    private function buildUrlset($urls): Response
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $content .= "  <url>\n";
            $content .= "    <loc>{$url['loc']}</loc>\n";
            if (isset($url['lastmod'])) {
                $content .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
            }
            $content .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
            $content .= "    <priority>{$url['priority']}</priority>\n";
            $content .= "  </url>\n";
        }

        $content .= '</urlset>';

        return response($content, 200)->header('Content-Type', 'application/xml');
    }
}
