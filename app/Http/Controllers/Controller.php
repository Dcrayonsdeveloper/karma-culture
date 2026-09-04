<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * A page size that can be trusted to reach paginate().
     *
     * `paginate($request->per_page ?? 20)` on a public endpoint is two bugs at
     * once. A non-numeric value ("abc") and a negative one both reached
     * paginate() and came back as an uncaught 500 from an anonymous request,
     * and `per_page=999999` was honoured - one call returned the entire
     * products table, over a megabyte of JSON, with no authentication.
     *
     * Anything unusable falls back to the default rather than erroring: a page
     * size is a hint from a client, not something worth refusing a request over.
     */
    protected function perPage(Request $request, int $default = 20, int $max = 100): int
    {
        $requested = $request->query('per_page');

        if (! is_numeric($requested)) {
            return $default;
        }

        return (int) max(1, min((int) $requested, $max));
    }
}
