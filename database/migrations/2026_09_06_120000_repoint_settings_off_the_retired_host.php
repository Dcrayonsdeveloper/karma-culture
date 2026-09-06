<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Move any setting still pointing at a retired host onto this site.
 *
 * The shop has answered on more than one address over its life, and one setting
 * never followed: og_image, the picture every social network and messaging app
 * shows when somebody shares the store. Production was still serving
 *
 *     <meta property="og:image" content="https://<retired-host>/images/...">
 *
 * so the storefront's share preview was fetched from a host this project no
 * longer controls. It answered, which is exactly why nobody noticed; the day
 * that account lapses, every shared link loses its image at once.
 *
 * The path is kept and only the host swapped, because the same file is served
 * from this site at the same path.
 *
 * The retired host is read from RETIRED_HOSTS in the environment rather than
 * written here - this repository is public, and past infrastructure is still
 * infrastructure. Set it as a comma-separated list on the machine that runs the
 * migration; with nothing set this is a no-op, which is the right behaviour for
 * every install that never lived anywhere else.
 */
return new class extends Migration
{
    public function up(): void
    {
        $retired = array_filter(array_map(
            'trim',
            explode(',', (string) env('RETIRED_HOSTS', ''))
        ));

        if ($retired === []) {
            return;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        $host = parse_url($appUrl, PHP_URL_HOST);

        // Nothing sensible to repoint onto. A developer machine running this
        // should not stamp "localhost" into a setting that gets published.
        if ($host === null || $host === '' || in_array($host, ['localhost', '127.0.0.1'], true)) {
            return;
        }

        foreach ($retired as $old) {
            DB::table('settings')
                ->where('value', 'like', '%'.$old.'%')
                ->get(['id', 'value'])
                ->each(function ($row) use ($old, $appUrl) {
                    $updated = preg_replace(
                        '#https?://'.preg_quote($old, '#').'#i',
                        $appUrl,
                        (string) $row->value
                    );

                    if ($updated !== $row->value) {
                        DB::table('settings')->where('id', $row->id)->update([
                            'value' => $updated,
                            'updated_at' => now(),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        // Deliberately empty. Putting a retired host back into a live setting
        // would be restoring the fault, not the state.
    }
};
