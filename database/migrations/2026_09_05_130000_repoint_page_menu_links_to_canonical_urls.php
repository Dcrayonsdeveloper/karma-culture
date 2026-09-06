<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Point menu rows generated from a page at that page's canonical address.
 *
 * syncMenuLink() wrote route('page.show', $slug) for every page it filed into
 * a menu. Four legal pages also have a route of their own, and /page/{slug}
 * now forwards to it - so those rows pointed the storefront menu at a
 * redirect. The live footer showed it plainly: three hand-written Policies
 * links on /privacy-policy, /terms-of-service and /cookie-policy, beside a
 * generated GDPR link on /page/gdpr.
 *
 * Only rows carrying a page_id are touched. A link an admin typed by hand in
 * the Navigation editor has no page_id, and whatever they typed is theirs.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('navigation_menus')->whereNotNull('page_id')->get(['id', 'page_id', 'url']);

        foreach ($rows as $row) {
            $page = Page::find($row->page_id);

            // The page is gone but the row outlived it - not this migration's
            // problem to solve, and deleting it here would be a surprise.
            if ($page === null) {
                continue;
            }

            $canonical = $page->canonicalPath();

            if ($canonical !== $row->url) {
                DB::table('navigation_menus')
                    ->where('id', $row->id)
                    ->update(['url' => $canonical, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // Restores the generic path these rows held before, for the pages that
        // have a route of their own. Rows for every other page already hold it.
        $rows = DB::table('navigation_menus')->whereNotNull('page_id')->get(['id', 'page_id']);

        foreach ($rows as $row) {
            $page = Page::find($row->page_id);

            if ($page === null) {
                continue;
            }

            DB::table('navigation_menus')
                ->where('id', $row->id)
                ->update([
                    'url' => route('page.show', $page->slug, absolute: false),
                    'updated_at' => now(),
                ]);
        }
    }
};
