<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin edit screens put a "Delete X" form next to the Save button. When that
 * delete form is nested inside the edit form, the browser hoists its
 * _method=DELETE hidden input into the edit form, which already sends
 * _method=PUT. PHP keeps the last value for a repeated key, so DELETE wins and
 * pressing Save destroys the record instead of updating it.
 *
 * This has now regressed twice, so it is guarded two ways: once against the
 * rendered HTML of a real screen, and once as a sweep over every view.
 */
class AdminFormNestingTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'role'       => 'admin',
        ]);

        Admin::create([
            'user_id'   => $this->adminUser->id,
            'role'      => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_staff_edit_page_does_not_nest_the_delete_form(): void
    {
        $member = User::factory()->create(['role' => 'staff']);
        $staff  = Staff::create([
            'user_id'     => $member->id,
            'employee_id' => 'EMP-TEST-1',
            'role'        => 'manager',
            'is_active'   => true,
        ]);

        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.staff.edit', $staff))
            ->assertOk()
            ->getContent();

        $this->assertSame(0, $this->nestedFormCount($html), 'The staff edit page nests a <form> inside another form.');

        // The precise failure: one form carrying both method spoofs, DELETE last.
        foreach ($this->formBodies($html) as $body) {
            $methods = [];
            preg_match_all('/name="_method"\s+value="([A-Z]+)"/', $body, $m);
            $methods = $m[1];
            $this->assertLessThanOrEqual(1, count($methods), 'A single form sends more than one _method value: ' . implode(',', $methods));
        }
    }

    public function test_no_admin_view_nests_forms(): void
    {
        $offenders = [];

        foreach ($this->bladeViews() as $path) {
            $src = file_get_contents($path);
            // A literal "<form>" inside a comment is not markup.
            $src = preg_replace('/\{\{--.*?--\}\}/s', '', $src);
            $src = preg_replace('/<!--.*?-->/s', '', $src);

            $depth = 0;
            preg_match_all('/<form\b|<\/form\s*>/', $src, $tags);
            foreach ($tags[0] as $tag) {
                if ($tag[1] === '/') {
                    $depth = max(0, $depth - 1);
                } else {
                    if ($depth >= 1) {
                        $offenders[] = $path;
                        break;
                    }
                    $depth++;
                }
            }
        }

        $this->assertSame([], $offenders, "Nested <form> found in:\n" . implode("\n", $offenders));
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

    private function nestedFormCount(string $html): int
    {
        $depth = 0;
        $nested = 0;
        preg_match_all('/<form\b|<\/form\s*>/', $html, $tags);
        foreach ($tags[0] as $tag) {
            if ($tag[1] === '/') {
                $depth = max(0, $depth - 1);
            } else {
                if ($depth >= 1) {
                    $nested++;
                }
                $depth++;
            }
        }

        return $nested;
    }

    /** @return string[] */
    private function formBodies(string $html): array
    {
        preg_match_all('/<form\b.*?<\/form\s*>/s', $html, $m);

        return $m[0];
    }
}
