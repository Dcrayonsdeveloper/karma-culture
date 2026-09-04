<?php

namespace Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Settings are memoised for the life of a request so the storefront does
     * not go back to the database cache store for every rendered price. A test
     * process is many "requests" in one PHP process, so the memo has to be
     * dropped between them or a test inherits the previous test's settings.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushMemo();
    }
}
