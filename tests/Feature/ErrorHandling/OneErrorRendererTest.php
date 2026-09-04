<?php

namespace Tests\Feature\ErrorHandling;

use Tests\TestCase;

/**
 * There is ONE renderer for validation messages, and this is what keeps it that
 * way.
 *
 * The duplicate-error bug was not a mistake in any single view. It was the
 * consequence of two renderers existing at once: 365 hand-rolled `@error(...)`
 * paragraphs across 60 blade files, and the site-wide validator in
 * resources/js/app.js, neither of which knew the other was printing under the
 * same field. Fixing the views without closing the door leaves the next
 * hand-rolled paragraph to reopen it, so the door is closed here.
 *
 * Everything below is about MESSAGES. Control flow over the bag - deciding
 * which panel to open with `$errors->has('phone')`, seeding an Alpine component
 * from `$errors->first('email')` inside a @php block - is untouched, because it
 * does not put a second sentence on the screen.
 *
 * If this fails, the fix is not to add an exception. It is:
 *
 *     @error('email') <p class="...">{{ $message }}</p> @enderror
 *        ->  <x-field-error field="email" />
 *
 *     @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
 *        ->  <x-form-errors :handled="[...the fields rendered inline...]" />
 */
class OneErrorRendererTest extends TestCase
{
    /** Views that ARE the one renderer, and so are allowed to touch the bag directly. */
    private const RENDERERS = [
        'components/field-error.blade.php',
        'components/form-errors.blade.php',
        'components/admin/form-errors.blade.php',
        'admin/settings/partials/errors.blade.php',
    ];

    /**
     * @return array<string, string> relative path => contents
     */
    private function views(): array
    {
        $root = resource_path('views');
        $found = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

            if (in_array($relative, self::RENDERERS, true)) {
                continue;
            }

            $found[$relative] = file_get_contents($file->getPathname());
        }

        ksort($found);

        return $found;
    }

    /**
     * @param  array<string, string>  $offenders
     */
    private function fail_with(string $what, array $offenders): void
    {
        $this->assertSame([], $offenders, sprintf(
            "%s\n\nUse <x-field-error field=\"...\" /> for a field message and <x-form-errors> for the\n".
            "form-level banner. %d file(s):\n  %s",
            $what,
            count($offenders),
            implode("\n  ", array_map(
                fn ($file, $hit) => "{$file}  ->  {$hit}",
                array_keys($offenders),
                $offenders,
            )),
        ));
    }

    public function test_no_view_renders_a_field_message_with_its_own_error_block(): void
    {
        $offenders = [];

        foreach ($this->views() as $file => $source) {
            if (preg_match('/@error\s*\(/', $source, $m, PREG_OFFSET_CAPTURE)) {
                $line = substr_count(substr($source, 0, $m[0][1]), "\n") + 1;
                $offenders[$file] = "line {$line}: @error(";
            }
        }

        $this->fail_with(
            'A view is printing its own field message. That paragraph is invisible to the '.
            'client-side validator, so it stacks with the live message instead of replacing it.',
            $offenders,
        );
    }

    public function test_no_view_lists_the_whole_error_bag(): void
    {
        $offenders = [];

        foreach ($this->views() as $file => $source) {
            if (preg_match('/\$errors\s*->\s*all\s*\(/', $source, $m, PREG_OFFSET_CAPTURE)) {
                $line = substr_count(substr($source, 0, $m[0][1]), "\n") + 1;
                $offenders[$file] = "line {$line}: \$errors->all()";
            }
        }

        $this->fail_with(
            'A view is listing the whole bag in a banner. Every one of those sentences is also '.
            'printed under its own field, which is the duplicate this refactor removes.',
            $offenders,
        );
    }

    public function test_no_view_echoes_a_message_straight_out_of_the_bag(): void
    {
        $offenders = [];

        // {{ $errors->first('x') }} and {!! ... !!}, but NOT the same call
        // inside a @php block, which is how a component is seeded rather than
        // how a message is printed.
        $pattern = '/\{\{-?!?!?\s*\$errors\s*->\s*(first|get)\s*\(/';

        foreach ($this->views() as $file => $source) {
            if (preg_match($pattern, $source, $m, PREG_OFFSET_CAPTURE)) {
                $line = substr_count(substr($source, 0, $m[0][1]), "\n") + 1;
                $offenders[$file] = "line {$line}: echoed \$errors->{$m[1][0]}()";
            }
        }

        $this->fail_with(
            'A view is echoing a message straight out of the error bag.',
            $offenders,
        );
    }

    public function test_the_field_error_component_marks_every_message_for_the_validator(): void
    {
        $source = file_get_contents(resource_path('views/components/field-error.blade.php'));

        // These three are the contract app.js relies on. Losing any one of them
        // silently reopens the bug: the message renders, looks right, and can
        // never be retired.
        $this->assertStringContainsString('data-kk-field-error=', $source);
        $this->assertStringContainsString('kk-field-error', $source);
        $this->assertStringContainsString('->first(', $source, 'one message per field, never a list');
    }
}
