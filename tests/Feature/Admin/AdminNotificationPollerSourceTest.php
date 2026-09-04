<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * The source-level half of the notification poller's guard. These read the
 * blade files themselves, so they need no database and keep working when MySQL
 * does not - see Tests\Feature\Notification\AdminNotificationPollingTest for
 * the endpoint and the rendered-page half.
 *
 * What they hold in place are the properties that are invisible in a passing
 * feature test and expensive in production: that the page is never reloaded out
 * from under an admin mid-form, that there is one timer rather than one per
 * page, that a customer's typed text reaches the DOM as text, and that the
 * request identifies itself as wanting JSON.
 */
class AdminNotificationPollerSourceTest extends TestCase
{
    private function poller(): string
    {
        return file_get_contents(resource_path('views/admin/partials/notification-poller.blade.php'));
    }

    private function layout(): string
    {
        return file_get_contents(resource_path('views/components/layouts/admin.blade.php'));
    }

    /**
     * The point of the whole change. A reload every ten seconds would keep the
     * bell current by throwing away the open form, the scroll position, the
     * filters, the modal and the unsaved work underneath it.
     */
    public function test_the_poller_never_reloads_the_page(): void
    {
        $src = $this->poller();

        foreach (['location.reload', 'location.href = window.location', 'window.location.replace'] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $src,
                "The notification poller must never reload the page ({$forbidden})."
            );
        }
    }

    public function test_the_poll_interval_is_ten_seconds(): void
    {
        $this->assertMatchesRegularExpression(
            '/var POLL_MS = 10000;/',
            $this->poller(),
            'The admin panel is specified to check for notifications every ten seconds.'
        );
    }

    /**
     * One controlled mechanism for the admin session. It is mounted by the
     * shell, so it survives navigation without any page being able to start a
     * second timer of its own.
     */
    public function test_the_shell_mounts_the_poller_exactly_once(): void
    {
        $this->assertSame(
            1,
            substr_count($this->layout(), "@include('admin.partials.notification-poller')"),
            'components.layouts.admin must include the poller exactly once.'
        );
    }

    /**
     * Load order, and the reason the include sits at the bottom of the layout:
     * toastr is a classic <script> in the body, so anything above it runs with
     * window.toastr undefined.
     */
    public function test_the_poller_is_mounted_after_toastr_is_configured(): void
    {
        $layout = $this->layout();

        $toastr = strpos($layout, 'toastr.options');
        $poller = strpos($layout, "@include('admin.partials.notification-poller')");

        $this->assertNotFalse($toastr);
        $this->assertNotFalse($poller);
        $this->assertGreaterThan(
            $toastr,
            $poller,
            'The poller raises toasts through toastr, so it must be mounted after toastr is set up.'
        );
    }

    /**
     * A notification's title and body carry text a customer typed - an enquiry
     * subject, a ticket subject, their own name. app/Rules/NoHtml.php is a
     * blocklist and says so in its own docblock: escaping on output is what
     * actually stops XSS. Server-side that is Blade's {{ }}; here it is
     * textContent for the rows and an escaper for the toast, and neither is
     * optional.
     */
    public function test_notification_text_never_reaches_the_dom_as_markup(): void
    {
        $src = $this->poller();

        $this->assertStringNotContainsString(
            '.innerHTML =',
            $src,
            'Notification fields must be written with textContent, never assigned as innerHTML.'
        );

        foreach (['title', 'content', 'time'] as $slot) {
            $this->assertStringContainsString(
                "slot.textContent = slots[name];",
                $src,
                'Row fields must be written with textContent.'
            );
        }

        // The toast is the one place a string of HTML is built, because toastr
        // renders its message as HTML - so every interpolated value there has
        // to go through the escaper first.
        $this->assertStringContainsString("esc(notification.content || '')", $src);
        $this->assertStringContainsString("esc(notification.title || 'New notification')", $src);
    }

    /**
     * Without this header the admin guard answers an unauthenticated poll with a
     * 302 to the login page, fetch follows it transparently, and the client is
     * handed 200 OK plus a page of HTML - which it would try to parse as JSON,
     * every ten seconds, forever. With it, the answer is a clean 401 the poller
     * can recognise and stand down on.
     */
    public function test_the_poll_asks_for_json(): void
    {
        $src = $this->poller();

        $this->assertStringContainsString("'Accept': 'application/json'", $src);
        $this->assertStringContainsString("credentials: 'same-origin'", $src);
    }

    /**
     * A polling error says nothing about the admin's session. Losing wifi for a
     * moment must not put "session expired" in front of someone who is still
     * signed in and mid-sentence.
     */
    public function test_a_failed_poll_never_claims_the_session_has_expired(): void
    {
        // Comments stripped first: the file explains at length why it does NOT
        // say this, and the explanation must not read as the offence.
        $src = preg_replace(
            ['/\{\{--.*?--\}\}/s', '~^\s*//.*$~m'],
            '',
            $this->poller()
        );

        foreach (['Session expired', 'session expired', 'Please log in again', 'Please sign in'] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $src,
                'A polling failure says nothing about the admin session, so it must not put this on screen.'
            );
        }
    }

    /**
     * The cursor is never carried across a page load in storage.
     *
     * It only advances on a successful poll and a hidden tab does not poll, so a
     * stored cursor can be hours old - and the click that brings the tab back is
     * a page load that has just redrawn the bell with the current state. Reusing
     * that cursor would announce a night's notifications the admin is already
     * looking at, which is exactly what requirement 7 forbids. The seen-set IS
     * carried across; the cursor is not.
     */
    public function test_the_cursor_is_rebaselined_by_every_page_load(): void
    {
        $src = $this->poller();

        $this->assertStringContainsString('var since = root.dataset.since;', $src);
        $this->assertStringNotContainsString(
            'SINCE_KEY',
            $src,
            'A stored cursor would survive a page load and re-announce history.'
        );
        $this->assertStringContainsString(
            'SEEN_KEY',
            $src,
            'The seen-set must survive a page load, or every click re-announces.'
        );
    }

    /**
     * No other admin view may start a notification timer of its own; that is
     * what "one controlled polling mechanism" means in practice, and it is the
     * failure mode a per-page implementation falls into first.
     */
    public function test_no_other_admin_view_polls_for_notifications(): void
    {
        $offenders = [];
        $poller = realpath(resource_path('views/admin/partials/notification-poller.blade.php'));

        $dir = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($dir as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            if (! str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            if (realpath($file->getPathname()) === $poller) {
                continue;
            }

            $src = file_get_contents($file->getPathname());

            if (str_contains($src, 'notifications.updates') || str_contains($src, 'notifications/updates')) {
                $offenders[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Only the shell's poller may call the notification updates endpoint:\n" . implode("\n", $offenders)
        );
    }
}
