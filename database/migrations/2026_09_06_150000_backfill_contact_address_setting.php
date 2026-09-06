<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Give `contact_address` the address the contact page used to hardcode.
 *
 * The address, phone and email were typed into resources/views/pages/contact
 * .blade.php, so the page and the footer disagreed: the footer rendered the
 * admin-managed contact_email / contact_phone (karmaa@gmail.com, 7797444000)
 * while the page showed support@karmaakulture.com and +91 93117 96900. Those
 * pages read the settings now, and contact_email / contact_phone already hold
 * values - but contact_address has never been set, so without this row the
 * address block and the map would simply vanish from a live page that shows
 * them today.
 *
 * The value is the one prod is already serving to shoppers and to Google, so
 * this preserves what is on screen rather than introducing anything new. It is
 * a backfill, not a default: an address already entered by the admin wins.
 */
return new class extends Migration
{
    private const FALLBACK = 'D-12/140, Rohini Sector-7, Delhi 110085';

    public function up(): void
    {
        $existing = DB::table('settings')->where('key', 'contact_address')->first();

        // Only ever fills a blank. If someone has since typed a real address in
        // under Online Store > Site Settings, that is the better value and this
        // migration must not walk over it.
        if ($existing && trim((string) $existing->value) !== '') {
            echo "  contact_address already set; left alone.\n";
            return;
        }

        if ($existing) {
            DB::table('settings')->where('key', 'contact_address')->update([
                'value'      => self::FALLBACK,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('settings')->insert([
                'group'      => 'homepage',
                'key'        => 'contact_address',
                'value'      => self::FALLBACK,
                'type'       => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Settings are cached per row, per group and once for the whole table,
        // and writing through the query builder skips the model hooks that
        // would clear them - the same reason the credential migration does this.
        DB::table('cache')->where('key', 'like', '%setting%')->delete();

        echo "  contact_address backfilled.\n";
    }

    public function down(): void
    {
        // Only remove the row if it still holds exactly what was put here; an
        // address edited since belongs to the admin, not to this migration.
        DB::table('settings')
            ->where('key', 'contact_address')
            ->where('value', self::FALLBACK)
            ->delete();

        DB::table('cache')->where('key', 'like', '%setting%')->delete();
    }
};
