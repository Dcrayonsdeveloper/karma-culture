<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Take the credentials out of the settings table.
 *
 * The PayU merchant key and salt, the Shiprocket login, the AI keys and a
 * handful of seeded-but-unread rows (Razorpay, SMS, WhatsApp) were all stored
 * here, which put live secrets in reach of anyone with an admin login and into
 * every database dump taken since. The values were copied into .env first;
 * this deletes what is left behind, and {@see Setting::isEnvOnly()} refuses to
 * let any of them be written again.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Delete on the query builder, not the model: the model's saving guard
        // is about writes, and going around Eloquent here also skips hydrating
        // rows whose whole point is that nothing should read them.
        $deleted = DB::table('settings')->whereIn('key', Setting::ENV_ONLY_KEYS)->delete();

        // The suffix rule catches anything an older deploy invented that the
        // explicit list does not name - the same test the guard applies.
        foreach (DB::table('settings')->pluck('key') as $key) {
            if (Setting::isEnvOnly((string) $key)) {
                DB::table('settings')->where('key', $key)->delete();
                $deleted++;
            }
        }

        // The settings cache is keyed per row, per group and once for the whole
        // table; a delete straight through the query builder skips the model
        // hooks that would normally clear them.
        DB::table('cache')->where('key', 'like', '%setting%')->delete();

        echo '  Removed '.$deleted." credential row(s) from settings.\n";
    }

    public function down(): void
    {
        // Deliberately irreversible. These rows held secrets; recreating them
        // empty would only re-add the shape of the problem, and recreating them
        // populated is impossible by design - the values live in .env now.
    }
};
