<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\NotificationController;
use Tests\TestCase;

/**
 * A notification type is only half-arrived when the row is written.
 *
 * The admin bell decides an icon, a tint and a destination from the type
 * string, in FOUR separate places - the notifications page's @switch, the
 * header dropdown's if/elseif, the hidden <template> each of those carries for
 * rows the poller stamps out between page loads, and the poller's own
 * ICON_FOR_TYPE map. Miss one and the type still shows, but as the grey
 * fallback bell, or it opens the notifications list instead of the product it
 * is about - a failure that no feature test of the writing side can see.
 *
 * This is the same source-reading shape as AdminNotificationPollerSourceTest,
 * and needs no database for the same reason.
 */
class StockAlertBellRegistrationTest extends TestCase
{
    private const TYPES = ['product_low_stock', 'product_out_of_stock'];

    private function source(string $path): string
    {
        return file_get_contents(resource_path($path));
    }

    public function test_both_stock_types_have_an_icon_on_the_notifications_page(): void
    {
        $src = $this->source('views/admin/notifications/index.blade.php');

        foreach (self::TYPES as $type) {
            $this->assertStringContainsString(
                "@case('{$type}')",
                $src,
                "{$type} has no icon case on the notifications page, so it draws the grey fallback bell."
            );
        }
    }

    /**
     * The rows the poller stamps out between page loads have to wear the same
     * icon as the ones the server drew, or a notification would change
     * appearance on the next full page load.
     */
    public function test_both_stock_types_have_a_hidden_icon_in_both_row_templates(): void
    {
        foreach ([
            'views/admin/notifications/index.blade.php',
            'views/admin/partials/header.blade.php',
        ] as $path) {
            $src = $this->source($path);

            foreach (self::TYPES as $type) {
                $this->assertStringContainsString(
                    'data-icon="'.$type.'"',
                    $src,
                    "{$path} has no hidden {$type} icon, so a polled row falls back to the grey bell."
                );
            }
        }
    }

    public function test_both_stock_types_are_mapped_in_the_pollers_icon_table(): void
    {
        $src = $this->source('views/admin/partials/notification-poller.blade.php');

        foreach (self::TYPES as $type) {
            $this->assertMatchesRegularExpression(
                '/'.preg_quote($type, '/').':\s*\''.preg_quote($type, '/').'\'/',
                $src,
                "ICON_FOR_TYPE has no entry for {$type}."
            );
        }
    }

    public function test_the_header_bell_tints_the_circle_for_both_stock_types(): void
    {
        $src = $this->source('views/admin/partials/header.blade.php');

        foreach (self::TYPES as $type) {
            $this->assertStringContainsString(
                "'{$type}' =>",
                $src,
                "The header bell has no background tint for {$type}."
            );
        }
    }

    /**
     * Opening a stock alert must land on the product it is about. Without a
     * DESTINATIONS entry the admin is dropped back on the notifications list,
     * which is the one thing they already had open.
     */
    public function test_both_stock_types_open_the_product_they_are_about(): void
    {
        $destinations = (new \ReflectionClass(NotificationController::class))
            ->getConstant('DESTINATIONS');

        foreach (self::TYPES as $type) {
            $this->assertArrayHasKey($type, $destinations, "{$type} has no destination.");

            [$key, $model, $route] = $destinations[$type];

            $this->assertSame('product_id', $key);
            $this->assertSame(\App\Models\Product::class, $model);
            $this->assertSame('admin.products.edit', $route);
        }
    }
}
