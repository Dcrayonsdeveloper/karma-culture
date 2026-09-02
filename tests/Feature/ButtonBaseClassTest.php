<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * `.btn` carries every dimension a button has - display, padding, line-height,
 * border-radius, font-weight. The colour classes carry colour and nothing else:
 *
 *     .btn-primary { color: white; background-color: var(--color-primary-600) }
 *
 * So `class="btn-primary"` on its own is not a small styling slip. It renders a
 * raw browser box with a colour painted on: no padding, square corners, and a
 * height set by the user agent rather than by the design system. On the search
 * page that produced a squat teal "Apply Price" bar beside an unpadded "Clear"
 * link that ran to the edge of the filter panel.
 *
 * It is easy to write and invisible in review, so it is swept for here rather
 * than left to be spotted in a screenshot.
 */
class ButtonBaseClassTest extends TestCase
{
    /**
     * Colour/size modifiers that style nothing on their own.
     *
     * `btn-icon` is deliberately absent: it sets its own padding and radius and
     * is used standalone on icon-only controls.
     */
    private const MODIFIERS = [
        'btn-primary',
        'btn-secondary',
        'btn-outline',
        'btn-ghost',
        'btn-danger',
        'btn-sm',
        'btn-lg',
    ];

    public function test_no_view_uses_a_button_modifier_without_the_base_class(): void
    {
        $offenders = [];

        foreach ($this->bladeViews() as $path) {
            foreach (file($path) as $i => $line) {
                if (! preg_match_all('/class="([^"]*)"/', $line, $m)) {
                    continue;
                }

                foreach ($m[1] as $classes) {
                    // Blade expressions inside the attribute ({{ ... }},
                    // :class bindings) are not literal class tokens.
                    $tokens = preg_split('/\s+/', trim($classes));

                    if (! array_intersect($tokens, self::MODIFIERS)) {
                        continue;
                    }

                    if (! in_array('btn', $tokens, true)) {
                        $relative = str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $path);
                        $offenders[] = sprintf('%s:%d  class="%s"', $relative, $i + 1, trim($classes));
                    }
                }
            }
        }

        $this->assertSame([], $offenders, "Button modifier used without the base `btn` class:\n" . implode("\n", $offenders));
    }

    /** @return string[] */
    private function bladeViews(): array
    {
        $files = [];
        $dir = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($dir as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
